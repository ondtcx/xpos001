# POS Sidebar State Specification

## Purpose

Manage reactive state for the POS sidebar: button visual consistency, panel data persistence, and accordion layout with pin support using Alpine.js.

## Requirements

### Requirement: Button State Consistency

The system MUST bind all four sidebar toggle buttons (fiado, received amount, client, payment method) to reactive Alpine.js state. Button visual appearance MUST reflect active/inactive state via CSS class binding.

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

The system MUST preserve panel data when a panel is closed and reopened. Data includes: received amount value, selected client, payment method selection.

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
