# Exploration: pos-v2

## Estado actual

El POS de xpos001 vive en `backend/resources/views/pos/index.blade.php` (976 líneas) con un layout de 2 columnas (`xl:grid-cols-[minmax(0,1fr)_22rem]`): izquierda = buscador de presentaciones + tabla de líneas de venta; derecha = sidebar con resumen operativo, **4 botones contextuales** (Asignar cliente, Cambiar método, Ingresar monto recibido, Convertir a fiado) y **4 paneles colapsables** apilados verticalmente con scroll interno. Los métodos de pago actuales son `cash | transfer | mixed` (fiado NO es método de pago, es un modificador sobre `cash` con `received_amount < total_amount` y cliente obligatorio). El `pos-ux-refinements` (recién archivado) introdujo el Alpine store `posSidebar`, el typeahead de cliente contra `GET /pos/customers/search`, el `used` indicator con CSS `.used`, y el layout vertical acotado. Toda la lógica de carrito (líneas, cantidades, subtotal) sigue siendo vanilla JS + DOM (`refreshSummary()`), separada del store Alpine por frontera hidden inputs.

**Archivos clave:**
- `backend/resources/views/pos/index.blade.php` — vista única
- `backend/resources/js/pos-sidebar-store.js` — store Alpine con 4 flags + typeahead
- `backend/resources/js/app.js` — registro del store antes de `Alpine.start()`
- `backend/resources/css/app.css` — define `.used` (indicador visual pos-ux-refinements)
- `backend/app/Http/Controllers/PosController.php` — `index()`, `searchCustomers()`, `store()` (usa `PosSaleDraftBuilder` + `CreateSaleService`)
- `backend/app/Http/Requests/Sales/StorePosSaleRequest.php` — valida `payment_method ∈ {cash, transfer, mixed}`
- `backend/app/Support/Sales/PosSaleDraftBuilder.php` — arma el draft; fiado solo si `cash + received < total + allow_credit_sale`
- `backend/app/Support/Sales/CreateSaleService.php` — persiste venta, items, pagos (`SalePayment` con `payment_method ∈ {cash, transfer}`) y receivable
- `backend/app/Support/Sales/VoidSaleService.php` — anulación post-cobrada (existente, no se toca)
- `backend/app/Models/{Customer, Product, ProductVariant, SalePresentation, SalePrice, InventoryLot, InventoryMovement, Sale, SaleItem, SalePayment, Receivable}.php` — modelo de dominio
- `backend/database/seeders/MinimarketDemoSeeder.php` — dataset demo con 18 productos, 3 clientes
- `backend/routes/web.php` — `pos.index`, `pos.store`, `pos.customers.search`
- `backend/tests/Feature/PosFlowTest.php` + 5 tests de sidebar/typeahead
- `openspec/specs/pos-sidebar-state/spec.md` + 4 specs hermanas de pos-ux-refinements

## Resumen del diseño (v0)

Stack: **Next.js 16 + React 19 + shadcn/ui (base-nova) + Tailwind 4**. Todo el estado es local (no backend). La paleta usa oklch (`--primary: oklch(0.87 0.19 118)` verde lima, `--accent: oklch(0.75 0.15 55)` naranja, `--destructive` rojo). Moneda: USD con `Intl.NumberFormat('es-HN', {currency:'USD'})` → renderiza `USD 2.50`. Idioma de UI: español.

**4 archivos principales:**

1. **`app/page.tsx`** — `main` con `max-w-[1400px] flex-col gap-5 p-4`. Header con icono tienda + "Caja / Punto de venta" + "Cajero: Ana López". Grid: `lg:grid-cols-[1fr_360px]` (catálogo+carrito | cobro). Estado: `items[]`, `cliente`, `metodo`, `recibido`, `aviso` (toast 3.5s). Acciones: `agregar()`, `cambiarCantidad()`, `quitar()`, `limpiar()`, `cobrar()`. Memoiza `subtotal`, `itemsCount`, `vuelto`.

2. **`components/pos/product-search.tsx`** — Input de búsqueda con icono Search + grid de cards (2/3/4 columnas según breakpoint). Cada card: chip categoría + chip stock (`{N} disp.` o `Agotado`), nombre, `Lote X · Vence MM/AAAA`, precio + botón `+`. Empty state: `PackageX` icon + "No se encontraron productos para X". Filtro client-side por `nombre`, `codigo`, `categoria`.

