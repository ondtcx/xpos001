# Plan de iteraciones

## Propósito

Separar claramente qué entra en la primera iteración del sistema y qué se difiere a la segunda, para evitar dispersión y asegurar un arranque sólido.

## Decisión de alcance

Las **recargas** se implementarán en la **iteración 2**.

## Iteración 1 — Núcleo operativo

## Objetivo

Poner en producción local un núcleo confiable que permita abandonar Excel en la operación principal.

## Incluye

- autenticación y roles básicos,
- categorías, marcas y unidades base,
- productos y variantes,
- presentaciones de venta,
- historial de precios,
- proveedores,
- relación proveedor-producto,
- compras en modo rápido y detallado,
- prorrateos de compra,
- generación de lotes,
- inventario inicial por tandas,
- movimientos y ajustes de inventario,
- ventas de productos físicos,
- consumo FIFO,
- ventas con pago mixto,
- fiado parcial o total,
- clientes,
- abonos,
- apertura de caja,
- movimientos de caja,
- cierre de caja,
- reportes básicos del núcleo.

## Estado real actual dentro de iteración 1

Ya quedaron además implementados dentro del núcleo:

- compras detalladas con impuestos por tipo, descuentos, bonificaciones y prorrateos,
- edición/anulación controlada de compras cuando no hubo consumo,
- ventas con anulación total controlada,
- búsqueda POS en vivo por nombre, código interno y barcode,
- override manual de precio con trazabilidad,
- advertencias explícitas de stock insuficiente y costo pendiente,
- cobertura de pruebas feature/unitaria para los refinamientos críticos de compras y ventas.

## Reportes mínimos de iteración 1

- ventas del día,
- utilidad del día,
- compras del período,
- stock actual,
- productos por agotarse,
- fiados pendientes,
- abonos recibidos,
- cierre de caja,
- margen por producto,
- compras por proveedor,
- movimiento por lote.

## No incluye

- recargas,
- retornables avanzados,
- XML de facturas,
- exportaciones refinadas complejas,
- lector físico de código de barras,
- acceso desde teléfono como foco principal.

## Resultado esperado

El negocio ya puede trabajar sus flujos más críticos con mejor control que en Excel.

## Iteración 2 — Extensión operativa

## Objetivo

Agregar módulos y aceleradores que mejoran cobertura y velocidad de operación, sin tocar la base del núcleo.

## Incluye

- saldo de plataforma de recargas,
- cargas de saldo,
- venta de recargas,
- comisión por recarga,
- recargas fiadas,
- reportes de recargas,
- pantallas asociadas de recargas,
- preparación adicional para móvil o red local si ya hace falta.

## Posibles extras si la iteración 1 queda estable

- XML de facturas como borrador de compra,
- retornables básicos,
- exportaciones más completas,
- alertas más refinadas.

## Dependencias de iteración 2

Para agregar recargas correctamente ya debe existir:

- venta con múltiples tipos de ítem,
- pagos y fiado funcionales,
- caja diaria estable,
- reglas monetarias definidas,
- reportes base operativos.

## Criterios para pasar a iteración 2

No conviene avanzar a recargas si aún falla cualquiera de estos puntos:

- compras no generan lotes confiables,
- FIFO no está estable,
- caja no cierra correctamente,
- fiado o abonos tienen inconsistencias,
- utilidad del día no es confiable.

## Orden recomendado dentro de iteración 1

### Bloque A

- seguridad,
- catálogo,
- precios,
- proveedores.

### Bloque B

- compras,
- lotes,
- inventario inicial,
- movimientos de inventario.

### Bloque C

- ventas,
- pagos,
- FIFO,
- clientes,
- fiado,
- abonos.

### Bloque D

- caja,
- reportes del núcleo,
- endurecimiento de validaciones.

## Orden recomendado dentro de iteración 2

### Bloque E

- balance de recargas,
- cargas de saldo,
- integración con caja.

### Bloque F

- recargas en venta,
- comisiones,
- recargas fiadas,
- reportes de recargas.

## Riesgos que evita esta separación

- inflar la primera iteración con demasiados casos especiales,
- mezclar lógica de recargas con ventas antes de estabilizar pagos,
- retrasar la salida del núcleo por una funcionalidad importante pero no crítica para arrancar.

## Señal de que la iteración 1 ya está bien

Cuando puedas hacer esto sin Excel:

1. registrar compra,
2. generar lote,
3. vender,
4. fiar parcialmente,
5. cobrar abono,
6. cerrar caja,
7. ver utilidad y stock confiables.

Si eso funciona, entonces sí tiene sentido abrir iteración 2.
