# Design: pos-ux-refinements

## Resumen de arquitectura

El estado reactivo del sidebar POS vive actualmente como `x-data` inline en el `<aside>` de `index.blade.php` (~100 líneas de estado + métodos). Este design lo extrae a un Alpine store registrado (`Alpine.store('posSidebar', {...})`) en `backend/resources/js/pos-sidebar-store.js`, importado desde `app.js` antes de `Alpine.start()`. El store es la única fuente de verdad para los 4 botones contextuales, los paneles y el typeahead de cliente. La vista Blade pasa de `x-data="{...}"` a `x-data` (sin objeto), leyendo todo desde `$store.posSidebar`.

El script vanilla de `DOMContentLoaded` (búsqueda de productos, líneas de venta, `refreshSummary()`) NO se migra a Alpine — sigue siendo vanilla JS y lee del DOM/hidden inputs. El store Alpine y el script vanilla coexisten con frontera clara: el store escribe hidden inputs (`syncToHiddenInputs()`), el script vanilla los lee. `refreshSummary()` se invoca desde eventos vanilla; el store no lo llama directamente.

Estrategia de entrega: **stacked-to-main** — cada PR targetea `main` directamente, en orden secuencial. Recomendación sobre feature-branch-chain: los slices son independientes enough para aterrizar en main uno por uno; cada PR es mergeable y reversible individualmente. Si el equipo prefiere feature-branch-chain, el orquestador debe capturarlo en preflight.

## Forma del Alpine store

```js
// backend/resources/js/pos-sidebar-store.js
export function registerPosSidebarStore(Alpine, initial) {
  Alpine.store('posSidebar', {
    // --- State ---
    activePanel: null,           // 'customer' | 'payment' | 'received' | 'credit' | null
    pinnedPanels: [],            // string[]
    usedPanels: [],              // string[] — hint visual persistente en sesión
    creditActive: initial.creditActive,
    paymentMethod: initial.paymentMethod,
    selectedCustomerId: initial.selectedCustomerId,
    selectedCustomerName: initial.selectedCustomerName,
    fiadoAutoEnabled: initial.fiadoAutoEnabled,
    // Typeahead
    customerQuery: initial.selectedCustomerName || '',
    customerResults: [],
    customerLoading: false,
    customerHighlightIndex: -1,
    _debounceTimer: null,

    // --- Actions ---
    togglePanel(name) { /* toggle activePanel; mark used; syncToHiddenInputs */ },
    togglePin(name)   { /* add/remove from pinnedPanels */ },
    markUsed(name)    { /* push to usedPanels if not present */ },
    syncToHiddenInputs()     { /* write payment_method, allow_credit_sale */ },
    handleCreditToggle()     { /* existing logic, markUsed('credit') */ },
    searchCustomers()        { /* fetch /pos/customers/search?q=..., 300ms debounce */ },
    selectCustomer(customer) { /* set selected*, clear results, syncToHiddenInputs */ },
    clearCustomer()          { /* reset selected*, syncToHiddenInputs */ },

    // --- Getters ---
    isPanelVisible(name)  { return this.activePanel === name || this.pinnedPanels.includes(name) },
    isButtonActive(name)  { /* name-specific: credit uses creditActive, others use activePanel/pinned */ },
    isButtonUsed(name)    { return this.usedPanels.includes(name) },
  });
}
```

**Frontera reactiva**: cada botón lee `isButtonActive(name)` + `isButtonUsed(name)` vía `:class`. Cada panel lee `isPanelVisible(name)` vía `x-show`. El typeahead lee `customerQuery`, `customerResults`, `customerLoading`, `customerHighlightIndex`. El resumen operativo lee `selectedCustomerName`, `paymentMethod`.

## Contrato del endpoint

`GET /pos/customers/search` **ya existe** en `PosController::searchCustomers()`. No se crea endpoint nuevo.

