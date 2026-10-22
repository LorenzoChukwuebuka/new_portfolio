# Design system

The system is defined in `resources/css/tokens.css` and consumed by
`resources/css/app.css`. Keep raw palette values in the token file. Public and
admin components should use semantic names so a palette change remains
centralized.

## Color tokens

### Public theme

The public site uses a dark editorial palette. The raw primitive scale is
available for token definitions; templates and components use the semantic
tokens below.

| Token | Value | Intended use |
| --- | --- | --- |
| `--color-canvas` | `#09090b` | Main page background |
| `--color-surface` | `#111113` | Navigation and cards |
| `--color-surface-raised` | `#18181b` | Inputs and raised/hover surfaces |
| `--color-line` | `#27272a` | Hairlines and card borders |
| `--color-line-strong` | `#3f3f46` | Emphasized borders |
| `--color-ink` | `#f4f4f5` | Headings and primary copy |
| `--color-ink-muted` | `#a1a1aa` | Secondary copy |
| `--color-ink-subtle` | `#8b8b94` | Captions and labels |
| `--color-accent` | `#fbbf24` | Primary amber accent |
| `--color-accent-strong` | `#fcd34d` | Hover and emphasized amber |
| `--color-accent-ink` | `#09090b` | Text on amber controls |
| `--color-accent-wash` | 10% accent mix | Quiet accent fills |
| `--color-success` | `#34d399` | Success states |
| `--color-danger` | `#fb7185` | Error and destructive states |

### Admin theme

Admin uses the same semantic names in light and dark modes. Light is the
default; `data-theme="dark"` selects dark values, and the system preference is
used until a saved choice is applied. `ThemeToggle` persists the selection in
`localStorage('admin_theme')`.

| Token | Light | Dark | Use |
| --- | --- | --- | --- |
| `--adm-bg` | neutral 100 | neutral 950 | App background |
| `--adm-surface` | white | neutral 925 | Cards and dialogs |
| `--adm-surface-2` | neutral 50 | neutral 900 | Subtle nested surfaces |
| `--adm-line` | neutral 300 | neutral 700 | Dividers and control borders |
| `--adm-ink` | neutral 900 | neutral 100 | Primary text |
| `--adm-ink-muted` | neutral 600 | neutral 400 | Secondary text |
| `--adm-accent` | amber 800 | amber 400 | Text links and accent labels |
| `--adm-accent-bg` | amber 500 | amber 400 | Accent fills and active controls |
| `--adm-accent-ink` | neutral 900 | neutral 900 | Text on amber |
| `--adm-sidebar-bg` | neutral 900 | neutral 925 | Sidebar surface |
| `--adm-sidebar-ink` | neutral 100 | neutral 100 | Sidebar text |
| `--adm-focus` | amber 700 | amber 300 | Visible keyboard focus |

Status pairs are `--adm-success-bg/-ink`, `--adm-warning-bg/-ink`,
`--adm-danger-bg/-ink`, and `--adm-info-bg/-ink`. Use the pair together rather
than mixing a status foreground with an unrelated surface.

Tailwind utilities are exposed through `@theme inline`; examples include
`bg-adm-surface`, `text-adm-ink-muted`, `bg-canvas`, and `text-accent`.

## Typography

| Token | Face | Usage |
| --- | --- | --- |
| `--font-body` | DM Sans variable | Body, UI, and admin |
| `--font-display` | Playfair Display variable | Public headings and editorial pull quotes |
| `--font-mono` | JetBrains Mono variable | Code, technical labels, and IDs |

All fonts are self-hosted latin variable WOFF2 files with `font-display: swap`.
The public layout preloads DM Sans and Playfair Display; the admin preloads DM
Sans. Metric-adjusted system fallback faces reduce layout movement while fonts
load.

The fluid scale is `--text-xs` through `--text-6xl`, plus `--text-display` for
the home hero. Each step defines a line height. Body text uses a comfortable
reading line length (about 65 characters); article copy is 18–19px with a 1.75
line height and a 68ch measure.

## Spacing, shape, elevation, motion, and layout

