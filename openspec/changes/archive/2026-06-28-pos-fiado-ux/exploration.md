## Exploration: POS Fiado UX Improvements

### Current State

The POS module (`backend/resources/views/pos/index.blade.php`) is a single-page Blade view with vanilla JavaScript (no Livewire/Alpine for the POS itself — Livewire is installed but not used in this view). It uses a 2-column grid layout (`xl:grid-cols-[minmax(0,1fr)_22rem]`): main content on the left (search + lines table), sidebar on the right (summary + action buttons + contextual panels + checkout).

The JavaScript manages state via DOM element reads (`input.value`, `classList.toggle`) rather than a centralized state object. There are four toggle buttons with associated panels:

1. **Asignar cliente** (`#toggle-customer`) → `#pos-customer-panel` — plain `<select>` dropdown listing all active customers.
2. **Cambiar método** (`#toggle-payment-methods`) → `#pos-payment-methods-panel` — payment method choices (cash/transfer/mixed).
3. **Ingresar monto recibido** (`#toggle-received-amount`) → `#pos-received-panel` — numeric input for received cash amount + change preview.
4. **Convertir a fiado** (`#toggle-credit-sale`) → `#pos-credit-panel` — credit sale activation with checkbox confirmation.

**Fiado state** is managed by a hidden input (`#pos-allow-credit-sale`, value `"0"` or `"1"`). The credit toggle is the only button that visually reflects its state (text changes between "Convertir a fiado" / "Fiado activado", and its CSS class changes between amber-50/amber-800 and amber-100/amber-900).

**Key behaviors discovered:**
- `clearReceivedAmount()` (line 468) resets the received amount value AND hides the panel when toggling off — data is lost.
- `clearCreditSale()` (line 477) resets `allow_credit_sale` to "0", unchecks checkbox, hides both panel AND summary.
- The `receivedAmountInput` listener (line 807) **automatically activates** fiado when `received < total` — this conflates "user intent" with "data-driven inference", which is part of the UX confusion.
- The `creditToggle` click handler (line 760) **toggles fiado on/off** — clicking it when fiado is already active DEACTIVATES it. This means if you activate fiado, then open the customer panel, then click "Fiado activado" again expecting to see the panel, you inadvertently deactivate fiado.

**Backend validation** lives in `PosSaleDraftBuilder::toCreateSalePayload()` and `StorePosSaleRequest`, both correctly enforce: customer required for credit, confirmation required, credit only from cash payment.

**Tests** in `PosFlowTest.php` cover: simple cash, received amount, credit sale with partial cash, full credit, missing customer, missing confirmation, transfer, mixed payments, stock warnings, full sale transition. All pass.

### Affected Areas

- `backend/resources/views/pos/index.blade.php` — Entire POS UI. All JavaScript state management, panel toggling logic, button rendering, and layout live here.
- `backend/app/Http/Controllers/PosController.php` — May need context passing adjustments (e.g., customer search options, JS data bootstrapping).
- `backend/app/Support/Sales/PosSaleDraftBuilder.php` — No changes expected; backend validation is already correct.
- `backend/app/Http/Requests/Sales/StorePosSaleRequest.php` — No changes expected; validation rules are correct.
- `backend/tests/Feature/PosFlowTest.php` — May need new tests if backend changes occur.
- `openspec/specs/pos/` — Should be created if we want source-of-truth specs for ongoing POS work.

### Approaches

1. **Centralized state management via Alpine.js** — Replace vanilla JS with Alpine.js `x-data` component that holds all state (creditActive, receivedAmount, customerSelected, paymentMethod, panelVisibility). Derive button CSS classes reactively.
   - Pros: Removes state fragmentation, reactive button styling, panels stay visible when feature is active, panel hide/show doesn't reset data.
   - Cons: Requires refactoring ~600 lines of vanilla JS, learning curve for Alpine patterns in this codebase, risk of breaking existing behaviors.
   - Effort: Medium (estimated 3-4 focused sessions)