3. **`components/pos/cart-item.tsx`** — Card por línea: nombre + `Lote X · N disp. · USD X c/u` + botón trash, fila inferior con `[− qty +]` + subtotal. Si `cantidad > disponibles` → warning inline `AlertTriangle` "Cantidad supera el stock disponible".

4. **`components/pos/checkout-panel.tsx`** — `aside` con 4 bloques apilados:
   - **Cliente**: `<select>` nativo con `User` icon + chevron, opciones incluyen `nombre · debe USD X` si `saldoFiado > 0`. Debajo: "Saldo pendiente: USD X" si aplica.
   - **Método de pago**: grid 4 columnas con 4 tabs `Efectivo | Tarjeta | Transfer. | Fiado`. Fiado usa `accent` (naranja) activo; los demás `primary` (verde). Iconos: `Banknote | CreditCard | Smartphone | Notebook`.
   - **Efectivo recibido** (solo si `metodo === 'efectivo'`): input numérico grande + chips `[Exacto, USD 20, USD 50, USD 100]` (filter-unique, Exacto solo si total > 0) + label "Vuelto: USD X" o "Insuficiente" en rojo.
   - **Fiado** (solo si `metodo === 'fiado'`): banner "Se sumará USD X a la cuenta de Nombre" o "Selecciona un cliente para registrar el fiado" si cliente es `c0` (Cliente General).
   - **Totales**: subtotal `(N art.)`, descuento (oculto si 0), total grande.
   - **CTA**: botón full-width "Cobrar" o "Registrar fiado" si fiado. Disabled si `itemsCount === 0 || faltaCliente || recibido < total` (en efectivo).

**5. `lib/pos-data.ts`** — `Producto {id, nombre, codigo, precio, lote, vence, disponibles, categoria}`, `ItemVenta = Producto & {cantidad}`, `Cliente {id, nombre, documento, saldoFiado}`, 12 productos y 4 clientes hardcoded (incluyendo `c0` = "Cliente General" con documento `—` y saldoFiado `0`).

## Tabla de mapeo: diseño → implementación actual

