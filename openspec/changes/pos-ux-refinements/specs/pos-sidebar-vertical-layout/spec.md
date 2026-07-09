# POS Sidebar Vertical Layout — Especificación

## Propósito

Definir el layout vertical del sidebar del POS para que, al coincidir varios paneles contextuales abiertos, la columna no crezca indefinidamente hacia abajo. La solución adoptada es un contenedor de altura fija con scroll interno por panel — decisión tomada frente a la alternativa de tabs/accordion exclusivo por simplicidad y compatibilidad con la semántica existente de pin documentada en `pos-sidebar-state`.

## Resolución Q3: elección de layout

- **Elegido**: contenedor de altura fija con `overflow-y: auto` interno por panel.
- **Rechazado**: tabs/accordion exclusivo. Justificación: tabs/accordion añadiría lógica de cambio de modo, conflictuaría con la semántica de pin ya documentada en `pos-sidebar-state` (un panel pineado debe permanecer visible siempre) y consumiría presupuesto del PR sin beneficio operativo claro para el flujo de mostrador.
- **Reversibilidad**: si tabs/accordion resulta mejor en una iteración posterior, este contrato se reemplaza sin tocar el dominio.

## Requirements

### Requirement: Contenedor del sidebar de altura fija

The system MUST envolver los cuatro paneles contextuales en un único contenedor cuya altura esté limitada por la grilla de layout del POS. El contenedor MUST NOT crecer más allá de su caja cuando se abren paneles adicionales.

#### Scenario: Cuatro paneles abiertos no exceden el contenedor

- GIVEN los cuatro paneles están abiertos
- WHEN el usuario ve la página del POS
- THEN la altura del contenedor del sidebar MUST ser igual a su valor acotado
- AND la página MUST NOT hacer scroll del sidebar fuera del viewport

#### Scenario: El scroll de la página es independiente del scroll del sidebar

- GIVEN el contenido del sidebar excede la altura del contenedor
- WHEN el usuario hace scroll dentro de un panel
- THEN solo el scroll interno del panel MUST moverse
- AND la posición de scroll de la página principal MUST NOT cambiar

### Requirement: Scroll interno por panel

The system MUST habilitar scroll vertical dentro de cada panel contextual de forma independiente, de modo que contenido largo (p.ej. una lista larga de resultados de búsqueda de cliente) no empuje el layout del panel.

#### Scenario: Lista larga de clientes scrollea dentro de su panel

- GIVEN el panel `client` muestra 20 resultados de búsqueda
- WHEN el usuario hace scroll dentro del panel
- THEN la barra de scroll del panel MUST moverse
- AND las alturas de los otros tres paneles MUST permanecer estables

#### Scenario: Paneles vacíos no colapsan

- GIVEN solo un panel está abierto
- WHEN el usuario ve la página del POS
- THEN el panel abierto MUST ocupar su altura natural
- AND los paneles cerrados MUST NOT dejar espacio vacío visible que empuje hacia abajo al panel abierto

### Requirement: Comportamiento de pin preservado (no regresión)

The system MUST continuar honrando la semántica de `pin` definida en `pos-sidebar-state`: un panel pineado MUST permanecer visible y no MUST ser ocultado por el nuevo layout.

#### Scenario: Panel pineado permanece visible

- GIVEN el usuario pineó el panel `fiado`
- WHEN el usuario abre y cierra otros paneles
- THEN el panel `fiado` MUST permanecer visible
- AND el layout MUST NOT ocultarlo

## Out of Scope

- Rediseño visual del sidebar más allá del layout.
- Reemplazar la semántica de pin (cubierta en `pos-sidebar-state`).
- Modo exclusivo de tabs/accordion (rechazado en Q3).
- Dominio de ventas, Livewire, cobertura E2E.

## Testability

Los tests Feature de PHPUnit assertúan que la vista Blade renderizada contiene el contenedor acotado (`<div ... class="... overflow-y-auto ...">`) y la clase `overflow-y-auto` en el wrapper de cada panel. Las dimensiones del layout se validan manualmente con el panel de layout de DevTools del navegador, usando el dataset `MinimarketDemoSeeder`. La preservación del pin está cubierta por los tests Feature existentes de `pos-sidebar-state`, que MUST seguir en verde.
