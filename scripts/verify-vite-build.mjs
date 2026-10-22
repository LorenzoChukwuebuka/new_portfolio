import { access, readFile, stat } from 'node:fs/promises'
import { resolve, sep } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = resolve(fileURLToPath(new URL('..', import.meta.url)))
const buildRoot = resolve(root, 'public/build')
const manifestPath = resolve(buildRoot, 'manifest.json')

function fail(message) {
  console.error(`Vite build verification failed: ${message}`)
  process.exit(1)
}

async function requireFile(relative, context) {
  if (typeof relative !== 'string' || relative.startsWith('/') || relative.includes('..')) {
    fail(`${context} contains an unsafe output path.`)
  }
  const path = resolve(buildRoot, relative)
  if (!path.startsWith(`${buildRoot}${sep}`)) fail(`${context} escapes public/build.`)
  try {
    const info = await stat(path)
    if (!info.isFile() || info.size === 0) fail(`${context} points to an empty or non-file output.`)
  } catch {
    fail(`${context} points to a missing output: ${relative}`)
  }
}

let manifest
try {
  manifest = JSON.parse(await readFile(manifestPath, 'utf8'))
} catch {
  fail('public/build/manifest.json is missing or invalid JSON.')
}

for (const entry of ['resources/js/site.ts', 'resources/js/admin.ts']) {
  if (!manifest[entry]?.isEntry || !manifest[entry]?.file?.endsWith('.js')) {
    fail(`${entry} is not a compiled JavaScript entry point.`)
  }
}

const outputs = new Set()
for (const [key, item] of Object.entries(manifest)) {
  if (!item || typeof item !== 'object') fail(`manifest entry ${key} is invalid.`)
  for (const path of [item.file, ...(item.css || []), ...(item.assets || [])].filter(Boolean)) {
    await requireFile(path, `manifest entry ${key}`)
    outputs.add(path)
  }
  for (const dependency of [...(item.imports || []), ...(item.dynamicImports || [])]) {
    if (!manifest[dependency]) fail(`manifest entry ${key} references missing entry ${dependency}.`)
  }
}

async function reachableCss(entry, visited = new Set()) {
  if (visited.has(entry)) return []
  visited.add(entry)
  const item = manifest[entry]
  const found = [...(item.css || [])]
  for (const dependency of item.imports || []) found.push(...await reachableCss(dependency, visited))
  return found
}

const siteCss = [...new Set(await reachableCss('resources/js/site.ts'))]
if (siteCss.length === 0) fail('the public site entry does not include compiled CSS.')

const layout = await readFile(resolve(root, 'resources/views/layouts/app.blade.php'), 'utf8')
const adminLayout = await readFile(resolve(root, 'resources/views/admin.blade.php'), 'utf8')
const siteEntry = await readFile(resolve(root, 'resources/js/site.ts'), 'utf8')
if (!layout.includes("@vite('resources/js/site.ts')")) fail('the public Blade layout does not load the site entry.')
if (!siteEntry.includes("../css/app.css")) fail('the public entry does not import the shared stylesheet.')
if (layout.includes("@vite('resources/css/app.css')")) fail('the public stylesheet is also listed as a separate Vite input.')
if (!layout.includes('name="app-commit"') || !adminLayout.includes('name="app-commit"')) fail('the Blade layouts do not expose the active application commit for deployment verification.')

await access(manifestPath)
console.log(`Vite manifest valid: ${Object.keys(manifest).length} entries, ${outputs.size} output files, public CSS: ${siteCss.join(', ')}`)