| Elemento del diseño | Implementación actual | Verdict |
|---|---|---|
| Header (Caja + Cajero) | `x-app-layout` con title "POS" + subtitle largo + 2 botones (Historial / Venta completa) | **REPLACE** — drop `x-app-layout`, shell standalone con header propio |
| Buscador "Buscar por nombre, código o categoría" | Input + dropdown de resultados (vanilla JS) | **REPLACE** — grid de cards con stock chip |
| Catálogo grid con stock chip | No existe (solo dropdown de resultados) | **REPLACE** |
| Cart panel con CartItem por línea | Tabla de líneas con qty input + subtotal | **REPLACE** — cards apiladas |
| `Lote X · Vence MM/AAAA` por producto | No se muestra (stock agregado) | **REPLACE** — necesita regla de "lote principal" |
| `disponibles` (suma por variante) | `available_sale_units` por presentación en línea | **REUSE** — misma fuente (`InventoryLot` agregado) |
| "Agotado" cuando `disponibles <= 0` | No se bloquea agregar (warning inline en línea) | **REPLACE** — botón disabled |
| `categoria` string por producto | `Product.category.name` (FK) | **REUSE** — eager load |
| `precio` (decimal) | `SalePrice.price_amount` (cents) | **REUSE** — convertir a decimal con `Money::centsToDollars` |
| Client dropdown + filtro inline | Typeahead contra `GET /pos/customers/search` (Alpine store) | **REPLACE** — user decision: dropdown con búsqueda inline (no typeahead libre) |
| `Cliente.documento` | **NO EXISTE** en modelo | **UNCERTAIN** — ver Open Q1 |
| `Cliente.saldoFiado` | Aggregate de `Receivable` (no denormalizado) | **UNCERTAIN** — ver Open Q2 |
| 4 payment tabs (Efectivo/Tarjeta/Transfer./Fiado) | 3 buttons (Efectivo/Transferencia/Mixto) + flag fiado | **REPLACE** — Mezcla cambia: Tarjeta out, Mixto out, Fiado entra como método |
| Input "Efectivo recibido" + quick amounts + Vuelto | Panel "Ingresar monto recibido" dentro de sidebar | **REPLACE** — vive dentro de checkout panel cuando metodo=efectivo |
| Banner Fiado "Se sumará USD X a la cuenta de N" | Panel "Convertir a fiado" + confirm checkbox | **REPLACE** — sin checkbox de confirmación (diseño lo trata como método pleno) |
| Totales (subtotal + descuento + total) | Total solo + 2 líneas (pagado/pendiente si fiado) | **REPLACE** — totales completos |
| Botón "Cobrar" / "Registrar fiado" | "Cobrar efectivo/transferencia/pago mixto" | **REPLACE** — label dinámico |
| Toast `aviso` 3.5s | `session('status')` flash (recarga página) | **REPLACE** — toast inline sin recarga |
| "Vaciar" en header de carrito | No existe (botones +/− y "Quitar" por línea) | **REPLACE** — botón en header |
| "Anular venta" en carrito (no cobradas) | "Continuar en venta completa" como escape | **REPLACE** — per user decision, scope = non-cobradas |
| Currency "USD 2.50" | "$2.50" (hardcoded) | **REPLACE** — usar `Intl.NumberFormat` |
| Empty state "Agrega productos para iniciar la venta" | "Todavía no agregas productos..." | **REPLACE** — texto exacto del diseño |
| Pin icon por panel | Sí, en cada botón contextual | **DELETE** — diseño no tiene concepto de pin |
| `.used` indicator | Sí, dot indigo en botones usados | **DELETE** — diseño no tiene equivalente |
| Vertical layout acotado (`max-h-[calc(100vh-12rem)]`) | Sí, wrapper de los 4 paneles | **DELETE** — diseño es single-page con checkout panel always-visible |
| Sección "Fase 2 parcial" (bullet list) | Sí, en sidebar | **DELETE** — fuera de scope de UI |
| Warning "No hay una caja abierta" en amber | Sí, en la vista | **UNCERTAIN** — ver Open Q6 (caja abierta requirement se mantiene en backend) |
| `fiado_auto_enabled` setting | Sí, gate del fiado en store + backend | **UNCERTAIN** — diseño no lo refleja, mantener en backend? |
| 4 specs pos-ux-refinements | Vivas en `openspec/specs/` | **DELETE/SUPERSEDE** — ver Risks |
| Tests `backend/tests/Feature/Pos*Test.php` (6 archivos) | Cubren pos-ux-refinements | **REWRITE/DELETE** — markup ya no existe |

## Fit del modelo de datos

Diseño quiere campos **planos** por producto (`precio`, `lote`, `vence`, `disponibles`, `categoria`) y por cliente (`documento`, `saldoFiado`). xpos001 tiene un modelo **normalizado** con variantes, presentaciones, precios históricos y receivables.

**Mapeo:**

| Diseño | xpos001 | Cómo resolverlo |
|---|---|---|
| `Producto.id` | `SalePresentation.id` (la unidad de venta es la presentación, no el producto) | Mantener `id` como presentation_id (es la "cosa" que se vende) |
| `Producto.nombre` | `Product.name + ' — ' + Variant.name + ' — ' + Presentation.name` | Concatenar en query (ya se hace en `presentationOptions.label`) |
| `Producto.codigo` | `ProductVariant.barcode` (o `Product.internal_code` como fallback) | Eager load ambos |
| `Producto.precio` | `SalePrice.price_amount` (cents, vigente = `ends_at IS NULL` o más reciente) | Convertir con `Money::centsToDollars` |
| `Producto.lote` | `InventoryLot.code` (no existe) → usar `id` o el más próximo a vencer | **Open Q4** — regla de "lote principal" |
| `Producto.vence` | `InventoryLot.expiration_date` (puede ser null) | Mostrar `—` si null (igual que en pos-data.ts) |
| `Producto.disponibles` | `SUM(InventoryLot.available_quantity) / SalePresentation.conversion_factor` | Ya implementado en `PosController::index` (`availableBaseUnitsByVariant`) |
| `Producto.categoria` | `Product.category.name` (FK) | Eager load `variant.product.category` |
| `ItemVenta.cantidad` | `SaleItem.quantity` (decimal:3) | Usar entero para coincidir con diseño (cliente lo tipea como int) |
| `Cliente.id === 'c0'` (Cliente General) | No existe sentinel | **Open Q3** — fila sintética vs estado vacío |
| `Cliente.nombre` | `Customer.name` | Directo |
| `Cliente.documento` | **NO EXISTE** | **Open Q1** — agregar columna o usar `phone` como proxy |
| `Cliente.saldoFiado` | Aggregate de `Receivable.pending_amount WHERE status='open'` | **Open Q2** — compute live o denormalize |

