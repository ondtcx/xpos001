# Tasks: pos-v2

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~520 net (end state vs master, per design §13) |
| 400-line budget risk | High (over by ~30%) |
| Chained PRs recommended | Yes |
| Suggested split | 3-PR feature-branch-chain (Option A) |
| Delivery strategy | force-chained (locked, engram #56) |
| Chain strategy | feature-branch-chain (locked, engram #56) |
| Tracker branch | `feat/pos-v2-tracker` |
| Final integration | tracker → master |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

## Delivery Strategy (Locked)

Force-chained, 3-PR feature-branch-chain. Each PR's diff is measured against the previous PR's branch in the chain (not against master). Old POS stays functional until PR 3 via `config/pos.php` feature flag (`enabled` defaults `false` in PR 2, flipped to `true` in PR 3). Caja abierta removal is in PR 3, not PR 2, to keep the old `PosFlowTest` passing in PR 2 (controller branches on `Accept: application/json`: AJAX → no caja + JSON, form submit → caja + redirect). Skills for sdd-apply: `sdd-apply`, `work-unit-commits`, `chained-pr`, `branch-pr`.

## PR Slicing Plan (3 PRs)

### PR 1: Foundation (data layer + new Alpine store + foundation tests)

- **Base branch**: `feat/pos-v2-tracker` (== master at PR 1 start)
- **Target branch**: `feat/pos-v2-foundation` → merge back to tracker
- **Estimated changed lines**: +280 net over master
- **Estimated commits**: 3 work-unit commits (data+model → controller+store → tests)
- **Work units**: WU-1.1 migration, WU-1.2 Customer model, WU-1.3 seeder, WU-1.4 `nearestLot`, WU-1.5 `index()`, WU-1.6 `searchCustomers()`, WU-1.7 `StorePosSaleRequest`, WU-1.8 `pos-store.js`, WU-1.9 `app.js`, WU-1.10 `PosV2CatalogTest`, WU-1.11 `PosV2CustomerTest` (partial, 4 of 6)
- **Start state**: master has 4-button + 4-panel POS, `posSidebar` store, 6 old tests passing, no `document`/`is_default` columns, no `Cliente General` row.
- **Finish state**: master has new columns + `Cliente General` + FEFO query + new `posStore` registered (but unused) + `StorePosSaleRequest` accepts `metodo`/`fiado`. Old `posSidebar` still registered, old view still renders, all 6 old tests still pass.
- **Verification**: `cd backend && composer test` green; `cd backend && php artisan migrate` succeeds; open `/pos` — old view unchanged; new `Alpine.store('posStore')` registered in browser console.
- **Rollback**: revert PR 1 merge commit. Migration `down()` drops columns + index. Seeder change is additive. Both stores remain in `app.js`; old store still works.
- **Risk**: `PosFlowTest` doesn't break because `StorePosSaleRequest` change is additive (adds `fiado` to enum; old `cash`/`transfer`/`mixed` unchanged). Caja check untouched.

### PR 2: New UI (new view behind feature flag + branched controller + view tests)

- **Base branch**: `feat/pos-v2-foundation`
- **Target branch**: `feat/pos-v2-ui-replacement` → merge back to foundation → tracker
- **Estimated changed lines**: +380 net over PR 1
- **Estimated commits**: 4 work-unit commits (config+rename → new view → controller store AJAX branch → cart+checkout view tests)
- **Work units**: WU-2.1 `config/pos.php`, WU-2.2 rename old view to `_legacy`, WU-2.3 new `pos/index.blade.php`, WU-2.4 `index()` view switch, WU-2.5 `store()` AJAX branch, WU-2.6 `PosV2CartTest`, WU-2.7 `PosV2CheckoutTest`, WU-2.8 visual smoke
- **Start state**: PR 1 merged. Old view at `pos/index.blade.php`. Old store + old tests still functional.
- **Finish state**: `config('pos.enabled') === false` → old view (renamed `_legacy`) renders; `=== true` → new 2-column view renders. Controller `store()` branches: `Accept: application/json` → metodo→payment_method+flags mapping, no caja check, returns JSON; form submit → caja check + redirect (preserves old behavior). All 6 old tests pass (form submit path unchanged).
- **Verification**: `cd backend && composer test` green; `POS_V2_ENABLED=true php artisan serve` + open `/pos` — new view loads; `POS_V2_ENABLED=false` — old view loads. Screenshot diff against `https://design-sales-interface-for-pos.vercel.app`. Manual: add to cart, switch tabs, click Cobrar with each metodo.
- **Rollback**: revert PR 2 merge commit. New view file deleted; `_legacy` renamed back. Config file harmless. Controller `store()` reverts to single path. Old tests still pass.
- **Risk**: Old `PosCustomerSearchTest` asserts on `pos/index.blade.php` file path — will break if view is renamed. Mitigation: keep the file at `pos/index.blade.php` (new view) and use `pos/_legacy.blade.php` for the old. Update controller `index()` to render `_legacy` when flag off. Old test's `file_get_contents` may then point at the new view (false positive) — verify and document; old test will be deleted in PR 3 anyway.

### PR 3: Cutover + Cleanup (delete old artifacts + flip flag + remove caja + final customer view tests)

- **Base branch**: `feat/pos-v2-ui-replacement`
- **Target branch**: `feat/pos-v2-customer-tests` → merge back to ui-replacement → foundation → tracker
- **Estimated changed lines**: -80 net over PR 2 (lots of deletes: -976 view, -174 store, -400 tests, -10 CSS; offset by ~50 additions for final tests + small edits)
- **Estimated commits**: 3 work-unit commits (test deletions → code deletions + config flip → final view tests)
- **Work units**: WU-3.1 delete `_legacy`, WU-3.2 delete `pos-sidebar-store.js`, WU-3.3 `app.js` cleanup, WU-3.4 delete `.used` CSS, WU-3.5 delete 6 old test files, WU-3.6 flip `pos.enabled` default to `true`, WU-3.7 add final 2 customer view tests, WU-3.8 full verify
- **Start state**: PR 2 merged. New view behind flag; old view behind `_legacy`. Both views work; old tests pass.
- **Finish state**: only new view, new store, new tests. Old artifacts gone. Flag default `true`. Caja check removed from controller (now always JSON, no caja).
- **Verification**: `cd backend && composer test` green; `php artisan serve` + open `/pos` — new view is default; `grep -r posSidebar backend/` returns 0 matches; `composer test` shows 27 new tests passing across 4 files.
- **Rollback**: revert PR 3 merge commit. Deleted files restored. `pos.enabled` reverts to `false`. Caja check re-added to `store()`. Old tests restored (test suite would fail until re-deleted, but codebase is back to PR 2 state).
- **Risk**: Old test deletion in same PR as view cutover — if any other test references `posSidebar` or old view markup, suite goes red. Mitigated: PR 1 verified no breakage from data changes; PR 2 verified new tests pass; PR 3's deletion is atomic with the cutover.

### Final Integration PR

- **Branch**: `feat/pos-v2-tracker` (target) → `master`
- **Base branch**: `master`
- **Contents**: no code changes. Just the tracker integration.
- **Verification**: `git diff master..tracker --stat` shows expected ~520 net lines; CI on master green; manual smoke on staging.
- **Rollback**: `git revert` the merge commit.

## Work Unit Detail

| WU | Files | Lines | Tests | Deps | Acceptance |
|----|-------|-------|-------|------|------------|
| 1.1 | `database/migrations/2026_07_09_XXXXXX_add_document_and_is_default_to_customers.php` (new) | 25 | covered by 1.11 | — | `php artisan migrate` succeeds |
| 1.2 | `app/Models/Customer.php` (modify) | 8 | covered by 1.3 | 1.1 | `$c->is_default` returns bool; `Customer::default()` returns is_default=true row |
| 1.3 | `database/seeders/MinimarketDemoSeeder.php` (modify `seedCustomers`) | 25 | 1.11 exactly-one-default | 1.1, 1.2 | 4 customers after seed; `Cliente General` has `is_default=true` |
| 1.4 | `app/Models/ProductVariant.php` (add `nearestLot()`) | 10 | 1.10 FEFO tests | — | 3-lot scenario → id 11 wins; null expiration skipped |
| 1.5 | `app/Http/Controllers/PosController.php` (modify `index`) | 30 | 1.10 card render + out-of-stock; 1.11 options | 1.4 | view data has `clientes[].document`, `saldo_fiado`, `defaultClienteId` |
| 1.6 | `app/Http/Controllers/PosController.php` (modify `searchCustomers`) | 15 | 1.11 inline search | 1.1 | `?q=` returns all; `?q=mar` filters with document + saldo_fiado |
| 1.7 | `app/Http/Requests/Sales/StorePosSaleRequest.php` (modify rules + prepareForValidation) | 10 | indirect (1.5/2.7 controller) | — | `payment_method=fiado` passes; `metodo` read into validated |
| 1.8 | `resources/js/pos-store.js` (new) | 120 | indirect via 2.7 cobrar | — | `Alpine.store('posStore')` registers; all actions callable |
| 1.9 | `resources/js/app.js` (modify — add 2nd register) | 5 | app.js test in 1.10 | 1.8 | both stores register; no console errors |
| 1.10 | `tests/Feature/PosV2CatalogTest.php` (new) | 100 | 5 tests | 1.4, 1.5, 1.9 | all 5 green |
| 1.11 | `tests/Feature/PosV2CustomerTest.php` (new, partial) | 80 | 4 of 6 tests | 1.3, 1.6 | 4 green; 2 view tests pending for 3.7 |
| 2.1 | `config/pos.php` (new) | 5 | — | — | `config('pos.enabled')` returns bool |
| 2.2 | `resources/views/pos/index.blade.php` → `_legacy.blade.php` (rename) | 0 | verify no test breaks | 2.1 | file renamed; old view still rendered when flag off |
| 2.3 | `resources/views/pos/index.blade.php` (new) | 250 | 2.6, 2.7 view tests | 1.5, 1.8, 1.9, 2.1 | 2-column layout; all Alpine directives reference `$store.posStore.*` |
| 2.4 | `PosController::index()` (modify — view switch) | 5 | flag-toggle test in 2.7 | 2.1, 2.2, 2.3 | flag off → `_legacy`; flag on → `index` |
| 2.5 | `PosController::store()` (modify — AJAX branch) | 30 | 2.7 cobrar success + validation | 1.7 | AJAX → no caja + JSON; form submit → caja + redirect |
| 2.6 | `tests/Feature/PosV2CartTest.php` (new) | 100 | 7 tests | 2.3 | all 7 green |
| 2.7 | `tests/Feature/PosV2CheckoutTest.php` (new) | 140 | 9 tests | 2.3, 2.5 | all 9 green |
| 2.8 | (manual visual verify) | 0 | — | 2.3 | layout matches design URL |
| 3.1 | delete `resources/views/pos/_legacy.blade.php` | -976 | — | — | file gone |
| 3.2 | delete `resources/js/pos-sidebar-store.js` | -174 | — | — | file gone |
| 3.3 | `resources/js/app.js` (modify — remove old import) | -3 | verify no test breaks | — | only `posStore` registered |
| 3.4 | `resources/css/app.css` (delete `.used` rules) | -10 | — | — | grep `.used` returns 0 |
| 3.5 | delete 6 test files | -400 | — | — | 0 old tests in suite |
| 3.6 | `config/pos.php` (flip default to `true`) | 1 | — | — | `config('pos.enabled') === true` by default |
| 3.7 | `PosV2CustomerTest.php` (add 2 view tests) | 50 | keyboard nav + dropdown default | 2.3, 3.5 | all 6 customer tests green |
| 3.8 | (full verify) | 0 | — | — | `composer test` green; grep posSidebar returns 0 |

## Proposed Slice Plan

**Option A: 3 PRs (CHOSEN)** — PR 1 foundation additive; PR 2 new view behind feature flag with branched controller; PR 3 cutover + cleanup with most deletes. Each PR <400 net, each shippable, granular rollback.

**Option B: 2 PRs (NOT CHOSEN)** — PR 1 = foundation + all new tests (~330 net); PR 2 = view replacement + 6 test deletions + cleanup (~+100 net but ~700 absolute change due to 600+ deletes). Cons: PR 2's review surface is huge; loses feature-flag safety net; caja check removal forced into PR 2 couples to test deletions; no way to ship new view in shadow mode for QA before cutover.

**Rationale for A**: 3 smaller PRs match the per-PR <400 net budget cleanly. The feature flag is the key safety mechanism — PR 2 ships the new view without breaking the old, so QA can compare both. Caja removal lives with the test deletions in PR 3 (the only PR that breaks the old tests), keeping the cause-effect relationship tight.

## Critical Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| `PosSaleDraftBuilder` requires `confirm_credit_sale=true` for credit path; new view sends `metodo=fiado`, not the checkbox | CRITICAL | Controller change in WU-2.5 maps `metodo=fiado` → `payment_method=cash` + `allow_credit_sale=true` + `confirm_credit_sale=true` BEFORE calling the builder. NOT a service change (per design §11). |
| 6 old tests (`PosSidebar*`, `PosFlowTest`, `PosCustomerSearchTest`) reference old `$store.posSidebar` markup + old form-submit flow | CRITICAL | Delete in WU-3.5 (same PR as view cutover + flag flip). PR 1 keeps old flow working (additive). PR 2 keeps old form-submit path in controller (caja check + redirect preserved when `Accept` is not JSON). |
| Caja abierta removal timing | CRITICAL | Moved to PR 3 (with test deletions). PR 2 controller branches on request type. Old `PosFlowTest::it_still_requires_open_cash_session_for_transfer_sales_from_pos` passes in PR 2 because form-submit path still has caja check. PR 3 deletes the test AND removes the branch. |
| `StorePosSaleRequest` adding `fiado` could break old `PosFlowTest` | WARNING | WU-1.7 only ADDS to enum. Verified safe — old tests use `cash`/`transfer`/`mixed` which remain valid. |
| Old `PosSidebarStoreTest::app_js_registers_possidebar_store` checks `registerPosSidebarStore` is in `app.js` | WARNING | PR 1 keeps the import (adds second register). PR 3 removes the import. Test deleted in PR 3 with the others. |
| `pos/index.blade.php` rename to `_legacy` may break tests with `file_get_contents` on that path | WARNING | `PosSidebarLayoutTest` reads the file via `route('pos.index')` (route → controller), not direct path. `PosCustomerSearchTest` uses `__DIR__.'/../../resources/views/pos/index.blade.php'` — broken by rename. Mitigated: delete in PR 3. |

## Forecast per PR

| PR | Net | Adds | Deletes | Notes |
|----|-----|------|---------|-------|
| PR 1 (foundation) | +280 | ~680 | 0 | Additive only; old POS still works |
| PR 2 (new UI) | +380 | ~770 | ~390 | New view + tests; old view renamed, test deltas for view path may affect some old tests in WU-2.2 (verified safe) |
| PR 3 (cutover) | -80 | ~55 | ~1,560 | Lots of deletes (-976 view, -174 store, -400 tests, -10 CSS); offset by +55 (final tests + flag flip) |
| **End state** | **+520** | **+1,505** | **-1,950** | Matches design forecast |

## Branch & PR Plan

```
master
└── feat/pos-v2-tracker                  (final integration target)
      ├── feat/pos-v2-foundation         (PR #1, base = tracker)
      │     └── feat/pos-v2-ui-replacement    (PR #2, base = foundation)
      │           └── feat/pos-v2-customer-tests (PR #3, base = ui-replacement)
      └── (final: tracker → master)
```

PR sequence:
1. `feat/pos-v2-tracker` from `master` (empty branch, just chains).
2. `feat/pos-v2-foundation` from `tracker` → PR #1 → merge back to `tracker` (fast-forward).
3. `feat/pos-v2-ui-replacement` from `foundation` → PR #2 → merge back to `foundation` → fast-forward `tracker`.
4. `feat/pos-v2-customer-tests` from `ui-replacement` → PR #3 → merge back to `ui-replacement` → fast-forward `foundation` → fast-forward `tracker`.
5. Final: `tracker` → `master` (no code, just integration).

## Skills to Load for sdd-apply

sdd-apply MUST load these skills (in order):
1. **`sdd-apply`** — primary, for implementation
2. **`work-unit-commits`** — MANDATORY for chained PRs (split each PR into reviewable work-unit commits)
3. **`chained-pr`** — MANDATORY for chained PRs (verify PR base branches, manage tracker chain, final integration)
4. **`branch-pr`** — for creating GitHub PRs

`chained-pr` will manage: PR base verification (each PR's base is the previous branch, never `master`), tracker fast-forward after each merge, final integration. `work-unit-commits` will manage: atomic commit splitting per WU, ordering so each commit is independently reviewable, keeping test files with the code they test.

## Phase 1: Foundation (PR 1)

- [ ] 1.1 Create migration `add_document_and_is_default_to_customers` (document string nullable 50; is_default boolean default false; index on is_default)
- [ ] 1.2 Update `Customer` model: add `document`, `is_default` to fillable; cast `is_default` to bool; add `scopeDefault()`
- [ ] 1.3 Update `MinimarketDemoSeeder::seedCustomers()`: add `document` to 3 customers; create 4th `Cliente General` with `is_default=true`, `document='—'`
- [ ] 1.4 Add `ProductVariant::nearestLot()` HasOne (FEFO: nearest non-null expiration, tie-break id ASC)
- [ ] 1.5 Update `PosController::index()`: eager-load `nearestLot`; build `clientes` array with `document` + computed `saldo_fiado`; pass `defaultClienteId`; keep old view data
- [ ] 1.6 Update `PosController::searchCustomers()`: include `document` + `saldo_fiado`; return all active customers when `q` empty
- [ ] 1.7 Update `StorePosSaleRequest`: add `fiado` to valid `payment_method` enum; add `metodo` to prepareForValidation merge
- [ ] 1.8 Create `resources/js/pos-store.js`: full Alpine `posStore` (state, getters, actions, formatMoney, AJAX cobrar)
- [ ] 1.9 Update `resources/js/app.js`: import AND register both `posSidebar` and `posStore` (old view still works)
- [ ] 1.10 Create `tests/Feature/PosV2CatalogTest.php` — 5 tests (card render, FEFO nearest, FEFO null skip, search, out-of-stock)
- [ ] 1.11 Create `tests/Feature/PosV2CustomerTest.php` — 4 of 6 tests (default exists, options with debt, search filters, missing-default guard)

## Phase 2: New UI (PR 2)

- [ ] 2.1 Create `config/pos.php` with `enabled` boolean (env `POS_V2_ENABLED`, default `false`)
- [ ] 2.2 Rename `resources/views/pos/index.blade.php` → `resources/views/pos/_legacy.blade.php`
- [ ] 2.3 Create new `resources/views/pos/index.blade.php`: 2-column layout, catalog + cart | checkout panel, all Alpine directives, Heroicons
- [ ] 2.4 Update `PosController::index()`: switch view based on `config('pos.enabled')` (off → `_legacy`, on → `index`)
- [ ] 2.5 Update `PosController::store()`: branch on `$request->expectsJson()`. AJAX → no caja check, map `metodo=fiado` to `payment_method=cash`+`allow_credit_sale=true`+`confirm_credit_sale=true`, return JSON. Form submit → caja check + redirect (unchanged from current behavior)
- [ ] 2.6 Create `tests/Feature/PosV2CartTest.php` — 7 tests (line render, qty decrement floors, trash removes, header sum, anular full reset, vaciar preserves state, currency format)
- [ ] 2.7 Create `tests/Feature/PosV2CheckoutTest.php` — 9 tests (3 tabs, efectivo, transfer hides, fiado disabled/enabled, totals, cobrar disabled, cobrar success AJAX, validation 422)
- [ ] 2.8 Visual smoke test: `POS_V2_ENABLED=true php artisan serve` + open `/pos` — compare against `https://design-sales-interface-for-pos.vercel.app`

## Phase 3: Cutover + Cleanup (PR 3)

- [ ] 3.1 Delete `resources/views/pos/_legacy.blade.php` (old view)
- [ ] 3.2 Delete `resources/js/pos-sidebar-store.js`
- [ ] 3.3 Update `resources/js/app.js`: remove `posSidebar` import + `registerPosSidebarStore` call
- [ ] 3.4 Delete `.used` and `.used::after` rules from `resources/css/app.css` (per V-001 archive)
- [ ] 3.5 Delete 6 old test files: `PosSidebarStoreTest.php`, `PosSidebarLayoutTest.php`, `PosSidebarReactivationTest.php`, `PosSidebarReceivedCreditBindingsTest.php`, `PosCustomerSearchTest.php`, `PosFlowTest.php`
- [ ] 3.6 Update `PosController::store()`: remove the form-submit branch (caja check + redirect), keep only the AJAX path (no caja + JSON)
- [ ] 3.7 Flip `config/pos.php` `enabled` default to `true`
- [ ] 3.8 Add remaining 2 view-related tests to `PosV2CustomerTest.php` (keyboard nav + dropdown default selection) — now safe since the new view is the only view
- [ ] 3.9 Verify: `cd backend && composer test` green; `grep -r posSidebar backend/` returns 0 matches; new view is default at `/pos`

## Phase 4: Final Integration

- [ ] 4.1 Merge `feat/pos-v2-tracker` → `master` (no code changes; just tracker integration)
- [ ] 4.2 Verify on staging: open `/pos` — new view loads; complete cash + transfer + fiado sales; verify receivable created for fiado
- [ ] 4.3 Archive `pos-v2` change per `sdd-archive` phase
