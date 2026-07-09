# POS v2 — Cart Specification

> **Supersedes** (archived under `_superseded/pos-ux-refinements-originals/`): `pos-sidebar-state`, `pos-contextual-buttons-state`, `pos-panel-reactivation`, `pos-client-typeahead`, `pos-sidebar-vertical-layout` — the cart is an always-visible block, not a sidebar panel; no buttons drive it, no reactivation, no typeahead.

## Purpose

Define the cart side of the new POS view: line rendering, quantity controls, removal, the "Venta actual N" header, the empty state, and the difference between "Vaciar" and "Anular venta".

## Requirements

### Requirement: Cart Line Rendering

Each cart line MUST display product name, unit price (`USD X.XX` en-US style), a quantity input with `−` and `+` buttons, the line total, and a remove (trash) button.

#### Scenario: Line shows full data

- GIVEN the cart contains 2 × Coca-Cola at USD 0.75
- WHEN the cart renders
- THEN the line MUST show name, unit price "USD 0.75", quantity 2, line total "USD 1.50", and a remove button

### Requirement: Quantity Controls

The system MUST provide `−` and `+` buttons and a numeric input. `−` MUST decrement by 1, flooring at 1 (never below). `+` MUST increment by 1. The input MUST accept manual entry. After any change, the line total and the "Venta actual N" header MUST update.

#### Scenario: Decrement floors at 1

- GIVEN a line with quantity 1
- WHEN the user clicks `−`
- THEN the line's quantity MUST remain 1
- AND the line MUST NOT be removed by the `−` action

### Requirement: Remove a Line

The system MUST provide a remove (trash) button on each line. Clicking it MUST remove the line and recompute totals.

#### Scenario: Trash button removes the line

- GIVEN the cart has 3 lines
- WHEN the user clicks the trash button on line 2
- THEN the cart MUST have 2 lines
- AND the cart header MUST reflect the new total quantity

### Requirement: Cart Header and Empty State

The cart header MUST display `Venta actual N` where `N` is the sum of quantities (0 when empty). When the cart has no lines, the cart area MUST show the empty state: a shopping-cart icon plus "Agrega productos para iniciar la venta".

#### Scenario: Header reflects sum; empty state appears when empty

- GIVEN line A qty 2 and line B qty 3
- WHEN the cart renders
- THEN the header MUST show "Venta actual 5"

- GIVEN the cart is empty
- THEN the empty state MUST be visible AND the header MUST show "Venta actual 0"

### Requirement: "Anular venta" Full Reset

The cart header MUST show an "Anular venta" button when the cart is non-empty. Clicking it MUST clear the cart AND reset the checkout state to defaults: cliente → `Cliente General`, metodo → `efectivo`, recibido → empty.

#### Scenario: Anular venta resets cart and checkout state

- GIVEN a non-empty cart, cliente "María Gómez", metodo "fiado", recibido "10.00"
- WHEN the user clicks "Anular venta"
- THEN the cart MUST be empty
- AND cliente MUST reset to `Cliente General`
- AND metodo MUST reset to `efectivo`
- AND recibido MUST be empty

### Requirement: "Vaciar" Clears Items Only

The cart header MUST also show a "Vaciar" action. Clicking it MUST clear all cart items but MUST NOT reset cliente, metodo, or recibido.

#### Scenario: Vaciar preserves checkout state

- GIVEN a non-empty cart, cliente "María Gómez", metodo "transfer."
- WHEN the user clicks "Vaciar"
- THEN the cart MUST be empty
- AND cliente MUST remain "María Gómez"
- AND metodo MUST remain "transfer."

### Requirement: Currency Formatting

All money values in the cart MUST be formatted as `USD X.XX` (below 1000) or `USD X,XXX.XX` (≥ 1000), with `,` thousands and `.` decimals. Negative values MUST render as `−USD X.XX`.

#### Scenario: Thousands separator activates above 1000

- GIVEN a line total of 1234.5
- WHEN the line is rendered
- THEN the line total MUST render as "USD 1,234.50"
