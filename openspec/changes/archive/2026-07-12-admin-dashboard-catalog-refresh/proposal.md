# Propuesta: admin-dashboard-catalog-refresh

## Intent

Actualizar el dashboard y las 7 pantallas de catálogo (categories, brands, base-units, products, variants, presentations, prices) con el lenguaje visual del reference (`ondtcx/point-of-sale-interface`). Reemplazar el acento `indigo` de Breeze por verde esmeralda (`oklch(0.56 0.12 151)` ≈ `#3f7a54`) y aplicar tokens, espaciado y tipografía del reference.

## Scope

### In Scope
- `backend/resources/views/dashboard.blade.php` (landing, 118 líneas)
- 7 módulos de catálogo × 2 vistas (index + form) = 14 archivos Blade
- Componentes Blade compartidos locales: `<x-page-header>`, `<x-stat-card>`
- `backend/tailwind.config.js`: tokens `catalog.*` y ajuste de `borderRadius`

### Out of Scope
- Sidebar/top nav (`layouts/navigation.blade.php`, `components/nav-link*`, `components/dropdown*`, `application-logo`) — workstream paralelo `admin-sidebar-refresh`
- POS (`pos/*`, pos-v2 ya en master `4b2f363`), auth, guest
- Otras pantallas admin (ventas, compras, inventario, clientes, caja, reportes, proveedores) — diferidas
- Migración a Tailwind 4

## Capabilities

### New Capabilities
- `admin-dashboard-ui`: landing con tokens esmeralda, page header, stat cards
- `admin-catalog-ui`: 7 pantallas de catálogo (index + form) con page headers, tablas/cards, formularios, acciones primarias esmeralda

### Modified Capabilities
None (no hay specs previos en `openspec/specs/` para estas áreas)

## Approach

1. `tailwind.config.js`: extender `theme.colors` con `catalog.primary`, `catalog.accent`, `catalog.muted` bajo namespace `catalog.*`
2. `<x-page-header>`: title `text-2xl font-semibold tracking-tight text-balance` + description `text-sm text-muted-foreground text-pretty` + slot acción
3. Card surface: `bg-white ring-1 ring-border rounded-lg` (`rounded-lg` ajustado a `0.7rem` en config si hace falta)
4. Reemplazar `indigo-*` por `catalog.primary` en botones, links destacados y hover states
5. Mantener Figtree global; aplicar `text-balance`/`text-pretty`

## Affected Areas

- `backend/resources/views/dashboard.blade.php` (Modified)
- `backend/resources/views/catalog/{module}/{index,form}.blade.php` × 7 módulos (Modified)
- `backend/resources/views/components/page-header.blade.php` (New)
- `backend/resources/views/components/stat-card.blade.php` (New)
- `backend/tailwind.config.js` (Modified)
- `backend/tests/Feature/DashboardTest.php` (New)
- `backend/tests/Feature/Catalog/{Module}Test.php` × 7 (New)

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Tokens esmeralda contaminan POS/auth/otras pantallas | Medium | Namespace `catalog.*`; ningún selector global usa `catalog.primary` |
| Tests fallan por cambios de markup | Low | TDD: tests antes que markup; assert presencia de título/acción, no clases específicas |
| `composer` no instalado en host Windows | High | Tests corren tras `composer install`; PRs manuales (sin `gh`) |

## Rollback Plan

Revertir la cadena de commits. Con chained PRs por budget de 400 líneas, cada PR es revertible individualmente:
1. PR de tokens/config (low risk, sin impacto en pantallas existentes)
2. PRs de pantallas catalog (cada PR afecta solo 2 vistas, index + form del módulo)
3. PR de dashboard (afecta solo `dashboard.blade.php`)

## Dependencies

- `composer install` previo a `cd backend && composer test`
- Tailwind 3 sigue activo (`resources/css/app.css` mantiene directivas `@tailwind`)
- Independiente de `admin-sidebar-refresh` (sin archivos compartidos)

## Success Criteria

- [ ] 15 vistas in-scope (1 dashboard + 7 catalog index + 7 catalog form) renderizan con acento esmeralda en acciones primarias
- [ ] No quedan utilidades `indigo-*` en markup de las 15 vistas in-scope
- [ ] Tests feature pasan (`cd backend && composer test`, 1 test por módulo de catálogo + 1 dashboard test = 8 tests mínimos)
- [ ] Tokens en `tailwind.config.js` namespaced bajo `catalog.*` (no contaminan `colors.primary` global)
- [ ] Page header pattern consistente en las 15 vistas
- [ ] Sin librería de iconos nueva (workstream sidebar decide globalmente)
- [ ] Cero archivos tocados fuera del scope declarado