**Implicancia de dominio:** el `StorePosSaleRequest` actual valida `payment_method ∈ {cash, transfer, mixed}`. El diseño exige aceptar `fiado` como método pleno (no como modificador). Esto **rompe el invariante** `cash + received < total + allow_credit_sale → receiva` y lo reemplaza por: `fiado` directo → genera receivable por el total, sin efectivo recibido, con cliente obligatorio. La lógica de `PosSaleDraftBuilder` debe reescribirse para soportar los 3 métodos como caminos independientes en lugar de `cash` con flag de fiado.

## Estrategia de port a Blade + Alpine

| Patrón React | Patrón Blade + Alpine |
|---|---|
| `useState` | `x-data="{ items: [], cliente: null, metodo: 'efectivo', ... }"` |
| `useMemo` (subtotal, itemsCount, vuelto) | `x-data` getter en el store, recálculo automático por reactividad |
| `useEffect` / `setTimeout(3500)` | `x-init` + `setTimeout(() => aviso = null, 3500)` |
| Componente (`.tsx`) | Partial Blade (`_product-search.blade.php`, `_cart-item.blade.php`, `_checkout-panel.blade.php`) o `<x-pos.product-search />` component |
| Tabs (click → state change → x-show) | `<button @click="metodo = 'efectivo'">` + `<div x-show="metodo === 'efectivo'">` |
| Dropdown nativo `<select>` | HTML `<select>` con `<option x-for>` + filtro inline en input separado (`x-show` sobre lista) |
| lucide-react (`import { Search } from 'lucide-react'`) | **Inline SVG** (estilo pin icon actual) o **Heroicons** (Breeze ya los usa). Decisión: Heroicons para consistencia. |
| Tailwind 4 oklch CSS vars (`bg-primary`) | Tailwind 3 utility classes (`bg-emerald-400`, `bg-amber-500`) + custom CSS vars en `app.css` para los 3 colores clave del diseño (primary, accent, destructive) |
| `"use client"` (Next.js) | Alpine store `posApp` (reemplaza `posSidebar`) — el store ahora es el **estado completo** del POS, no solo el sidebar |
| `useState` items[] con map inmutable | Alpine reactive array: `items.push({...p, cantidad: 1})` y `items = items.filter(...)` |
| `setItems(prev => prev.find(i => i.id === p.id) ? ...)` | `agregar(p) { existe = items.find(...); if (existe) existe.cantidad++; else items.push(...) }` |
| `Intl.NumberFormat('es-HN', {currency:'USD'})` | JS: `new Intl.NumberFormat('es-HN', {style:'currency', currency:'USD'})` con `Intl` ya disponible |
| `await fetch(...)` (Next.js async) | `await fetch('/pos/customers/search?q=...')` con `@input.debounce.300ms` (ya patrón existente) |

**Reemplazo de archivos JS:**
- `pos-sidebar-store.js` (174 líneas) → **`pos-app-store.js`** (nuevo) con el estado completo del POS
- `app.js` — actualizar import: `registerPosSidebarStore` → `registerPosAppStore`
- Eliminar: `.used` class binding, `isButtonActive`, `isButtonUsed`, `usedPanels`, `pinnedPanels`, `markUsed`, `togglePin`, `handleCreditToggle`, `clearCreditSale`, `fiadoAutoEnabled` (mover al backend como gate)

**Reemplazo de CSS:**
- Eliminar `.used` y `.used::after` de `app.css`
- Agregar custom properties: `--pos-primary: oklch(0.87 0.19 118)` (verde lima) o fallback `theme(colors.emerald.400)`, `--pos-accent: oklch(0.75 0.15 55)` o `theme(colors.amber.500)`, `--pos-destructive: oklch(0.58 0.22 27)` o `theme(colors.red.500)`

