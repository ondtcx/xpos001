# Propuesta: pos-ux-refinements

## Intención

Cerrar la deuda de UX del POS de mostrador documentada en `docs/pos/17`, `18` y `19`. El dominio de ventas ya está completo y operativo; la fricción actual es de interacción, no de negocio. Los cuatro botones contextuales del sidebar no reflejan su estado de forma consistente, la reactivación de paneles es ambigua, la selección de cliente es básica y el panel lateral crece sin control vertical. Este change aborda esos cuatro sub-frentes sin tocar `CreateSaleService`, `PosSaleDraftBuilder` ni ningún componente del dominio.

## Alcance

### Incluido
- Estado visual consistente de los 4 botones contextuales del sidebar POS (`Asignar cliente`, `Ingresar monto recibido`, `Convertir a fiado`, `Cambiar método`).
- Comportamiento predecible de reactivación de paneles contextuales (reabrir un panel ya usado después de usar otro).
- Typeahead de cliente dentro del POS (búsqueda debounced del lado del servidor, navegación por teclado).
- Mejor aprovechamiento vertical del panel lateral del POS (composición de paneles, límite de crecimiento).

### Excluido
- Dominio de ventas: `CreateSaleService`, `PosSaleDraftBuilder`, `VoidSaleService` no se modifican.
- Livewire: la UI actual es Blade + Alpine.js; introducir Livewire es un cambio arquitectónico separado.
- Spec `fiado-auto-config`: fuera de este change (iteración posterior).
- Recargas, lectura XML, gestión móvil, barcode físico (iteración 2).
- Semántica de navegación `Inventario` vs `Stock actual` (frente paralelo, change separado).
- Cobertura E2E automatizada; la regresión se valida manualmente.

## Capacidades

### Capacidades nuevas
- `pos-contextual-buttons-state`: estado visual reactivo y consistente de los 4 botones contextuales del sidebar POS.
- `pos-panel-reactivation`: comportamiento predecible de reactivación y reexposición de paneles contextuales.
- `pos-client-typeahead`: typeahead de cliente dentro del POS con búsqueda debounced y navegación por teclado.
- `pos-sidebar-vertical-layout`: mejor uso vertical del panel lateral mediante composición controlada de paneles.

### Capacidades modificadas
- `pos-sidebar-state`: se extiende para cubrir los 4 botones (no solo fiado) y el comportamiento de reactivación de paneles.

## Enfoque

Los cuatro sub-frentes se resuelven en Blade + Alpine.js, sin nuevos componentes Livewire ni dependencias externas. El estado reactivo se centraliza en un store Alpine único para el sidebar del POS, reemplazando la dispersión actual de `input.value` y `classList.toggle` en el DOM. El typeahead de cliente reutiliza el endpoint de búsqueda existente y agrega debounce ~300ms. La composición vertical del panel lateral se controla con un layout de altura fija y scroll interno por panel, en lugar de apilamiento libre.

## Áreas afectadas

| Área | Impacto | Descripción |
|------|---------|-------------|
| `backend/resources/views/pos/index.blade.php` | Modified | Sidebar: botones, paneles, layout vertical, Alpine store |
| `backend/resources/js/` | New/Modified | Alpine store para estado del sidebar |
| `backend/app/Http/Controllers/PosController.php` | Modified | Endpoint de búsqueda de clientes para typeahead (si no existe) |

## Riesgos

| Riesgo | Probabilidad | Mitigación |
|--------|--------------|------------|
| Blade sin Livewire limita reutilización de estado | Media | Centralizar estado en Alpine store; documentar límite como decisión consciente |
| Sin regresión visual automatizada | Alta | Validación manual con `MinimarketDemoSeeder`; checklist de escenarios en spec |
| Confusión semántica `Inventario` vs `Stock actual` | Baja | Alcance explícito: solo POS; inventario es change separado |
| Deriva respecto a specs existentes | Media | Specs existentes se extienden, no se reemplazan; delta spec documenta la delta exacta |

## Plan de rollback

El change es 100% UI/UX sobre Blade + Alpine.js:
1. `git revert` del merge commit (o de cada PR encadenado en orden inverso).
2. Regenerar assets con `npm run build` si aplica.
3. Sin migraciones de base de datos ni cambios en el dominio; no se requiere reset de seeds.
4. Validar manualmente con `MinimarketDemoSeeder` que el POS vuelve al estado anterior.

## Dependencias

- Ninguna externa. Alpine.js 3 y Tailwind ya instalados.

## Criterios de éxito

- [ ] Los 4 botones contextuales muestran estado activo/inactivo visualmente consistente en todos los recorridos.
- [ ] Reactivar un panel ya usado después de usar otro restaura su contenido previo sin ambigüedad.
- [ ] Typeahead de cliente resuelve búsqueda por nombre/documento con debounce ~300ms y navegación por teclado.
- [ ] Panel lateral no crece indefinidamente hacia abajo cuando coinciden múltiples paneles abiertos.
- [ ] `cd backend && composer test` sigue en verde (tests feature existentes no se rompen).
- [ ] Regresión manual validada con `MinimarketDemoSeeder`: venta simple, venta con cliente, venta fiada, pago mixto.

## Slicing orientativo para PRs encadenados

División en unidades de revisión independientes (presupuesto ~400 líneas por PR):

- **PR 1**: Alpine store centralizado + estado visual de los 4 botones contextuales (sub-frente a).
- **PR 2**: Comportamiento de reactivación de paneles contextuales (sub-frente b).
- **PR 3**: Typeahead de cliente dentro del POS (sub-frente c).
- **PR 4**: Composición vertical del panel lateral (sub-frente d).

Cada PR es autoejecutable y verificable por separado. El orden refleja dependencia: el store (PR 1) es base para los demás.

## Preguntas abiertas (para fase de spec)

- ¿El Alpine store debe persistir estado entre navegaciones de página o solo dentro de la sesión POS actual?
- ¿El typeahead de cliente debe soportar alta rápida de cliente nuevo desde el mismo panel, o queda para iteración posterior?
- ¿El layout vertical del panel lateral usa altura fija con scroll interno por panel, o un sistema de tabs/accordion exclusivo?