2. **Targeted vanilla JS fixes** — Keep vanilla JS but fix each specific UX complaint: make all buttons reflect active state via CSS classes, prevent data loss on panel toggle, decouple "panel visibility" from "feature activation", add searchable customer selector.
   - Pros: Minimal refactoring, lower risk, can be delivered incrementally.
   - Cons: Does not address the fragmented state management root cause; each new feature will require more event binding.
   - Effort: Low-Medium (estimated 1-2 sessions)

3. **Livewire component decomposition** — Extract POS into Livewire components (e.g., `PosProductSearch`, `PosLineList`, `PosSidePanel`) with proper state management.
   - Pros: Best long-term architecture, server-side state management, easier testing.
   - Cons: Major overhaul, large diff, risk of breaking the working POS, Livewire is installed but not yet proven in this codebase for real-time UIs.
   - Effort: High (estimated 5+ sessions)

4. **Hybrid: Alpine.js for state + targeted fixes** — Introduce a minimal Alpine.js `x-data` component around the sidebar (the core UX pain area) to manage button states and panel visibility reactively. Keep the rest of the POS as-is.
   - Pros: Addresses the root cause for the problematic area without a full rewrite, reactive styling on buttons, panels preserve state when hidden.
   - Cons: Two patterns coexist in the same page (vanilla JS + Alpine), requires careful boundary management, Alpine integration must respect existing form submission flow.
   - Effort: Medium (estimated 2-3 sessions)

### Recommendation

**Approach 4 (Hybrid: Alpine.js for sidebar state)** is the recommended path.

Rationale:
- The core UX debt is concentrated in the sidebar: button state visualization, panel visibility, and feature activation. These are exactly what Alpine.js solves well with reactive bindings.
- The search and line management (left column) work correctly and don't need rewriting.
- A full Livewire refactor would be premature for this concentrated pain point.
- Targeted vanilla JS fixes (Approach 2) would work but defer the real issue: scattered state that doesn't know itself.

The Alpine migration should:
1. Wrap the sidebar `<aside>` in an `x-data` component with state: `{ creditActive: false, receivedAmount: '', customerActive: false, paymentMethod: 'cash', ... }`
2. Bind button classes reactively (`x-bind:class`) to reflect active/inactive states for ALL four toggle buttons
3. Decouple "panel visibility" from "feature activation" — a closed panel should NOT reset feature state
4. Add a `x-transition` for panel open/close to communicate state changes visually
5. Replace the customer `<select>` with a searchable input (either Alpine-filtered or a select2-like behavior)

### Risks

- **Alpine + vanilla JS coexistence**: The search results and line management JS interacts with total calculation, which feeds into the credit/received logic in the sidebar. Must carefully wire Alpine state back into the existing `refreshSummary()` function.
- **Form submission flow**: The POS form submits with hidden inputs (`#pos-allow-credit-sale`, `#pos-payment-method`). Alpine state must keep these synced — or replace them with Alpine-bound inputs.
- **Existing test impact**: No backend tests should break if the backend validation is untouched. Could add frontend test coverage later.
- **Layout overflow**: Even with correct state management, the sidebar stacking problem persists. May need a follow-up to evaluate scroll-containers or accordion patterns.
- **Delivery strategy**: The total change may exceed 400 lines. Recommend splitting PRs: (1) Alpine sidebar state + button consistency, (2) customer search improvement, (3) layout/lateral space improvement.

### Ready for Proposal

Yes — the exploration is thorough and the gaps are well-documented. The orchestrator should tell the user: "Hice una exploración completa del POS. Encontré 4 áreas concretas de mejora: (1) consistencia visual de botones, (2) estado de paneles que no se pierde al alternar, (3) selector de cliente mejorado con búsqueda, (4) mejor uso del espacio lateral. Recomiendo arrancar con estado reactivo vía Alpine.js en el panel lateral para resolver los puntos 1 y 2, que son los que más mencionaste. Los puntos 3 y 4 se pueden planificar como PRs separados."
