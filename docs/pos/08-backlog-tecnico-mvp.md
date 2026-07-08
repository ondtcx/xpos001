# Backlog técnico ejecutable del MVP

## Propósito

Este documento define el backlog operativo real del proyecto POS. Su objetivo es reflejar con claridad:

- lo ya implementado,
- lo que está en refinamiento,
- lo que sigue a continuación,
- y lo que queda diferido para iteraciones posteriores.

No debe funcionar como una lista aspiracional desconectada del estado real, sino como una guía práctica para decidir el siguiente trabajo.

## Convenciones

- **Epic**: bloque funcional grande.
- **Historia**: necesidad concreta del negocio.
- **Tarea técnica**: trabajo implementable.
- **Criterio de aceptación**: condición mínima para considerar la historia cerrada.
- **Estado**:
  - `completado`
  - `en refinamiento`
  - `pendiente`
  - `diferido`
- **Prioridad**:
  - `alta`
  - `media`
  - `posterior`

## Estado actual del producto

### Completado

- autenticación y roles básicos,
- catálogo base,
- proveedores,
- compras rápidas,
- inventario inicial,
- lotes,
- ventas,
- fiado y abonos,
- caja,
- reportes operativos,
- exportaciones básicas,
- refinamientos UX iniciales,
- compras detalladas con prorrateos y corrección controlada,
- ventas refinadas con anulación total, búsqueda rápida, override de precio y warnings explícitos.

### En refinamiento

- UX operativa transversal,
- visibilidad operativa y auditabilidad fina en compras/ventas,
- exportaciones más completas.

### Cerrado recientemente

- semántica de reportes `bruto + neto` para ventas, compras y cobranza,
- exclusión de ventas anuladas y líneas con warning de la utilidad/margen principal,
- separación explícita de reversas de caja frente a operación vigente,
- consolidado histórico de caja por período con análisis de diferencias,
- envejecimiento operativo y seguimiento enriquecido de cuentas por cobrar.

### Diferido a iteración 2 o posterior

- recargas,
- XML de compras,
- retornables,
- importación inicial asistida desde Excel,
- acceso móvil o red local como prioridad,
- periféricos especializados.

---

## Epic 1 — Base del sistema

**Estado:** completado  
**Prioridad:** alta

### Historia 1.1 — Como administrador quiero iniciar sesión y gestionar acceso básico

#### Tareas técnicas
- autenticación local,
- entidades `users`, `roles`, `role_user`,
- roles base,
- protección de rutas,
- usuario administrador inicial.

#### Criterios de aceptación
- existe login funcional,
- las rutas privadas requieren autenticación,
- existe al menos un usuario administrador inicial,
- el sistema distingue acceso administrativo y operativo.

---

## Epic 2 — Catálogo

**Estado:** completado  
**Prioridad:** alta

### Historia 2.1 — Como operador quiero registrar categorías, marcas y unidades base

#### Tareas técnicas
- CRUD de categorías,
- CRUD de marcas,
- CRUD de unidades base.

#### Criterios de aceptación
- se pueden crear y editar registros,
- no hay duplicados obvios en nombres,
- la base del catálogo queda lista para productos y variantes.

### Historia 2.2 — Como operador quiero registrar productos y variantes

#### Tareas técnicas
- CRUD de productos,
- CRUD de variantes,
- soporte para código interno, SKU y código de barras,
- soporte de estado activo/inactivo/descontinuado.

#### Criterios de aceptación
- un producto puede tener múltiples variantes,
- una variante puede identificarse por nombre o código,
- el estado del catálogo queda explícito.

### Historia 2.3 — Como operador quiero definir presentaciones y precios vigentes

#### Tareas técnicas
- CRUD de presentaciones,
- historial de precios,
- cambio de precio con vigencia,
- validación de factor de conversión,
- validación de una sola presentación por defecto.

#### Criterios de aceptación
- una variante puede venderse en múltiples presentaciones,
- el sistema conserva histórico de precios,
- solo un precio vigente por presentación está activo al mismo tiempo.

---

## Epic 3 — Proveedores

**Estado:** completado  
**Prioridad:** alta

### Historia 3.1 — Como operador quiero registrar proveedores básicos

