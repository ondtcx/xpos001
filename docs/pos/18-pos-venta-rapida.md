# POS — venta rápida de mostrador

## Propósito

Definir una nueva experiencia principal de mostrador llamada **`POS`** para registrar ventas frecuentes con la menor fricción posible, sin duplicar la lógica de negocio ya existente y manteniendo la trazabilidad del núcleo actual.

Este documento cubre únicamente el diseño **funcional + técnico** de `POS / venta rápida`.
La corrección de la semántica entre `stock actual`, `inventario inicial` y `lotes` se documenta aparte como frente relacionado, pero distinto.

---

## Problema que se quiere resolver

La pantalla actual de ventas ya resuelve el flujo completo y trazable, pero muestra demasiadas opciones por defecto para el caso más frecuente de mostrador:

- fecha editable,
- cliente visible siempre,
- pagos múltiples visibles de entrada,
- override de precio visible por línea,
- confirmaciones de warning visibles aun cuando no aplican.

Eso hace que el operador vea primero la excepción y no el camino habitual.

El flujo diario deseable para tienda quedó validado así:

1. agregar producto,
2. ajustar cantidad,
3. cobrar,
4. dejar lista la siguiente venta.

Por eso `POS` debe optimizar el flujo dominante y revelar lo excepcional solo cuando haga falta.

---

## Objetivos

- reducir pasos para ventas frecuentes de mostrador,
- mantener un acceso obvio y rápido desde cualquier parte de la aplicación,
- conservar compatibilidad con caja, fiado, warnings y trazabilidad,
- evitar duplicar la lógica del dominio de ventas,
- permitir escalar sin pérdida de contexto hacia una `Venta completa`.

---

## Principios de diseño

### 1. El flujo habitual manda

La interfaz debe asumir por defecto:

- fecha actual automática,
- cliente anónimo,
- método de pago efectivo,
- venta sin override ni warnings.

### 2. Divulgación progresiva

Lo excepcional no debe verse de entrada. Solo aparece cuando el operador:

- cambia método de pago,
- asigna cliente,
- necesita vuelto,
- entra a fiado,
- activa cambio manual de precio,
- enfrenta warnings de stock o costo.

### 3. Un solo dominio de negocio

`POS` NO crea una segunda lógica de ventas.

La regla es:

- interfaz nueva: **sí**,
- servicio de negocio nuevo para vender: **no**.

### 4. Escalar sin castigar al usuario

Si una venta rápida deja de ser simple, el operador debe poder pasar a `Venta completa` sin perder:

- productos,
- cantidades,
- cliente,
- método de pago,
- contexto excepcional relevante.

---

## Posicionamiento dentro de la aplicación

### `POS`

Nueva entrada principal para mostrador.

### `Ventas`

Se mantiene como módulo de:

- historial,
- consulta,
- detalle,
- anulación,
- flujo completo / casos especiales.

### Accesos prioritarios

`POS` debe destacarse en:

- barra de navegación principal,
- dashboard,
- accesos contextuales relevantes.

---

## Alcance funcional

## Flujo normal

La venta rápida debe permitir:

1. entrar a `POS`,
2. buscar o escanear producto,
3. autoagregar si hay coincidencia exacta por código o barcode,
4. mostrar lista para elegir si la coincidencia no es exacta,
5. incrementar cantidad si la misma presentación se vuelve a agregar,
6. ajustar cantidad con botones `+ / -` y edición manual,
7. cobrar con flujo rápido,
8. mostrar confirmación breve,
9. dejar una nueva venta lista inmediatamente.

## Flujo excepcional

Debe permitir, pero sin contaminar el flujo base:

- asignar cliente,
- cambiar método de pago,
- usar pagos mixtos,
- convertir a fiado,
- calcular vuelto,
- confirmar warnings,
- escalar a `Venta completa`.

---

## Comportamientos acordados

### Identidad y navegación

- nombre de la nueva entrada principal: **`POS`**,
- debe coexistir con `Ventas`, no reemplazarla de golpe,
- desde `POS` debe existir un acceso claro a `Venta completa`.

### Información visible siempre

La pantalla debe mostrar siempre:

- buscador principal,
- líneas agregadas,
- total,
- método de pago actual,
- cliente actual o estado `anónimo`,
- botón para cobrar.

### Búsqueda y agregado de productos

- si el código o barcode coincide exacto, el producto se agrega automáticamente,
- si no coincide exacto, se muestran resultados para elegir,
- si la misma presentación se vuelve a agregar, aumenta la cantidad en la línea existente,
- la cantidad se ajusta con `+ / -` y edición manual.

### Cliente

