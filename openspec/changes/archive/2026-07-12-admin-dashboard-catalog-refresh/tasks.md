# Tasks: admin-dashboard-catalog-refresh

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~1200 across 1 dashboard + 14 catalog views + 2 components + 1 config + 8 tests |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (foundation) -> PR 2 (dashboard) -> PRs 3-9 (one per catalog module) |
| Delivery strategy | auto-chain |
| Chain strategy | feature-branch-chain |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Base branch | Focused test command | Runtime harness | Rollback boundary |
|------|------|-----------|-------------|----------------------|-----------------|-------------------|
| 1 | Foundation: tailwind tokens + shared components | PR 1 | feat/admin-dashboard-catalog-refresh | `cd backend && composer test -- --filter='PageHeaderTest\|StatCardTest'` | N/A (components unit-tested via render) | Revert `backend/tailwind.config.js`; delete `components/page-header.blade.php` and `components/stat-card.blade.php` |
| 2 | Dashboard: rewrite + DashboardTest | PR 2 | PR 1 branch | `cd backend && composer test -- --filter=DashboardTest` | `php artisan serve` then `curl /dashboard` returns 200 OK and contains `catalog-primary` | Revert `resources/views/dashboard.blade.php`; delete `tests/Feature/DashboardTest.php` |
| 3 | Categories: index+form rewrite + IndexTest | PR 3 | PR 2 branch | `cd backend && composer test -- --filter=CategoriesIndexTest` | `curl /categories` returns 200 OK and emerald create link present | Revert `resources/views/catalog/categories/{index,form}.blade.php`; delete `tests/Feature/Catalog/CategoriesIndexTest.php` |
| 4 | Brands: index+form rewrite + IndexTest | PR 4 | PR 3 branch | `cd backend && composer test -- --filter=BrandsIndexTest` | `curl /brands` returns 200 OK | Revert `resources/views/catalog/brands/{index,form}.blade.php`; delete `tests/Feature/Catalog/BrandsIndexTest.php` |
| 5 | Base-units: index+form rewrite + IndexTest | PR 5 | PR 4 branch | `cd backend && composer test -- --filter=BaseUnitsIndexTest` | `curl /base-units` returns 200 OK | Revert `resources/views/catalog/base-units/{index,form}.blade.php`; delete `tests/Feature/Catalog/BaseUnitsIndexTest.php` |
| 6 | Products: index+form rewrite + IndexTest | PR 6 | PR 5 branch | `cd backend && composer test -- --filter=ProductsIndexTest` | `curl /products` returns 200 OK | Revert `resources/views/catalog/products/{index,form}.blade.php`; delete `tests/Feature/Catalog/ProductsIndexTest.php` |
| 7 | Variants: index+form rewrite + IndexTest | PR 7 | PR 6 branch | `cd backend && composer test -- --filter=VariantsIndexTest` | `curl /variants` returns 200 OK | Revert `resources/views/catalog/variants/{index,form}.blade.php`; delete `tests/Feature/Catalog/VariantsIndexTest.php` |
| 8 | Presentations: index+form rewrite + IndexTest | PR 8 | PR 7 branch | `cd backend && composer test -- --filter=PresentationsIndexTest` | `curl /presentations` returns 200 OK | Revert `resources/views/catalog/presentations/{index,form}.blade.php`; delete `tests/Feature/Catalog/PresentationsIndexTest.php` |
| 9 | Prices: index+form rewrite + IndexTest + tracker merge | PR 9 | PR 8 branch | `cd backend && composer test -- --filter=PricesIndexTest` | `curl /prices` returns 200 OK; tracker PR `feat/admin-dashboard-catalog-refresh` -> `master` merged last | Revert `resources/views/catalog/prices/{index,form}.blade.php`; delete `tests/Feature/Catalog/PricesIndexTest.php`; tracker merge is the final integration step |

## Phase 1: Foundation (PR 1, base = tracker)

- [x] 1.1 RED: Write `backend/tests/Feature/PageHeaderTest.php` covering title, optional description, and action slot.
- [x] 1.2 GREEN: Create `backend/resources/views/components/page-header.blade.php` with `@props(['title','description'=>null])` and named `action` slot per design §Interfaces.
- [x] 1.3 RED: Write `backend/tests/Feature/StatCardTest.php` covering label, optional `href` switching root to `<a>`, and default slot.
- [x] 1.4 GREEN: Create `backend/resources/views/components/stat-card.blade.php` with `@props(['label','href'=>null])` and dynamic root tag per design §Interfaces.
- [x] 1.5 GREEN: Extend `backend/tailwind.config.js` with `colors.catalog.{primary,accent,muted,border,card}` (oklch emerald) and override `borderRadius.lg='0.7rem'`.
- [x] 1.6 REFACTOR: Document `catalog.*` namespace and `0.7rem` radius rationale in `tailwind.config.js`.

