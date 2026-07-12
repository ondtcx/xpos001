# admin-catalog-ui Specification

## Purpose

Define the rendered behavior of the admin catalog screens — index and form views for categories, brands, base-units, products, variants, presentations, and prices. The screens MUST share a page-header pattern, surface validation feedback, and use the accent color for primary actions. Visual changes are scoped to these screens; the navigation chrome belongs to the parallel `admin-sidebar-refresh` workstream.

## Requirements

### Requirement: Index and Form Views per Module

Each catalog module MUST expose an index view of its records and a form view for create or update.

#### Scenario: All required modules expose both views

- GIVEN the catalog namespace
- THEN categories, brands, base-units, products, variants, presentations, and prices MUST each have an index view and a form view

### Requirement: Index Page Header and Create Action

The index view MUST render a page header with the module title, a short description, and a primary create action linking to the create route.

#### Scenario: Index header and create action

- GIVEN a catalog index renders
- WHEN the page renders
- THEN the header MUST show the module title and a short description
- AND a primary create action MUST link to the create route

### Requirement: Index Lists Records

The index view MUST render the module's records in a list or table, each row providing an edit control.

#### Scenario: Index renders one entry per record

- GIVEN the module has 5 records
- WHEN the index renders
- THEN the page MUST show 5 entries
- AND each MUST include a control that navigates to the edit form

#### Scenario: Zero records shows empty state

- GIVEN the module has 0 records
- WHEN the index renders
- THEN the list MUST NOT crash
- AND the page MUST show an empty state with a call-to-action

### Requirement: Form Renders Fields and Actions

The form view MUST render the input controls, a primary save action, and a secondary cancel action that returns to the index without persisting.

#### Scenario: Edit form pre-populates fields

- GIVEN a record with name "Bebidas" exists
- WHEN the operator opens its edit form
- THEN the name input MUST show "Bebidas"

#### Scenario: Create form starts empty

- GIVEN the create form is opened
- WHEN the form renders
- THEN the input controls MUST be visible
- AND they MUST NOT be pre-populated with any existing record

#### Scenario: Cancel returns to index without saving

- GIVEN the form has unsaved changes
- WHEN the operator clicks cancel
- THEN the form MUST NOT submit
- AND the browser MUST navigate to the index

### Requirement: Validation Errors Surface Inline

When the form submission returns validation errors, the form MUST re-render with user input preserved and display each error near its field.

#### Scenario: Validation error preserves user input

- GIVEN the create form is submitted with a blank required field
- WHEN the server rejects the submission
- THEN the form MUST re-render
- AND the field MUST still hold the value the operator typed
- AND an error message MUST be visible near the rejected field

### Requirement: Accent Color and Visual Consistency

Primary actions MUST use the accent color and MUST NOT use the legacy indigo palette. Catalog screens MUST share the dashboard's page header, card surface, and button styles.

#### Scenario: No legacy indigo remains on primary surfaces

- GIVEN any catalog view renders
- THEN primary action surfaces MUST be visually consistent with the new accent color
- AND no element rendered as a primary action MUST use the legacy indigo color

#### Scenario: Page header and card surface match the dashboard

- GIVEN the operator opens the dashboard and then a catalog index
- THEN the page-header structure (title, description, optional action) MUST match the dashboard
- AND the card surface (background, border, corner radius) MUST match the dashboard