## Preguntas abiertas / riesgos

### CRITICAL
1. **`Cliente.documento` no existe.** El modelo `Customer` solo tiene `name, phone, address, notes, is_active`. Opciones: (a) agregar columna `document` con migración, (b) mostrar `phone` en lugar de `documento`, (c) drop del display. **Preguntar al usuario.**
2. **Fiado como método de pago pleno rompe el invariante de dominio actual.** `PosSaleDraftBuilder` y `StorePosSaleRequest` tratan fiado como `cash + received < total + allow_credit_sale`. El diseño lo trata como camino independiente: `fiado` directo → receivable por el total, cliente obligatorio, sin efectivo. Decidir si se mantiene el flag `allow_credit_sale` para cash o se elimina.

### WARNING
3. **`Cliente.saldoFiado` requiere agregado en cada load del POS.** Con N receivables por cliente, el dropdown puede ser lento. Alternativa: denormalizar `customers.pending_receivable_amount` y actualizar en eventos de venta/abono.
4. **Cliente General (`c0`).** El diseño tiene un sentinel "Cliente General" con documento `—` y saldo `0`. xpos001 no tiene ese concepto. Opciones: (a) fila sintética (crear en seeder o en runtime), (b) estado "sin cliente" en el dropdown (default vacío).
5. **"Lote + Vence" por producto con múltiples lotes.** El diseño muestra 1 lote; xpos001 puede tener varios `InventoryLot` por variante. Regla: (a) más próximo a vencer, (b) más antiguo, (c) primero en llegar. **Preguntar al usuario.**
6. **Caja abierta requirement se mantiene en backend pero el diseño no avisa.** `PosController::store` rechaza ventas sin caja abierta. El diseño no muestra banner. Mantener el guardrail sin UI = mala UX. Decidir: (a) agregar banner discreto (estilo actual), (b) relajar el requirement para el nuevo POS, (c) hard fail con toast de error.
7. **Anular venta en carrito (non-cobradas).** "Anular venta" del diseño vive en el cart header. El cart es state local, no hay venta persistida todavía. ¿Qué hace? Opciones: (a) `limpiar()` — igual que "Vaciar", (b) `limpiar() + reset cliente + reset metodo` — "anulación total del intento". **Preguntar al usuario.**

### SUGGESTION
8. **`session('status')` flash + toast 3.5s.** Ambos pueden coexistir (toast se muestra en la misma vista sin recarga, flash sobrevive un redirect). Si la respuesta es AJAX, no hay flash. Decidir: el `store` debe ser AJAX (toast inline) o form submit (flash + redirect)? El diseño sugiere AJAX (no hay form en `page.tsx`).
9. **Heroicons vs lucide-react.** Decisión: usar Heroicons outline (Breeze ya los tiene) en lugar de lucide para evitar dependencias. SVGs disponibles en `vendor/blade-ui-kit/blade-heroicons`.
10. **Spec `fiado-auto-config` actual.** Existe `openspec/specs/fiado-auto-config/spec.md` que define el setting `fiado_auto_enabled`. Con fiado como método pleno, ese setting podría perder sentido (o se reinterpreta como "permitir fiado en POS" en lugar de "auto-activar"). **Decidir antes de la spec.**

### FUERA DEL PRIMER SLICE (explícito)
- Tarjeta — user decision
- Mixto — user decision (opción 2)
- Anulación post-cobrada — user decision (sale void ya existe en backend, no se expone UI)
- Pin panels — eliminado
- `.used` indicator — eliminado
- "Continuar en venta completa" escape hatch — sin equivalente en diseño, decidir si se mantiene como link de fallback
- Manual price override — diseño no lo expone
- Discount — diseño lo tiene hardcoded a 0
- Foto de producto — diseño no muestra imágenes
- Dark mode — fuera de scope (el diseño tiene variables dark pero el seed no las activa)
- Mobile-first — diseño es desktop-first
- Livewire migration — fuera de scope
- High-velocity customer creation — ya fuera de pos-ux-refinements Q2

## Scope del primer slice

