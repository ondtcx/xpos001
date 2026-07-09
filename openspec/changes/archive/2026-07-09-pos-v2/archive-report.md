# pos-v2 — Archive Report

## Summary

The `pos-v2` change replaced xpos001's 4-contextual-buttons + 4-collapsible-panels POS sidebar with a single-page 2-column layout (catalog + cart | checkout panel), ported wholesale from a Next.js/shadcn design to the existing Laravel 12 + Blade + Alpine 3.x stack. The new Alpine store `posStore` owns the full sale state (items, customer, payment method, cash received, toast), and the checkout flow is AJAX-based with a 3.5s green/red toast. `fiado` was promoted from a checkbox-modifier on `cash` to a first-class payment method mapped at the controller boundary to the existing credit-sale path.

Delivered as a 3-PR feature-branch chain (foundation → UI replacement → cutover + cleanup), the change shipped 1,505 net additions and 1,950 deletions (net +520) for 14 product decisions, 4 new specs, 5 superseded specs, 1 migration, 1 new Alpine store, 1 view rewrite, 1 controller overhaul, and 31 new feature tests (4 test files). The PR chain (PRs #9-#12) merged into master at commit `4b2f363`. The 3 pre-existing test failures (Auth\Registration x2, Example x1) are out of scope and were unchanged before/after the change.

## Locked decisions honored (14)

1. Currency `USD X.XX` with 2 decimals (en-US format with `,` thousands and `.` decimal) — honored. Live at `pos-store.js:228-236` `formatMoney`. Spec text updated from es-AR to en-US during this archive (Fix W1).
2. Client dropdown with inline search (button-triggered panel) — honored. `index.blade.php:175-222`.
3. 3 payment tabs (Efectivo, Transfer., Fiado, NO Tarjeta) — honored. `index.blade.php:225-242`. `PosV2CheckoutTest::new_view_renders_three_tabs_no_tarjeta`.
4. Anular venta button in cart header — honored. `index.blade.php:131`.
5. `Customer.document` column added via migration `2026_07_09_120000_add_document_and_is_default_to_customers.php` — honored.
6. Fiado → `allow_credit_sale=true` + `confirm_credit_sale=true` via controller `StorePosSaleRequest::prepareForValidation` — honored.
7. `Cliente General` synthetic row with `is_default=true`, `document='—'` — honored. Seeder lines 558-566.
8. FEFO lote display (nearest non-null expiration, id ASC tie-break) — honored. `ProductVariant::nearestLot()` lines 50-57. Tested in `PosV2CatalogTest::fefo_*`.
9. Anular venta = FULL RESET (items, cliente→General, metodo→efectivo, recibido→'') — honored. `pos-store.js:109-122`.
10. Caja abierta DROPPED (no `currentCashSession` guard in `PosController::store`) — honored. Passes `null` to `CreateSaleService`.
11. AJAX submission (`fetch` POST JSON) — honored. `pos-store.js:185-194` and `PosController::store` returns `JsonResponse`.
12. `.used` indicator removed from CSS and store — honored. `app.css` is 3 lines; no `markUsed`/`usedPanels` in `pos-store.js`.
13. USD format with 2 decimals (en-US) — honored. `pos-store.js:235` produces `USD 1,234.50`. Matches live design.
14. Demo customer names match seeder originals ("María Gómez", "José Paredes", "Lucía Torres") — honored.

## Pre-archive fixes applied

- **W1: spec text updated from es-AR to en-US in 4 specs.** `pos-v2-catalog/spec.md:13`, `pos-v2-cart/spec.md:13` + `:83-89`, `pos-v2-checkout/spec.md:56` changed from "es-AR style" / "`.` thousands, `,` decimals" / `USD X.XXX,XX` to "en-US style" / "`,` thousands, `.` decimals" / `USD X,XXX.XX`. The cart-spec scenario at lines 88-89 was updated to expect `"USD 1,234.50"` (was `"USD 1.234,50"`). This removes the textual contradiction called out in verify-report.md WARNING-1.
- **W3: deleted the misnamed test method** `search_endpoint_filters_by_name` in `backend/tests/Feature/PosV2CatalogTest.php:170-185`. The test was calling `pos.customers.search` and asserting `{results}` shape — coverage already exists in `PosV2CustomerTest` (4 test methods hit the same endpoint). **Deletion chosen** (cleanest option per the verify report recommendation).
- **S1: deleted `_legacy.blade.php`.** `backend/resources/views/pos/_legacy.blade.php` (976 lines) removed entirely. The file referenced the unregistered `$store.posSidebar` Alpine store and was a latent bug. The feature flag `config('pos.enabled')` defaults to `true`, so the legacy view was never rendered in practice. No code references to `_legacy.blade.php` existed (only documentation references in the change's own archive and design docs).

## Spec changes

### 4 NEW specs added to baseline

- `openspec/specs/pos-v2-catalog/spec.md` — product card display, FEFO lote selection, search/filter, add-to-cart, out-of-stock disabling (8 scenarios)
- `openspec/specs/pos-v2-cart/spec.md` — line rendering, quantity controls, remove, header + empty state, Anular venta full reset, Vaciar preserves checkout, currency formatting (7 scenarios)
- `openspec/specs/pos-v2-checkout/spec.md` — 3 payment tabs, Efectivo input + chips + Vuelto, Transfer. hides cash UI, Fiado disabled for Cliente General, totals, Cobrar disabled states, success/error flows (9 scenarios)
- `openspec/specs/pos-v2-customer/spec.md` — Cliente General default selection, dropdown options with debt, inline search, selection updates checkout, keyboard navigation, render refuses without default (6 scenarios)

### 5 SUPERSEDED specs removed from baseline

- `pos-sidebar-state` — 4 contextual buttons + 4 collapsible panels no longer exist
- `pos-contextual-buttons-state` — contextual buttons replaced by always-visible 2-col layout
- `pos-panel-reactivation` — `usedPanels`/`markUsed` removed (`.used` class deleted)
- `pos-client-typeahead` — replaced by inline-search dropdown
- `pos-sidebar-vertical-layout` — replaced by single-page 2-col layout

### 6 superseded spec copies removed from change folder

- `openspec/changes/pos-v2/specs/_superseded/pos-ux-refinements-originals/` (5 folders + 6 `.md` files) — historical record, the originals are gone from baseline

## Files changed in archive

### Spec moves (4 new specs, baseline promotion)

- `openspec/changes/pos-v2/specs/pos-v2-catalog/spec.md` → `openspec/specs/pos-v2-catalog/spec.md`
- `openspec/changes/pos-v2/specs/pos-v2-cart/spec.md` → `openspec/specs/pos-v2-cart/spec.md`
- `openspec/changes/pos-v2/specs/pos-v2-checkout/spec.md` → `openspec/specs/pos-v2-checkout/spec.md`
- `openspec/changes/pos-v2/specs/pos-v2-customer/spec.md` → `openspec/specs/pos-v2-customer/spec.md`

### Spec deletions (5 superseded baseline + 6 historical copies)

- `openspec/specs/pos-sidebar-state/spec.md` — deleted
- `openspec/specs/pos-contextual-buttons-state/spec.md` — deleted
- `openspec/specs/pos-panel-reactivation/spec.md` — deleted
- `openspec/specs/pos-client-typeahead/spec.md` — deleted
- `openspec/specs/pos-sidebar-vertical-layout/spec.md` — deleted
- `openspec/changes/pos-v2/specs/_superseded/` — deleted (6 historical `.md` files + 5 sub-folders)

### Change folder move (5 files)

- `openspec/changes/pos-v2/explore.md` → `openspec/changes/archive/2026-07-09-pos-v2/explore.md`
- `openspec/changes/pos-v2/proposal.md` → `openspec/changes/archive/2026-07-09-pos-v2/proposal.md`
- `openspec/changes/pos-v2/design.md` → `openspec/changes/archive/2026-07-09-pos-v2/design.md`
- `openspec/changes/pos-v2/tasks.md` → `openspec/changes/archive/2026-07-09-pos-v2/tasks.md`
- `openspec/changes/pos-v2/verify-report.md` → `openspec/changes/archive/2026-07-09-pos-v2/verify-report.md`
- `openspec/changes/pos-v2/archive-report.md` (this file) — created in archive
- `openspec/changes/pos-v2/` — empty directory removed

### W1 spec text edits (4 specs)

- `openspec/specs/pos-v2-catalog/spec.md:13` — "es-AR style" → "en-US style"; "` thousands, `,` decimals" → "`,` thousands, `.` decimals"
- `openspec/specs/pos-v2-cart/spec.md:13` — "es-AR style" → "en-US style"
- `openspec/specs/pos-v2-cart/spec.md:83-89` — "`USD X.XXX,XX`" → "`USD X,XXX.XX`"; "`USD 1.234,50`" → "`USD 1,234.50`"
- `openspec/specs/pos-v2-checkout/spec.md:56` — "`USD X.XXX,XX` es-AR format" → "`USD X,XXX.XX` en-US format"

### W3 test deletion

- `backend/tests/Feature/PosV2CatalogTest.php:170-185` — `search_endpoint_filters_by_name` test method removed (16 lines including the `#[Test]` attribute and trailing blank line)

### S1 file deletion

- `backend/resources/views/pos/_legacy.blade.php` — deleted (976 lines)

## Final test count

- **passing: 108** (was 109 pre-W3; W3 deletion dropped the redundant `search_endpoint_filters_by_name` test from 31 → 30 PosV2 tests)
- **failing: 3** (pre-existing, out of scope)
  - `Tests\Feature\Auth\RegistrationTest::registration_screen_can_be_rendered` — 404 (Breeze registration disabled)
  - `Tests\Feature\Auth\RegistrationTest::new_users_can_register` — depends on the 404 above
  - `Tests\Feature\ExampleTest::the_application_returns_a_successful_response` — root `/` returns 302 redirect to login (expected)
- **new tests added by pos-v2: 30** (in 4 `PosV2*Test` files; was 31 in verify report, minus 1 W3 deletion)

## PR chain (recap)

- **PR #9** `feat/pos-v2-foundation` → `feat/pos-v2-tracker` (merged) — migration + model + seeder + FEFO + `posStore` Alpine store + `StorePosSaleRequest` updates + 5 catalog tests + 4 customer tests
- **PR #10** `feat/pos-v2-ui-replacement` → `feat/pos-v2-foundation` (merged) — `config/pos.php` + new view + `pos/_legacy.blade.php` rename + `PosController` AJAX branch + 7 cart tests + 10 checkout tests
- **PR #11** `feat/pos-v2-customer-tests` → `feat/pos-v2-ui-replacement` (merged) — final 3 customer view tests (keyboard nav, dropdown default)
- **PR #12** `feat/pos-v2-tracker` → `master` (merged) — final integration, conflict markers resolved at `4b2f363`

## Outstanding follow-ups (not blocking)

- **W2: catalog spec coverage gaps** (verify-report WARNING-2). 31 % strict compliance for `pos-v2-catalog`; missing assertions for: card render of full data (name/category/USD/stock), search filter narrows list, "No se encontraron productos para xyz" empty state, add-to-cart action (functional), Agotado chip + disabled button. Mostly visual/behavioral assertions that need a Dusk/Playwright layer.
- **S2: no browser tests (Dusk/Playwright) for runtime behavior** (verify-report SUGGESTION-1 + SUGGESTION-2). The 30 PosV2 tests are predominantly stringContains/regex assertions on view source and JS source. Functional behavior (add-to-cart updates header, arrow keys move highlight, Enter selects, toast 3.5s auto-dismiss) is not pinned at the runtime level. Recommended: add a small Playwright/Dusk suite targeting the catalog → cart → checkout flow.
- **3 pre-existing test failures** (Auth\Registration x2, Example x1) — team debt, not blocking pos-v2 archive. Disabling Breeze registration or updating the example test to expect 302 would clear them.

## SDD Cycle Complete

The change has been fully planned, proposed, designed, implemented, verified, and archived. The 4 new specs are now the source of truth for the pos-v2 behavior. The 5 superseded specs are gone from baseline (historical copies deleted from the archive). Source of truth updated: `openspec/specs/pos-v2-{catalog,cart,checkout,customer}/spec.md`. Ready for the next change.
