# POS Contextual Buttons State — Especificación

## Propósito

Definir el modelo de estado reactivo (Alpine store) que gobierna los cuatro botones contextuales del sidebar del POS: `Asignar cliente`, `Ingresar monto recibido`, `Convertir a fiado` y `Cambiar método`. Esta spec describe el **contrato del estado**, no la UX del usuario final (esa vive en `pos-sidebar-state`). Centraliza la lógica dispersa actualmente entre `input.value` y `classList.toggle` en el DOM y la reemplaza por una única fuente de verdad declarativa.

## Requirements

### Requirement: Single Alpine Store for Contextual Buttons

The system MUST exponer un único Alpine store, con alcance la página del POS, que mantenga un flag booleano por cada botón contextual (`client`, `received_amount`, `fiado`, `payment_method`). El store MUST ser el único escritor de esos flags durante la sesión.

#### Scenario: Estado inicial todo inactivo

- GIVEN una carga fresca de la página del POS
- WHEN el Alpine store se inicializa
- THEN los cuatro flags booleano MUST ser `false`
- AND el estado visual de cada botón MUST reflejar `inactive`

#### Scenario: Activar un solo flag

- GIVEN los cuatro flags son `false`
- WHEN el usuario activa el flag `client`
- THEN `client` MUST pasar a `true`
- AND los otros tres flags MUST permanecer en `false`
- AND el botón `client` MUST renderizar la clase CSS activa

#### Scenario: Desactivar un solo flag

- GIVEN el flag `client` es `true`
- WHEN el usuario desactiva `client`
- THEN `client` MUST pasar a `false`
- AND el botón `client` MUST renderizar la clase CSS inactiva

### Requirement: Persistencia solo en sesión (resolución Q1)

The system MUST mantener el store en memoria durante la sesión de la página del POS. The system MUST NOT persistir el store en `localStorage`, `sessionStorage`, cookies ni ningún otro almacenamiento durable.

#### Scenario: Recarga reinicia el store

- GIVEN el usuario activó `fiado` y seleccionó un cliente
- WHEN el usuario recarga la página del POS
- THEN los cuatro flags MUST volver a `false`
- AND cualquier selección de cliente que vivía solo en el store MUST limpiarse
- AND el usuario MUST ver un estado fresco del POS

#### Scenario: Navegar y volver reinicia el store

- GIVEN el usuario tiene `received_amount` activo
- WHEN el usuario navega a otra página y vuelve al POS
- THEN el store MUST re-inicializarse a todo inactivo

#### Scenario: Justificación capturada

- **(Rationale)**: PII (selección de cliente) y estado intermedio de venta (monto recibido) MUST NOT filtrarse entre sesiones en un POS compartido. Mantener el store en memoria alinea con la simplicidad de rollback y con el alcance "100% UI/UX" de la propuesta.

## Out of Scope

- La apariencia visual de los botones (cubierta en `pos-sidebar-state`).
- Los datos internos de cada panel contextual (cubiertos en `pos-sidebar-state` y en las specs por panel).
- Dominio de ventas (`CreateSaleService`, `PosSaleDraftBuilder`, `VoidSaleService`).
- Componentes Livewire.
- Cobertura E2E del estado visual de los botones.

## Testability

Los tests Feature de PHPUnit cubren el **contrato del store** inspeccionando la vista Blade renderizada para el atributo `x-data` y la presencia del nombre del store. Las transiciones reactivas reales (click → flip de flag → cambio de clase) MUST validarse manualmente contra el checklist de `docs/pos/18` y `docs/pos/19`. La cobertura del invariante "solo en sesión" es un chequeo estático sobre la vista Blade: el archivo de spec MUST NOT hacer referencia a `localStorage`, `sessionStorage` o `$persist`. PHPUnit no assertúa contra la clase CSS del DOM al togglear — eso es un ítem de regresión manual.
