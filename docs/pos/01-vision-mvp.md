# Visión y alcance del MVP

## Problema actual

La operación actual en Excel presenta limitaciones estructurales:

- datos crecientes y difíciles de mantener,
- registros faltantes, huérfanos o duplicados,
- poca trazabilidad de compras y ventas,
- costeo inexacto por uso de precio promedio o referencias inconsistentes,
- dificultad para conocer utilidad real,
- manejo manual e inseguro de fiado, recargas y cierres de caja.

El problema de fondo NO es solamente “registrar ventas”. El negocio necesita un sistema integral para operar compras, inventario, ventas, cuentas por cobrar, recargas y reportes con precisión.

## Objetivo del sistema

Construir una solución local, simple e intuitiva para una sola tienda, operada principalmente desde PC, que permita controlar la operación diaria sin complejidad innecesaria y sin perder precisión de costos.

## Alcance del MVP / iteración 1 actual

### Incluido desde la primera versión

- Usuarios y roles básicos.
- Catálogo de productos, variantes, marcas y categorías.
- Presentaciones de venta y conversiones a unidad base.
- Compras con lotes en versión rápida, con evolución posterior a impuestos, descuentos y bonificaciones más fieles.
- Inventario inicial por tandas, con estado de regularización.
- Ventas con pago en efectivo, transferencia, mixto y fiado parcial o total.
- Cuentas por cobrar y abonos.
- Caja diaria con apertura, movimientos y cierre.
- Reportes operativos prioritarios y exportaciones básicas.

### Preparado, pero no obligatorio en la primera versión

- Importación inicial del catálogo desde Excel.
- Compras detalladas con impuestos por línea, descuentos, bonificaciones y adjuntos.
- Vencimientos por lote y alertas básicas.
- Recargas telefónicas con saldo disponible y comisión.
- Lectura asistida de XML de factura electrónica.
- Gestión móvil desde teléfono en red local.
- Código de barras mediante lector físico.
- Flujo completo de envases retornables.
- Impresión de tickets.

## Decisiones funcionales clave

### 1. Costeo por lote

Cada compra genera lotes. La utilidad se calcula contra el costo real del lote consumido. Esto evita la distorsión del costo promedio cuando hay cambios frecuentes de precio, promociones o bonificaciones.

### 2. FIFO como política de salida

La salida de inventario usará FIFO automático porque es entendible, auditable y suficientemente preciso para el negocio. La venta debe guardar trazabilidad de qué lotes fueron consumidos.

### 3. Precio de venta con historial

El precio de venta no se modela como un único valor sobrescrito. Debe existir historial por presentación, con posibilidad de que el sistema sugiera cambios cuando entra un lote más caro, manteniendo la decisión final en manos del usuario.

### 4. Operación no bloqueante durante el arranque

Se permitirá vender aunque el inventario inicial no esté completo, pero dejando alertas y marcas como:

- stock negativo,
- costo pendiente,
- producto por regularizar.

Esto facilita la adopción sin sacrificar control.

### 5. Separación explícita de dominios

El sistema debe separar claramente:

- ventas de productos,
- cuentas por cobrar,
- caja,
- inventario,
- recargas,
- retornables.

Si estos conceptos se mezclan, los números dejan de cuadrar.

## Perfil operativo objetivo

- Una sola tienda.
- Operación principal por una persona, con apoyo ocasional de uno o dos ayudantes.
- Uso prioritario en computadora local.
- Necesidad de trabajar sin internet.
- Interfaz rápida para tareas repetitivas.

## Reportes obligatorios del MVP

- Ventas del día.
- Utilidad del día.
- Compras del período.
- Stock actual.
- Productos por agotarse.
- Fiados pendientes.
- Abonos recibidos.
- Cierre de caja.
- Margen por producto.
- Compras por proveedor.
- Movimiento por lote.

## Recomendación de migración

No se recomienda migrar el historial viejo completo como fuente confiable. Lo correcto es:

1. importar catálogo base desde Excel,
2. limpiar duplicados relevantes,
3. crear presentaciones y variantes,
4. cargar inventario inicial por tandas,
5. marcar ese inventario como estimado o no auditado,
6. empezar a operar correctamente desde el nuevo sistema.

## Resultado esperado

Al finalizar la iteración 1 del MVP, la tienda debe poder operar compras, ventas, fiado, caja y reportes básicos sin depender de Excel y con visibilidad suficiente para tomar decisiones sobre utilidad, stock y compras. Las recargas quedan preparadas para una iteración posterior, una vez estabilizado el núcleo.