#### Tareas técnicas
- CRUD de proveedores,
- relación proveedor-variante,
- almacenamiento de última referencia de compra.

#### Criterios de aceptación
- se puede registrar proveedor,
- se puede asociar proveedor con variante,
- queda trazabilidad mínima de referencia comercial.

---

## Epic 4 — Compras rápidas e inventario inicial

**Estado:** completado en versión inicial  
**Prioridad:** alta

### Historia 4.1 — Como operador quiero registrar compras rápidas

#### Tareas técnicas
- formulario de cabecera,
- detalle de líneas,
- persistencia transaccional,
- creación automática de lotes,
- creación de movimientos de inventario,
- actualización de referencia proveedor-variante.

#### Criterios de aceptación
- una compra se registra completa,
- la compra genera lotes,
- el inventario aumenta correctamente,
- la compra queda relacionada con proveedor si aplica.

### Historia 4.2 — Como operador quiero cargar inventario inicial por tandas

#### Tareas técnicas
- formulario de inventario inicial,
- marcador de inventario auditado/no auditado,
- creación de lote origen `opening_balance`,
- creación de movimientos asociados.

#### Criterios de aceptación
- se puede cargar stock inicial por grupos,
- el sistema distingue stock estimado de compras reales,
- el inventario inicial queda trazable.

---

## Epic 5 — Ventas y cuentas por cobrar

**Estado:** completado en versión inicial  
**Prioridad:** alta

### Historia 5.1 — Como operador quiero registrar ventas rápidas de productos físicos

#### Tareas técnicas
- pantalla de venta,
- selección de presentaciones,
- cálculo automático del total,
- registro de pagos,
- persistencia transaccional.

#### Criterios de aceptación
- una venta simple se registra en pocos pasos,
- el total es consistente,
- la venta queda asociada a usuario y cliente si aplica.

### Historia 5.2 — Como sistema quiero consumir inventario por FIFO

#### Tareas técnicas
- algoritmo FIFO,
- asignación de lotes a líneas,
- creación de `sale_item_lot_consumptions`,
- movimientos de salida,
- advertencia de stock o costo pendiente.

#### Criterios de aceptación
- el costo de venta se calcula desde lotes reales,
- la venta consume stock disponible,
- si falta stock o costo, la venta deja advertencia trazable.

### Historia 5.3 — Como operador quiero vender con pago mixto o fiado parcial

#### Tareas técnicas
- múltiples formas de pago por venta,
- captura de monto pagado y monto fiado,
- validación de cliente cuando haya saldo pendiente,
- generación de cuenta por cobrar.

#### Criterios de aceptación
- una venta puede combinar efectivo, transferencia y fiado,
- el saldo pendiente queda registrado correctamente,
- no se permite fiado sin cliente.

### Historia 5.4 — Como operador quiero registrar abonos

#### Tareas técnicas
- pantalla de abonos,
- actualización de saldo pendiente,
- integración obligatoria con caja abierta,
- historial de pagos.

#### Criterios de aceptación
- un abono reduce la deuda,
- el estado de cuenta refleja el movimiento,
- no se puede abonar más de lo debido,
- no se puede registrar abono sin caja abierta.

---

## Epic 6 — Caja diaria

**Estado:** completado en versión inicial  
**Prioridad:** alta

### Historia 6.1 — Como operador quiero abrir caja

#### Tareas técnicas
- formulario de apertura,
- validación de una sola caja abierta activa,
- creación de sesión de caja,
- registro de movimiento inicial.

#### Criterios de aceptación
- la sesión queda abierta con monto inicial,
- no se duplica caja abierta por error,
- la apertura queda auditada.

### Historia 6.2 — Como operador quiero registrar gastos, retiros e ingresos manuales

#### Tareas técnicas
- formulario de movimientos de caja,
- tipos de movimiento,
- validación contra saldo esperado por método,
- relación opcional con referencias.

#### Criterios de aceptación
- el movimiento queda auditado,
- no se puede retirar o gastar por encima del saldo esperado,
- el cálculo esperado del cierre se ajusta correctamente.

### Historia 6.3 — Como operador quiero cerrar caja y ver diferencias

#### Tareas técnicas
- cálculo de efectivo esperado,
- captura de efectivo contado,
- cálculo de diferencias,
- resumen por método de pago.

