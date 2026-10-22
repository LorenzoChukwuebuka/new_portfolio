import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../resources/css/tokens.css', import.meta.url), 'utf8');

function block(selector, after = 0) {
  const start = source.indexOf(selector, after);
  if (start < 0) throw new Error(`Missing token block: ${selector}`);
  const open = source.indexOf('{', start);
  const close = source.indexOf('}', open);
  if (open < 0 || close < 0) throw new Error(`Malformed token block: ${selector}`);
  return source.slice(open + 1, close);
}

function variables(css) {
  return Object.fromEntries([...css.matchAll(/(--[\w-]+)\s*:\s*(#[\da-fA-F]{6})\s*;/g)].map((match) => [match[1], match[2]]));
}

function luminance(hex) {
  const channels = hex.slice(1).match(/.{2}/g).map((channel) => parseInt(channel, 16) / 255).map((value) =>
    value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4,
  );
  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}

function contrast(foreground, background) {
  const values = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
  return (values[0] + 0.05) / (values[1] + 0.05);
}

const theme = variables(block('@theme'));
const light = variables(block(':root {'));
const dark = variables(block(':root[data-theme="dark"]'));
const failures = [];
let checked = 0;

function assertPairs(name, vars, pairs) {
  for (const [foreground, background, target] of pairs) {
    const fg = vars[foreground];
    const bg = vars[background];
    if (!fg || !bg) {
      failures.push(`${name}: missing ${foreground} or ${background}`);
      continue;
    }
    checked++;
    const ratio = contrast(fg, bg);
    if (ratio < target) failures.push(`${name}: ${foreground} on ${background} is ${ratio.toFixed(2)}:1; expected ${target}:1`);
  }
}

assertPairs('public', theme, [
  ['--color-ink', '--color-canvas', 4.5],
  ['--color-ink-muted', '--color-canvas', 4.5],
  ['--color-ink-subtle', '--color-canvas', 4.5],
  ['--color-accent', '--color-canvas', 4.5],
  ['--color-accent-ink', '--color-accent', 4.5],
  ['--color-success', '--color-canvas', 4.5],
  ['--color-danger', '--color-canvas', 4.5],
]);

const adminPairs = [
  ['--adm-ink', '--adm-bg', 4.5],
  ['--adm-ink-muted', '--adm-bg', 4.5],
  ['--adm-accent-ink', '--adm-accent', 4.5],
  ['--adm-sidebar-ink', '--adm-sidebar-bg', 4.5],
  ['--adm-focus', '--adm-bg', 3],
  ['--adm-success-ink', '--adm-success-bg', 4.5],
  ['--adm-warning-ink', '--adm-warning-bg', 4.5],
  ['--adm-danger-ink', '--adm-danger-bg', 4.5],
  ['--adm-info-ink', '--adm-info-bg', 4.5],
];

assertPairs('admin light', light, adminPairs);
assertPairs('admin dark', dark, adminPairs);

if (failures.length) {
  console.error(failures.join('\n'));
  process.exitCode = 1;
} else {
  console.log(`Contrast check passed (${checked} documented foreground/background pairs).`);
}
