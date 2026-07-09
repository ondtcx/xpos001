# POS v2 — Catalog Specification

> **Supersedes** (archived under `_superseded/pos-ux-refinements-originals/`): `pos-sidebar-state`, `pos-contextual-buttons-state`, `pos-panel-reactivation`, `pos-client-typeahead`, `pos-sidebar-vertical-layout` — the 4 contextual buttons + 4 collapsible panels are gone; the catalog is an always-visible grid with no sidebar and no typeahead.

## Purpose

Define the product catalog side of the new POS view: rendering, `Lote + Vence` selection (FEFO), search/filter, add-to-cart, and out-of-stock disabling.

## Requirements

### Requirement: Product Card Display

The system MUST render one card per active sale presentation. Each card MUST show product name, category, unit price formatted as `USD X.XX` (en-US style: `,` thousands, `.` decimals), and available stock in integer units.

#### Scenario: Card renders full data

- GIVEN "Coca-Cola 500 ml", price USD 0.75, 48 units available
- WHEN the catalog renders
- THEN the card MUST show name, category "Bebidas", price "USD 0.75", and stock "48 disp."

### Requirement: Lote + Vence Display (FEFO)

The system MUST display one lot per card using FEFO: from `inventory_lots`, pick the row with the nearest non-null `expiration_date` for the card's variant. Render `Lote L-XXXX · Vence MM/YYYY`.

#### Scenario: Multiple lots — nearest expiration wins

- GIVEN lots: id 10 expires 2027-06-30, id 11 expires 2026-09-15, id 12 expires 2027-02-28
- WHEN the card renders
- THEN the card MUST show `Lote L-11 · Vence 09/2026`

#### Scenario: Null-expiration lots are ignored for selection

- GIVEN lot 20 has `expiration_date IS NULL`, lot 21 expires 2026-09-15
- WHEN the card renders
- THEN the card MUST show `Lote L-21 · Vence 09/2026` (null-dated lot does not win)

### Requirement: Search / Filter Input

The system MUST provide a text input above the catalog grid. The input MUST filter cards client-side by case-insensitive substring match against name, internal code, or barcode. Empty input MUST show all cards.

#### Scenario: Filter narrows the list

- GIVEN 18 products loaded
- WHEN the user types "agua"
- THEN only matching cards MUST remain visible

#### Scenario: No matches shows an empty state

- GIVEN the user typed "xyz" with no match
- WHEN the filter applies
- THEN the grid MUST show "No se encontraron productos para xyz"

### Requirement: Add-to-Cart Action

Each card MUST provide an add-to-cart action. Invoking it on a card already in the cart MUST increment that line's quantity by 1. Invoking it on a card not yet in the cart MUST add a new line with quantity 1. The cart header "Venta actual N" MUST update immediately (see `pos-v2-cart`).

#### Scenario: Adding a new product creates a line

- GIVEN the cart is empty
- WHEN the user clicks the add action on "Coca-Cola 500 ml"
- THEN the cart MUST contain one line for Coca-Cola with quantity 1
- AND the cart header MUST show "Venta actual 1"

#### Scenario: Adding an existing product increments qty

- GIVEN the cart contains 2 Coca-Cola
- WHEN the user clicks the add action on Coca-Cola again
- THEN the cart MUST contain one line for Coca-Cola with quantity 3

### Requirement: Out-of-Stock Card Is Visually Disabled

When a product's available stock is 0 or below, the card MUST be visually disabled, the add action MUST NOT be callable, and the card MUST display an "Agotado" stock chip.

#### Scenario: Stock 0 disables the card

- GIVEN a product with available stock 0
- WHEN the catalog renders
- THEN the card MUST show an "Agotado" chip
- AND the add action MUST be disabled (clicking MUST NOT add a line)
