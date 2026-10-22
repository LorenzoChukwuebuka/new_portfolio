import assert from 'node:assert/strict'
import { createHash } from 'node:crypto'
import { chromium } from '@playwright/test'

const base = (process.env.SMOKE_BASE_URL || 'https://lorenzochukwuebuka.com.ng').replace(/\/$/, '')
const expectedCommit = process.env.SMOKE_EXPECTED_COMMIT || ''
const requireIndexable = process.env.SMOKE_REQUIRE_INDEXABLE === '1' || new URL(base).hostname === 'lorenzochukwuebuka.com.ng'
const failures = []
const report = (message) => console.log(`PASS ${message}`)
const fail = (message) => { failures.push(message); console.error(`FAIL ${message}`) }
const statusOf = (response) => typeof response?.status === 'function' ? response.status() : response?.status
const assertStatus = (response, expected, label) => {
  const status = statusOf(response)
  if (!response || status !== expected) fail(`${label} returned HTTP ${status ?? 'no response'}, expected ${expected}.`)
}

async function fetchRetry(url, extraHeaders = {}) {
  let lastResponse
  for (const delay of [0, 1000, 2500, 5000]) {
    if (delay) await new Promise((resolve) => setTimeout(resolve, delay))
    try {
      lastResponse = await fetch(url, { headers: { 'User-Agent': 'Portfolio deployment smoke check', 'Cache-Control': 'no-cache', ...extraHeaders }, redirect: 'follow' })
      if (lastResponse.ok) break
    } catch { /* retry transient connection failures */ }
  }
  return lastResponse
}

async function fetchText(url, expectedType) {
  const lastResponse = await fetchRetry(url)
  assertStatus(lastResponse, 200, new URL(url).pathname)
  if (!lastResponse?.ok) return ''
  const contentType = lastResponse.headers.get('content-type') || ''
  if (expectedType && !contentType.toLowerCase().includes(expectedType)) fail(`${new URL(url).pathname} returned ${contentType}, expected ${expectedType}.`)
  return await lastResponse.text()
}

const manifestText = await fetchText(`${base}/build/manifest.json?smoke=${Date.now()}`, 'application/json')
let manifest = {}
try { manifest = JSON.parse(manifestText) } catch { fail('the production Vite manifest is invalid JSON.') }

const manifestFiles = new Set()
const manifestRefs = new Set()
for (const value of Object.values(manifest)) {
  if (value.file) manifestFiles.add(value.file)
  for (const ref of [...(value.css || []), ...(value.assets || [])]) manifestRefs.add(ref)
}
for (const ref of manifestFiles) manifestRefs.add(ref)

for (const entry of ['resources/js/site.ts', 'resources/js/admin.ts']) {
  if (!manifest[entry]?.isEntry) fail(`production manifest is missing the ${entry} entry point.`)
}

if (expectedCommit) {
  const markerUrl = `${base}/build/deploy-${expectedCommit}.json?smoke=${Date.now()}`
  const markerText = await fetchText(markerUrl, 'application/json')
  try {
    const marker = JSON.parse(markerText)
    if (marker.commit !== expectedCommit) fail('the served release marker does not match this workflow commit.')
    if (marker.manifest_sha256 !== createHash('sha256').update(manifestText).digest('hex')) fail('the served manifest does not match the release marker.')
    report(`release marker matches ${expectedCommit.slice(0, 12)}`)
  } catch { fail('the served release marker is invalid JSON.') }
}

const assetRefs = [...manifestRefs]
for (let start = 0; start < assetRefs.length; start += 8) {
  await Promise.all(assetRefs.slice(start, start + 8).map(async (ref) => {
  if (typeof ref !== 'string' || ref.startsWith('/') || ref.includes('..')) {
    fail('production manifest contains an unsafe asset reference.')
    return
  }
  const response = await fetchRetry(`${base}/build/${ref}`)
  if (!response || response.status !== 200) {
    fail(`/build/${ref} returned HTTP ${response?.status ?? 'no response'}.`)
    return
  }
  const type = (response.headers.get('content-type') || '').toLowerCase()
  const expected = ref.endsWith('.css') ? 'text/css' : ref.endsWith('.js') ? 'javascript' : ref.endsWith('.woff2') ? 'font/woff2' : ''
  if (expected && !type.includes(expected)) fail(`/build/${ref} returned ${type || 'no content type'}, expected ${expected}.`)
  await response.arrayBuffer()
  }))
}
if (!failures.length) report(`all ${manifestRefs.size} manifest assets are reachable with expected content types`)