#### Criterios de aceptación
- el cierre muestra resumen claro,
- las diferencias quedan registradas,
- la caja cerrada ya no acepta movimientos.

---

## Epic 7 — Reportes y exportaciones básicas

**Estado:** completado en versión inicial  
**Prioridad:** alta

### Historia 7.1 — Como administrador quiero ver ventas y utilidad del período

#### Tareas técnicas
- consulta agregada por fecha,
- filtros básicos,
- resumen por tipo de pago,
- resumen por utilidad.

#### Criterios de aceptación
- el reporte cuadra con las ventas registradas,
- la utilidad usa costo real por lotes.

### Historia 7.2 — Como administrador quiero ver stock actual y productos por agotarse

#### Tareas técnicas
- consulta de stock disponible,
- alertas por umbral,
- filtros básicos,
- vista resumida por producto/variante separada de `Inventario inicial`,
- acceso claro al detalle por lotes desde el stock actual.

#### Criterios de aceptación
- el stock puede verse por variante,
- el sistema destaca productos críticos,
- la navegación no confunde `stock actual` con `inventario inicial`.

### Historia 7.3 — Como administrador quiero ver fiados, abonos y cierres de caja

#### Tareas técnicas
- reporte de cuentas por cobrar,
- reporte de abonos,
- reporte de cierres de caja.

#### Criterios de aceptación
- se puede conocer saldo pendiente por cliente,
- se puede revisar histórico de cierres.

### Historia 7.4 — Como operador quiero exportar información operativa básica

#### Tareas técnicas
- exportación CSV,
- vista imprimible,
- formato operativo mínimo.

#### Criterios de aceptación
- el sistema puede exportar reportes en CSV,
- existe salida imprimible básica,
- la exportación no altera los datos originales.

---

## Epic 8 — Refinamiento de compras

**Estado:** completado en versión inicial refinada  
**Prioridad:** alta

### Historia 8.1 — Como operador quiero registrar compras con mayor fidelidad tributaria

#### Tareas técnicas
- impuestos por línea,
- descuentos por línea,
- bonificaciones,
- prorrateos reproducibles,
- costo final auditable por línea.

#### Criterios de aceptación
- el sistema representa compras complejas con fidelidad razonable,
- el costo final por línea queda claramente trazable,
- el total final es consistente con cabecera y detalle.

### Historia 8.2 — Como operador quiero un modo detallado de compra sin perder velocidad

#### Tareas técnicas
- mantener modo rápido actual,
- agregar modo detallado,
- validaciones claras,
- resumen monetario consistente.

#### Criterios de aceptación
- el operador puede usar modo rápido o detallado según el caso,
- la UX no se vuelve confusa,
- el sistema sigue permitiendo capturar compras simples sin fricción.

---

## Epic 9 — Refinamiento de ventas

**Estado:** completado en versión inicial refinada  
**Prioridad:** alta

### Historia 9.1 — Como operador quiero una venta más rápida

#### Tareas técnicas
- búsqueda por nombre/código más rápida,
- flujo más corto,
- ayudas visuales en POS,
- reducción de fricción para registrar líneas.

#### Criterios de aceptación
- una venta frecuente requiere menos pasos,
- la búsqueda encuentra productos de forma más ágil,
- la operación repetitiva se vuelve más rápida.

### Historia 9.2 — Como administrador quiero edición o anulación controlada de ventas

#### Tareas técnicas
- reglas de autorización,
- trazabilidad de cambios,
- reversión consistente de inventario,
- reversión consistente de pagos, fiado y caja cuando aplique.

#### Criterios de aceptación
- la venta puede corregirse sin romper consistencia,
- toda reversión queda auditada,
- no se altera inventario ni caja sin rastro histórico.

### Historia 9.3 — Como operador quiero manejar excepciones de venta con más claridad

#### Tareas técnicas
- cambio manual de precio por línea,
- advertencias de margen,
- mejores mensajes de stock/costo pendiente.

#### Criterios de aceptación
- el cambio manual de precio deja trazabilidad,
- el sistema alerta cuando el margen sea bajo o negativo,
- las advertencias ayudan sin bloquear innecesariamente.

---

