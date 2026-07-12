# Exploration: admin-dashboard-catalog-refresh

> Phase 0 artifact. Provides the input for `sdd-propose`. The full visual
> delta mapping (current vs. reference repo) lives in
> `openspec/changes/admin-sidebar-refresh/exploration.md` (sibling change
> owned by the parallel sidebar workstream). This file captures ONLY the
> scope decisions confirmed with the user for THIS change.

## Goal

Refresh the **admin dashboard and catalog screens** of xpos001 with the
visual language of the reference repo
(https://github.com/ondtcx/point-of-sale-interface). Apply the design
tokens, spacing, typography, and component shapes used by the reference
repo WITHOUT touching the navigation chrome (which is being redesigned
in parallel in the `admin-sidebar-refresh` change).

## Confirmed Scope (user decisions 2026-07-12)

### In scope (this change)

- `backend/resources/views/dashboard.blade.php` — landing page.
- 8 catalog screens (index + form for each):
  - `backend/resources/views/catalog/categories/{index,form}.blade.php`
  - `backend/resources/views/catalog/brands/{index,form}.blade.php`
  - `backend/resources/views/catalog/base-units/{index,form}.blade.php`
  - `backend/resources/views/catalog/products/{index,form}.blade.php`
  - `backend/resources/views/catalog/variants/{index,form}.blade.php`
  - `backend/resources/views/catalog/presentations/{index,form}.blade.php`
  - `backend/resources/views/catalog/prices/{index,form}.blade.php`
- Optional: small shared Blade components introduced for this scope only
  (e.g. `<x-page-header>`, `<x-stat-card>`). Keep them local to admin
  views; do NOT register as global components unless reuse justifies it.
- Optional: extend `backend/tailwind.config.js` with the color and
  radius tokens used by the reference repo.

### Out of scope (parallel workstream or already done)

- **Sidebar / top nav**: owned by the `admin-sidebar-refresh` change
  (parallel). Do NOT touch `layouts/navigation.blade.php`,
  `layouts/app.blade.php`, `components/nav-link.blade.php`,
  `components/responsive-nav-link.blade.php`,
  `components/dropdown.blade.php`,
  `components/application-logo.blade.php`.
- **POS / register**: already shipped via `pos-v2` (master at
  `4b2f363`). Do NOT touch `pos/index.blade.php` or any pos-v2 file.
- **Auth / guest screens**: not part of the admin chrome. Leave
  `layouts/guest.blade.php` and `auth/*` views alone.
- **Sales / Purchases / Inventory / Customers / Cash / Reports / Suppliers**:
  other admin screens; deferred to later phases.
- **Color tokens leaking globally**: the new emerald palette MUST be
  scoped (via `bg-catalog-card` style names or guarded selector) so it
  does not affect POS, auth, or sibling admin screens outside this
  change. (Decision: scope via prefixed utility names; full migration
  happens when the sidebar workstream lands.)

## Confirmed Visual Decisions

- **Accent color**: emerald green (reference repo's
  `oklch(0.56 0.12 151)` ≈ `#3f7a54`). Replaces the current
  `indigo` accent ONLY in the screens in scope.
- **Icon library**: keep what the existing catalog screens use
  (no icon library is currently present in these views; the sidebar
  workstream will decide globally). Do not introduce an icon library
  in this change.
- **Typography**: keep Figtree (already loaded globally by
  `layouts/app.blade.php`); apply the reference's `text-balance` and
  `text-pretty` patterns on headings/descriptions.
- **Border radius**: reference uses `--radius: 0.7rem`. Apply via
  `rounded-lg` (which Tailwind maps to 0.5rem by default) and adjust
  in `tailwind.config.js` if a tighter match is needed.
- **Page header pattern**: title (`text-2xl font-semibold
  tracking-tight text-balance`) + description
  (`text-sm text-muted-foreground text-pretty`) + optional action
  slot, as in the reference's `components/page-header.tsx`.
- **Card surface**: `bg-white ring-1 ring-border rounded-lg` (the
  reference uses oklch near-white cards with a subtle border).

## Tech Stack Constraints

- Laravel 12, Breeze, Alpine 3, Vite 7, Tailwind 3 (project uses
  Tailwind 3 directives in `app.css`; `@tailwindcss/vite` 4 is also
  installed but PostCSS still routes to Tailwind 3 — keep Tailwind 3
  path; do NOT migrate to Tailwind 4 in this change).
- Strict TDD is ON. Every Blade change in scope MUST be paired with at
  least one new feature test (e.g. assert that the dashboard still
  renders and that the catalog index page still lists its items after
  the visual refactor).
- `composer` and `gh` are not installed on this Windows host. Tests
  will need to be runnable after `composer install`; PR creation is
  manual.

## Reference: Visual Token Snapshot

(Extracted from `https://github.com/ondtcx/point-of-sale-interface`,
`app/globals.css`. Full mapping in the sidebar workstream's
`exploration.md`.)

| Token | Value | Use |
| --- | --- | --- |
| `--background` | `oklch(0.985 0.006 95)` | App background (near-white) |
| `--card` | `oklch(1 0 0)` | Card surface |
| `--primary` | `oklch(0.56 0.12 151)` | Brand emerald (≈ #3f7a54) |
| `--accent` | `oklch(0.94 0.03 150)` | Hover/accent background |
| `--muted-foreground` | `oklch(0.55 0.012 80)` | Muted text |
| `--border` | `oklch(0.9 0.008 90)` | Border color |
| `--radius` | `0.7rem` | Base radius |

## Open Items (for sdd-propose / sdd-design)

1. Confirm whether to introduce shared `<x-page-header>` and
   `<x-stat-card>` Blade components or keep markup inline in each view.
2. Confirm the Tailwind color extension strategy in
   `tailwind.config.js` (extend `theme.colors` with named tokens like
   `catalog.primary` that map to the oklch values, OR add raw hex and
   use the existing `indigo-*` palette as aliases).
3. Confirm that the green palette is SCOPED to catalog/dashboard only
   (no global primary change in this change) — the sidebar workstream
   may do the global swap when it lands.
4. List the feature tests each Blade change requires (minimum:
   `200 OK` on the route, presence of the page title and primary
   action).
