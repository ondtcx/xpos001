# Pantallas del MVP

## Propósito

Definir las pantallas principales del sistema, qué información muestran, qué acciones permiten y qué validaciones deben respetar. Este documento sirve para alinear UX, dominio y desarrollo antes de implementar.

## Principios de diseño de pantallas

- La venta y la compra deben resolverse rápido.
- Las pantallas administrativas pueden ser más detalladas, pero no confusas.
- Las validaciones deben prevenir errores graves sin bloquear la operación en casos controlados.
- Toda advertencia operativa importante debe ser visible.

## 1. Inicio / Resumen diario

### Objetivo

Dar una vista rápida del estado operativo del día.

### Componentes

- ventas del día,
- utilidad del día,
- compras del día,
- saldo pendiente de fiados,
- saldo disponible de recargas,
- caja actual abierta/cerrada,
- alertas: stock bajo, lotes por vencer, diferencias de caja.

### Acciones

- abrir venta,
- abrir compra,
- abrir caja,
- cerrar caja,
- ver reportes,
- revisar alertas.

### Validaciones

- si no hay caja abierta y el negocio decide exigirla para vender, mostrar aviso,
- si hay alertas críticas, destacarlas sin impedir navegar.

## 2. Login

### Campos

- usuario,
- contraseña.

### Acciones

- iniciar sesión,
- cerrar sesión.

### Validaciones

- usuario activo,
- credenciales correctas.

## 3. Gestión de usuarios

### Objetivo

Administrar accesos y roles.

### Lista

- nombre,
- usuario,
- rol,
- estado,
- fecha de creación.

### Formulario

- nombre completo,
- nombre de usuario,
- contraseña,
- confirmar contraseña,
- rol,
- estado activo.

### Acciones

- crear usuario,
- editar usuario,
- activar/desactivar,
- restablecer contraseña.

### Validaciones

- usuario único,
- contraseña obligatoria al crear,
- al menos un rol asignado.

## 4. Categorías

### Campos

- nombre,
- estado.

### Acciones

- crear,
- editar,
- desactivar.

### Validaciones

- nombre único,
- no eliminar si tiene productos asociados; desactivar en su lugar.

## 5. Marcas

### Campos

- nombre,
- estado.

### Acciones y validaciones

Iguales a categorías.

## 6. Unidades base

### Campos

- nombre,
- símbolo.

### Acciones

- crear,
- editar.

### Validaciones

- nombre único,
- símbolo obligatorio.

## 7. Productos

### Objetivo

Administrar productos base del catálogo.

### Lista

- nombre,
- categoría,
- marca,
- código interno,
- estado,
- cantidad de variantes.

### Formulario

- nombre,
- categoría,
- marca,
- código interno,
- foto opcional,
- estado,
- notas.

### Acciones

- crear,
- editar,
- activar/inactivar,
- marcar descontinuado,
- ver variantes.

### Validaciones

- nombre obligatorio,
- código interno único si se usa.

## 8. Variantes de producto

### Objetivo

Distinguir versiones específicas de un mismo producto.

### Lista

- producto base,
- nombre de variante,
- SKU,
- código de barras,
- unidad base,
- controla vencimiento,
- retornable,
- estado.

### Formulario

- producto base,
- nombre variante,
- SKU opcional,
- código de barras opcional,
- unidad base,
- controla vencimiento,
- es retornable,
- estado,
- notas.

### Acciones

- crear,
- editar,
- activar/inactivar,
- ver presentaciones,
- ver lotes,
- ver historial de compras.

### Validaciones

- variante debe pertenecer a un producto,
- código de barras único si se usa,
- SKU único si se usa.

## 9. Presentaciones de venta

### Lista

- variante,
- nombre,
- factor de conversión,
- precio vigente,
- precio mínimo,
- estado,
- si es presentación por defecto.

### Formulario

- variante,
- nombre,
- factor de conversión,
- precio inicial o vigente,
- precio mínimo,
- margen sugerido opcional,
- es por defecto,
- estado.

### Acciones

- crear,
- editar,
- cambiar precio,
- ver historial de precios,
- activar/inactivar.

### Validaciones

- factor de conversión > 0,
- solo una presentación por defecto por variante,
- el cambio de precio crea historial, no sobrescribe sin rastro.

## 10. Historial de precios