### IN
- Reemplazo total de `backend/resources/views/pos/index.blade.php` con la UI del diseño portada a Blade + Alpine
- Tres partials nuevos (o un solo view grande con secciones): catálogo, carrito, checkout panel
- Nuevo Alpine store `posApp` (o equivalente) con estado: `items, cliente, metodo, recibido, aviso, computed (subtotal, itemsCount, vuelto)`
- 3 tabs de pago: Efectivo, Transfer., Fiado. Sin Tarjeta, sin Mixto
- Dropdown de cliente con búsqueda inline (filter on `<select>` o custom Alpine)
- Input de efectivo recibido con chips de montos rápidos (Exacto, USD 20, USD 50, USD 100) + label "Vuelto" / "Insuficiente"
- Banner Fiado ("Se sumará USD X a la cuenta de N" / "Selecciona un cliente")
- Botón "Cobrar" / "Registrar fiado" full-width, con disabled state
- Botón "Vaciar" en header del carrito
- Botón "Anular venta" en header del carrito (solo si `items.length > 0`)
- Empty state "Agrega productos para iniciar la venta"
- Toast inline 3.5s con mensaje de éxito
- Formato de moneda `USD X.XX` (Intl.NumberFormat es-HN)
- Backend: `StorePosSaleRequest` acepta `payment_method ∈ {cash, transfer, fiado}`, rechaza `mixed` y `tarjeta`
- Backend: `PosSaleDraftBuilder` reescrito para los 3 métodos como caminos independientes (fiado → receivable por el total sin cash; cash y transfer sin flag de crédito)
- Backend: `PosController::store` mantiene requirement de caja abierta (decidir UX warning en Open Q6)
- Endpoint `GET /pos/customers/search` extendido para devolver `documento` (si se agrega columna) y `saldo_fiado` (computado)
- Eliminar `pos-sidebar-store.js`, `pos-sidebar-state`, `pos-contextual-buttons-state`, `pos-panel-reactivation`, `pos-client-typeahead`, `pos-sidebar-vertical-layout` (specs y store JS)
- Eliminar `.used` de `app.css`
- Reescribir 6 archivos de test en `backend/tests/Feature/Pos*Test.php`

### OUT
- Tarjeta (user decision)
- Mixto (implícito por user decision "solo quita tarjeta" interpretado en diseño: 3 tabs, no 4 con tarjeta)
- Anulación post-cobrada (user decision — el botón "Anular venta" en cart es para non-cobradas)
- Pin panels (sin equivalente en diseño)
- `.used` indicator (sin equivalente en diseño)
- High-velocity customer creation (Q2 pos-ux-refinements)
- Discount / manual price override / product photos (diseño no los expone)
- Dark mode (fuera de scope)
- Mobile-first / responsive perfeccionado (diseño es desktop)
- Livewire, Stimulus, htmx (mantener Blade + Alpine)
- Cambios al `VoidSaleService` (no se toca; sigue disponible para admins en `sales.show`)

## Estrategia de tests

**Nuevos (cubren la nueva UI):**
1. `backend/tests/Feature/PosV2IndexTest.php` — snapshot del Blade renderizado: header "Caja", search input con placeholder "Buscar por nombre...", 3 tabs (Efectivo, Transfer., Fiado, ausencia de Tarjeta), cart header con "Venta actual 0", empty state con texto exacto del diseño, currency "USD" en el render
2. `backend/tests/Feature/PosV2SaleTest.php` — rewrite de `PosFlowTest` para 3 métodos: cash simple, transfer simple, fiado con cliente obligatorio, fiado con cash session cerrado → error
3. `backend/tests/Feature/PosV2CustomerSearchTest.php` — extender `PosCustomerSearchTest` para incluir `documento` y `saldo_fiado` en la respuesta; assert dropdown options incluyen "debe USD X" inline
4. `backend/tests/Feature/PosV2PaymentValidationTest.php` — `StorePosSaleRequest` rechaza `mixed` y `tarjeta` con 422
5. `backend/tests/Feature/PosV2StoreMigrationTest.php` — assert `pos-sidebar-store.js` no existe, `pos-app-store.js` existe, `Alpine.store('posApp')` se registra
6. `backend/tests/Feature/PosV2VoidAnularTest.php` (TBD per Open Q7) — POST "anular" del cart → cart cleared (validar comportamiento una vez definido)
7. `backend/tests/Feature/PosV2TarjetaAbsentTest.php` — assert que el método `tarjeta` no se renderiza ni se acepta

