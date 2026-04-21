# Diseño funcional

## Principios de uso

- La operación diaria debe ser rápida.
- Las pantallas deben minimizar escritura repetitiva.
- El sistema debe ser flexible para registrar casos imperfectos sin permitir descontrol silencioso.
- Toda inconsistencia operativa debe quedar visible y trazable.

## Módulos funcionales

## 1. Usuarios y roles

### Objetivo

Permitir control básico de acceso sin elevar demasiado la complejidad inicial.

### Roles iniciales

- Administrador.
- Ayudante/cajero.

### Restricciones recomendadas para ayudante

- no cambiar precios libremente,
- no editar compras sensibles,
- no borrar ventas,
- no ver reportes completos de utilidad,
- no cerrar cajas ajenas.

## 2. Catálogo de productos

### Objetivo

Gestionar productos de manera clara, evitando confusión entre artículos parecidos.

### Conceptos

- **Producto base**: identidad comercial principal.
- **Variante**: versión específica del producto.
- **Presentación de venta**: forma en que se vende una variante.

### Ejemplos

#### Coca-Cola 500ml

Producto base: Coca-Cola 500ml

Variantes posibles:

- con azúcar normal,
- sin azúcar normal,
- retornable plástico,
- retornable vidrio.

#### Huevo

Producto base: Huevo

Presentaciones:

- unidad,
- paquete de 4,
- paquete de 8,
- cubeta de 30.

### Datos principales

- nombre,
- categoría,
- marca,
- unidad base,
- código interno,
- código de barras opcional,
- foto opcional,
- estado: activo, inactivo o descontinuado.

## 3. Presentaciones de venta

### Objetivo

Permitir vender una misma variante de varias maneras sin duplicar productos artificialmente.

### Datos

- nombre de presentación,
- factor de conversión a unidad base,
- precio vigente,
- precio mínimo sugerido,
- margen objetivo opcional,
- estado.

### Reglas

- una presentación descuenta stock en unidad base,
- una variante puede tener varias presentaciones,
- el precio puede cambiar en el tiempo y debe guardar historial.

## 4. Compras

### Objetivo

Registrar ingresos de mercadería y generar lotes con costo real.

### Cabecera de compra

- fecha,
- proveedor opcional,
- número de factura opcional,
- tipo de pago,
- crédito a proveedor opcional,
- descuento general opcional,
- impuestos globales opcionales,
- flete u otros costos opcionales,
- archivo adjunto opcional.

### Detalle de compra

- variante,
- cantidad,
- costo unitario base o costo total,
- impuestos específicos,
- descuento por línea,
- bonificación,
- observaciones,
- vencimiento opcional.

### Modos de registro

#### Modo rápido

Pensado para registrar una compra sin desglosar todos los impuestos manualmente.

#### Modo detallado

Pensado para capturar fielmente facturas con mayor complejidad tributaria.

### Reglas

- toda línea de compra genera uno o más lotes,
- los impuestos y descuentos globales deben poder prorratearse,
- el prorrateo debe respetar productos exentos,
- regalos del mismo producto pueden redistribuir costo,
- regalos de otro producto entran por defecto con costo 0,
- la política exacta de tratamiento de regalos debe ser configurable en el futuro.

## 5. Lotes e inventario

### Objetivo

Conocer qué se compró, a qué costo, cuánto queda y cuándo vence.

### Datos del lote

- variante,
- compra origen,
- cantidad inicial,
- cantidad disponible,
- costo final unitario,
- fecha de compra,
- vencimiento,
- precio sugerido de venta,
- estado.

### Reglas

- la venta descuenta por FIFO,
- el sistema debe permitir stock negativo durante el arranque,
- toda anomalía debe quedar marcada,
- debe existir historial de movimientos por lote.

## 6. Ventas

### Objetivo

Registrar ventas mixtas, rápidas y trazables.

### Tipos de ítems posibles en una venta

- producto físico,
- ítem retornable o depósito asociado,
- recarga telefónica.

### Datos de la venta

- fecha/hora,
- usuario,
- cliente opcional,
- tipo de pago,
- parte pagada,
- parte fiada,
- observaciones,
- estado.

### Detalle de venta

- presentación vendida,
- cantidad,
- precio aplicado,
- descuento opcional,
- lotes consumidos,
- costo real,
- utilidad,
- alertas si aplica.

### Reglas

- puede haber fiado total o parcial,
- puede mezclarse efectivo y transferencia,
- puede cambiarse un precio manualmente en casos puntuales,
- si el precio cambia manualmente debe quedar trazabilidad,
- si el margen es muy bajo o negativo debe mostrarse alerta.

## 7. Fiado y abonos

### Objetivo

Separar claramente la venta del cobro pendiente.

### Cliente

- nombre obligatorio,
- teléfono opcional,
- dirección opcional,
- observaciones.

### Funciones

- registrar saldo pendiente,
- registrar abonos parciales,
- ver estado de cuenta,
- alertar deudas antiguas.

### Regla madre

La venta ocurre hoy; el cobro puede ocurrir después. Eso obliga a separar ambos conceptos.

## 8. Caja diaria

### Objetivo

Controlar el dinero real que entra y sale durante la jornada.

### Funciones

- apertura de caja,
- gastos de caja,
- retiros de efectivo,
- ingresos extraordinarios opcionales,
- cierre por método de pago,
- observación de diferencias.

### Cierre

Debe mostrar al menos:

- efectivo esperado,
- efectivo contado,
- transferencia esperada,
- diferencia,
- observaciones.

## 9. Recargas

### Objetivo

Gestionar el saldo de la plataforma y las recargas vendidas sin tratarlo como inventario físico.

### Funciones

- registrar cargas a plataforma,
- ver saldo disponible,
- registrar recargas vendidas,
- calcular comisión,
- asociar fiado si aplica,
- registrar número telefónico opcional.

### Regla

La utilidad de recargas proviene de la comisión, no del manejo de lotes.

## 10. Vencimientos

### Objetivo

Anticipar pérdidas por caducidad y apoyar decisiones de venta o reposición.

### Funciones

- registrar vencimiento por lote,
- alertar por vencer,
- listar vencidos,
- facilitar salida por merma o ajuste.

## 11. Proveedores

### Objetivo

Tomar mejores decisiones de compra.

### Funciones

- registrar proveedor,
- asociar variantes frecuentes,
- consultar historial de compra,
- ver último precio,
- ver mejor precio histórico,
- comparar compras por proveedor.

## 12. Retornables

### Alcance MVP

Debe quedar preparado desde diseño, pero puede implementarse en una fase posterior.

### Casos a cubrir

- cliente trae envase,
- cliente paga depósito por envase,
- cliente devuelve envase y recupera depósito,
- cliente se lleva envase prestado y no lo devuelve,
- venta definitiva del envase.

## Pantallas recomendadas

- Inicio / resumen diario.
- Productos.
- Variantes y presentaciones.
- Compras.
- Inventario inicial.
- Ventas.
- Clientes y fiados.
- Caja.
- Recargas.
- Proveedores.
- Reportes.
- Configuración.

## Alertas prioritarias

- stock bajo,
- stock negativo,
- producto sin regularizar,
- margen bajo o pérdida,
- lote por vencer,
- deuda antigua,
- caja con diferencia,
- saldo bajo de recargas.