### Lista

- presentación,
- precio,
- precio mínimo,
- margen sugerido,
- inicio de vigencia,
- fin de vigencia,
- usuario,
- motivo.

### Acciones

- crear nuevo precio vigente,
- cerrar vigencia actual.

### Validaciones

- no puede haber dos vigencias abiertas simultáneas para la misma presentación,
- fecha de inicio obligatoria.

## 11. Proveedores

### Lista

- nombre,
- teléfono,
- estado,
- última compra.

### Formulario

- nombre,
- identificación tributaria opcional,
- teléfono,
- dirección,
- notas,
- estado.

### Acciones

- crear,
- editar,
- activar/inactivar,
- ver productos relacionados,
- ver historial de compras.

### Validaciones

- nombre obligatorio.

## 12. Relación proveedor-producto

### Lista

- proveedor,
- variante,
- nombre según proveedor,
- código según proveedor,
- último precio,
- fecha última compra.

### Acciones

- asociar variante,
- editar referencia,
- ver historial.

### Validaciones

- no duplicar la misma combinación proveedor-variante.

## 13. Compras — listado

### Lista

- fecha,
- proveedor,
- número de factura,
- subtotal,
- impuestos,
- descuentos,
- total,
- tipo de pago,
- usuario.

### Filtros

- rango de fechas,
- proveedor,
- con/sin factura,
- manual/XML.

### Acciones

- nueva compra,
- ver detalle,
- adjuntar archivo,
- anular según política futura.

## 14. Compra — formulario

### Cabecera

- fecha,
- proveedor,
- número de factura,
- tipo de pago,
- compra a crédito,
- descuento global,
- impuesto global,
- costos adicionales,
- archivo adjunto,
- notas,
- modo: rápido o detallado.

### Detalle por línea

- variante,
- cantidad,
- costo unitario base o total línea,
- descuento línea,
- IVA,
- impuesto fijo,
- otros impuestos,
- regalo/bonificación,
- vencimiento,
- observaciones.

### Acciones

- agregar línea,
- duplicar línea,
- recalcular totales,
- guardar borrador,
- confirmar compra.

### Validaciones

- al menos una línea,
- cantidad > 0,
- costo válido,
- si hay valores globales, debe poder prorratearlos,
- si un producto controla vencimiento, mostrar y validar fecha cuando aplique,
- confirmar compra debe generar lotes.

## 15. Lotes — consulta

### Lista

- variante,
- fecha ingreso,
- origen,
- cantidad inicial,
- cantidad disponible,
- costo final,
- vencimiento,
- sugerencia de precio,
- estado.

### Filtros

- variante,
- proveedor,
- con stock,
- próximos a vencer,
- estimados.

### Acciones

- ver movimientos,
- ver compra origen,
- ajustar lote según permisos.

## 16. Inventario inicial

### Formulario

- variante,
- cantidad,
- costo estimado,
- fecha de registro,
- estado auditado/no auditado,
- notas.

### Acciones

- agregar línea,
- importar por tanda futura,
- guardar,
- confirmar carga.

### Validaciones

- variante obligatoria,
- cantidad > 0,
- costo estimado >= 0,
- al confirmar debe crear lote y movimiento.

## 17. Ajustes de inventario

### Formulario

- variante,
- lote opcional,
- tipo de ajuste,
- motivo,
- cantidad,
- costo referencial opcional,
- observaciones.

### Tipos sugeridos

- ajuste positivo,
- ajuste negativo,
- merma,
- vencimiento,
- consumo interno,
- corrección inicial.

### Acciones

- guardar ajuste.

### Validaciones

- tipo obligatorio,
- cantidad > 0,
- si descuenta stock, advertir si deja saldo negativo.

## 18. Venta — punto de venta

### Objetivo

Registrar una venta rápida y precisa.

### Secciones

#### Búsqueda/agregado

- buscador por nombre,
- código interno,
- código de barras,
- acceso a productos frecuentes.

#### Detalle de líneas

- ítem,
- presentación,
- cantidad,
- precio unitario,
- subtotal,
- advertencias de stock o margen.

#### Resumen

- subtotal,
- descuento total,
- total,
- pagado,
- pendiente/fiado.

#### Cliente

- cliente opcional,
- obligatorio si hay fiado.

#### Pago