## Epic 10 — Refinamiento de caja y cuentas por cobrar

**Estado:** completado  
**Prioridad:** media

### Historia 10.1 — Como administrador quiero mayor visibilidad de caja

#### Tareas técnicas
- reportes consolidados por período,
- visibilidad de diferencias históricas,
- mejoras de consulta por método y tipo.

#### Criterios de aceptación
- la caja puede revisarse más allá de la sesión individual,
- el histórico permite detectar patrones de diferencia.

#### Estado real implementado
- consolidado de caja por rango para sesiones cerradas,
- métricas de exactitud/faltantes/sobrantes/peor desvío,
- análisis neto por tipo y método,
- cobertura feature para consolidación.

### Historia 10.2 — Como administrador quiero seguimiento más sólido de fiados

#### Tareas técnicas
- alertas de deuda antigua,
- mejor estado de cuenta,
- base para exportación futura de cuentas.

#### Criterios de aceptación
- el sistema identifica deudas envejecidas,
- el estado de cuenta es más útil para seguimiento operativo.

#### Estado real implementado
- buckets de envejecimiento `0-7`, `8-30` y `31+`,
- resumen de cartera abierta pendiente,
- métricas de seguimiento en detalle: días abiertos, monto cobrado, progreso y último abono,
- cobertura feature para aging y tracking.

---

## Epic 11 — UX operativa transversal

**Estado:** en refinamiento  
**Prioridad:** alta

### Hallazgos recientes que deben absorberse en este epic

- la semántica actual de `Inventario` induce a error porque abre `Inventario inicial` y no una vista de stock actual,
- el detalle por lotes existe, pero falta una puerta de entrada más clara para consulta operativa diaria,
- la venta actual sigue siendo útil para casos completos, pero muestra demasiadas excepciones por defecto para mostrador,
- el flujo frecuente de venta debe optimizarse para: producto + cantidad + efectivo + cliente anónimo + guardar.

### Historia 11.1 — Como operador quiero menos fricción en formularios críticos

#### Tareas técnicas
- mejoras en compras,
- mejoras en ventas,
- mejoras en inventario inicial,
- navegación entre módulos relacionados.

#### Criterios de aceptación
- las tareas frecuentes requieren menos pasos,
- el flujo entre módulos críticos es más directo.

### Historia 11.3 — Como operador quiero consultar stock actual sin confundirlo con inventario inicial

#### Tareas técnicas
- renombrar o reposicionar la entrada principal de inventario,
- crear vista de stock actual por producto o variante,
- mantener `Inventario inicial` como flujo específico de regularización,
- enlazar desde stock actual hacia lotes y movimientos relevantes.

#### Criterios de aceptación
- entrar a `Inventario` muestra stock operativo actual y no solo cargas iniciales,
- `Inventario inicial` queda identificado como flujo aparte,
- el operador entiende sin ambigüedad dónde revisar stock y dónde registrar apertura.

### Historia 11.4 — Como operador quiero una venta de mostrador realmente rápida

#### Tareas técnicas
- crear interfaz adicional de `venta rápida` reutilizando el mismo dominio actual,
- dejar fecha no editable y cliente anónimo por defecto,
- dejar efectivo como método base del flujo habitual,
- ocultar por defecto override manual, pagos mixtos y confirmaciones de warning,
- mostrar opciones adicionales solo cuando el caso lo requiera,
- agregar cálculo opcional de vuelto cuando aplique.

#### Criterios de aceptación
- una venta frecuente puede cerrarse con muy pocos pasos,
- los campos excepcionales solo aparecen cuando son necesarios,
- la lógica de negocio no se duplica respecto a la venta completa,
- la operación rápida sigue siendo trazable y compatible con caja, fiado y warnings.

### Historia 11.2 — Como operador quiero ayudas visuales más claras

#### Tareas técnicas
- resúmenes monetarios,
- estados visibles,
- acciones rápidas,
- feedback de validación más claro.

#### Criterios de aceptación
- el sistema reduce errores operativos,
- el usuario entiende más rápido el estado de la operación.

---

## Epic 12 — Exportaciones más completas

**Estado:** en refinamiento  
**Prioridad:** media

### Historia 12.1 — Como administrador quiero exportación real a Excel

