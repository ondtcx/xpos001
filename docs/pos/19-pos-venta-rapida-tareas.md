# POS — tareas de implementación

## Propósito

Traducir la especificación `18-pos-venta-rapida.md` a un plan de implementación incremental, con foco inicial en una **Fase 1 útil y controlada**.

Este documento NO redefine el diseño funcional. Su objetivo es bajar ese diseño a tareas concretas, verificables y ejecutables sin improvisación.

---

## Relación con otros documentos

- `18-pos-venta-rapida.md` define el diseño técnico-funcional completo de `POS`.
- `08-backlog-tecnico-mvp.md` mantiene la prioridad del frente dentro del backlog general.
- `17-estado-implementacion.md` documenta por qué `POS` pasó a ser una necesidad operativa real.

Este documento toma esas decisiones y las convierte en trabajo incremental.

---

## Estrategia elegida

La implementación de `POS` será **incremental**.

### Razón

No conviene construir toda la experiencia en un solo bloque porque:

- aumenta riesgo de romper el flujo actual de ventas,
- mezcla el caso habitual con demasiadas excepciones desde el inicio,
- dificulta validar si el núcleo del mostrador realmente mejora.

Primero se debe entregar una base operativa clara. Después se agregan capas de complejidad.

---

## Fase 1 — POS base útil

## Objetivo de la fase

Entregar una primera versión de `POS` que ya sirva para mostrador en ventas simples, sin absorber todavía pagos no efectivos, warnings ni excepciones avanzadas.

## Alcance funcional confirmado

La Fase 1 incluye:

- acceso nuevo a `POS`,
- pantalla propia de `POS`,
- buscador principal,
- agregado de productos,
- incremento de cantidad si se repite la presentación,
- ajuste de cantidad con `+ / -` y edición manual,
- total de venta,
- cobro solo en efectivo,
- cliente opcional,
- confirmación breve después de guardar,
- nueva venta lista inmediatamente,
- transición a `Venta completa` conservando contexto.

La Fase 1 NO incluye todavía dentro de `POS`:

- pagos no efectivos,
- pagos mixtos,
- fiado operativo dentro del POS,
- confirmación de warnings,
- override manual de precio,
- cálculo de vuelto,
- permisos finos específicos,
- reemplazo de `Ventas` como entrada principal.

---

## Reglas operativas de la Fase 1

### Casos que sí resuelve directamente

- venta simple de contado en efectivo,
- venta con cliente asignado si el operador quiere trazabilidad nominal,
- búsqueda rápida y carga ágil de líneas,
- paso a venta completa cuando el caso deje de ser simple.

### Casos que deben derivarse a `Venta completa`

En Fase 1, `POS` debe derivar al flujo completo si ocurre alguno de estos casos:

- la venta requiere saldo pendiente,
- la venta requiere método distinto de efectivo,
- la venta necesita pagos mixtos,
- aparece warning de stock insuficiente,
- aparece warning de costo pendiente,
- el caso requiere excepción que todavía no pertenece a la fase.

Esto es intencional. La primera entrega debe ser clara y segura, no artificialmente ambiciosa.

---

## Criterios de aceptación globales de Fase 1

- el operador puede abrir `POS` y vender una venta simple más rápido que en la pantalla completa actual,
- el flujo base no expone por defecto campos excepcionales,
- el guardado sigue usando el dominio actual de ventas,
- `POS` no duplica lógica de negocio,
- cuando el caso excede la fase, la derivación a `Venta completa` conserva contexto útil,
- `POS` convive con `Ventas` sin desplazarla todavía como entrada principal.

---

## Bloques de trabajo

## Bloque 1 — Entrada y navegación de POS

### Objetivo

Agregar el nuevo punto de entrada sin romper la navegación actual.

### Tareas

1. Crear la nueva ruta base de `POS`.
2. Crear el controlador inicial de `POS`.
3. Crear la vista base propia de `POS`.
4. Agregar acceso a `POS` en lugares controlados, sin reemplazar aún a `Ventas` como módulo principal.
5. Mantener visible el acceso a `Venta completa` desde `POS`.

### Entregable esperado

El usuario puede abrir una nueva pantalla `POS` desde la aplicación y volver al flujo completo si lo necesita.

### Criterios de aceptación

- existe una ruta dedicada a `POS`,
- la pantalla carga correctamente dentro del layout actual,
- `POS` convive con `Ventas`,
- el acceso a `Venta completa` está disponible desde `POS`.

---

## Bloque 2 — Búsqueda y agregado de productos

### Objetivo

Resolver el corazón del flujo de mostrador: encontrar y agregar rápido.

### Tareas

