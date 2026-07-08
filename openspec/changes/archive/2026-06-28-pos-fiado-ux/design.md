# Design: POS Fiado UX Improvements

## Technical Approach

Wrap the POS `<aside>` in a single Alpine.js `x-data` component that becomes the source of truth for sidebar state (panel visibility, button active classes, selected values). Panel data lives in Alpine state and syncs to hidden `<input>` elements on change, preserving form submission compatibility. The customer `<select>` is replaced by a typeahead that fetches from a new `PosController@searchCustomers` endpoint (300ms debounce). Fiado auto-activation reads a per-user setting stored in a new `user_settings` key-value table. Accordion logic uses `activePanel` + `pinnedPanels[]` in Alpine state.

## Architecture Decisions

| Decision | Options | Tradeoff | Choice | Rationale |
|----------|---------|----------|--------|-----------|
| Sidebar state management | (A) Vanilla JS refactor (B) Alpine.js `x-data` wrapper | A: no new deps but repeats Breeze pattern; B: consistent with stack, reactive | **B: Alpine.js** | Alpine already bundled via Vite; Breeze uses it everywhere; reactive bindings eliminate manual class toggling |
| Panel data persistence | (A) Store in DOM, toggle visibility only (B) Store in Alpine state, sync to hidden inputs | A: simpler but data lost on panel close (current bug); B: data survives toggle | **B: Alpine state** | Spec requires data persistence across open/close cycles |
| Fiado setting storage | (A) `user_settings` JSON column on `users` (B) Separate `user_settings` table (C) Config file | A: simple but requires full user load; B: clean key-value, scalable; C: not per-user | **B: `user_settings` table** | Per-user, per-setting key-value. Follows Laravel convention. Extensible for future settings. |
| Customer search endpoint | (A) New `PosController@searchCustomers` (B) Reuse `SaleController@search` with type param | A: isolated, POS-specific; B: shared but couples concerns | **A: New method in PosController** | Different search semantics (name/document vs product), different response shape. Keeps POS self-contained. |
| Typeahead implementation | (A) Alpine component inline (B) Extract to `resources/js/components/` | A: co-located with view; B: reusable but over-engineered for single use | **A: Inline Alpine** | Single use case; project has no JS component directory yet. Extract if reused. |

## Data Flow

### Sidebar State (Alpine.js)

```
    User click ──→ @click="togglePanel('credit')"
         │
         ▼
    Alpine x-data (sidebar)
    ├── activePanel: 'credit' | null
    ├── pinnedPanels: ['received']
    ├── creditActive: bool
    ├── receivedAmount: string
    ├── selectedCustomerId: number | null
    ├── selectedCustomerName: string
    ├── paymentMethod: 'cash' | 'transfer' | 'mixed'
    └── fiadoAutoEnabled: bool (from server)
         │
         ├── x-bind:class ──→ button visual state (reactive)
         ├── x-show ──→ panel visibility (accordion-aware)
         └── @change ──→ sync to hidden <input> (form submission)
```

### Typeahead Search Sequence

```
    User types "Joh"
         │
         ▼ (300ms debounce)
    fetch('/pos/customers/search?q=Joh')
         │
         ▼
    PosController@searchCustomers
    └── Customer::where('name', 'LIKE', '%joh%')
        ->orWhere('document_number', 'LIKE', '%joh%')
        ->limit(10)->get()
         │
         ▼
    JSON: [{ id: 1, name: "John Doe", document: "..." }, ...]
         │
         ▼
    Alpine dropdown renders results
         │
    User clicks result ──→ selectedCustomerId = result.id
                        ──→ sync hidden input name="customer_id"
```

### Fiado Auto-Activation

```
    POS loads ──→ fetch fiadoAutoEnabled from user_settings
                  (injected via Blade @php or data attribute)
         │
    User enters received < total
         │
         ▼
    if fiadoAutoEnabled:
        activateCreditSale() (existing logic)
    else:
        show validation error
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `backend/database/migrations/xxxx_create_user_settings_table.php` | Create | Migration for `user_settings` table (user_id, key, value) |
| `backend/app/Models/UserSetting.php` | Create | Eloquent model for user settings key-value store |
| `backend/app/Http/Controllers/PosController.php` | Modify | Add `searchCustomers()` method for typeahead endpoint |
| `backend/routes/web.php` | Modify | Add `GET pos/customers/search` route |
| `backend/resources/views/pos/index.blade.php` | Modify | Wrap `<aside>` in `x-data`, replace toggle logic with Alpine bindings, replace `<select>` with typeahead, inject fiado setting |
| `backend/resources/views/pos/partials/sidebar-summary.blade.php` | Create | Extract summary section from inline aside |
| `backend/resources/views/pos/partials/sidebar-panels.blade.php` | Create | Extract panel sections (customer, payment, received, credit) |
| `backend/tests/Feature/PosCustomerSearchTest.php` | Create | Tests for customer search endpoint |
| `backend/tests/Feature/PosFlowTest.php` | Modify | Add assertions for Alpine attributes, update for typeahead |
| `backend/tests/Feature/UserSettingTest.php` | Create | Tests for fiado auto-activation setting toggle |

## Interfaces / Contracts

### User Settings Table

```sql
CREATE TABLE user_settings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    key VARCHAR(100) NOT NULL,
    value TEXT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Customer Search Endpoint

```
GET /pos/customers/search?q={term}
Accept: application/json
X-Requested-With: XMLHttpRequest

Response 200:
{
    "results": [
        { "id": 1, "name": "John Doe", "document_number": "12345678" }
    ]
}
```

### Alpine.js Sidebar State Shape

```js
{
    activePanel: null,           // 'customer' | 'payment' | 'received' | 'credit' | null
    pinnedPanels: [],            // ['received', ...]
    creditActive: false,
    receivedAmount: '',
    selectedCustomerId: null,
    selectedCustomerName: '',
    paymentMethod: 'cash',
    fiadoAutoEnabled: true,
    // Typeahead
    customerQuery: '',
    customerResults: [],
    customerLoading: false,
    customerHighlightIndex: -1,

    togglePanel(name) { ... },
    syncToHiddenInputs() { ... },
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `UserSetting` model get/set, default values | PHPUnit with `RefreshDatabase` |
| Integration | `PosController@searchCustomers` returns correct JSON, respects auth, handles empty query | Feature test with factory-created customers |
| Integration | Fiado auto-activation respects setting (on/off) | Feature test toggling setting, verifying POS behavior |
| E2E | Alpine sidebar state persistence, accordion, typeahead UX | Browser test (if available) or manual QA checklist |

## Migration / Rollout

No data migration required. `user_settings` table is additive. Fiado auto-activation defaults to `true` (matches current behavior) — no breaking change for existing users. Each PR is independently deployable and revertable.

## Open Questions

- [ ] Should `user_settings` use a JSON column for batch reads, or individual rows per setting? (Current design: individual rows — simpler queries, standard Laravel patterns)
- [ ] Should the typeahead cache results client-side (Alpine state) to avoid re-fetching on panel reopen? (Leaning yes — low effort, improves perceived performance)