| Aspecto | Detalle |
|---------|---------|
| Método | GET |
| Ruta | `/pos/customers/search` (nombre: `pos.customers.search`) |
| Query params | `q` (string, trim, min 1 char tras trim) |
| Respuesta 200 | `{ "results": [{ "id": int, "name": string, "phone": string|null }, ...] }` |
| Límite | 10 resultados max (hardcoded en controller) |
| Búsqueda | `name LIKE %q%` OR `phone LIKE %q%` (no document — campo no existe en modelo) |
| Auth | Middleware `auth` (sesión). Sin middleware de rol — `Role` existe pero no hay middleware role-based en rutas POS. |
| Debounce | **Lado cliente** — `@input.debounce.300ms="searchCustomers()"` (Alpine modifier). El servidor no aplica throttle. |
| Error | Si `q` vacío → `{ results: [] }` (200). Si no auth → 302 redirect (middleware). |

**Nota de autorización**: el proyecto no tiene middleware de rol (`Role` model existe, `User::roles()` existe, pero ninguna ruta POS usa `can:cashier` o similar). Agregar autorización por rol está fuera de alcance para este change. Se documenta como riesgo.

## Integración con `refreshSummary()`

`refreshSummary()` es vanilla JS (línea 761 del Blade actual). Recorre filas del DOM, calcula total, actualiza labels. NO lee del Alpine store directamente — lee del DOM (`totalLabel.textContent`, inputs).

**Orden de updates cuando el usuario cambia método de pago**:
1. Usuario click en `.pos-payment-choice` → vanilla JS actualiza `paymentMethodInput.value`
2. Vanilla JS llama `updatePaymentMethodUi()` → lee `paymentMethodInput.value`, actualiza labels, mixed panel, y también actualiza `Alpine.store('posSidebar').paymentMethod`
3. Alpine re-renderiza `:class` de botones y `x-show` de paneles
4. `updatePaymentMethodUi()` llama `updateMixedBreakdown()`, `updateReceivedFeedback()`, `updateCreditSummary()` — todas vanilla JS

**Regla**: el store Alpine es escritor de estado de sidebar; `refreshSummary()` y funciones asociadas son escritores de estado de líneas de venta. La frontera es el hidden input + la referencia `Alpine.store('posSidebar')` desde vanilla JS.

## Diagramas de secuencia

### (a) Reactivación de panel

```
User        Blade           Alpine.store('posSidebar')
 │            │                      │
 │ click 'customer'                  │
 │───────────>│ @click="togglePanel('customer')"
 │            │─────────────────────>│ activePanel='customer'
 │            │                      │ markUsed('customer')
 │            │<─────────────────────│ usedPanels=['customer']
 │            │ :class re-render     │ isButtonUsed('customer')=true
 │<───────────│                      │
 │            │                      │
 │ click 'fiado'                     │
 │───────────>│ togglePanel('credit')│
 │            │─────────────────────>│ activePanel='credit'
 │            │                      │ markUsed('credit')
 │            │<─────────────────────│
 │<───────────│                      │
 │            │                      │
 │ click 'customer' again            │
 │───────────>│ togglePanel('customer')
 │            │─────────────────────>│ activePanel='customer'
 │            │<─────────────────────│ (ya estaba en usedPanels)
 │            │ x-show → visible     │ isPanelVisible('customer')=true
 │            │ (datos intactos)     │
 │<───────────│                      │
```

### (b) Typeahead de cliente

```
User        Alpine.store              Server
 │            │                         │
 │ type 'jua' │                         │
 │───────────>│ @input.debounce.300ms   │
 │            │ (timer starts)          │
 │            │                         │
 │  (300ms)   │                         │
 │            │ searchCustomers()       │
 │            │ fetch('/pos/customers/search?q=jua')
 │            │────────────────────────>│
 │            │          {results:[{id:1,name:'Juan Pérez',phone:'...'}]}
 │            │<────────────────────────│
 │            │ customerResults = [...] │
 │<───────────│ x-for renders dropdown  │
 │            │                         │
 │ ↓ arrow    │                         │
 │───────────>│ customerHighlightIndex=0│
 │<───────────│ :class highlight        │
 │            │                         │
 │ Enter      │                         │
 │───────────>│ selectCustomer(results[0])
 │            │ selectedCustomerId=1    │
 │            │ customerQuery='Juan Pérez'
 │            │ customerResults=[]      │
 │            │ syncToHiddenInputs()    │
 │<───────────│ x-text updates label    │
```