1. Reutilizar la lógica de búsqueda existente donde sea útil.
2. Adaptar la experiencia visual a una UI propia de `POS`.
3. Soportar búsqueda por nombre, código y barcode.
4. Autoagregar cuando haya coincidencia exacta por código o barcode.
5. Mostrar lista cuando la coincidencia sea ambigua.
6. Preparar el input para uso futuro con scanner tipo teclado, sin implementar todavía integración más específica.

### Entregable esperado

El operador puede encontrar y agregar productos con un flujo corto y compatible con mostrador.

### Criterios de aceptación

- la coincidencia exacta agrega automáticamente,
- la coincidencia ambigua muestra resultados utilizables,
- la experiencia es más directa que en el formulario completo,
- la solución reutiliza lógica útil sin heredar toda la UX de la pantalla actual.

---

## Bloque 3 — Manejo de líneas y cantidades

### Objetivo

Permitir edición rápida de la venta sin ruido innecesario.

### Tareas

1. Mostrar líneas activas en una lista clara.
2. Si se vuelve a agregar la misma presentación, incrementar cantidad en la línea existente.
3. Agregar controles `+ / -` por línea.
4. Permitir edición manual de cantidad.
5. Permitir quitar línea.
6. Recalcular subtotal y total en tiempo real.

### Entregable esperado

El operador puede construir una venta simple sin navegar por formularios pesados.

### Criterios de aceptación

- repetir una presentación no crea líneas duplicadas por defecto,
- la cantidad puede ajustarse rápida y manualmente,
- el total responde correctamente a los cambios.

---

## Bloque 4 — Cobro base en efectivo

### Objetivo

Permitir cerrar ventas simples de contado usando únicamente el camino habitual.

### Tareas

1. Definir el cierre base de Fase 1 como efectivo.
2. Mostrar total visible y acción principal de cobro.
3. Mapear el payload simple de `POS` al formato esperado por el dominio actual.
4. Reutilizar `CreateSaleService` para persistir la venta.
5. Mostrar confirmación breve al completar la venta.
6. Reiniciar `POS` y dejar lista una nueva venta.

### Entregable esperado

El operador puede cerrar una venta simple de contado desde `POS` sin pasar por la venta completa.

### Criterios de aceptación

- la venta se guarda correctamente usando el servicio actual,
- no hay duplicación de lógica de negocio,
- el flujo es más corto que la pantalla completa,
- el sistema deja inmediatamente preparada una nueva venta.

---

## Bloque 5 — Cliente opcional y trazabilidad nominal

### Objetivo

Permitir cliente solo cuando el operador lo necesita, sin ensuciar el flujo base.

### Tareas

1. Mostrar `anónimo` como estado por defecto.
2. Agregar acción discreta `Asignar cliente`.
3. Permitir seleccionar cliente activo sin convertirlo en campo permanente visible.
4. Reflejar el cliente actual en el resumen operativo de `POS`.

### Entregable esperado

El operador puede vender anónimo normalmente, pero también asociar cliente sin abandonar la experiencia rápida.

### Criterios de aceptación

- el cliente no aparece como selector permanente en la interfaz base,
- la asignación es simple y visible cuando se activa,
- el contexto del cliente se conserva si el flujo escala.

---

## Bloque 6 — Derivación a venta completa con contexto

### Objetivo

Resolver correctamente los casos fuera de alcance de Fase 1 sin castigar al usuario.

### Tareas

1. Definir mecanismo de transferencia de contexto hacia `Venta completa`.
2. Transferir al menos:
   - productos,
   - cantidades,
   - cliente si ya fue asignado.
3. Detectar casos fuera del alcance de Fase 1 y bloquear su cierre en `POS`.
4. Mostrar mensaje claro explicando por qué debe completarse en la venta completa.

### Entregable esperado

Cuando la venta ya no es simple, el operador no pierde trabajo previo.

### Criterios de aceptación

- `POS` deriva correctamente los casos fuera de fase,
- el contexto útil se conserva,
- el operador entiende por qué el caso salió del flujo rápido.

---

## Bloque 7 — Validación y compatibilidad con el dominio actual

### Objetivo

Cerrar la fase sin romper consistencia funcional.

### Tareas

1. Crear validaciones específicas para `POS`.
2. Asegurar que el backend no permita usar `POS` para casos fuera de fase.
3. Verificar compatibilidad con caja abierta y reglas monetarias existentes.
4. Agregar pruebas feature del flujo básico.
5. Agregar pruebas de derivación hacia `Venta completa`.

### Entregable esperado

La Fase 1 queda operativa y segura, no solo visualmente armada.

### Criterios de aceptación

- las validaciones viven en backend además de la UI,
- `POS` no permite guardar casos excluidos de Fase 1,
- las pruebas cubren flujo simple y flujo derivado.

