# POS Client Typeahead — Especificación

## Propósito

Definir la integración específica del componente typeahead de cliente dentro del panel `Asignar cliente` del POS. Esta spec **refina** la spec genérica `client-typeahead` (componente) describiendo el binding concreto al sidebar del POS: endpoint invocado, debounce, manejo de teclado dentro del contexto del panel y conservación de la selección al cerrar y reabrir el panel. La spec genérica `client-typeahead` permanece como contrato del componente; esta spec es su instanciación POS.

## Relación con la spec existente

- **Existente**: `client-typeahead` define el componente genérico (server-side search, 300ms debounce, keyboard navigation, selection state).
- **Esta spec**: `pos-client-typeahead` acota ese componente al sidebar del POS — endpoint, binding al panel `Asignar cliente` y conservación de selección.
- **Sin contradicción**: cada requirement de `client-typeahead` sigue vigente; esta spec solo agrega binding POS.
- **Alta rápida de cliente queda FUERA de alcance** (resolución Q2): el typeahead solo busca clientes existentes.

## Requirements

### Requirement: Endpoint de búsqueda de cliente en POS

The system MUST reutilizar el endpoint de búsqueda de clientes existente (el consumido por la búsqueda de productos en el POS) o exponer un endpoint dedicado `GET /pos/clients/search` que devuelva hasta N coincidencias por nombre, documento o código. La respuesta MUST ser JSON con al menos `id`, `name` y `document`.

#### Scenario: Búsqueda por nombre parcial

- GIVEN existe al menos un cliente llamado `Juan Pérez`
- WHEN el usuario tipea `jua` en el typeahead de cliente del POS
- AND transcurren los 300ms de debounce
- THEN el endpoint MUST devolver `Juan Pérez` en los resultados

#### Scenario: Conjunto de resultados vacío

- GIVEN ningún cliente coincide con `xyz123`
- WHEN el debounce expira
- THEN el endpoint MUST devolver un array vacío
- AND el dropdown MUST renderizar el mensaje "No se encontraron clientes"

#### Scenario: Navegación por teclado dentro del panel

- GIVEN el dropdown está abierto con 3 resultados
- WHEN el usuario presiona la flecha abajo
- THEN el primer resultado MUST quedar resaltado
- AND las flechas abajo subsecuentes MUST mover el resaltado

### Requirement: La selección persiste entre toggles del panel

The system MUST mantener el cliente seleccionado en el estado del sidebar del POS cuando el panel `client` se cierra y se reabre. La selección MUST NOT disparar un re-fetch al reabrir.

#### Scenario: Cliente seleccionado sobrevive al cierre del panel

- GIVEN el usuario seleccionó `Juan Pérez`
- WHEN el usuario cierra el panel `client`
- AND el usuario reabre el panel `client`
- THEN el input del typeahead MUST mostrar `Juan Pérez`
- AND no MUST emitirse ninguna petición de red al reabrir

### Requirement: Sin alta rápida en este change (resolución Q2)

The system MUST NOT exponer una acción de "crear cliente nuevo" desde el dropdown del typeahead en este change. Si no hay coincidencias, el único feedback SHALL ser el mensaje de estado vacío.

#### Scenario: Sin opción de alta cuando no hay coincidencias

- GIVEN el usuario tipeó una query que no devuelve clientes
- WHEN se muestra el estado vacío
- THEN el dropdown MUST NOT mostrar un botón o enlace de "crear cliente nuevo"

#### Scenario: Justificación

- **(Rationale)**: alta rápida de cliente se mantiene fuera de alcance (ver `docs/pos/19`). Merece su propio change con validaciones, permisos finos y unicidad de documento explícitos.

## Out of Scope

- Contrato genérico del componente typeahead — ver `client-typeahead`.
- Flujo de alta rápida de cliente — diferido a un change posterior.
- Reemplazar la spec existente `client-typeahead`.
- Dominio de ventas, `fiado-auto-config`, Livewire, cobertura E2E.

## Testability

Los tests Feature de PHPUnit cubren el **contrato del endpoint**: `GET /pos/clients/search?q=jua` debe responder 200 con JSON con la forma correcta y debe exigir autenticación. Los tests también cubren el **invariante "sin alta rápida"** haciendo un snapshot del Blade renderizado y assertuando que NO contiene los marcadores `alta rápida`, `crear cliente` o equivalentes. El timing del debounce (300ms) y la navegación por teclado son ítems de regresión manual validados contra el dataset `MinimarketDemoSeeder`.