const [robots, sitemap, health] = await Promise.all([
  fetchText(`${base}/robots.txt?smoke=${Date.now()}`, 'text/plain'),
  fetchText(`${base}/sitemap.xml?smoke=${Date.now()}`, 'xml'),
  fetchText(`${base}/up?smoke=${Date.now()}`, 'text/html'),
])
if (!robots.includes('User-agent: *')) fail('robots.txt is missing the crawler directive.')
if (requireIndexable && (!robots.includes('Allow: /') || !robots.includes(`${base}/sitemap.xml`))) fail('production robots.txt is missing the public allow and sitemap directives.')
if (!requireIndexable && !robots.includes('Disallow: /')) fail('nonproduction robots.txt should keep the site out of search indexes.')
if (!sitemap.includes('<urlset') || (requireIndexable && !sitemap.includes(base))) fail('sitemap.xml does not contain the expected public sitemap.')
if (/"status"\s*:\s*"(down|error)"/i.test(health)) fail('/up reported an unhealthy application.')
if (!failures.length) report('robots.txt, sitemap.xml, and /up return healthy responses')

const browser = await chromium.launch({ headless: true })
try {
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 })
  const page = await context.newPage()
  page.setDefaultTimeout(12000)
  page.setDefaultNavigationTimeout(30000)
  const checkedImages = new Set()
  const transparentGif = Buffer.from('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=', 'base64')
  await page.route('**/*', async (route) => {
    if (route.request().resourceType() === 'image') {
      await route.fulfill({ status: 200, contentType: 'image/gif', body: transparentGif })
      return
    }
    await route.continue()
  })
  page.on('pageerror', (error) => fail(`browser JavaScript exception: ${error.message}`))
  page.on('console', (message) => { if (message.type() === 'error') fail(`browser console error: ${message.text()}`) })
  page.on('requestfailed', (request) => {
    if (request.url().startsWith(base)) fail(`browser request failed: ${new URL(request.url()).pathname}`)
  })
  page.on('response', (response) => {
    if (response.url().startsWith(base) && response.status() >= 400) fail(`browser request returned HTTP ${response.status()}: ${new URL(response.url()).pathname}`)
    if (response.url().startsWith(`${base}/build/`) && response.status() !== 200) fail(`browser asset returned HTTP ${response.status()}: ${new URL(response.url()).pathname}`)
  })

  const visit = async (path, label, expectedSchema = null) => {
    const normalized = new URL(path, base)
    if (normalized.origin !== base) fail(`${label} link points outside the production origin.`)
    path = `${normalized.pathname}${normalized.search}`
    const url = `${base}${path}${path.includes('?') ? '&' : '?'}__smoke=${Date.now()}`
    const response = await page.goto(url, { waitUntil: 'domcontentloaded' })
    assertStatus(response, 200, path)
    await page.locator('main h1').first().waitFor({ state: 'visible' })
    await page.waitForFunction(() => typeof window.refreshReveal === 'function', null, { timeout: 15000 }).catch(() => fail(`${path} public JavaScript entry did not initialize.`))
    const pageState = await page.evaluate(() => ({
      title: document.title,
      appCommit: document.querySelector('meta[name="app-commit"]')?.content || '',
      seoText: [document.title, ...[...document.querySelectorAll('meta[property^="og:"], meta[name^="twitter:"]')].map((el) => el.content)].join(' '),
      h1Count: document.querySelectorAll('main h1').length,
      navVisible: !!document.querySelector('header nav') && getComputedStyle(document.querySelector('header nav')).display !== 'none',
      background: getComputedStyle(document.body).backgroundColor,
      font: getComputedStyle(document.body).fontFamily,
      dmFont: document.fonts.check('16px "DM Sans"'),
      canonical: document.querySelector('link[rel="canonical"]')?.href || '',
      robots: document.querySelector('meta[name="robots"]')?.content || '',
      htmlLang: document.documentElement.lang,
      schemas: [...document.querySelectorAll('script[type="application/ld+json"]')].map((el) => { try { return JSON.parse(el.textContent || '{}')['@type'] } catch { return '' } }),
      css: [...document.querySelectorAll('link[rel="stylesheet"]')].map((el) => new URL(el.href).pathname.replace(/^\/build\//, '')),
      scripts: [...document.querySelectorAll('script[src]')].map((el) => new URL(el.src).pathname.replace(/^\/build\//, '')),
      images: [...document.querySelectorAll('img[src]')].map((el) => el.src),
      revealHidden: [...document.querySelectorAll('[data-reveal]')].filter((el) => getComputedStyle(el).opacity === '0').length,
      overflow: document.documentElement.scrollWidth - window.innerWidth,
      viewport: window.innerWidth,
    }))

    if (!pageState.title.trim()) fail(`${path} has no page title.`)
    if (pageState.seoText.includes('&amp;')) fail(`${path} SEO metadata contains a double-escaped entity.`)
    if (expectedCommit && pageState.appCommit !== expectedCommit) fail(`${path} Blade response came from commit ${pageState.appCommit || 'without a release marker'} instead of ${expectedCommit}.`)
    if (pageState.h1Count !== 1) fail(`${path} has ${pageState.h1Count} main h1 headings.`)
    if (!pageState.navVisible) fail(`${path} has no visible public navigation.`)
    if (pageState.background !== 'rgb(9, 9, 11)') fail(`${path} did not render the dark public theme (${pageState.background}).`)
    if (!pageState.font.includes('DM Sans') || !pageState.dmFont) fail(`${path} did not load the self-hosted body font.`)
    if (!pageState.canonical.startsWith(base)) fail(`${path} has no canonical URL on the expected host.`)
    if (requireIndexable && !pageState.robots.includes('index')) fail(`${path} is unexpectedly noindex.`)
    if (!requireIndexable && !pageState.robots.includes('noindex')) fail(`${path} should remain noindex outside production.`)
    if (pageState.htmlLang !== 'en') fail(`${path} has an unexpected document language.`)
    if (!pageState.schemas.includes('Person') || !pageState.schemas.includes('WebSite')) fail(`${path} is missing Person/WebSite JSON-LD.`)
    if (expectedSchema && !pageState.schemas.includes(expectedSchema)) fail(`${path} is missing ${expectedSchema} JSON-LD.`)
    for (const ref of [...pageState.css, ...pageState.scripts]) if (ref && !manifestRefs.has(ref)) fail(`${path} HTML references an asset absent from the served manifest.`)
    if (pageState.overflow > 1) fail(`${path} overflows horizontally by ${pageState.overflow}px at ${pageState.viewport}px.`)
    for (const imageUrl of pageState.images) {
      if (checkedImages.has(imageUrl)) continue
      checkedImages.add(imageUrl)
      const imageResponse = await fetchRetry(imageUrl, { Range: 'bytes=0-0' })
      if (!imageResponse || ![200, 206].includes(imageResponse.status) || !(imageResponse.headers.get('content-type') || '').startsWith('image/')) {
        fail(`${new URL(imageUrl).pathname} is not served as an image.`)
      }
      await imageResponse?.body?.cancel().catch(() => {})
    }

    await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight))
    await page.waitForFunction(() => [...document.querySelectorAll('[data-reveal]')].every((el) => getComputedStyle(el).opacity !== '0'), null, { timeout: 6000 }).catch(() => {})
    const hidden = await page.locator('[data-reveal]').evaluateAll((items) => items.filter((el) => getComputedStyle(el).opacity === '0').length)
    if (hidden) fail(`${path} left ${hidden} reveal elements invisible after scrolling.`)

    const islands = await page.locator('[data-island]').count()
    for (let index = 0; index < islands; index++) {
      const island = page.locator('[data-island]').nth(index)
      await island.evaluate((el) => {
        const disclosure = el.closest('details')
        if (disclosure) disclosure.open = true
      })
      await island.scrollIntoViewIfNeeded()
      await island.evaluate((el) => new Promise((resolve, reject) => {
        const started = Date.now()
        const check = () => {
          if (el.dataset.mounted === 'true') return resolve(true)
          if (Date.now() - started > 8000) return reject(new Error(`Island ${el.dataset.island} did not mount`))
          setTimeout(check, 100)
        }
        check()
      })).catch((error) => fail(`${path}: ${error.message}`))
    }

    report(`${path}: SSR content, theme, fonts, navigation and${islands ? ` ${islands} Vue island(s)` : ' no required islands'}`)
    return page
  }

  const home = await visit('/', 'home')
  for (const width of [360, 768, 1024, 1440]) {
    await home.setViewportSize({ width, height: width < 500 ? 780 : 900 })
    const overflow = await home.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)
    if (overflow > 1) fail(`home overflows horizontally by ${overflow}px at ${width}px.`)
  }
  await home.setViewportSize({ width: 390, height: 844 })
  const toggle = home.locator('[data-menu-toggle]')
  if (await toggle.count()) {
    await toggle.click()
    if (await toggle.getAttribute('aria-expanded') !== 'true') fail('mobile navigation did not open.')
    await home.keyboard.press('Escape')
    if (await toggle.getAttribute('aria-expanded') !== 'false') fail('Escape did not close mobile navigation.')
    report('mobile navigation opens and closes with Escape')
  } else fail('mobile navigation toggle is missing.')

  const blog = await visit('/blog', 'blog')
  const articleHref = await blog.locator('main a[href*="/blog/"]').first().getAttribute('href').catch(() => null)
  if (!articleHref) fail('blog index has no server-rendered article link.')
  else await visit(articleHref, 'blog post', 'BlogPosting')

  const projects = await visit('/projects', 'projects')
  const projectHref = await projects.locator('main a[href*="/projects/"]').first().getAttribute('href').catch(() => null)
  if (!projectHref) fail('projects index has no server-rendered project link.')
  else await visit(projectHref, 'project detail', 'CreativeWork')

  const login = await page.goto(`${base}/admin/login?__smoke=${Date.now()}`, { waitUntil: 'domcontentloaded' })
  assertStatus(login, 200, '/admin/login')
  await page.locator('#login-email').waitFor({ state: 'visible' })
  const loginState = await page.evaluate(() => ({ title: document.title, appCommit: document.querySelector('meta[name="app-commit"]')?.content || '', noindex: document.querySelector('meta[name="robots"]')?.content, overflow: document.documentElement.scrollWidth - window.innerWidth }))
  if (!loginState.title.includes('Admin') || !loginState.noindex?.includes('noindex')) fail('admin login did not mount or is not marked noindex.')
  if (expectedCommit && loginState.appCommit !== expectedCommit) fail(`/admin/login Blade response came from commit ${loginState.appCommit || 'without a release marker'} instead of ${expectedCommit}.`)
  if (loginState.overflow > 1) fail(`admin login overflows horizontally by ${loginState.overflow}px at 390px.`)
  report('admin login Vue app renders and remains noindex')

  await page.setViewportSize({ width: 1440, height: 900 })
  await visit('/', 'home desktop')

  const notFound = await fetch(`${base}/__portfolio-smoke-404-${Date.now()}`, { headers: { 'User-Agent': 'Portfolio deployment smoke check' } }).catch(() => null)
  assertStatus(notFound, 404, 'unknown public route')
  if (!failures.length) report('unknown public route returns HTTP 404')
  await context.close()
} catch (error) {
  fail(`browser smoke aborted: ${error.message}`)
} finally {
  await browser.close()
}

if (failures.length) {
  console.error(`Production smoke failed with ${failures.length} issue(s).`)
  process.exit(1)
}
console.log('Production browser smoke passed.')