#### Tareas técnicas
- selección de reportes exportables,
- formato tabular consistente,
- compatibilidad con uso operativo habitual.

#### Criterios de aceptación
- los reportes clave pueden exportarse a Excel,
- el archivo exportado es utilizable sin reprocesamiento manual excesivo.

#### Estado real implementado
- exportación `.xlsx` real para ventas, compras y cobranza,
- múltiples hojas por dominio para resumen + detalle,
- formato administrativo ligero con encabezados, autosize, freeze de fila y formato monetario/cantidades,
- cobertura feature para validar hojas y contenido exportado.

### Historia 12.2 — Como administrador quiero exportación real a PDF

#### Tareas técnicas
- plantillas legibles,
- salida formal por reporte,
- formato adecuado para archivo o impresión.

#### Criterios de aceptación
- los reportes clave pueden exportarse a PDF,
- el formato es legible y consistente.

#### Estado real implementado
- PDF real para ventas, compras y cobranza,
- una plantilla por dominio con resumen + detalle en el mismo archivo,
- orientación por reporte según legibilidad,
- botones PDF integrados junto a CSV y Excel.

---

## Epic 13 — Recargas

**Estado:** diferido  
**Prioridad:** posterior

### Alcance futuro
- saldo de plataforma,
- cargas,
- venta de recargas,
- comisión,
- recargas fiadas,
- reportes de recargas.

---

## Epic 14 — XML de compras

**Estado:** diferido  
**Prioridad:** posterior

### Alcance futuro
- parser de XML,
- mapeo de líneas,
- propuesta de borrador,
- revisión manual antes de confirmar.

---

## Epic 15 — Retornables

**Estado:** diferido  
**Prioridad:** posterior

### Alcance futuro
- modelo de ítems retornables,
- relación con venta,
- reglas de devolución o pérdida,
- impacto en caja y cuentas.

---

## Epic 16 — Importación inicial desde Excel

**Estado:** diferido  
**Prioridad:** posterior

### Alcance futuro
- plantilla de importación,
- parser del archivo,
- vista previa,
- deduplicación mínima.

---

## Epic 17 — Acceso móvil, red local y periféricos

**Estado:** diferido  
**Prioridad:** posterior

### Alcance futuro
- exposición en red local,
- revisión de seguridad mínima,
- compatibilidad con periféricos si hace falta.

---

## Dependencias críticas reales

- no abrir recargas antes de estabilizar ventas, caja y fiado,
- no abrir XML antes de cerrar compras detalladas manuales,
- no abrir exportaciones complejas antes de consolidar datos del núcleo,
- no seguir agregando exportes por checklist si no aportan valor operativo claro,
- no implementar anulaciones sin definir reversión de inventario, pagos y caja,
- no meter automatizaciones o periféricos antes de cerrar el flujo POS base.

## Orden recomendado desde hoy

1. Resolver fricción del núcleo en `stock actual vs inventario inicial` y `venta rápida de mostrador`
2. Recién después reevaluar si conviene abrir Epic 13 — Recargas
3. Epic 14 — XML de compras
4. Epic 15 — Retornables
5. Epic 16 — Importación inicial desde Excel
6. Epic 17 — Acceso móvil, red local y periféricos

## Qué NO meter todavía

- recargas como prioridad inmediata,
- XML automático completo,
- retornables avanzados,
- lector físico como foco principal,
- móvil como prioridad del producto,
- importación Excel como dependencia del núcleo actual.

## Criterio para pasar a iteración 2

Solo conviene abrir iteración 2 si ya se cumple esto con evidencia:

- compras reflejan costo real con suficiente fidelidad,
- ventas son rápidas y trazables,
- caja cierra consistentemente,
- fiado y abonos están sólidos,
- reportes del núcleo son confiables,
- la UX operativa transversal del núcleo ya no introduce fricción crítica,
- las exportaciones básicas ya no son el cuello de botella principal.

## Nota de uso

Este backlog debe mantenerse alineado con:

- `14-plan-iteraciones.md`
- `16-plan-implementacion-ejecutable.md`
- `17-estado-implementacion.md`

Si alguno de esos documentos cambia, este backlog también debe revisarse para no volver a mezclar visión antigua con ejecución actual.
