# Estado de implementación

## Propósito

Registrar el estado real del sistema a medida que se construye. Este documento evita que la documentación de diseño y la implementación se separen.

## Resumen actual

### Backend

- Framework: Laravel 12
- Base de datos: SQLite
- UI base: Blade + Breeze + Livewire instalado
- Ubicación del proyecto: `backend/`

## Decisiones aplicadas en la implementación

- Laravel vive en `backend/` para no mezclar código con `docs/`.
- Se usa Laravel 12 porque el entorno actual tiene PHP 8.2.
- El dinero se maneja en centavos en columnas monetarias implementadas.
- El registro público fue desactivado.
- Las recargas siguen fuera de la iteración 1.

## Módulos implementados hasta ahora

## 1. Base técnica

Estado: ✅ implementado

Incluye:

- proyecto Laravel inicial,
- SQLite operativa,
- autenticación base,
- seeders iniciales,
- helper monetario,
- dashboard inicial.

## 2. Seguridad mínima

Estado: ✅ implementado

Incluye:

- roles `admin` y `assistant`,
- usuario administrador inicial,
- relación `role_user`,
- extensión de `users` con `username` e `is_active`.

## 3. Catálogo

Estado: ✅ implementado en versión inicial

Incluye:

- categorías,
- marcas,
- unidades base,
- productos,
- variantes,
- presentaciones,
- historial de precios.

Notas:

- ya existe navegación y pantallas de alta/edición,
- los precios cierran vigencia anterior al registrar uno nuevo,
- ya se valida una sola presentación por defecto por variante.

## 4. Proveedores

Estado: ✅ implementado en versión inicial

Incluye:

- CRUD básico de proveedores.

## 5. Compras

Estado: ✅ implementado en versión rápida y refinada

Incluye:

- cabecera de compra,
- múltiples líneas,
- costo unitario por línea,
- proveedor opcional,
- factura opcional,
- generación automática de lotes,
- creación de movimientos de inventario,
- actualización de referencia proveedor-variante con último precio,
- compra detallada separada de compra rápida,
- impuestos globales por tipo (`IVA`, `ICE`, `otro`),
- impuestos por línea,
- descuentos globales y por línea,
- bonificación del mismo producto en la misma línea,
- bonificación de producto distinto como línea separada,
- costo final por línea con prorrateos,
- edición solo si no hubo consumo,
- anulación lógica con motivo obligatorio,
- bloqueo de edición/anulación cuando ya hubo consumo,
- tests unitarios y feature del flujo detallado.

Limitaciones actuales:

- sin lectura XML.

## 6. Inventario inicial

Estado: ✅ implementado en versión inicial

Incluye:

- registro manual de inventario inicial,
- costo estimado,
- marca auditado/no auditado,
- creación automática de lote,
- creación de movimiento de inventario.

## 7. Lotes

Estado: ✅ implementado en consulta inicial

Incluye:

- listado de lotes,
- origen,
- cantidad inicial,
- cantidad disponible,
- costo unitario,
- estado.

## Módulos del núcleo implementados y su estado

## 8. Ventas

Estado: ✅ implementado en versión inicial y refinada

Incluye:

- ventas de productos físicos,
- pagos en efectivo y transferencia,
- fiado parcial calculado por diferencia,
- consumo FIFO básico por lotes,
- creación de consumos de lote por línea,
- anulación total controlada con motivo obligatorio,
- reversa de lotes consumidos,
- reversa de caja en caja abierta actual,
- reversa de cuenta por cobrar,
- reversa trazable de abonos,
- búsqueda POS en vivo por nombre, código interno y barcode,
- autoselección en coincidencias exactas por código/barcode,
- override manual de precio con motivo,
- bloqueo de venta por debajo de costo,
- advertencias explícitas con confirmación para stock insuficiente,
- advertencias explícitas con confirmación para costo pendiente,
- señales visibles de override y warnings en el índice,
- tests feature para anulación, búsqueda, override y warnings.