| Group | Tokens | Guidance |
| --- | --- | --- |
| Spacing | Tailwind 4px scale, `--gutter`, `--section-y` | Use the shared page gutter and section rhythm; avoid one-off page spacing values. |
| Layout | `.container-page` | Use for aligned page content; maximum width is 80rem. |
| Radius | `--radius-sm: 2px`, `--radius-md: 8px`, `--radius-lg: 12px`, `--radius-pill` | Public cards and controls stay sharp; admin panels use medium/large radius; pills are for compact chips. |
| Elevation | `--shadow-card`, `--shadow-pop`, `--shadow-adm-card` | Keep shadows subtle; reserve the amber glow for an emphasized public hover. |
| Motion | `--ease-out`, `--dur-fast`, `--dur-base`, `--dur-slow` | Animate opacity and transform only. Reduced-motion mode removes reveal and transition motion. |
| Layering | `--z-index-nav`, `--z-index-drawer`, `--z-index-modal`, `--z-index-toast` | Use these tokens for overlays and fixed controls. |

Layouts are mobile-first, use `svh`/`dvh` for viewport-sized regions, and keep
interactive targets at least 44px high where practical. Focus-visible uses a
2px token-colored ring with offset. Never remove the browser outline without a
replacement.

Run `npm run check:contrast` after changing semantic pairs. It checks the
documented text/background combinations against WCAG AA for normal text and
the applicable 3:1 threshold for large text/UI boundaries.

## Component catalogue

| Class | Purpose | Use |
| --- | --- | --- |
| `.container-page` | Shared centered layout | Wrap page sections and keep their edges aligned. |
| `.section` | Standard vertical rhythm | Apply to major public sections; combine with a container inside. |
| `.eyebrow` | Small mono uppercase label | Use for section context, never as the only heading. |
| `.card` | Public surface with border/elevation | Use for content groups and project/post cards. |
| `.btn` | Public action base | Apply with one variant and optional `.btn-sm`/`.btn-lg`; links that act as buttons keep real `href`s. |
| `.btn-primary` | Main action | One primary action per local group when possible. |
| `.btn-secondary` | Outlined secondary action | Use for peer actions that should remain visible. |
| `.btn-ghost` | Quiet action | Use for low-emphasis navigation and table actions. |
| `.btn-danger` | Destructive action | Pair with a confirmation where the action cannot be undone. |
| `.chip` | Public taxonomy/status pill | Use short labels and maintain enough contrast. |
| `.field` | Public field and label layout | Associate each label with an input ID; render hint and error text explicitly. |
| `.field-control` | Admin field control | Prefer the typed `Field`/`Input`/`Textarea`/`Select` UI components. |
| `.prose-article` | Sanitized article body | Use only with `ArticleHtml` output; never pass unsanitized editor HTML. |

Admin controls are Vue components in `resources/js/admin/ui/` and use the
`adm-*` semantic tokens. Keep shared controls typed and accessible rather than
copying per-page styles.

### Usage rules

- Components consume semantic tokens, not raw hex values or primitive palette
  utilities. Primitive values belong in `tokens.css` only.
- Use real links for navigation and buttons for actions. Avoid clickable
  containers with nested links or controls.
- Every field has a visible associated label, and validation errors are linked
  with `aria-describedby` and reflected with `aria-invalid`.
- Keep headings in order, provide one page `<h1>`, and preserve visible
  keyboard focus.
- For color, check both the foreground/background pair and interactive states.
- Prefer existing spacing, radius, and motion tokens over custom values.

### Adding a token

1. Add or adjust a primitive in the palette section of `tokens.css` only when
   the existing scale cannot express the needed color.
2. Define the meaning as a semantic token, including both admin theme values
   when it applies to admin.
3. Expose it through `@theme inline` if it needs a Tailwind utility.
4. Add the foreground/background pair to `scripts/check-token-contrast.mjs`.
5. Use the semantic utility or component class and run the contrast check,
   typecheck, build, and relevant page tests.

## Kitchen sink pages

- `/_design` is available only with `APP_ENV=local` and shows the public
  buttons, chips, cards, fields, article typography, and statuses.
- `/admin/_kit` is included only in a Vite development build and requires admin
  authentication. It previews the shared admin controls in the active theme.
