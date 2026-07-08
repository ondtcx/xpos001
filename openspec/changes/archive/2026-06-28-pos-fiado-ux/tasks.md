# Tasks: POS Fiado UX Improvements

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~450-550 total (3 PRs) |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 → PR 2 → PR 3 |
| Delivery strategy | ask-always |
| Chain strategy | stacked-to-main |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: stacked-to-main
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Alpine state + button consistency + panel persistence + fiado config | PR 1 | Base branch: main; ~150-200 lines |
| 2 | Client typeahead + search endpoint | PR 2 | Base branch: main (stacked); ~150-200 lines |
| 3 | Accordion layout + pin capability | PR 3 | Base branch: main (stacked); ~100-150 lines |

---

## PR 1: Alpine State + Button Consistency + Panel Persistence + Fiado Config

### Phase 1.1: Foundation (Infrastructure)

- [x] 1.1.1 Create migration `backend/database/migrations/2026_06_27_000000_create_user_settings_table.php` with columns: `id`, `user_id` (FK), `key` (varchar 100), `value` (text), timestamps, unique index `(user_id, key)`
- [x] 1.1.2 Create `backend/app/Models/UserSetting.php` with `user_id`, `key`, `value` fillable; relationship to User; static `get($userId, $key, $default)` and `set($userId, $key, $value)` methods
- [x] 1.1.3 Run migration: `php artisan migrate`

### Phase 1.2: Tests (RED — write failing tests first)

- [x] 1.2.1 Create `backend/tests/Feature/UserSettingTest.php`: test `UserSetting::get()` returns default when not set; test `UserSetting::set()` persists and retrieves value; test unique constraint on `(user_id, key)`
- [x] 1.2.2 Modify `backend/tests/Feature/PosFlowTest.php`: add assertions for Alpine `x-data` attributes on `<aside>`; verify button class bindings reflect state

### Phase 1.3: Core Implementation (GREEN — make tests pass)

- [x] 1.3.1 Wrap `<aside>` in `backend/resources/views/pos/index.blade.php` with Alpine `x-data` containing: `activePanel`, `creditActive`, `receivedAmount`, `selectedCustomerId`, `selectedCustomerName`, `paymentMethod`, `fiadoAutoEnabled`
- [x] 1.3.2 Replace vanilla JS toggle logic with Alpine `@click="togglePanel(name)"` and `x-bind:class` for button active/inactive state
- [x] 1.3.3 Add `syncToHiddenInputs()` method in Alpine component to sync state to hidden `<input>` elements on change
- [x] 1.3.4 Inject `fiadoAutoEnabled` from `UserSetting::get(auth()->id(), 'fiado_auto_enabled', true)` via Blade data attribute
- [x] 1.3.5 Update fiado activation logic to check `fiadoAutoEnabled` before auto-activating

### Phase 1.4: Verification

- [x] 1.4.1 Run `cd backend && composer test` — all tests pass (94 tests, 594 assertions, 3 pre-existing failures unrelated to PR)
- [ ] 1.4.2 Manual QA: toggle buttons show consistent active/inactive state; panel data persists across close/open; fiado respects setting

---

## PR 2: Client Typeahead + Search Endpoint

### Phase 2.1: Tests (RED)

- [x] 2.1.1 Create `backend/tests/Feature/PosCustomerSearchTest.php`: test `GET /pos/customers/search?q=Joh` returns matching customers by name; test search by `phone` (adapted — no `document_number` column in schema); test empty query returns empty results; test non-authenticated request returns 401/403; test limits to 10 results

### Phase 2.2: Core Implementation (GREEN)

- [x] 2.2.1 Add `searchCustomers(Request $request)` method to `backend/app/Http/Controllers/PosController.php`: query `Customer` by `name` or `phone` with `LIKE '%term%'`, limit 10, return JSON `{ results: [...] }`
- [x] 2.2.2 Add route `GET /pos/customers/search` to `backend/routes/web.php` pointing to `PosController@searchCustomers`
- [x] 2.2.3 Replace `<select>` in `backend/resources/views/pos/index.blade.php` with Alpine typeahead component: input with `x-model="customerQuery"`, `@input.debounce.300ms="searchCustomers()"`, dropdown with `x-show="customerResults.length > 0"`, loading indicator
- [x] 2.2.4 Add `searchCustomers()` Alpine method: `fetch('/pos/customers/search?q=' + query)`, populate `customerResults`, handle loading state
- [x] 2.2.5 Add keyboard navigation: `@keydown.arrow-down` / `@keydown.arrow-up` to highlight, `@keydown.enter` to select, `@keydown.escape` to close; `customerHighlightIndex` state
- [x] 2.2.6 Add clear button: `@click="selectedCustomerId = null; customerQuery = ''"`

### Phase 2.3: Verification

- [x] 2.3.1 Run `cd backend && composer test` — all tests pass (97 passed, 3 pre-existing failures unrelated to PR 2)
- [ ] 2.3.2 Manual QA: typeahead searches by name/document; selection updates state; keyboard navigation works; clear button resets

---

## PR 3: Accordion Layout + Pin Capability

### Phase 3.1: Tests (RED)

- [x] 3.1.1 Modify `backend/tests/Feature/PosFlowTest.php`: add assertions for accordion state, pinnedPanels, togglePin method, x-show with pinnedPanels.includes, and pin toggle icons

### Phase 3.2: Core Implementation (GREEN)

- [x] 3.2.1 Add `pinnedPanels: []` array to Alpine `x-data` state
- [x] 3.2.2 Update `togglePanel(name)` method: if accordion mode (not pinned), close other panels before opening; if panel is pinned, allow multiple open
- [x] 3.2.3 Add pin toggle UI: pin icon on each panel header with `@click="togglePin(name)"`, visual indicator when pinned
- [x] 3.2.4 Add `togglePin(name)` Alpine method: add/remove from `pinnedPanels` array
- [x] 3.2.5 Add `x-bind:class` for pin icon active state

### Phase 3.3: Verification

- [x] 3.3.1 Run `cd backend && php artisan test` — all PR 3 tests pass (99 passed, 3 pre-existing failures)
- [ ] 3.3.2 Manual QA: accordion closes other panels; pinned panels stay open; pin toggle works

---

## Implementation Order

1. **PR 1** first: establishes Alpine.js foundation, button state, panel persistence, and fiado config — no dependencies on other PRs
2. **PR 2** second: typeahead builds on Alpine state from PR 1; adds search endpoint (backend) and typeahead UI (frontend)
3. **PR 3** third: accordion + pin extends Alpine state from PR 1; independent of PR 2 but logically last (layout refinement)

Each PR is independently deployable and revertable.