### (c) Inicialización del store (carga de página)

```
Browser           Blade              Alpine              Server
  │                │                   │                    │
  │ GET /pos       │                   │                    │
  │───────────────>│                   │                    │
  │                │                   │                    │
  │    HTML + @json($oldCustomerId, ...)                    │
  │<───────────────│                   │                    │
  │                │                   │                    │
  │ <script> import pos-sidebar-store  │                    │
  │───────────────>│                   │                    │
  │                │ Alpine.store(     │                    │
  │                │  'posSidebar',    │                    │
  │                │  {initial from @json})                 │
  │                │──────────────────>│                    │
  │                │                   │ state initialized  │
  │                │                   │ :class bindings    │
  │                │                   │ resolve            │
  │<───────────────│                   │                    │
  │ DOMContentLoaded                   │                    │
  │ vanilla JS init                    │                    │
  │ refreshSummary()                   │                    │
```

## Arquitectura por PR (stacked-to-main)

### PR 1: Alpine store + estado visual de 4 botones

| Archivos | Acción | Descripción |
|----------|--------|-------------|
| `backend/resources/js/pos-sidebar-store.js` | Create | Store Alpine con state, actions, getters |
| `backend/resources/js/app.js` | Modify | Importar y registrar store antes de `Alpine.start()` |
| `backend/resources/views/pos/index.blade.php` | Modify | Reemplazar `x-data="{...}"` inline por `x-data` + `$store.posSidebar`; agregar `:class` con `isButtonUsed()` |
| `backend/tests/Feature/PosSidebarStoreTest.php` | Create | Asserts: `x-data` presente, nombre del store, inicialización |

**Contrato expuesto a PR 2**: `$store.posSidebar` con `togglePanel()`, `isButtonActive()`, `isButtonUsed()`, `markUsed()`, `usedPanels`.
**Testable**: PHPUnit (snapshot Blade: `x-data`, `$store.posSidebar`, clases `used`). Manual: transiciones visuales de botones.
**Rollback**: `git revert` → vuelve a `x-data` inline. Sin impacto en PR 2+ si aún no merged.

### PR 2: Comportamiento de reactivación de paneles

| Archivos | Acción | Descripción |
|----------|--------|-------------|
| `backend/resources/js/pos-sidebar-store.js` | Modify | `togglePanel()` marca `usedPanels`; `isPanelVisible()` respeta `usedPanels` + `pinnedPanels` |
| `backend/resources/views/pos/index.blade.php` | Modify | `x-show` de paneles usa `isPanelVisible(name)`; botón `used` class binding |
| `backend/tests/Feature/PosPanelReactivationTest.php` | Create | Asserts: markup de `used` class, presencia de `isPanelVisible` |

**Contrato expuesto a PR 3**: `usedPanels` + `isPanelVisible()` estables.
**Testable**: PHPUnit (markup estático). Manual: reactivación con `MinimarketDemoSeeder`.
**Edge cases**: panel cerrado + reabierto conserva datos (garantizado porque datos viven en store, no en DOM).

### PR 3: Typeahead de cliente en POS

| Archivos | Acción | Descripción |
|----------|--------|-------------|
| `backend/resources/js/pos-sidebar-store.js` | Modify | `searchCustomers()` con debounce 300ms; `selectCustomer()`; `clearCustomer()` |
| `backend/resources/views/pos/index.blade.php` | Modify | Input typeahead usa `$store.posSidebar`; `@input.debounce.300ms`; keyboard nav |
| `backend/tests/Feature/PosCustomerSearchTest.php` | Create | Asserts: endpoint 200, JSON shape, auth required, empty query, sin alta rápida |

**Contrato expuesto**: endpoint `GET /pos/customers/search` (sin cambios). Store: `customerQuery`, `customerResults`, `customerLoading`, `customerHighlightIndex`.
**Testable**: PHPUnit (endpoint contract, auth, shape, "sin alta rápida" snapshot). Manual: debounce timing, keyboard nav.
**Nota**: el endpoint ya existe — este PR solo agrega tests y ajusta el binding Alpine.

