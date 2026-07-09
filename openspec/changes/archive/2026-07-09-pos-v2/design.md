# Design: pos-v2

## 1. Overview

Total replacement of the POS sales interface. The current 4-button + 4-collapsible-panel sidebar paradigm is replaced by a single-page 2-column layout: catalog + cart on the left, always-visible checkout panel on the right. Stack remains Laravel 12 + Blade + Alpine 3.x + Tailwind 3 — no new dependencies. The Alpine store `posSidebar` is replaced by a new `posStore` that owns all POS state (cart, customer, payment, toast). Submission becomes AJAX (`fetch` POST, no redirect). One migration adds `document` and `is_default` to `customers`.

## 2. File Plan

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/2026_07_09_XXXXXX_add_document_and_is_default_to_customers.php` | Create | Adds `document` (string, nullable, 50) and `is_default` (boolean, default false, indexed) to `customers` |
| `resources/js/pos-store.js` | Create | New Alpine store `posStore` — replaces `pos-sidebar-store.js` |
| `resources/views/pos/index.blade.php` | Modify | Full rewrite — 2-column layout, Alpine-driven |
| `resources/css/app.css` | Modify | Drop `.used` rules; add POS custom properties |
| `resources/js/app.js` | Modify | Import `posStore` instead of `posSidebar` |
| `app/Models/Customer.php` | Modify | Add `document`, `is_default` to `$fillable`/`$casts`; add `scopeDefault()` |
| `app/Http/Controllers/PosController.php` | Modify | Remove `currentCashSession` check from `store()`; add JSON response; extend `searchCustomers` with `document` + `saldo_fiado`; add FEFO lot data to `index()` |
| `app/Http/Requests/Sales/StorePosSaleRequest.php` | Modify | Accept `fiado` as `payment_method`; accept `metodo` field mapping |
| `app/Models/ProductVariant.php` | Modify | Add `nearestLot()` relationship (FEFO scope) |
| `database/seeders/MinimarketDemoSeeder.php` | Modify | Add `document` to 3 demo customers; create `Cliente General` row with `is_default=true` |
| `tests/Feature/PosV2CatalogTest.php` | Create | Catalog scenarios (card render, FEFO, search, add-to-cart, out-of-stock) |
| `tests/Feature/PosV2CartTest.php` | Create | Cart scenarios (line render, qty controls, remove, header, anular, vaciar, currency) |
| `tests/Feature/PosV2CheckoutTest.php` | Create | Checkout scenarios (3 tabs, efectivo input/vuelto, transfer hides, fiado disabled for General, totals, cobrar AJAX) |
| `tests/Feature/PosV2CustomerTest.php` | Create | Customer dropdown scenarios (default, options, search, keyboard, missing-default guard) |
| `resources/js/pos-sidebar-store.js` | Delete | Replaced by `pos-store.js` |
| `tests/Feature/PosSidebarStoreTest.php` | Delete | Markup no longer exists |
| `tests/Feature/PosSidebarLayoutTest.php` | Delete | Markup no longer exists |
| `tests/Feature/PosSidebarReactivationTest.php` | Delete | `usedPanels`/`markUsed` no longer exists |
| `tests/Feature/PosSidebarReceivedCreditBindingsTest.php` | Delete | Panels `received`/`credit` no longer exist |
| `tests/Feature/PosCustomerSearchTest.php` | Delete | Replaced by `PosV2CustomerTest` (new response shape) |
| `tests/Feature/PosFlowTest.php` | Delete | Replaced by `PosV2CheckoutTest` (AJAX, 3 methods) |

## 3. Alpine Store API (`posStore`)

### State

| Property | Type | Default | Notes |
|----------|------|---------|-------|
| `items` | `Array<{id, nombre, precio, disponibles, lote, vence, categoria, cantidad}>` | `[]` | Cart lines |
| `cliente` | `{id, nombre, documento, saldo_fiado}` | `{id: <generalId>, nombre: 'Cliente General', ...}` | From `$store.posStore.clientes[0]` (the `is_default` row) |
| `metodo` | `'efectivo' \| 'transfer' \| 'fiado'` | `'efectivo'` | Active payment tab |
| `recibido` | `string` | `''` | Cash received amount |
| `aviso` | `{tipo: 'success'\|'error', mensaje: string} \| null` | `null` | Toast state |
| `procesando` | `boolean` | `false` | AJAX loading flag |
| `clientes` | `Array<{id, nombre, documento, saldo_fiado}>` | from Blade `@json` | Full customer list |
| `productos` | `Array<product card data>` | from Blade `@json` | Full catalog |
| `busqueda` | `string` | `''` | Catalog search filter |
| `clienteOpen` | `boolean` | `false` | Customer dropdown open state |
| `clienteQuery` | `string` | `''` | Customer dropdown filter |
| `clienteHighlight` | `number` | `-1` | Keyboard nav index |

### Getters (computed via Alpine reactivity)

| Getter | Returns | Formula |
|--------|---------|---------|
| `subtotal` | `number` | `items.reduce((s, i) => s + i.precio * i.cantidad, 0)` |
| `total` | `number` | `= subtotal` (no discount) |
| `itemsCount` | `number` | `items.reduce((s, i) => s + i.cantidad, 0)` |
| `vuelto` | `number` | `Math.max(0, parseFloat(recibido \|\| 0) - total)` |
| `puedeCobrar` | `boolean` | `itemsCount > 0 && !(metodo === 'fiado' && cliente.id === generalId)` |
| `filteredProductos` | `Array` | `productos.filter(...)` by `busqueda` against nombre/codigo/barcode/categoria |
| `filteredClientes` | `Array` | `clientes.filter(...)` by `clienteQuery` against nombre/documento |

### Actions

| Action | Signature | Side Effects | Edge Cases |
|--------|-----------|--------------|------------|
| `agregar(producto)` | `(product) => void` | Push to `items` or increment `cantidad` if exists. No-op if `disponibles <= 0`. | Stock check at add time |
| `cambiarCantidad(id, qty)` | `(id, qty) => void` | Update line qty. If `qty < 1`, remove line. | Floors at 1 via `−` button |
| `quitar(id)` | `(id) => void` | Remove line from `items` | — |
| `limpiar()` | `() => void` | `items = []`, `recibido = ''`. Does NOT reset `cliente` or `metodo`. | "Vaciar" semantics |
| `anularVenta()` | `() => void` | `items = []`, `cliente = default`, `metodo = 'efectivo'`, `recibido = ''` | Full reset per Q5 |
| `cobrar()` | `async () => void` | AJAX POST to `/pos`. On 200: success toast + full reset. On 4xx/5xx: error toast, cart preserved. | `procesando` guards double-submit |
| `setCliente(c)` | `(customer) => void` | Sets `cliente`, closes dropdown. If `c.id === generalId` and `metodo === 'fiado'`, reset `metodo` to `'efectivo'`. | — |
| `setMetodo(m)` | `(method) => void` | Sets `metodo`. If `m === 'fiado'` and `cliente.id === generalId`, do nothing (tab disabled). | — |
| `setRecibido(r)` | `(value) => void` | Sets `recibido` string | — |
| `quickAmount(valor)` | `(value) => void` | `setRecibido(String(valor))` | — |
| `cerrarAviso()` | `() => void` | `aviso = null` | Manual dismiss |

### Currency Formatter

```js
// Inside posStore
formatMoney(value) {
  return 'USD ' + Number(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
```

Per decision #1/#13: prefix "USD", thousands `,`, decimal `.` (en-US style). Matches the design's `Intl.NumberFormat('es-HN', {currency:'USD'})` output.

## 4. View Structure

```
<x-app-layout> (kept for auth shell, nav)
  <x-slot:header> — "Caja / Punto de venta" + cashier name
  <main x-data x-ref="pos" class="mx-auto max-w-[1400px] flex-col gap-5 p-4 lg:p-6">
    <!-- Toast -->
    <div x-show="$store.posStore.aviso" x-transition class="fixed top-4 left-1/2 -translate-x-1/2 z-50 ...">
      <span x-text="$store.posStore.aviso.mensaje"></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-5">
      <!-- LEFT COLUMN -->
      <div class="flex flex-col gap-5">
        <!-- Catalog section -->
        <section class="rounded-3xl border p-1">
          <input x-model="$store.posStore.busqueda" placeholder="Buscar por nombre, codigo o categoria...">
          <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 p-3">
            <template x-for="p in $store.posStore.filteredProductos">
              <!-- Product card: category chip, stock chip, name, Lote+Vence, price, + button -->
            </template>
          </div>
          <!-- Empty state when filteredProductos.length === 0 -->
        </section>

        <!-- Cart section -->
        <section class="rounded-3xl border bg-secondary/50 p-4">
          <header>
            <h2>ShoppingCart icon + "Venta actual" + badge(itemsCount)</h2>
            <button x-show="items.length > 0" @click="limpiar()">Vaciar</button>
            <button x-show="items.length > 0" @click="anularVenta()">Anular venta</button>
          </header>
          <div x-show="items.length === 0">
            <!-- Empty state: ShoppingCart icon + "Agrega productos para iniciar la venta." -->
          </div>
          <template x-for="item in items">
            <!-- Cart line: name, unit price, [− qty +], line total, trash button -->
          </template>
        </section>
      </div>

      <!-- RIGHT COLUMN: Checkout panel -->
      <aside class="rounded-3xl border p-5 flex flex-col gap-4">
        <!-- Customer dropdown -->
        <div>
          <label>Cliente</label>
          <div class="relative" @click.outside="clienteOpen = false">
            <select x-model="cliente.id" @change="setCliente(...)">
              <template x-for="c in clientes" :key="c.id">
                <option :value="c.id" x-text="c.nombre + (c.saldo_fiado > 0 ? ' · debe USD ' + formatMoney(c.saldo_fiado) : '')"></option>
              </template>
            </select>
          </div>
        </div>

        <!-- Payment tabs: 3 buttons -->
        <div class="grid grid-cols-3 gap-2">
          <button @click="setMetodo('efectivo')" :class="metodo === 'efectivo' ? 'active-primary' : ''">Efectivo</button>
          <button @click="setMetodo('transfer')" :class="metodo === 'transfer' ? 'active-primary' : ''">Transfer.</button>
          <button @click="setMetodo('fiado')" :disabled="cliente.id === generalId" :class="metodo === 'fiado' ? 'active-accent' : ''">Fiado</button>
        </div>

        <!-- Conditional: Efectivo received -->
        <div x-show="metodo === 'efectivo'">
          <input type="number" x-model="recibido" placeholder="0.00">
          <div class="flex gap-2">
            <!-- Quick amount chips: Exacto (if total > 0), USD 20, USD 50, USD 100 -->
          </div>
          <div>Vuelto: <span x-text="formatMoney(vuelto)"></span></div>
        </div>

        <!-- Conditional: Fiado banner -->
        <div x-show="metodo === 'fiado'">
          <template x-if="cliente.id === generalId">
            <p>Selecciona un cliente para registrar el fiado.</p>
          </template>
          <template x-if="cliente.id !== generalId">
            <p>Se sumara USD X.XX a la cuenta de {nombre}.</p>
          </template>
        </div>

        <!-- Totals -->
        <div>
          <div>Subtotal (<span x-text="itemsCount"></span> art.) — <span x-text="formatMoney(subtotal)"></span></div>
          <div>Total — <span x-text="formatMoney(total)" class="text-3xl font-bold"></span></div>
        </div>

        <!-- Cobrar button -->
        <button @click="cobrar()" :disabled="!puedeCobrar || procesando">
          <span x-show="!procesando" x-text="metodo === 'fiado' ? 'Registrar fiado' : 'Cobrar'"></span>
          <span x-show="procesando">Procesando...</span>
        </button>
      </aside>
    </div>
  </main>
</x-app-layout>
```

## 5. Data Flow (AJAX Cobrar)

```
User clicks "Cobrar"
    │
    ▼
posStore.cobrar()
    │ procesando = true
    │ builds payload: {items: [{id, cantidad}], cliente_id, payment_method, received_amount, allow_credit_sale, total}
    │
    ▼
fetch(POST /pos, {headers: {X-CSRF-TOKEN, Accept: application/json}, body: JSON})
    │
    ├─ 200 OK ──► aviso = {tipo: 'success', mensaje: 'Venta cobrada: USD X.XX (efectivo).'}
    │             setTimeout(() => aviso = null, 3500)
    │             anularVenta() (full reset)
    │             procesando = false
    │
    └─ 422/500 ──► aviso = {tipo: 'error', mensaje: response.errors[first key]}
                   cart preserved
                   procesando = false
```

### Payload mapping (posStore → PosController)

| posStore field | POST field | Backend mapping |
|----------------|-----------|-----------------|
| `items[].id` | `items[][sale_presentation_id]` | unchanged |
| `items[].cantidad` | `items[][quantity]` | unchanged |
| `cliente.id` | `customer_id` | unchanged |
| `metodo === 'efectivo'` | `payment_method = 'cash'` | maps to existing |
| `metodo === 'transfer'` | `payment_method = 'transfer'` | maps to existing |
| `metodo === 'fiado'` | `payment_method = 'cash'`, `allow_credit_sale = 1` | existing credit path |
| `recibido` | `received_amount` | unchanged |
| `total` | (informational, backend recalculates) | — |

## 6. Controller Changes (PosController)

### `index()`

- Add FEFO lot data to presentations query: eager load `nearestLot` per variant (see Section 8).
- Pass `clientes` as JSON-serializable array with `id`, `nombre`, `documento`, `saldo_fiado` (computed aggregate from `receivables WHERE status='open'`).
- Pass `defaultClienteId` = `Customer::default()->first()->id`.
- Remove `currentCashSession` from view data (no longer needed in UI).
- Remove `fiadoAutoEnabled` from view data.

### `store()`

- **Remove** lines 90-96 (`currentCashSession` null check + redirect with error).
- Accept `$currentCashSession` as nullable (pass `null` to `CreateSaleService` if no open session — the service already handles `?CashSession`).
- Return `JsonResponse` instead of `RedirectResponse`:
  - Success: `{ok: true, sale_id: $sale->id, message: "Venta cobrada: USD X.XX (metodo)."}`
  - Validation error: catch `ValidationException`, return `{ok: false, errors: $e->errors()}` with 422.
- Map `metodo` field: if request has `metodo=fiado`, set `payment_method=cash` + `allow_credit_sale=true` before passing to draft builder.

### `searchCustomers()`

- Extend response: add `document` and `saldo_fiado` (computed: `SUM(receivables.pending_amount) WHERE status='open'`).
- Return all active customers when `q` is empty (for dropdown initial population).

## 7. Migration

```
add_document_and_is_default_to_customers

up():
  Schema::table('customers', function (Blueprint $table) {
    $table->string('document', 50)->nullable()->after('name');
    $table->boolean('is_default')->default(false)->after('is_active');
    $table->index('is_default');
  });

down():
  Schema::table('customers', function (Blueprint $table) {
    $table->dropIndex(['is_default']);
    $table->dropColumn(['document', 'is_default']);
  });
```

Backwards compatible: `document` is nullable, `is_default` defaults to false.

## 8. FEFO Query

Add to `ProductVariant` model:

```php
public function nearestLot(): HasOne
{
    return $this->hasOne(InventoryLot::class)
        ->where('available_quantity', '>', 0)
        ->whereNotNull('expiration_date')
        ->orderBy('expiration_date', 'asc')
        ->orderBy('id', 'asc');
}
```

**SQL intent**: For a given variant, find the lot with the nearest non-null `expiration_date` that still has available quantity. Tie-break by `id ASC` (oldest received first).

**Edge cases**:
- No lots with non-null expiration → `nearestLot` returns null → display "Sin lote" in UI.
- All lots expired → same handling (no date filter on past, just `available_quantity > 0`; expired lots with qty are still shown — business decision for now).
- All lots have null expiration → `nearestLot` returns null → display "Sin lote".

In `PosController::index()`, load presentations with their variant's `nearestLot` to populate `lote` and `vence` per card.

## 9. Test Strategy

### New test files (map 1:1 to spec scenarios)

**PosV2CatalogTest.php** (5 tests):
- `test_card_renders_full_data` — name, category, price USD X.XX, stock "N disp."
- `test_fefo_multiple_lots_nearest_expiration_wins`
- `test_fefo_null_expiration_ignored`
- `test_search_filters_by_name_code_barcode`
- `test_out_of_stock_card_shows_agotado`

**PosV2CartTest.php** (7 tests):
- `test_line_shows_full_data`
- `test_decrement_floors_at_1`
- `test_trash_removes_line`
- `test_header_reflects_sum_and_empty_state`
- `test_anular_venta_full_reset`
- `test_vaciar_preserves_checkout_state`
- `test_currency_format_thousands_separator`

**PosV2CheckoutTest.php** (9 tests):
- `test_three_tabs_render_no_tarjeta`
- `test_efectivo_shows_input_vuelto_quick_amounts`
- `test_transfer_hides_cash_ui`
- `test_fiado_disabled_for_cliente_general`
- `test_fiado_enabled_for_non_general`
- `test_totals_display_no_discount`
- `test_cobrar_disabled_when_cart_empty`
- `test_cobrar_success_ajax_toast_and_reset`
- `test_cobrar_validation_error_red_toast_preserves_cart`

**PosV2CustomerTest.php** (6 tests):
- `test_exactly_one_default_customer_exists`
- `test_dropdown_options_include_debt`
- `test_inline_search_filters_by_name_document`
- `test_selection_updates_checkout_state`
- `test_keyboard_navigation_arrow_enter_escape`
- `test_pos_refuses_render_without_default_customer`

### Old tests to DELETE (6 files)

| File | Reason |
|------|--------|
| `PosSidebarStoreTest.php` | `posSidebar` store no longer exists |
| `PosSidebarLayoutTest.php` | Vertical layout wrapper no longer exists |
| `PosSidebarReactivationTest.php` | `usedPanels`/`markUsed` no longer exists |
| `PosSidebarReceivedCreditBindingsTest.php` | `received`/`credit` panels no longer exist |
| `PosCustomerSearchTest.php` | Response shape changed (adds `document`, `saldo_fiado`); replaced by `PosV2CustomerTest` |
| `PosFlowTest.php` | Form submit replaced by AJAX; 3 methods replace old cash/transfer/mixed; replaced by `PosV2CheckoutTest` |

## 10. Visual Fidelity Notes

| Aspect | Implementation |
|--------|---------------|
| Currency | `'USD ' + number.toFixed(2)` with comma thousands. JS: `Intl.NumberFormat('en-US', {style:'currency', currency:'USD'})` produces `USD 2.50`. |
| Icons | Heroicons outline (already in project via Breeze). Map: ShoppingCart → `<x-heroicon-o-shopping-cart />`, Trash2 → `<x-heroicon-o-trash />`, Store → `<x-heroicon-o-building-storefront />`, Search → `<x-heroicon-o-magnifying-glass />`, User → `<x-heroicon-o-user />`, ChevronDown → `<x-heroicon-o-chevron-down />`, Banknote → `<x-heroicon-o-banknotes />`, Smartphone → `<x-heroicon-o-phone />`, Notebook → `<x-heroicon-o-clipboard-document />` |
| Spacing | `rounded-3xl`, `gap-5`, `p-4 lg:p-6` — matches design |
| Toast | Fixed top-center, green bg for success, red bg for error, `x-transition` for fade, 3.5s auto-dismiss |
| Colors | Primary: `bg-emerald-400` (green lima). Accent: `bg-amber-500` (orange, for Fiado active). Destructive: `bg-red-500`. Match design's oklch palette with Tailwind fallbacks. |
| Grid | `lg:grid-cols-[1fr_360px]` — matches design exactly |

## 11. Risks + Mitigations

| Risk | Severity | Mitigation |
|------|----------|------------|
| Single PR >400 changed lines | WARNING | Forecast in Section 13. If over, request `size:exception` from user (pre-decided single-pr). |
| 6 existing tests break on view replacement | WARNING | Delete all 6 in same PR. New tests cover new behavior. `composer test` stays green. |
| `PosSaleDraftBuilder` credit path expects `confirm_credit_sale` checkbox | WARNING | New flow: when `metodo=fiado`, set `allow_credit_sale=true` AND `confirm_credit_sale=true` in the mapped request. The builder's existing validation passes. |
| `CreateSaleService` requires `CashSession` for cash payments | WARNING | Q6 dropped the requirement. Pass `null` when no open session. The service already accepts `?CashSession` (nullable type hint at line 21). |
| Visual fidelity drift from v0 design | SUGGESTION | Verify phase includes side-by-side screenshot comparison against `https://design-sales-interface-for-pos.vercel.app`. |

## 12. Implementation Order

1. **Migration + Model + Seeder** (~50 lines) — `add_document_and_is_default_to_customers`, `Customer` model changes, seeder updates. Independent, testable in isolation.
2. **FEFO query** (~15 lines) — `ProductVariant::nearestLot()`. Test: assert correct lot selected for multiple-lots scenario.
3. **Controller: searchCustomers extension** (~20 lines) — Add `document`, `saldo_fiado` to response. Accept empty query → return all.
4. **Controller: store() changes** (~30 lines) — Remove cash session check, return JSON, map `metodo` → `payment_method` + `allow_credit_sale`.
5. **New Alpine store `posStore`** (~120 lines) — Full state, getters, actions, currency formatter, AJAX `cobrar()`.
6. **New view `index.blade.php`** (~250 lines) — Full rewrite with Alpine directives, 2-column layout, all sections.
7. **CSS changes** (~15 lines) — Drop `.used`, add custom properties if needed.
8. **`app.js` update** (~5 lines) — Swap import from `posSidebar` to `posStore`.
9. **Delete old store + old tests** — Remove `pos-sidebar-store.js`, 6 test files.
10. **New tests** (~200 lines) — 4 test files, 27 test methods mapping to spec scenarios.
11. **Verify** — Visual comparison + functional walkthrough.

## 13. Forecast

| Category | Files | Changed Lines (est.) |
|----------|-------|---------------------|
| Migration | 1 new | ~25 |
| Model changes | 2 modify | ~30 |
| Seeder | 1 modify | ~20 |
| Controller | 1 modify | ~50 |
| Form Request | 1 modify | ~15 |
| Alpine store | 1 new, 1 delete | ~140 (net +120) |
| View | 1 rewrite | ~300 (net ~250) |
| CSS | 1 modify | ~20 (net -10) |
| app.js | 1 modify | ~5 |
| New tests | 4 new | ~270 |
| Deleted tests | 6 delete | ~-400 |
| **Total net** | | **~520** |

**Forecast: ~520 net changed lines. Over the 400 budget.**

Breakdown: the view rewrite (~250) and new tests (~270) dominate. Old test deletion (-400) offsets significantly but the new view + new tests push past budget.

**Recommended action**: `size:exception` — pre-decided single PR per user's preflight decision. The change is atomic (old POS is non-functional without the new view) and cannot be split without leaving a broken intermediate state.
