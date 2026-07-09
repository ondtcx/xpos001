# POS v2 — Checkout Specification

> **Supersedes** (archived under `_superseded/pos-ux-refinements-originals/`): the 5 pos-ux-refinements specs — the 4 contextual buttons are gone; folded into 3 tabs + the customer dropdown + Cobrar.

## Purpose

Define the right-column checkout panel: 3 payment tabs, conditional inputs, totals, and Cobrar (AJAX, no caja abierta check, Fiado → credit path).

## Requirements

### Requirement: Three Payment Tabs

The panel MUST show exactly three tabs: **Efectivo**, **Transfer.**, **Fiado**. No "Tarjeta" tab. Exactly one is active.

#### Scenario: Tabs render and switch

- GIVEN the panel is rendered
- THEN the tabs MUST appear in the order Efectivo, Transfer., Fiado AND no "Tarjeta" MUST be rendered
- AND clicking a tab MUST switch the active one

### Requirement: Efectivo Tab

When "Efectivo" is active, the system MUST show a numeric "Efectivo recibido" input, three chips (`USD 20`, `USD 50`, `USD 100`), and a "Vuelto" label `max(0, recibido − total)` updating live.

#### Scenario: Vuelto, quick amount, non-negative

- GIVEN total "USD 6.20" and the user types "10" in recibido
- THEN "Vuelto" MUST display "USD 3.80"
- AND the "USD 20" chip MUST fill the input with "20"
- AND recibido "5" MUST display "Vuelto USD 0.00" (never negative)

### Requirement: Transfer. Tab

When "Transfer." is active, the system MUST NOT render any amount input, chips, or "Vuelto" label.

#### Scenario: Transfer. hides the cash UI

- GIVEN "Transfer." is active
- THEN the recibido input, chips, and "Vuelto" label MUST NOT be visible

### Requirement: Fiado Tab — Requires a Non-General Cliente

When "Fiado" is active, the selected cliente MUST NOT be `Cliente General`. If it is, the tab MUST be disabled with the hint "Seleccioná un cliente para fiar". Otherwise it MUST show "Se sumará USD X.XX a la cuenta de {nombre}".

#### Scenario: Fiado enabled/disabled

- GIVEN cliente is "Cliente General"
- WHEN the user activates the Fiado tab
- THEN the Fiado tab MUST be disabled AND the hint MUST be visible

- GIVEN cliente "María Gómez" and cart total "USD 12.50"
- THEN the message MUST read "Se sumará USD 12.50 a la cuenta de María Gómez"

### Requirement: Totals Display

The panel MUST display "Subtotal (N art.)" (N = total quantity) and "Total" (= subtotal). No discount line. Money uses the `USD X.XX` / `USD X,XXX.XX` en-US format.

#### Scenario: Subtotal, total, no discount

- GIVEN the cart totals USD 7.30 across 3 units
- THEN the display MUST show "Subtotal (3 art.)" and "Total USD 7.30" AND no discount line MUST be visible

### Requirement: Cobrar Button Disabled States

Cobrar MUST be disabled when the cart is empty OR (active tab is "Fiado" AND cliente is "Cliente General"). Otherwise enabled.

#### Scenario: Empty cart disables Cobrar

- GIVEN the cart is empty
- WHEN the panel renders
- THEN the Cobrar button MUST be disabled


#### Scenario: Success — Efectivo (no cash session) resets state

- GIVEN a non-empty cart, metodo "efectivo", no cash session, backend returns 200 OK
- WHEN the user clicks Cobrar
- THEN a green toast MUST appear reading "Venta cobrada: USD 6.20 (efectivo)" AND disappear after 3.5s AND the cart MUST be empty AND checkout MUST reset

#### Scenario: Success — Fiado creates a receivable

- GIVEN active tab "Fiado", cliente "María Gómez", cart total USD 12.50, backend returns 200 OK
- WHEN the user clicks Cobrar
- THEN a `Sale` MUST be created with the credit-sale flag AND a `Receivable` MUST be created for USD 12.50 against "María Gómez" AND the cart MUST reset

#### Scenario: Validation failure shows red toast

- GIVEN a non-empty cart and the backend returns 422
- WHEN the user clicks Cobrar
- THEN a red toast MUST appear with the error AND cart and checkout state MUST remain unchanged
