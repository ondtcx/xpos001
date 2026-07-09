# POS Panel Reactivation — Especificación

## Propósito

Definir el comportamiento predecible de los cuatro paneles contextuales del POS cuando el usuario los abre, los cierra y los vuelve a abrir en cualquier orden, sin importar qué otros paneles estén abiertos. Esta spec cierra la ambigüedad actual: al pulsar un botón de un panel ya usado, el sistema MUST re-mostrar el panel con su contenido previo, su botón MUST indicar de forma inequívoca que la acción tiene estado activo, y el panel MUST NOT quedar oculto debajo de otros paneles.

## Requirements

### Requirement: Panel cerrado y reabierto reaparece

The system MUST restaurar un panel previamente abierto (con sus datos preservados) cuando el usuario activa su botón contextual después de cerrarlo. El panel MUST re-aparecer en el mismo orden del sidebar que tenía antes de cerrarse.

#### Scenario: Reabrir un solo panel

- GIVEN el usuario abrió y cerró el panel `client` previamente
- WHEN el usuario activa el botón `client`
- THEN el panel `client` MUST quedar visible
- AND el cliente previamente seleccionado MUST seguir mostrándose en el input

#### Scenario: Reabrir tras abrir otro panel

- GIVEN el usuario abrió el panel `client`, luego abrió `fiado`, luego cerró `client`
- WHEN el usuario activa el botón `client` de nuevo
- THEN el panel `client` MUST re-aparecer
- AND sus datos MUST estar preservados
- AND el panel `fiado` MUST mantener su estado actual (no se fuerza cierre)

### Requirement: Hint visual de "usado" en botones

The system MUST marcar el botón contextual con un hint visual persistente (distinto del flag `active`) cuando el usuario haya usado esa acción al menos una vez en la sesión actual, incluso si el panel está cerrado en ese momento.

#### Scenario: Botón usado-pero-cerrado muestra hint

- GIVEN el usuario abrió y cerró el panel `received_amount` después de tipear un valor
- WHEN el usuario cierra el panel
- THEN el botón `received_amount` MUST mostrar el hint visual `used` (separado de `active`)

#### Scenario: Botón nunca usado no muestra hint

- GIVEN el usuario no activó `payment_method` durante la sesión
- WHEN el POS está en estado estable
- THEN el botón `payment_method` MUST NOT mostrar el hint visual `used`

### Requirement: Sin botones inertes silenciosos

The system MUST NOT dejar un botón en un estado donde hacer click no tenga efecto visible. Cada click en un botón contextual MUST abrir, cerrar o togglear visiblemente el panel correspondiente.

#### Scenario: Click en botón inactivo siempre abre su panel

- GIVEN el panel `fiado` está cerrado
- WHEN el usuario hace click en el botón `fiado`
- THEN el panel `fiado` MUST abrirse
- AND el click MUST NOT descartarse silenciosamente

## Out of Scope

- El modelo de estado reactivo detrás de los paneles (cubierto en `pos-contextual-buttons-state`).
- El layout vertical del sidebar (cubierto en `pos-sidebar-vertical-layout`).
- Persistir el estado de reactivación entre recargas de página — el hint `used` es solo en sesión (ver resolución Q1 en `pos-contextual-buttons-state`).
- Dominio de ventas, Livewire, E2E, `fiado-auto-config`.

## Testability

Los tests Feature de PHPUnit verifican que la vista Blade renderizada contiene la clase `used` solo en los botones que el estado inicial del lado servidor marca como usados. El flujo real "click → reabrir" se valida manualmente con el dataset `MinimarketDemoSeeder`, recorriendo los cuatro escenarios del item 2 de `docs/pos/19` §"Deuda abierta prioritaria". No hay regresión automatizada para el toggle visual del hint.
