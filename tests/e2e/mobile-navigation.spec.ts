import { mkdir } from 'node:fs/promises'
import { join } from 'node:path'
import { expect, test } from '@playwright/test'

const viewports = [
  { width: 360, height: 640 },
  { width: 390, height: 844 },
  { width: 741, height: 923 },
  { width: 768, height: 1024 },
  { width: 1024, height: 768 },
]

for (const viewport of viewports) {
  test(`mobile navigation covers and blocks the page at ${viewport.width}x${viewport.height}`, async ({ page }) => {
    await page.setViewportSize(viewport)
    await page.goto('/')

    const toggle = page.getByRole('button', { name: 'Open menu' })
    const panel = page.locator('#site-mobile-nav')
    await expect(toggle).toBeVisible()
    await expect(toggle).toHaveAttribute('aria-controls', 'site-mobile-nav')
    const tapTarget = await toggle.boundingBox()
    expect(tapTarget?.width).toBeGreaterThanOrEqual(44)
    expect(tapTarget?.height).toBeGreaterThanOrEqual(44)

    await page.evaluate(() => window.scrollTo(0, 0))
    await toggle.click()
    await expect(page.getByRole('button', { name: 'Close menu' })).toHaveAttribute('aria-expanded', 'true')
    await expect(panel).toBeVisible()
    await page.waitForFunction(() => document.querySelector('#site-mobile-nav')?.getAttribute('data-open') === 'true')
    await expect(panel).toHaveCSS('opacity', '1')

    const evidence = await page.evaluate(() => {
      const drawer = document.querySelector<HTMLElement>('#site-mobile-nav')!
      const title = document.querySelector<HTMLElement>('#hero-title')!
      const panelRect = drawer.getBoundingClientRect()
      const titleRect = title.getBoundingClientRect()
      const hitTarget = document.elementFromPoint(titleRect.left + titleRect.width / 2, titleRect.top + titleRect.height / 2)
      const background = getComputedStyle(drawer).backgroundColor
      const rgbaAlpha = background.match(/rgba\([^)]*,\s*([\d.]+)\s*\)$/)
      const slashAlpha = background.match(/\/\s*([\d.]+)\s*\)$/)
      const beforeScroll = window.scrollY
      window.scrollBy(0, 400)
      const afterScroll = window.scrollY
      const main = document.querySelector<HTMLElement>('main')!
      const footer = document.querySelector<HTMLElement>('footer')!

      return {
        panel: { x: panelRect.x, y: panelRect.y, width: panelRect.width, height: panelRect.height },
        alpha: rgbaAlpha ? Number(rgbaAlpha[1]) : slashAlpha ? Number(slashAlpha[1]) : 1,
        heroHitIsInPanel: hitTarget !== null && drawer.contains(hitTarget),
        scroll: { before: beforeScroll, after: afterScroll },
        main: { inert: main.inert, ariaHidden: main.getAttribute('aria-hidden') },
        footer: { inert: footer.inert, ariaHidden: footer.getAttribute('aria-hidden') },
        horizontalOverflow: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) > document.documentElement.clientWidth,
      }
    })

    expect(evidence.panel.x).toBe(0)
    expect(evidence.panel.y).toBe(0)
    expect(evidence.panel.width).toBeGreaterThanOrEqual(viewport.width)
    expect(evidence.panel.height).toBeGreaterThanOrEqual(viewport.height)
    expect(evidence.alpha).toBe(1)
    expect(evidence.heroHitIsInPanel).toBe(true)
    expect(evidence.scroll.after).toBe(evidence.scroll.before)
    expect(evidence.main).toEqual({ inert: true, ariaHidden: 'true' })
    expect(evidence.footer).toEqual({ inert: true, ariaHidden: 'true' })
    expect(evidence.horizontalOverflow).toBe(false)

    if (process.env.NAV_SCREENSHOT_DIR && [360, 741].includes(viewport.width)) {
      await mkdir(process.env.NAV_SCREENSHOT_DIR, { recursive: true })
      await page.screenshot({ path: join(process.env.NAV_SCREENSHOT_DIR, `mobile-nav-after-${viewport.width}.png`) })
    }

    await page.keyboard.press('Shift+Tab')
    await expect(page.getByRole('button', { name: 'Close menu' })).toBeFocused()
    await page.keyboard.press('Shift+Tab')
    await expect(page.getByRole('link', { name: 'Browse selected work' })).toBeFocused()
    await page.keyboard.press('Tab')
    await expect(page.getByRole('button', { name: 'Close menu' })).toBeFocused()
    await page.keyboard.press('Escape')

    await expect(panel).toBeHidden()
    await expect(page.getByRole('button', { name: 'Open menu' })).toHaveAttribute('aria-expanded', 'false')
    await expect(page.getByRole('button', { name: 'Open menu' })).toBeFocused()
    await expect(page.locator('main')).not.toHaveAttribute('aria-hidden', 'true')
    await expect(page.locator('footer')).not.toHaveAttribute('aria-hidden', 'true')
  })
}

test('menu links close the drawer on same-page hash navigation', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto('/')
  await page.getByRole('button', { name: 'Open menu' }).click()
  await page.locator('#site-mobile-nav a[href="/#work"]').click()

  await expect(page).toHaveURL(/#work$/)
  await expect(page.locator('#site-mobile-nav')).toBeHidden()
  await expect(page.getByRole('button', { name: 'Open menu' })).toBeFocused()
})

test('menu closes when desktop navigation becomes available', async ({ page }) => {
  await page.setViewportSize({ width: 1024, height: 768 })
  await page.goto('/')
  await page.getByRole('button', { name: 'Open menu' }).click()
  await expect(page.locator('#site-mobile-nav')).toBeVisible()

  await page.setViewportSize({ width: 1025, height: 768 })
  await expect(page.locator('#site-mobile-nav')).toBeHidden()
  await expect(page.locator('[data-menu-toggle]')).toHaveAttribute('aria-expanded', 'false')
  await expect(page.locator('.site-nav__links a').first()).toBeFocused()
  await expect(page.locator('.site-nav__links')).toBeVisible()
})

test('short landscape menu keeps the CTA and location line separated and scrollable', async ({ page }) => {
  await page.setViewportSize({ width: 640, height: 360 })
  await page.goto('/')
  await page.getByRole('button', { name: 'Open menu' }).click()

  const layout = await page.evaluate(() => {
    const panel = document.querySelector<HTMLElement>('#site-mobile-nav')!
    const cta = panel.querySelector<HTMLElement>('.btn')!.getBoundingClientRect()
    const note = panel.querySelector<HTMLElement>('.mobile-drawer__note')!.getBoundingClientRect()
    return {
      scrollable: panel.scrollHeight > panel.clientHeight,
      overlaps: cta.bottom > note.top && cta.top < note.bottom,
      overflow: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) > document.documentElement.clientWidth,
    }
  })

  expect(layout.scrollable).toBe(true)
  expect(layout.overlaps).toBe(false)
  expect(layout.overflow).toBe(false)
})

test('reduced motion disables the menu transition', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto('/')
  const transition = await page.locator('#site-mobile-nav').evaluate((element) => getComputedStyle(element).transitionProperty)
  expect(transition).toBe('none')
})