- cliente por defecto: **anónimo**,
- el cliente identificado se activa con un botón discreto tipo `Asignar cliente`,
- no debe haber selector visible siempre en la pantalla base.

### Método de pago

- método por defecto: **efectivo**,
- la interfaz debe usar una acción discreta `Cambiar método`,
- al activarla aparecen las opciones necesarias:
  - transferencia,
  - mixto.

### Cierre habitual

- el cierre normal parte desde una acción principal de cobro,
- debe poder resolverse de forma rápida,
- si hace falta revisar o completar datos, puede abrir un mini paso final de confirmación.

### Vuelto

- el cálculo de vuelto NO aparece siempre,
- solo aparece si el operador activa `Ingresar monto recibido`,
- aplica principalmente al flujo en efectivo.

### Fiado

- debe existir detección automática de saldo pendiente,
- también debe haber una acción explícita tipo `Convertir a fiado`,
- si queda saldo pendiente, se debe exigir cliente antes de cerrar.

### Precio manual

- no debe mostrarse por defecto,
- se activa por línea como acción oculta,
- requiere motivo,
- se restringe por permisos.

### Warnings

- no deben aparecer como casillas visibles desde el inicio,
- si hay stock insuficiente o costo pendiente:
  - se bloquea el cierre normal,
  - se abre un paso excepcional de confirmación.

### Disponibilidad por línea

- cada línea muestra un estado simple siempre,
- el detalle exacto solo aparece si existe riesgo o warning.

### Postventa inmediata

- al cerrar una venta rápida se muestra una confirmación breve,
- luego queda lista una nueva venta,
- debe existir una opción discreta para `Ver / imprimir comprobante` sin abrirlo automáticamente.

### Estados visibles y consistencia de acciones

- las acciones secundarias del POS deben comportarse como estados explícitos, no como botones ambiguos,
- si una acción está activa, la interfaz debe indicarlo claramente (`activado` / `desactivado` o equivalente),
- si el operador pulsa otra acción y luego vuelve a la primera, lo esperado es que esa acción vuelva a mostrarse o reactivarse, no que entre en un estado silencioso o inerte,
- ocultar un panel contextual no debería resetear arbitrariamente otra intención operativa relacionada,
- cuando una condición obligatoria falta (por ejemplo cliente para fiado), la interfaz debe comunicarlo explícitamente y no limitarse a mover el foco.

---

## Permisos y control operativo

## Roles previstos

### Admin

Puede usar en `POS`:

- cambio manual de precio,
- confirmación de warnings,
- fiado,
- pagos mixtos,
- escalamiento a venta completa,
- resto de acciones operativas del flujo.

### Assistant

Puede usar en `POS`:

- fiado,
- pagos mixtos,
- confirmación de warnings.

No puede usar en `POS`:

- cambio manual de precio.

### Requisito extra para warnings confirmados por `assistant`

Si `assistant` confirma warnings, se debe exigir:

- confirmación explícita,
- motivo obligatorio,
- registro destacado para auditoría.

---

## Relación con la venta completa

`POS` no intenta absorber todos los casos. Cuando el operador elija `Venta completa`, el sistema debe transferir todo el contexto posible:

- productos,
- cantidades,
- cliente ya asignado,
- método de pago actual,
- contexto excepcional relevante.

La escalada no debe sentirse como empezar de cero.

---

## Propuesta de estructura de interfaz

## 1. Cabecera operativa

Debe incluir:

- título `POS`,
- cliente actual/anónimo,
- método actual,
- acceso a `Venta completa`.

## 2. Buscador principal

Características esperadas:

- foco automático al entrar,
- soporte para nombre, código y barcode,
- agregado automático por coincidencia exacta,
- lista de resultados cuando la búsqueda es ambigua.

## 3. Lista de líneas

Cada línea debería mostrar al menos:

- descripción corta,
- presentación,
- cantidad,
- botones `+ / -`,
- edición manual de cantidad,
- subtotal,
- estado simple.

Estado simple sugerido:

- normal,
- revisar,
- warning.

## 4. Resumen y cobro

Debe concentrar:

- total actual,
- cliente actual,
- método actual,
- botón principal para cobrar.

## 5. Acciones discretas

Acciones secundarias sugeridas:

- `Asignar cliente`,
- `Cambiar método`,
- `Ingresar monto recibido`,
- `Venta completa`.

## 6. Mini paso de cobro

No debe sentirse como un formulario completo nuevo.
Debe servir para:

- confirmar total,
- ingresar monto recibido cuando aplique,
- calcular vuelto,
- cambiar método de pago,
- derivar a mixto o fiado si corresponde.

## 7. Paso excepcional

Se muestra solo cuando:

