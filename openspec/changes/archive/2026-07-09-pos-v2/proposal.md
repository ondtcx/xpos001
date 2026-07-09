# Proposal: pos-v2

## 1. Why

The current POS uses a 4-contextual-buttons + 4-collapsible-panels paradigm (`Asignar cliente`, `Ingresar monto recibido`, `Convertir a fiado`, `Cambiar método`) that forces cashiers to open/close panels sequentially, losing at-a-glance visibility of the sale state. The design reference (Next.js/shadcn v0) collapses everything into an always-visible 2-column layout: catalog + cart on the left, a single checkout panel on the right with client dropdown, 3-tab payment selector, and live totals. This change ports that design wholesale to the existing Laravel + Blade + Alpine stack, replacing the sidebar paradigm entirely.

## 2. What changes

### Schema
- +1 migration `add_document_to_customers`: adds `document` (string, nullable) and `is_default` (boolean, default false) to `customers`.
- **No changes** to `sales`, `sale_items`, `sale_payments`, `receivables`, or `inventory_lots`.

### Model
- `Customer`: gains `document` (cast string) and `is_default` (cast boolean). Add scope `scopeDefault()`.

### Seeder
- `MinimarketDemoSeeder`: populates the 3 existing demo customers with `document` values (DNI-like strings). Creates a 4th row: `Cliente General` with `is_default=true`, `document='—'`.

### View
- Full replacement of `backend/resources/views/pos/index.blade.php` with new 2-column layout (catalog + cart | checkout panel). USD currency via `Intl.NumberFormat('es-HN', {currency:'USD'})`.

### Alpine store
- Replace `pos-sidebar-store.js` with new `pos-app-store.js`. State: `items[]`, `cliente`, `metodo` (3 values: efectivo/transfer/fiado), `recibido`, `aviso` (toast 3.5s). Computed: `subtotal`, `itemsCount`, `vuelto`.
- Drop: `.used` indicator, `markUsed()`, `togglePin()`, `usedPanels`, `pinnedPanels`, `fiadoAutoEnabled` (moved to backend gate).

### CSS
- New Tailwind utilities + CSS custom properties in `app.css` for the 3 design colors (primary/accent/destructive). Drop `.used` and `.used::after`.

### Controller
- `PosController::index`: pass `is_default` customer to view as preselected. `searchCustomers`: extend response shape to include `document` and `saldo_fiado` (computed aggregate).
- `PosController::store`: no change to submission flow; `fiado` tab is a UI label over existing `cash + allow_credit_sale` path.

### Routes
- No new routes.

### Tests
- Rewrite: `PosSidebarStoreTest`, `PosSidebarLayoutTest`, `PosSidebarReactivationTest`, `PosSidebarReceivedCreditBindingsTest` (obsolete markup).
- Update: `PosCustomerSearchTest` (new response shape), `PosFlowTest` (3 payment paths).
- Add: `PosV2IndexTest` (view snapshot), `PosV2PaymentValidationTest` (reject mixed/tarjeta).

## 3. What does NOT change (scope boundaries)

