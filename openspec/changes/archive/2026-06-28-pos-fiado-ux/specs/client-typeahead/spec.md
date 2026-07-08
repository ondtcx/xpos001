# Client Typeahead Specification

## Purpose

Replace the current client `<select>` dropdown with a typeahead component that performs debounced server-side search to handle 100+ clients efficiently.

## Requirements

### Requirement: Server-Side Search

The system MUST provide a typeahead input that searches for clients via a server endpoint. The search MUST be debounced (~300ms) to avoid excessive requests.

#### Scenario: User types search query

- GIVEN the client typeahead input is focused
- WHEN the user types "John"
- THEN after 300ms debounce, the system MUST fetch matching clients from the server
- AND the dropdown MUST display clients whose name or document contains "John"

#### Scenario: No results found

- GIVEN the user types a query with no matching clients
- WHEN the server returns an empty result set
- THEN the dropdown MUST display "No clients found"

#### Scenario: Loading state

- GIVEN the user is typing a search query
- WHEN the request is in flight
- THEN the typeahead MUST display a loading indicator

### Requirement: Client Selection

The system MUST allow the user to select a client from the search results. Selection MUST update the POS state and close the dropdown.

#### Scenario: User selects a client

- GIVEN the dropdown shows search results
- WHEN the user clicks on a client
- THEN the typeahead input MUST display the selected client's name
- AND the POS state MUST update with the selected client ID
- AND the dropdown MUST close

#### Scenario: User clears selection

- GIVEN a client is selected
- WHEN the user clicks the clear button
- THEN the typeahead input MUST be empty
- AND the POS state MUST clear the client selection

### Requirement: Keyboard Navigation

The system MUST support keyboard navigation through search results for accessibility.

#### Scenario: Navigate with arrow keys

- GIVEN the dropdown shows search results
- WHEN the user presses the down arrow key
- THEN the first result MUST be highlighted
- AND subsequent down arrow presses MUST move the highlight down

#### Scenario: Select with Enter

- GIVEN a result is highlighted
- WHEN the user presses Enter
- THEN that client MUST be selected
- AND the dropdown MUST close

#### Scenario: Close with Escape

- GIVEN the dropdown is open
- WHEN the user presses Escape
- THEN the dropdown MUST close
- AND no selection change MUST occur