- hay stock insuficiente,
- hay costo pendiente,
- falta cliente para un saldo pendiente,
- se requiere motivo y trazabilidad reforzada.

---

## Propuesta técnica

## Reutilización del dominio actual

Se debe reutilizar el flujo de negocio existente centrado en `CreateSaleService`.

La nueva interfaz `POS` debe traducir su payload a la estructura que el dominio actual ya entiende, en lugar de recalcular negocio por su cuenta.

## Componentes sugeridos

### Controlador nuevo

Crear un controlador específico, por ejemplo:

- `PosController`

Responsabilidades:

- cargar la pantalla principal,
- preparar datos de búsqueda y contexto operativo,
- validar el flujo rápido,
- delegar el guardado al dominio de ventas.

### Mapper o assembler

Agregar un componente intermedio, por ejemplo:

- `PosSaleRequestMapper`,
- o `PosSalePayloadFactory`.

Responsabilidad:

- traducir los datos del flujo rápido al payload esperado por `CreateSaleService`.

Eso evita contaminar el controller con lógica de transformación y deja más clara la separación entre UI y dominio.

### Requests específicos

Conviene separar la validación del flujo rápido de la validación del flujo completo. Por ejemplo:

- `StorePosSaleRequest`,
- `StoreSaleRequest` para la venta completa, si luego se quiere consolidar el orden de validaciones.

---

## Rutas sugeridas

Ejemplo de estructura mínima:

- `GET /pos` → pantalla principal `POS`,
- `POST /pos/checkout` → cierre y guardado de la venta rápida,
- `GET /sales/create` → venta completa,
- `POST /sales` → guardado del flujo completo.

Si la transición de `POS` a `Venta completa` requiere persistir contexto temporal, se puede resolver con:

- query params limitados si el payload es pequeño, o
- estado temporal en sesión si la transición necesita más estructura.

---

## Restricciones de implementación

- no duplicar reglas de negocio de ventas,
- no duplicar validaciones críticas solo en frontend,
- no dejar visibles por defecto las excepciones,
- no reemplazar la venta completa de golpe,
- no romper compatibilidad con caja, fiado, warnings y trazabilidad ya existentes.

---

## Riesgos a evitar

### 1. Duplicar el backend de ventas

Sería el peor error. Generaría dos interpretaciones distintas del mismo dominio.

### 2. Hacer un POS demasiado cargado

Si pagos mixtos, warnings, override y cliente quedan demasiado visibles, se repite el problema actual.

### 3. Hacer un POS demasiado limitado

Si cualquier excepción manda inmediatamente a la venta completa, el flujo rápido pierde valor real de mostrador.

### 4. Resolver permisos solo en UI

Ocultar botones no reemplaza reglas backend. Los permisos deben validarse también del lado del servidor.

### 5. Crear botones con estado implícito o inconsistente

Si acciones como `Asignar cliente`, `Ingresar monto recibido` o `Convertir a fiado` no dejan claro cuándo están activas, o si se cancelan entre sí sin intención evidente, el POS se vuelve más confuso justo donde debería ser más directo.

---

## Estado real actual del documento

Esta especificación ya fue implementada parcialmente y validada en código para:

- efectivo,
- transferencia simple,
- pago mixto,
- vuelto opcional,
- fiado inicial.

La deuda más importante que sigue abierta no es de dominio sino de UX:

- consistencia de estados visuales de botones/paneles,
- composición del panel lateral para evitar crecimiento vertical excesivo,
- cliente con búsqueda explícita y alta rápida dentro de `POS`.

---

## Orden recomendado de implementación

1. crear entrada principal `POS` y acceso desde navegación,
2. resolver flujo normal de búsqueda, agregado, cantidades y cobro base,
3. agregar cambio de método de pago,
4. agregar cliente y fiado,
5. agregar vuelto opcional,
6. agregar manejo de warnings excepcionales,
7. agregar permisos finos,
8. agregar transición completa hacia `Venta completa`,
9. refinar confirmación final y acceso a comprobante.

---

## Relación con el backlog actual

Este documento implementa en detalle la intención ya registrada en:

- `08-backlog-tecnico-mvp.md` → Epic 11, Historia 11.4,
- `17-estado-implementacion.md` → hallazgos recientes de ventas de mostrador.

No reemplaza el backlog ni el estado del proyecto; los aterriza para implementación futura.

---

## Resultado esperado

Cuando `POS` esté implementado correctamente, el operador debería poder vender la mayoría de los casos frecuentes con una experiencia mucho más directa:

- entrar,
- agregar,
- cobrar,
- seguir vendiendo.

Y cuando el caso ya no sea simple, el sistema debe seguir siendo seguro, trazable y escalable hacia `Venta completa` sin perder contexto.