Limitaciones actuales:

- no hay edición de venta confirmada,
- no hay anulación parcial,
- no hay recalculo proporcional de caja/fiado/abonos en anulaciones parciales.

## 9. Fiado y abonos

Estado: ✅ implementado en versión inicial

Incluye:

- clientes,
- cuentas por cobrar,
- abonos,
- saldo pendiente,
- integración con ventas fiadas,
- reversa trazable de abonos cuando una venta total se anula.

Limitaciones actuales:

- sin alertas de deuda antigua,
- sin estados de cuenta exportables.

## 10. Caja

Estado: ✅ implementado en versión inicial

Incluye:

- apertura,
- movimientos,
- cierre,
- diferencias.

Incluye adicionalmente:

- vínculo automático de pagos de venta con caja abierta,
- vínculo automático de abonos con caja abierta,
- movimientos manuales de gasto, retiro e ingreso extraordinario,
- cálculo de efectivo esperado y transferencias esperadas al cierre.

Limitaciones actuales:

- no hay reporte consolidado de caja por período,
- no hay reapertura o corrección de cierre,
- no existen permisos finos aún para apertura/cierre,
- no se muestran todavía dashboards de diferencias históricas.

## 11. Recargas

Estado: ⏸ diferido a iteración 2

## 12. Reportes operativos

Estado: ✅ implementado en versión inicial

Incluye:

- ventas del día o rango,
- utilidad del día o rango,
- compras del período,
- stock actual total,
- productos por agotarse,
- fiados pendientes,
- abonos recibidos,
- cierres de caja del período,
- margen por producto,
- compras por proveedor,
- movimiento por lote,
- resumen de caja por método y tipo.

Limitaciones actuales:

- con exportación CSV básica y vista imprimible, pero sin Excel/PDF nativo todavía,
- sin dashboards gráficos,
- sin filtros avanzados por categoría/marca/usuario,
- algunas métricas están en versión operativa inicial, no analítica avanzada.

## Observaciones importantes

- Breeze ejecutó instalación/build frontend automáticamente durante el scaffold inicial.
- A partir de ese punto no se han disparado builds adicionales.
- La implementación actual ya es suficiente para empezar a operar catálogo, proveedores, compras, inventario inicial, ventas, fiado, caja y reportes básicos.
- El registro público quedó desactivado; los usuarios ya no pueden crearse desde `/register`.
- Los reportes ya existen como panel operativo inicial y cuentan con exportación CSV básica y vista imprimible, aunque todavía no como módulo analítico refinado ni con Excel/PDF nativo.
- Ya existen mejoras de UX operativa en compras, ventas, inventario inicial y dashboard para reducir fricción en tareas frecuentes.
- Compras ya tiene separación clara entre flujo rápido y detallado, con cálculo encapsulado fuera del controller.
- Ventas ya tiene anulación total controlada y búsqueda POS en vivo; la anulación parcial sigue diferida por complejidad de caja/fiado/abonos.

## Próximo bloque recomendado

1. refinamientos del núcleo pendientes en compras/ventas
2. cerrar refinamientos UX y visibilidad operativa derivados de compras/ventas
3. luego exportaciones más completas y reportabilidad más fina (Excel/PDF real)
 4. después preparación de iteración 2

## Enfoque actual recomendado

- consolidar primero la fidelidad del núcleo antes de abrir módulos nuevos,
- priorizar calidad de compras y ventas sobre exportaciones refinadas,
- mantener recargas fuera de iteración 1.

## Archivos clave actuales

- `backend/routes/web.php`
- `backend/routes/auth.php`
- `backend/app/Models/*`
- `backend/app/Http/Controllers/*`
- `backend/resources/views/catalog/*`
- `backend/resources/views/suppliers/*`
- `backend/resources/views/purchases/*`
- `backend/resources/views/inventory/*`
- `backend/app/Support/Money.php`