**A eliminar (superseded por la nueva UI):**
- `PosSidebarStoreTest.php` — markup de `posSidebar` no existe
- `PosSidebarLayoutTest.php` — wrapper `max-h-[calc(100vh-12rem)]` no existe
- `PosSidebarReactivationTest.php` — `usedPanels`/`markUsed` no existe
- `PosSidebarReceivedCreditBindingsTest.php` — paneles `received`/`credit` no existen

**A extender (mantener tests de auth/contract):**
- `PosCustomerSearchTest.php` — mantener tests de endpoint (auth, shape) pero actualizar shape para incluir `documento` y `saldo_fiado`; eliminar el snapshot Blade de typeahead (ya no aplica)
- `PosFlowTest.php` — reemplazar wholesale por `PosV2SaleTest`

**Checklist manual (sin E2E):**
- Click en card de producto → suma a cart, incrementa qty si ya existe
- Stock badge: `48 disp.` (verde), `7 disp.` (amber), `0 disp.` (rojo "Agotado" + card disabled)
- Cart: input de qty acepta número, `+`/`−` funcionan, trash icon remueve
- qty > disponibles → warning inline naranja en la línea
- Abrir dropdown de cliente → ver lista, escribir → filtrar, seleccionar → label arriba cambia
- Cliente con saldo → opción dice "Nombre · debe USD X", debajo muestra "Saldo pendiente: USD X"
- Tab Efectivo → input + chips aparecen, escribir 10 → "Vuelto: USD 3.80" si total es 6.20
- Tab Transfer. → se ocultan input/chips/vuelto, banner cash session sigue si aplica
- Tab Fiado → aparece banner "Se sumará USD X a la cuenta de N" o "Selecciona un cliente" si `c0`/vacío
- Sin cliente + tab Fiado → botón "Registrar fiado" disabled
- Click "Cobrar" en efectivo con todo OK → toast verde 3.5s "Venta cobrada: USD X (efectivo).", cart vacía, totales a 0, cliente preservado, metodo vuelve a efectivo
- Click "Registrar fiado" con cliente OK → toast "Fiado registrado a N por USD X.", cart vacía, cliente se resetea a default
- Click "Vaciar" → cart vacía, totales a 0, todo lo demás intacto
- Click "Anular venta" → cart vacía, totales a 0, cliente preservado, metodo intacto (TBD per Open Q7)
- Empty state aparece solo cuando `items.length === 0`

## Risks (resumen ejecutivo)

- **CRITICAL**: las 5 specs de pos-ux-refinements se invalidan wholesale. No hay migración parcial posible — el paradigma cambia.
- **CRITICAL**: `Customer.documento` y `Customer.saldoFiado` no existen; la decisión de cómo resolverlos bloquea el proposal.
- **WARNING**: los 6 tests `Pos*Test.php` existentes rompen cuando se reemplaza el Blade. Deben reescribirse antes o junto con el view replacement, sino `composer test` muestra failures visibles en el primer commit.
- **WARNING**: `currentCashSession` requirement se mantiene pero el diseño no avisa — riesgo de UX confusa.
- **SUGGESTION**: usar Heroicons (Breeze) en vez de lucide-react para no introducir assets nuevos.

## Ready for Proposal

**Sí**, condicional a que el orchestrator pregunte al usuario las 7 open questions (especialmente 1, 2, 3, 5, 7) antes de la spec. Las decisiones de scope (IN/OUT) están claras con las 5 decisiones locked por el usuario. El siguiente paso es `sdd-propose` con un proposal que:
1. Archive las 5 specs pos-ux-refinements como superseded
2. Cree la nueva spec `pos-v2` (o varias) cubriendo el comportamiento del nuevo store Alpine + 3 métodos de pago
3. Decida las open questions o las marque como "exploration gaps" en la propuesta
4. Plantee un plan de PRs (probablemente 1 PR grande + cleanup, o 2-3 PRs apilados) con TDD estricto
