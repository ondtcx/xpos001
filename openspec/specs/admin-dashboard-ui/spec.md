# admin-dashboard-ui Specification

## Purpose

Define the rendered behavior of the admin landing page (`dashboard.blade.php`). The page is the operator's entry point: it summarizes system status, links to the primary operations (Sales, Purchases, Cash, Reports), links to the catalog entities (Categories, Brands, Base Units, Products), and signals the next recommended focus. All visual changes from this change are scoped to this page; the navigation chrome is owned by the parallel `admin-sidebar-refresh` workstream.

## Requirements

### Requirement: Page Header

The dashboard MUST render a page header with a title, a one-line description, and an optional action slot on the right.

#### Scenario: Header renders title and description

- GIVEN an authenticated operator opens the dashboard
- WHEN the page renders
- THEN the header MUST show the dashboard title
- AND it MUST show a short description of what the operator can do from here

### Requirement: System Status Card

The dashboard MUST render a system status card summarizing the local-first operation of the system. The card MUST be visually distinct from the quick-access cards.

#### Scenario: Status card visible on render

- GIVEN the dashboard renders
- THEN the status card MUST appear in the upper section
- AND it MUST render without depending on user data

### Requirement: Operations Quick-Access Grid

The dashboard MUST render a grid of cards linking to Ventas, Compras, Caja, and Reportes. Each card MUST show a label, a short description, and a link to the corresponding index route.

#### Scenario: Operations grid renders all four links

- GIVEN the dashboard renders
- THEN four cards MUST appear with labels Ventas, Compras, Caja, Reportes
- AND each card MUST navigate to the correct index route

### Requirement: Catalog Quick-Access Grid

The dashboard MUST render a grid of cards linking to Categorías, Marcas, Unidades, and Productos. Each card MUST show a label, a short description, and a link to the corresponding index route.

#### Scenario: Catalog grid renders all four links

- GIVEN the dashboard renders
- THEN four cards MUST appear with labels Categorías, Marcas, Unidades, Productos
- AND each card MUST navigate to the correct index route

### Requirement: Next Focus Panel

The dashboard MUST render a "Siguiente foco recomendado" panel that explains the next recommended work area and shows at least one status signal for the current iteration.

#### Scenario: Next focus panel renders signals

- GIVEN the dashboard renders
- THEN the panel MUST be visible
- AND it MUST show at least one status signal

### Requirement: Quick Links Panel

The dashboard MUST render an "Accesos rápidos" panel listing at least three secondary links (e.g. POS, Clientes, Lotes, Proveedores). Each link MUST navigate to its target route.

#### Scenario: Quick links list renders

- GIVEN the dashboard renders
- THEN the panel MUST be visible
- AND it MUST list at least three secondary links with their target routes

### Requirement: Empty-Data Resilience

The page MUST render without errors when no data exists. No card, panel, or grid MUST depend on a non-empty database to appear.

#### Scenario: Dashboard renders with empty database

- GIVEN no sales, purchases, or catalog records exist
- WHEN the operator opens the dashboard
- THEN the page MUST return HTTP 200
- AND every required section (header, status card, both grids, both panels) MUST still render

### Requirement: Accent Color on Primary Actions

All primary actions on the dashboard (link cards, action buttons, highlighted entries) MUST use the accent color. They MUST NOT use the legacy indigo palette that previously marked primary actions in this project.

#### Scenario: No legacy indigo remains on primary surfaces

- GIVEN the dashboard renders
- THEN the primary action surfaces MUST be visually consistent with the new accent color
- AND no element rendered as a primary action on the dashboard MUST use the legacy indigo color