- efectivo,
- transferencia,
- mixto,
- fiado parcial o total.

### Acciones

- agregar producto,
- agregar recarga,
- editar precio según permiso,
- quitar línea,
- seleccionar cliente,
- confirmar venta,
- guardar borrador futuro opcional.

### Validaciones

- al menos un ítem,
- si hay saldo pendiente, cliente obligatorio,
- si se cambia precio manualmente, registrar alerta o motivo según política,
- si no hay stock suficiente, advertir pero permitir continuar,
- si se mezcla pago, el total pagado no debe exceder el total salvo devolución controlada.

## 19. Venta — detalle

### Muestra

- cabecera de venta,
- cliente,
- pagos,
- líneas,
- lotes consumidos,
- utilidad,
- advertencias registradas.

### Acciones

- ver comprobante interno,
- registrar observación,
- anular/corregir según política futura.

## 20. Clientes / fiado

### Lista

- nombre,
- teléfono,
- saldo pendiente,
- última venta fiada,
- estado.

### Formulario

- nombre,
- teléfono,
- dirección,
- observaciones,
- estado.

### Acciones

- crear,
- editar,
- ver cuenta,
- registrar abono.

### Validaciones

- nombre obligatorio.

## 21. Estado de cuenta / abonos

### Muestra

- cliente,
- ventas pendientes,
- saldo total,
- historial de abonos.

### Formulario de abono

- monto,
- fecha,
- método de pago,
- caja asociada opcional,
- observaciones.

### Acciones

- registrar abono,
- ver detalle de movimientos.

### Validaciones

- monto > 0,
- no exceder saldo pendiente sin tratamiento explícito,
- si registra en caja abierta, reflejar movimiento de caja.

## 22. Caja — apertura

### Campos

- fecha/hora,
- monto inicial,
- observaciones.

### Acciones

- abrir caja.

### Validaciones

- monto inicial >= 0,
- no debe existir otra caja abierta incompatible según política del negocio.

## 23. Caja — movimientos

### Lista

- fecha/hora,
- tipo,
- monto,
- método,
- referencia,
- usuario,
- observaciones.

### Formulario

- tipo de movimiento,
- monto,
- método,
- referencia opcional,
- observaciones.

### Tipos sugeridos

- gasto,
- retiro,
- ingreso extraordinario,
- abono recibido,
- carga a plataforma de recargas.

### Validaciones

- monto > 0,
- caja abierta obligatoria.

## 24. Caja — cierre

### Muestra

- monto de apertura,
- ventas en efectivo,
- ventas por transferencia,
- gastos,
- retiros,
- ingresos extraordinarios,
- efectivo esperado,
- efectivo contado,
- diferencia.

### Campos

- efectivo contado,
- observaciones de cierre.

### Acciones

- recalcular,
- confirmar cierre.

### Validaciones

- caja debe estar abierta,
- no cerrar dos veces,
- registrar diferencia aunque sea negativa.

## 25. Recargas — saldo y cargas

### Lista

- proveedor/plataforma,
- saldo actual,
- fecha última actualización.

### Formulario de carga

- monto cargado,
- fecha,
- método de pago,
- observaciones.

### Acciones

- registrar carga,
- ver historial.

### Validaciones

- monto > 0,
- actualizar saldo disponible.

## 26. Recargas — historial de ventas

### Lista

- fecha,
- monto recargado,
- número telefónico,
- comisión,
- venta asociada,
- cliente si fue fiado.

### Filtros

- fecha,
- número telefónico,
- cliente.

## 27. Reportes

### Reportes obligatorios del MVP

- ventas del día,
- utilidad del día,
- compras del período,
- stock actual,
- productos por agotarse,
- fiados pendientes,
- abonos recibidos,
- recargas vendidas,
- saldo disponible de recargas,
- cierre de caja,
- margen por producto,
- compras por proveedor,
- movimiento por lote.

### Acciones

- filtrar por fecha,
- exportar a Excel,
- exportar a PDF,
- abrir detalle relacionado.

## 28. Alertas

### Tipos mínimos

- stock bajo,
- stock negativo,
- lote por vencer,
- margen bajo,
- venta con costo pendiente,
- deuda antigua,
- caja con diferencia,
- saldo bajo de recargas.

### Acciones

- ver detalle,
- marcar revisado,
- navegar al módulo origen.
