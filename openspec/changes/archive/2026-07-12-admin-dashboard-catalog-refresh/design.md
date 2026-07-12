# Design: admin-dashboard-catalog-refresh

## Technical Approach

Markup-only visual refactor of 15 Blade views (1 dashboard + 7 catalog modules × 2 views). Replace `indigo-*` accents with emerald `catalog.*` tokens, introduce two shared Blade components (`<x-page-header>`, `<x-stat-card>`), and extend `tailwind.config.js` with scoped design tokens. Strict TDD: one feature test per view, written before markup changes. Delivered via 9 chained PRs (each ≤400 lines).

Refs: `admin-dashboard-ui` spec (8 requirements), `admin-catalog-ui` spec (6 requirements).

## Architecture Decisions

| # | Decision | Choice | Alternatives | Rationale |
|---|----------|--------|--------------|-----------|
| 1 | Tailwind token namespace | `theme.extend.colors.catalog.{primary, accent, muted, border, card}` | Rename `colors.primary` globally; use CSS custom properties | Scoped under `catalog.*` prevents leaking into POS, auth, and out-of-scope admin screens. Sidebar workstream can do the global swap later. |
| 2 | `<x-page-header>` shape | Hybrid: `title` (required prop), `description` (optional prop), `action` (named slot) | Pure props; pure slots | All 15 views need a title; 12 need a description; 8 need an action button. Named slot for `action` keeps the component open-ended without over-prop-ifying. Follows existing `@props` + `$slot` convention from `status-badge`. |
| 3 | `<x-stat-card>` shape | Props: `label` (required), `href` (optional). Default slot for body. | Pure slots; config-array driven | Dashboard cards share label+optional-link pattern. `href` prop makes the card an `<a>` when present, a `<div>` otherwise — avoids wrapper components. |
| 4 | Tailwind 3/4 dual install | Keep Tailwind 3 path (`@tailwind` directives in `app.css`); ignore `@tailwindcss/vite` | Migrate to Tailwind 4 | `app.css` uses `@tailwind base/components/utilities` (TW3 syntax). PostCSS routes to TW3. Migration is out of scope and risky. |
| 5 | Border radius `0.7rem` | Override `theme.extend.borderRadius.lg` to `'0.7rem'` in config | Arbitrary value `rounded-[0.7rem]` everywhere | Keeps markup clean (`rounded-lg`), matches reference exactly, one-line config change. Non-lg radii (`rounded-md` on inputs) stay at Tailwind defaults. |
| 6 | Chained PR layout | 9 PRs (see Migration/Rollout) | Single PR; 3 PRs | Each PR ≤400 lines, independently revertible, maps to one work unit. Reviewer can verify one module at a time. |

## Data Flow

No data flow changes. This is a markup-only refactor — controllers, routes, models, and service layer are untouched. All views continue to receive the same `$variables` from controllers.

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `backend/tailwind.config.js` | Modify | Add `colors.catalog.*`, override `borderRadius.lg` |
| `backend/resources/views/components/page-header.blade.php` | Create | Shared page header component |
| `backend/resources/views/components/stat-card.blade.php` | Create | Shared stat card component |
| `backend/resources/views/dashboard.blade.php` | Modify | Replace indigo → catalog tokens; use `<x-page-header>`, `<x-stat-card>` |
| `backend/resources/views/catalog/{module}/index.blade.php` | Modify | Replace indigo → catalog tokens; use `<x-page-header>` (×7 modules) |
| `backend/resources/views/catalog/{module}/form.blade.php` | Modify | Replace indigo → catalog tokens; use `<x-page-header>` (×7 modules) |
| `backend/tests/Feature/DashboardTest.php` | Create | Dashboard render + section assertions |
| `backend/tests/Feature/Catalog/{Module}IndexTest.php` | Create | Index render + header + empty-state assertions (×7 modules) |

Modules: `categories`, `brands`, `base-units`, `products`, `variants`, `presentations`, `prices`.

## Interfaces / Contracts

### `<x-page-header>` — Props: `title` (required), `description` (optional). Slot: `action` (optional).

```blade
{{-- illustrative --}}
@props(['title', 'description' => null])
<div class="flex items-center justify-between gap-3">
    <div>
        <h2 class="text-2xl font-semibold tracking-tight text-balance text-gray-900">{{ $title }}</h2>
        @if ($description)<p class="mt-1 text-sm text-gray-500 text-pretty">{{ $description }}</p>@endif
    </div>
    @isset($action)<div>{{ $action }}</div>@endisset
</div>
```

### `<x-stat-card>` — Props: `label` (required), `href` (optional). Default slot for body.

```blade
{{-- illustrative --}}
@props(['label', 'href' => null])
@php($tag = $href ? 'a' : 'div')
<{{ $tag }} {{ $attributes->merge(['class' => 'rounded-lg bg-white p-6 ring-1 ring-border']) }}
    @if($href) href="{{ $href }}" @endif>
    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
    <div class="mt-2">{{ $slot }}</div>
</{{ $tag }}>
```

## Testing Strategy

| Test File | Assertions |
|-----------|-----------|
| `DashboardTest` | `assertOk`; `assertSee` for title, status card, 4 operation routes, 4 catalog routes, quick links; `assertDontSee('indigo')` scoped to dashboard-specific patterns on raw HTML; renders with empty DB |
| `Catalog/{Module}IndexTest` (×7) | `assertOk`; `assertSee` for module title, create route, seeded record names; empty state shows "Aún no hay"; `assertDontSee('indigo')` scoped to module-specific patterns |

Convention: `RefreshDatabase`, `actingAs(User::factory()->create())`, `#[Test]` attributes (matches `DashboardViewTest`).

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable-file classification, or process-integration boundary. This change is markup-only.

## Migration / Rollout

No data migration. Chained PR order (each depends on PR 1; PRs 3–9 are mutually independent):

```
PR 1 foundation: tailwind.config.js + components + tests (~150 lines)
  ▼
PR 2 dashboard: dashboard.blade.php + DashboardTest (~150 lines)
  ▼
PRs 3–9 (one per module, ~80–120 lines each):
  categories → brands → base-units → products → variants → presentations → prices
  Each: index+form rewrite + Catalog/{Module}IndexTest
```

Rollback: revert any PR independently. Reverting PR 1 requires reverting all downstream first.

## Open Questions

None.