## Phase 2: Dashboard (PR 2, base = PR 1)

- [x] 2.1 RED: Write `backend/tests/Feature/DashboardTest.php` (RefreshDatabase + actingAs): assertOk, status card, 4 op routes (sales/purchases/cash/reports), 4 catalog routes, next focus, quick links, `assertDontSee('indigo', false)`, and render with empty DB.
- [x] 2.2 GREEN: Rewrite `backend/resources/views/dashboard.blade.php` using `<x-page-header>` and `<x-stat-card>`, replace `indigo-*` with `catalog-primary`, add `text-balance`/`text-pretty` per exploration §Confirmed Visual Decisions.
- [x] 2.3 REFACTOR: Extract repeated card surface `rounded-lg bg-white p-6 ring-1 ring-border` into a local partial only if used 3+ times; otherwise keep inline.

## Phase 3: Categories (PR 3, base = PR 2)

- [x] 3.1 RED: Write `backend/tests/Feature/Catalog/CategoriesIndexTest.php` covering title, `categories.create` route, seeded record, empty state, and `assertDontSee('indigo', false)`.
- [x] 3.2 GREEN: Rewrite `backend/resources/views/catalog/categories/{index,form}.blade.php` using `<x-page-header>` (title + description + action slot to `categories.create`) and `catalog-primary`; keep validation error display in form per spec §Validation Errors.

## Phase 4: Brands (PR 4, base = PR 3)

- [x] 4.1 RED: Write `backend/tests/Feature/Catalog/BrandsIndexTest.php` covering "Marcas" title, `brands.create` route, empty state, and `assertDontSee('indigo', false)`.
- [x] 4.2 GREEN: Rewrite `backend/resources/views/catalog/brands/{index,form}.blade.php` using `<x-page-header>` and `catalog-primary`; keep validation error display.

## Phase 5: Base Units (PR 5, base = PR 4)

- [x] 5.1 RED: Write `backend/tests/Feature/Catalog/BaseUnitsIndexTest.php` covering "Unidades base" title, `base-units.create` route, empty state, and `assertDontSee('indigo', false)`.
- [x] 5.2 GREEN: Rewrite `backend/resources/views/catalog/base-units/{index,form}.blade.php` using `<x-page-header>` and `catalog-primary`; keep validation error display.

## Phase 6: Products (PR 6, base = PR 5)

- [x] 6.1 RED: Write `backend/tests/Feature/Catalog/ProductsIndexTest.php` covering "Productos" title, `products.create` route, empty state, and `assertDontSee('indigo', false)`.
- [x] 6.2 GREEN: Rewrite `backend/resources/views/catalog/products/{index,form}.blade.php` using `<x-page-header>` and `catalog-primary`; keep validation error display.

## Phase 7: Variants (PR 7, base = PR 6)

- [x] 7.1 RED: Write `backend/tests/Feature/Catalog/VariantsIndexTest.php` covering "Variantes" title, `variants.create` route, empty state, and `assertDontSee('indigo', false)`.
- [x] 7.2 GREEN: Rewrite `backend/resources/views/catalog/variants/{index,form}.blade.php` using `<x-page-header>` and `catalog-primary`; keep validation error display.

## Phase 8: Presentations (PR 8, base = PR 7)

- [x] 8.1 RED: Write `backend/tests/Feature/Catalog/PresentationsIndexTest.php` covering "Presentaciones" title, `presentations.create` route, empty state, and `assertDontSee('indigo', false)`.
- [x] 8.2 GREEN: Rewrite `backend/resources/views/catalog/presentations/{index,form}.blade.php` using `<x-page-header>` and `catalog-primary`; keep validation error display.

## Phase 9: Prices + Tracker Merge (PR 9, base = PR 8)

- [x] 9.1 RED: Write `backend/tests/Feature/Catalog/PricesIndexTest.php` covering "Precios" title, `prices.create` route, empty state, and `assertDontSee('indigo', false)`.
- [x] 9.2 GREEN: Rewrite `backend/resources/views/catalog/prices/{index,form}.blade.php` using `<x-page-header>` and `catalog-primary`; keep validation error display.
- [x] 9.3 TRACKER: After PRs 1-8 merge into `feat/admin-dashboard-catalog-refresh`, open tracker PR to `master` as draft; flip to ready and merge last to integrate the feature. Originally **BLOCKED**: requires user to push branch and create PR manually. `gh` not installed per orchestration rules. **Reconciled at archive time (sdd-archive)**: PR #13 merged by `ondtcx` at 2026-07-12T19:12:52Z (commit `4ef3825`), fast-forwarded to local `master`. The persistent state proves the work is done; the checkbox was the only stale artifact. See `archive-report` for the full closure note.
