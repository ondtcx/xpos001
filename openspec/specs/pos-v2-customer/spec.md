# POS v2 — Customer Selector Specification

> **Supersedes** (archived under `_superseded/pos-ux-refinements-originals/`): `pos-sidebar-state`, `pos-contextual-buttons-state`, `pos-panel-reactivation`, `pos-client-typeahead`, `pos-sidebar-vertical-layout` — the customer selector is the first block of the always-visible checkout column; not a sidebar panel, no typeahead, no reactivation.

## Purpose

Define the customer selector at the top of the checkout panel: the dropdown, the inline search filter, the option list (with document and outstanding debt), the default selection (Cliente General), and keyboard navigation.

## Requirements

### Requirement: Cliente General Is the Default Selection

On initial render, the system MUST preselect the customer whose `is_default` flag is true. The seeder MUST create exactly one such row: `Cliente General` (id `c0`, document `—`, `is_default = true`).

#### Scenario: Exactly one default customer exists

- GIVEN the seeder has run
- WHEN the system queries `customers WHERE is_default = true`
- THEN exactly one row MUST be returned

### Requirement: Dropdown Options

The dropdown MUST list the default customer (Cliente General) first, followed by the three active customers from the seeder. Each option MUST display the name, the document (if present), and the outstanding balance if greater than 0, formatted as "debe USD X.XX".

#### Scenario: Options, debt shown, debt hidden

- GIVEN the seeder has run and created 4 customers
- WHEN the user opens the dropdown
- THEN the list MUST include 4 options, with Cliente General first

- GIVEN "María Gómez" has an open receivable with a positive pending amount
- THEN the "María Gómez" option MUST include a "debe USD X.XX" segment

- GIVEN "José Paredes" has no open receivable
- THEN the "José Paredes" option MUST NOT show "debe USD 0.00"

### Requirement: Inline Search Filter

The dropdown MUST provide a search input that filters options client-side by case-insensitive substring match against name or document. Empty input MUST show all options. Initial render MUST populate from `GET /pos/customers/search` (extended with `document` and `saldo_fiado`).

#### Scenario: Typing narrows the list

- GIVEN the dropdown is open with 4 options
- WHEN the user types "mar"
- THEN only options whose name or document contains "mar" MUST remain

### Requirement: Selection Updates the Checkout State

Selecting a customer (by click or Enter) MUST set the panel's `cliente` to that customer's id and name, and MUST close the dropdown. When the selected cliente is `Cliente General` (id `c0`), the Fiado tab MUST be disabled (per `pos-v2-checkout`).

#### Scenario: Re-selecting Cliente General disables Fiado

- GIVEN a non-General cliente is selected and metodo is "Fiado"
- WHEN the user selects "Cliente General" again
- THEN the Fiado tab MUST be disabled AND the hint "Seleccioná un cliente para fiar" MUST be visible

### Requirement: Keyboard Navigation

The dropdown MUST support keyboard navigation: `Arrow Down`/`Arrow Up` MUST move the highlight through visible options, `Enter` MUST select the highlighted option, `Escape` MUST close without changing the selection.

#### Scenario: Arrow, Enter, and Escape all behave

- GIVEN the dropdown is open with 4 options
- WHEN the user presses `Arrow Down` twice
- THEN the third option MUST be highlighted

- GIVEN the third option is highlighted
- WHEN the user presses `Enter`
- THEN that option MUST be selected (dropdown closed)

- GIVEN the user has navigated to the third option
- WHEN the user presses `Escape`
- THEN the dropdown MUST close AND the selected cliente MUST remain unchanged

### Requirement: Cliente General Row Must Be Seeded Before POS Can Run

The `MinimarketDemoSeeder` MUST create the `Cliente General` row (`is_default = true`, document `—`) before the POS can be used. The POS MUST refuse to render the checkout panel if this row is missing.

#### Scenario: POS refuses to render without the default customer

- GIVEN no `is_default = true` row exists in `customers`
- WHEN the user navigates to `/pos`
- THEN the panel MUST show "Falta el cliente por defecto" and MUST NOT render the dropdown
