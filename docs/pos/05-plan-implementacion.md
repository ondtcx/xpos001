# Plan de implementación

## Estrategia general

Construir en fases pequeñas, cerrando primero el núcleo operativo. El objetivo no es “tener todo”, sino empezar a operar con una base correcta y luego expandir sin reescribir el sistema.

## Principios de implementación

- primero estabilidad del modelo, luego automatizaciones,
- primero operaciones centrales, luego casos especiales,
- primero trazabilidad, luego comodidad extra,
- no bloquear la adopción por exigir inventario perfecto desde el día 1.

## Fase 0 — Preparación

### Objetivo

Preparar la base del proyecto y el catálogo inicial.

### Entregables

- estructura base del proyecto,
- autenticación y roles,
- catálogo de categorías, marcas, productos y variantes,
- presentaciones de venta,
- historial de precios.

### Resultado esperado

El negocio puede empezar a cargar productos correctamente y abandonar la estructura improvisada de Excel para el catálogo.

## Fase 1 — Núcleo operativo

### Objetivo

Permitir operación real de compras, ventas y control mínimo diario.

### Entregables

- proveedores,
- compras y detalle de compras,
- creación de lotes,
- inventario inicial por tandas,
- movimientos de inventario,
- ventas,
- consumo FIFO,
- fiado y abonos,
- caja diaria.

### Resultado esperado

La tienda ya puede registrar su operación principal y conocer ventas, utilidad, stock y cuentas pendientes.

## Fase 2 — Refinamiento del núcleo

### Objetivo

Cerrar brechas del núcleo antes de abrir módulos nuevos o casos especiales.

### Entregables

- compras más fieles con impuestos, descuentos y bonificaciones,
- mejoras de UX en compras, ventas e inventario,
- búsquedas más rápidas para POS,
- endurecimiento de anulaciones/edición controlada,
- reportes y exportaciones más completas,
- vencimientos y alertas,
- sugerencia de precio por nuevo lote,
- precios mínimos y alertas de margen.

### Resultado esperado

El núcleo transaccional deja de ser solo usable y pasa a ser más confiable, rápido y auditable para la operación diaria.

## Fase 3 — Casos especiales y aceleradores

### Objetivo

Cubrir situaciones más específicas sin comprometer el núcleo.

### Entregables

- recargas completas con reportes refinados,
- retornables,
- adjuntos mejorados,
- importación XML asistida,
- acceso desde red local / teléfono,
- soporte más cómodo a código de barras.

### Resultado esperado

Se reducen fricciones operativas en tareas específicas y se amplía la cobertura funcional.

## Orden recomendado de construcción técnica

1. seguridad y usuarios,
2. catálogo,
3. presentaciones y precios,
4. proveedores,
5. compras,
6. lotes e inventario,
7. ventas,
8. cuentas por cobrar,
9. caja,
10. reportes y exportaciones básicas,
11. refinamiento del núcleo,
12. recargas,
13. vencimientos,
14. retornables,
15. XML de facturas,
16. importación de catálogo desde Excel.

## Backlog del MVP

## Prioridad alta

- usuarios y roles básicos,
- productos, variantes y presentaciones,
- compras por lote,
- inventario inicial,
- ventas,
- fiado y abonos,
- caja,
- reportes principales.

## Prioridad media

- proveedores por producto,
- exportaciones más completas,
- refinamientos de UX y búsqueda,
- alertas de stock,
- vencimientos,
- adjuntos de compra,
- sugerencias de precio.

## Prioridad posterior

- recargas,
- retornables avanzados,
- XML de factura,
- móvil en red local,
- lector de código de barras,
- impresión de tickets.

## Riesgos y mitigaciones

### Riesgo 1: complejidad excesiva en compras

**Mitigación**: ofrecer modo rápido y modo detallado.

### Riesgo 2: lentitud en adopción por inventario incompleto

**Mitigación**: permitir regularización por tandas con alertas explícitas.

### Riesgo 3: utilidad incorrecta

**Mitigación**: costeo por lote y consumo FIFO con trazabilidad.

### Riesgo 4: caja descuadrada

**Mitigación**: separar caja de venta, fiado y abonos; exigir apertura y cierre.

### Riesgo 5: contaminar el sistema nuevo con historial viejo defectuoso

**Mitigación**: importar catálogo, no reconstruir a ciegas la historia completa.

## Criterios de aceptación del MVP

- Se puede registrar una compra rápida y generar lotes consultables.
- Se puede vender una presentación y descontar stock correctamente.
- Se puede registrar fiado parcial y luego un abono.
- Se puede abrir y cerrar caja con diferencias visibles.
- Se pueden consultar ventas y utilidad del día.
- Se puede ver stock actual y productos por agotarse.

## Estado actual resumido

- Ya están implementados: autenticación, roles, catálogo, proveedores, compras rápidas, inventario inicial, lotes, ventas, fiado, abonos, caja, reportes operativos y exportaciones básicas.
- El foco recomendado ya NO es abrir recargas, sino refinar compras, ventas, UX y exportaciones antes de pasar a iteración 2.

## Recomendación de adopción

1. Cargar catálogo mínimo.
2. Registrar inventario inicial por familias de productos.
3. Empezar con compras nuevas bien registradas.
4. Operar ventas y fiado diariamente.
5. Revisar caja cada cierre.
6. Ajustar reglas finas después de una o dos semanas de uso real.