---

## Diseño técnico sugerido para Fase 1

## Componentes mínimos

- `PosController`
- vista propia de `POS`
- request específico para `POS`
- mapper o assembler para traducir el payload rápido al formato de `CreateSaleService`

## Reutilización esperada

Reutilizar donde tenga sentido:

- búsqueda actual,
- lógica de ventas existente,
- layout general,
- componentes que no arrastren complejidad innecesaria.

Crear específico donde sí importe:

- UI de líneas,
- resumen operativo de `POS`,
- acciones discretas de mostrador,
- manejo visual del flujo rápido.

---

## Fuera de alcance explícito para Fase 1

No meter todavía:

- cambio de método de pago dentro de `POS`,
- pagos mixtos,
- cálculo de vuelto,
- fiado operativo completo dentro de `POS`,
- warnings confirmables dentro de `POS`,
- cambio manual de precio,
- permisos finos completos,
- promoción fuerte de `POS` como reemplazo total de `Ventas`,
- soporte específico de hardware más allá de dejar el input preparado para scanner futuro.

Esto NO significa que no exista en el diseño general. Solo significa que no debe entrar todavía en el primer corte.

---

## Riesgos de Fase 1

### 1. Heredar demasiada UX del formulario completo

Si la nueva pantalla se parece demasiado a la actual, el beneficio de `POS` se diluye.

### 2. Hacer demasiada lógica nueva en frontend

El frontend puede mejorar experiencia, pero no debe redefinir reglas de negocio.

### 3. Derivar tarde los casos complejos

Si el sistema deja avanzar demasiado y recién al final expulsa a `Venta completa`, genera frustración.

### 4. Querer meter pagos mixtos o warnings demasiado pronto

Eso inflaría la fase y la convertiría en una pseudo versión completa disfrazada.

---

## Criterio de cierre de Fase 1

La fase se considera cerrada cuando exista evidencia de que:

- `POS` sirve para ventas simples de contado en efectivo,
- el flujo es más rápido que la pantalla completa actual,
- el operador puede asignar cliente sin ensuciar la UI base,
- el sistema deriva correctamente los casos complejos a `Venta completa`,
- no se duplicó la lógica de negocio de ventas,
- la solución convive sin conflicto con el módulo `Ventas` existente.

---

## Fases posteriores sugeridas

### Fase 2

- cambio de método de pago,
- transferencia,
- pagos mixtos,
- mini paso de cobro más rico.

### Fase 3

- fiado dentro de `POS`,
- vuelto opcional,
- warnings con manejo excepcional,
- permisos finos.

### Fase 4

- consolidación de `POS` como entrada principal,
- refinamiento de comprobante,
- mejoras adicionales orientadas a scanner y operación sostenida.

---

## Estado implementado hoy

Aunque este documento nació para Fase 1, el frente `POS` ya avanzó más allá de ese corte inicial.

### Ya implementado

- acceso nuevo a `POS`,
- flujo base de búsqueda y líneas,
- efectivo,
- transferencia simple,
- pago mixto,
- vuelto opcional en efectivo,
- fiado total o parcial desde efectivo,
- derivación a `Venta completa`,
- pruebas feature específicas del flujo POS.

### Deuda abierta prioritaria

La siguiente prioridad ya no es agregar más reglas de cobro a ciegas, sino cerrar deuda de interacción:

1. **Consistencia de estados de botones contextuales**
   - `Asignar cliente`
   - `Ingresar monto recibido`
   - `Convertir a fiado`
   - todos deben reflejar mejor si están activos o no.

2. **Comportamiento de reactivación / reexposición**
   - si una acción ya fue activada y el operador vuelve a pulsarla después de usar otra, debe recuperarse de forma comprensible,
   - no debe dar la impresión de que el botón “murió” o hace algo distinto sin avisar.

3. **Cliente dentro de POS**
   - hacer explícita la capacidad de búsqueda,
   - luego incorporar creación rápida sin abandonar `POS`.

4. **Mejor aprovechamiento vertical del panel lateral**
   - reducir crecimiento hacia abajo cuando coinciden varios paneles abiertos,
   - evaluar un panel contextual más unificado en lugar de múltiples bloques apilados.

### Recomendación actual

Antes de seguir abriendo más excepciones en `POS`, conviene resolver esta deuda de UX y estabilidad visual. El dominio principal ya cubre suficiente; lo que ahora más duele es la orquestación de estado del frontend.

---

## Resultado esperado

Este plan debe permitir construir `POS` de forma seria:

- empezando por lo más usado,
- sin romper ventas actuales,
- sin mezclar excepciones demasiado temprano,
- y dejando una base sana para evolucionar luego.
