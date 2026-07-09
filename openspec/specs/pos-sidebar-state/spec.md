# POS Sidebar State Specification

## Purpose

Manage reactive state for the POS sidebar: button visual consistency, panel data persistence, accordion layout with pin support, and predictable panel reactivation, using a single Alpine store (`posSidebar`).

## Requirements

### Requirement: Button State Consistency

The system MUST vincular los 4 botones contextuales del sidebar (`Asignar cliente`, `Ingresar monto recibido`, `Convertir a fiado`, `Cambiar método`) a un único Alpine store (ver `pos-contextual-buttons-state`). La apariencia visual MUST usar `x-bind:class`, no `classList.toggle` imperativo.

#### Scenario: Toggle button shows active state

- GIVEN a sidebar toggle button is inactive
- WHEN the user clicks the button
- THEN the button MUST display the active visual state (e.g., highlighted background)
- AND the corresponding panel MUST open

#### Scenario: Toggle button shows inactive state

- GIVEN a sidebar toggle button is active
- WHEN the user clicks the button again
- THEN the button MUST display the inactive visual state
- AND the corresponding panel MUST close

#### Scenario: Multiple buttons maintain independent state

- GIVEN button A is active and button B is inactive
- WHEN the user clicks button B
- THEN button B MUST become active
- AND button A MUST remain active (unless accordion mode is enabled)

### Requirement: Panel Data Persistence

The system MUST preservar los datos del panel (monto recibido, cliente seleccionado, método de pago) al cerrar y reabrir. Los datos MUST vivir en el Alpine store en sesión (ver Q1 en `pos-contextual-buttons-state`); la UI MUST leerlos desde el store, no desde `input.value`.

#### Scenario: Received amount persists across toggle

- GIVEN the user entered "500" in the received amount panel
- WHEN the user closes the panel
- AND the user reopens the panel
- THEN the received amount field MUST display "500"

#### Scenario: Selected client persists across toggle

- GIVEN the user selected client "John Doe"
- WHEN the user closes the client panel
- AND the user reopens the panel
- THEN the client selector MUST display "John Doe"

### Requirement: Accordion Layout

The system MUST support an accordion layout where only one panel is open at a time by default. The system MAY allow pinning panels to keep multiple open.

#### Scenario: Accordion closes other panels

- GIVEN panel A is open and panel B is closed
- WHEN the user opens panel B
- THEN panel A MUST close automatically
- AND panel B MUST open

#### Scenario: Pinned panel stays open

- GIVEN panel A is pinned and open
- WHEN the user opens panel B
- THEN panel A MUST remain open
- AND panel B MUST also be open

#### Scenario: Pin toggle

- GIVEN a panel is open and not pinned
- WHEN the user clicks the pin icon
- THEN the panel MUST become pinned
- AND the pin icon MUST reflect pinned state

### Requirement: Panel Reactivation

The system MUST re-exponer un panel previamente abierto (con datos intactos) cuando el usuario activa su botón contextual tras cerrarlo, sin importar el orden en que otros paneles fueron abiertos/cerrados. Un botón cuyo panel fue usado al menos una vez en la sesión actual MUST mostrar hint `used` (distinto de `active`) cuando su panel está cerrado.

#### Scenario: Reabrir un panel previamente cerrado

- GIVEN el usuario abrió y cerró el panel `client`
- WHEN el usuario activa el botón `client` de nuevo
- THEN el panel `client` MUST re-aparecer en el sidebar
- AND sus datos (cliente seleccionado) MUST estar preservados

#### Scenario: Botón usado-pero-cerrado muestra hint `used`

- GIVEN el usuario abrió y cerró el panel `received_amount` después de ingresar un valor
- WHEN el POS está en estado estable
- THEN el botón `received_amount` MUST mostrar el hint visual `used`
- AND el hint MUST permanecer visible hasta que se recargue la página

#### Scenario: Botón nunca usado no muestra `used`

- GIVEN el usuario no activó `payment_method` durante la sesión
- WHEN el POS está en estado estable
- THEN el botón `payment_method` MUST NOT mostrar el hint visual `used`

#### Scenario: Sin botones inertes silenciosos

- GIVEN un botón contextual está en cualquier estado
- WHEN el usuario hace click
- THEN el click MUST tener un efecto visible (abrir, cerrar o togglear el panel)
- AND el click MUST NOT descartarse silenciosamente