### PR 4: Layout vertical del sidebar

| Archivos | Acción | Descripción |
|----------|--------|-------------|
| `backend/resources/views/pos/index.blade.php` | Modify | Wrapper `<div class="max-h-[calc(100vh-12rem)] overflow-y-auto">` alrededor de paneles; cada panel con `overflow-y-auto` interno |
| `backend/tests/Feature/PosSidebarLayoutTest.php` | Create | Asserts: `overflow-y-auto` en wrapper y paneles |

**Sin nuevos archivos JS**. Solo CSS/layout.
**Testable**: PHPUnit (snapshot: clases CSS). Manual: scroll con DevTools, 4 paneles abiertos, pin preservado.
**Rollback**: `git revert` → layout vuelve a apilamiento libre.

## Matriz de testabilidad

| PR | PHPUnit (Feature) | Alpine/JS test | Manual |
|----|-------------------|----------------|--------|
| 1 | Store en Blade (`x-data`, `$store`), clases `used` estáticas | — (no hay test runner JS) | Transiciones click→visual de 4 botones |
| 2 | Markup `isPanelVisible`, `used` class binding | — | Reactivación con `MinimarketDemoSeeder` (4 escenarios docs/pos/19) |
| 3 | Endpoint 200, JSON shape, auth, empty query, "sin alta rápida" snapshot | — | Debounce 300ms, keyboard nav, selección sobrevive toggle |
| 4 | `overflow-y-auto` en wrapper + paneles | — | Scroll con 4 paneles, pin preservado, DevTools layout |

**Gap honesto**: sin E2E (Dusk/Playwright/Cypress no están instalados), las transiciones reactivas de Alpine son 100% manuales. PHPUnit solo cubre snapshots estáticos del Blade renderizado y contratos de endpoint.

## Decisiones y tradeoffs

| Decisión | Opción elegida | Alternativa rechazada | Rationale |
|----------|---------------|----------------------|-----------|
| Alpine store vs Livewire | Alpine store (`Alpine.store()`) | Livewire 4 (instalado, no usado) | Livewire es cambio arquitectónico mayor; el proyecto usa Blade+Alpine. Introducir Livewire solo para UX de sidebar no tiene ROI. Reversible. |
| Persistencia del store | Solo en sesión (memoria) | `$persist` / `localStorage` | PII de cliente y estado intermedio de venta no deben filtrarse en POS compartido. Alinea con rollback simple. |
| Layout vertical | Altura fija + scroll interno por panel | Tabs/accordion exclusivo | Tabs/accordion choca con semántica de pin (panel pineado debe permanecer visible). Añade lógica de modo innecesaria. |
| Debounce location | Cliente (Alpine `@input.debounce.300ms`) | Server-side throttle | El endpoint ya sirve búsquedas de productos sin throttle server-side. Consistencia con patrón existente. Menos latencia percibida. |
| Nuevas dependencias | Ninguna |htmx, Stimulus, etc. | Alpine 3 + Tailwind ya cubren el caso. Añadir dependencias para UX de sidebar es over-engineering. |
| Chain strategy | Stacked-to-main | Feature-branch-chain | PRs son independientes y mergeables en orden. Cada uno tiene valor por sí mismo. Revert individual funciona. |

## Riesgos abiertos

1. **Cobertura Alpine**: transiciones reactivas sin test automatizado. Mitigación: checklist manual detallado por PR con `MinimarketDemoSeeder`.
2. **Sin middleware de rol**: el endpoint de búsqueda de clientes es accesible por cualquier usuario autenticado, no solo cajeros. Fuera de alcance para este change, pero documentado.
3. **PR chain dependency**: si PR-1 se revierte después de que PR-2 landed, PR-2 pierde su base (`$store.posSidebar`). Mitigación: cada PR debe ser reversible en orden inverso; si PR-1 se revierte, PR-2+ deben revertirse primero.
4. **`refreshSummary()` coupling**: la frontera vanilla/Alpine es frágil. Si un PR modifica hidden inputs sin actualizar `syncToHiddenInputs()`, el resumen se desincroniza. Mitigación: tests Feature que assertúan la presencia de `syncToHiddenInputs` en el store.