- **Tarjeta** payment method (user decision).
- **Mixto** payment method (deferred).
- **Anulación post-cobrada** (VoidSaleService untouched; out of scope per user decision).
- **Pin panels / `.used` indicator** (deleted, no replacement).
- **Discount / manual price override / product photos** (design doesn't expose them).
- **Dark mode / mobile-first / Livewire migration** (out of scope).
- **`fiado_auto_enabled` setting** (kept as backend gate; UI doesn't reflect it yet).

**Open WARNING questions deferred to spec phase** (not decided here):
- Q4: Lote + Vence display rule (nearest expiration? oldest received? first created?)
- Q5: Anular venta semantics (same as Vaciar? or also reset cliente + metodo + recibido?)
- Q6: Caja abierta requirement UX (keep banner? drop? hard-fail toast?)
- Q7: Sale submission pattern (AJAX + inline toast, or form submit + session flash?)

## 4. Specs impact

The 5 specs from `pos-ux-refinements` are **superseded wholesale** by pos-v2:
- `pos-sidebar-state`
- `pos-contextual-buttons-state`
- `pos-panel-reactivation`
- `pos-client-typeahead`
- `pos-sidebar-vertical-layout`

**Supersession plan:**
- Archive originals in `openspec/changes/pos-v2/specs/_superseded/pos-ux-refinements-originals/` (copy of current `openspec/specs/pos-*`).
- Delete the 5 folders from `openspec/specs/` (they no longer describe the system).
- Write 1-3 NEW specs in `openspec/specs/` covering pos-v2 behavior (e.g., `pos-v2-checkout-flow`, `pos-v2-customer-selection`, `pos-v2-payment-tabs`).

## 5. Approach

**Phase 1 — Isolated data layer (low risk):** migration + model + seeder. Can be merged independently; no UI impact. Tests: assert `document` and `is_default` columns exist, seeder creates 4 customers with expected values.

**Phase 2 — New Alpine store (parallel to old):** write `pos-app-store.js` alongside existing `pos-sidebar-store.js`. Register both in `app.js` temporarily. Tests: unit-style assertions on store shape (items, cliente, metodo, recibido, aviso, computed).

**Phase 3 — New view (parallel to old):** write new `index.blade.php` as a separate file (e.g., `index-v2.blade.php`) with a temporary route alias `/pos-v2` for visual QA. Tests: snapshot asserts on markup (header, 3 tabs, cart, checkout panel, USD currency).

**Phase 4 — Cutover:** replace old view, delete old store, update controller to pass new data. Remove `/pos-v2` alias. Tests: full `PosFlowTest` rewrite for 3 payment paths.

**Phase 5 — Test rewrite pass:** delete obsolete tests, update `PosCustomerSearchTest` shape, add new validation tests.

**Phase 6 — Archive old specs:** move 5 superseded specs to `_superseded/`, write new specs.

Single PR default (per preflight decision). If tasks phase forecasts >400 changed lines, surface to user for `size:exception` approval.

## 6. Open questions (from explore WARNINGs)

These require user input before spec phase. **Not decided in this proposal.**

| # | Question | Options | Recommendation |
|---|----------|---------|----------------|
| Q4 | Lote + Vence display rule for products with multiple `InventoryLot` rows | (a) nearest expiration, (b) oldest received, (c) first created | (a) nearest expiration — matches FIFO physical flow |
| Q5 | Anular venta semantics (cart button, non-cobradas only) | (a) same as Vaciar (clear items only), (b) clear items + reset cliente + reset metodo + reset recibido | (a) same as Vaciar — simpler, less surprising |
| Q6 | Caja abierta requirement UX (backend still rejects without open session) | (a) keep amber banner (current style), (b) drop banner, hard-fail with toast on submit, (c) drop requirement for new POS | (b) hard-fail toast — design has no banner, matches new paradigm |
| Q7 | Sale submission pattern | (a) AJAX + inline toast (no reload), (b) form submit + session flash + redirect | (a) AJAX + toast — matches design (no form in page.tsx), better UX |

## 7. Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| New view is a large single-PR change (>400 lines) | High | Tasks phase forecasts changed lines; if >400, surface to user for `size:exception` per preflight |
| 5 superseded specs have downstream references (archive docs, other specs) | Medium | Grep found references in `archive/2026-06-28-pos-fiado-ux/` and `archive/2026-07-09-pos-ux-refinements/` — these are historical, kept as-is. Live specs in `openspec/specs/` are the only ones deleted |
| 6 existing tests break on view replacement | High | List in tasks phase; rewrite in same PR. Tests are Feature-level (markup asserts), easy to identify |
| Visual fidelity to v0 design is subjective | Medium | Include screenshot/visual diff in verify phase; compare against live preview URL |
| `fiado` tab as UI label over existing `cash + allow_credit_sale` path may confuse future devs | Low | Document in spec: "Fiado tab is a UI convenience; backend still uses credit-sale flag on cash payment" |

## 8. Success criteria

- [ ] A new sale (efectivo, no client) completes end-to-end from the new view
- [ ] A new sale (fiado, with client) creates a receivable correctly
- [ ] The new view renders on `/pos` and requires authentication
- [ ] `Cliente General` row exists in DB and is the default selection in the dropdown
- [ ] Currency displays as `USD X.XX` (Intl.NumberFormat es-HN)
- [ ] Toast appears on Cobrar and disappears after ~3.5s
- [ ] Cart "Anular venta" button clears the cart (semantics depend on Q5 resolution)
