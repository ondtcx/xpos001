# Flujos operativos del MVP

## Propósito

Describir los recorridos más importantes entre pantallas para validar que la operación real del negocio queda cubierta de punta a punta.

## Flujo 1 — Arranque inicial del sistema

1. Iniciar sesión como administrador.
2. Configurar categorías, marcas y unidades base.
3. Cargar productos y variantes.
4. Definir presentaciones y precios iniciales.
5. Cargar usuarios ayudantes si aplica.
6. Importar catálogo desde Excel si se va a reutilizar base existente.
7. Registrar inventario inicial por tandas.

### Resultado esperado

El sistema queda listo para empezar a operar sin depender todavía de una migración histórica completa.

## Flujo 2 — Registro de compra simple

1. Abrir módulo de compras.
2. Crear nueva compra en modo rápido.
3. Seleccionar proveedor si existe.
4. Ingresar fecha, factura y tipo de pago.
5. Agregar líneas de productos.
6. Aplicar descuento o impuesto global si corresponde.
7. Confirmar compra.
8. El sistema genera lotes y movimientos de inventario.
9. El sistema puede sugerir ajustes de precio de venta.

### Alertas posibles

- producto sin presentación activa,
- producto con control de vencimiento sin fecha cargada,
- prorrateo pendiente de revisar.

## Flujo 3 — Registro de compra detallada con impuestos complejos

1. Abrir nueva compra en modo detallado.
2. Registrar línea por línea.
3. Ingresar descuentos por producto si existen.
4. Ingresar IVA, impuestos fijos y otros cargos según corresponda.
5. Marcar bonificaciones.
6. Aplicar valores globales para prorrateo si los hay.
7. Confirmar vista previa de costos finales.
8. Guardar compra.
9. Generar lotes con costo final unitario.

### Resultado esperado

La compra queda representada con fidelidad suficiente para calcular utilidad real por lote.

## Flujo 4 — Venta rápida de productos físicos

1. Abrir pantalla de venta.
2. Buscar producto por nombre, código o código de barras.
3. Elegir presentación.
4. Ajustar cantidad.
5. Repetir para las demás líneas.
6. Elegir método de pago.
7. Confirmar venta.
8. El sistema consume lotes por FIFO.
9. El sistema registra pagos y movimientos asociados.

### Advertencias posibles

- stock insuficiente,
- costo pendiente,
- margen bajo o negativo,
- caja no abierta si la política exige caja activa.

## Flujo 5 — Venta con fiado parcial

1. Iniciar venta.
2. Agregar productos.
3. Asociar cliente.
4. Registrar parte pagada y parte pendiente.
5. Confirmar venta.
6. El sistema registra pago recibido.
7. El sistema crea cuenta por cobrar por el saldo.

### Regla importante

La venta se considera realizada; lo pendiente pasa a cuentas por cobrar. NO se crea una “media venta”.

## Flujo 6 — Registro de abono

1. Buscar cliente.
2. Abrir estado de cuenta.
3. Revisar saldo pendiente.
4. Registrar abono.
5. Elegir método de pago.
6. Asociar a caja abierta si corresponde.
7. Confirmar.
8. El sistema reduce saldo pendiente y crea movimiento de caja si aplica.

## Flujo 7 — Apertura y cierre de caja

### Apertura

1. Abrir pantalla de caja.
2. Registrar monto inicial.
3. Confirmar apertura.

### Durante el día

1. Registrar ventas.
2. Registrar gastos o retiros si ocurren.
3. Registrar cargas de recargas si se pagan desde caja.
4. Registrar abonos si entran por caja.

### Cierre

1. Abrir pantalla de cierre.
2. Revisar resumen esperado.
3. Ingresar efectivo contado.
4. Revisar diferencia.
5. Confirmar cierre.

## Flujo 8 — Registro de recarga

1. Iniciar una venta.
2. Agregar ítem de recarga.
3. Ingresar monto y número telefónico opcional.
4. El sistema calcula la comisión.
5. Confirmar pago normal o fiado.
6. El sistema descuenta saldo disponible de la plataforma.
7. La venta queda registrada junto con los demás ítems si los hubiera.

## Flujo 9 — Carga de saldo a plataforma de recargas

1. Ir al módulo de recargas.
2. Registrar nueva carga.
3. Ingresar monto, fecha y método de pago.
4. Confirmar.
5. El sistema incrementa saldo disponible.
6. Si salió dinero de caja, registrar movimiento correspondiente.

## Flujo 10 — Regularización de inventario por tandas

1. Ir al módulo de inventario inicial.
2. Seleccionar grupo de productos a regularizar.
3. Ingresar cantidades y costo estimado.
4. Confirmar carga.
5. El sistema crea lotes estimados.
6. El producto deja de estar completamente pendiente.

### Objetivo

Permitir adopción progresiva sin frenar la operación diaria.

## Flujo 11 — Revisión de alertas

1. Abrir inicio o panel de alertas.
2. Revisar alertas prioritarias.
3. Entrar al detalle relacionado.
4. Corregir o registrar acción.
5. Volver al panel y continuar.

## Flujo 12 — Consulta de utilidad y margen

1. Abrir reportes.
2. Seleccionar período.
3. Ver ventas, costo total, utilidad y margen.
4. Filtrar por producto o categoría.
5. Abrir detalle si una línea necesita revisión.

## Flujo 13 — Evaluación de compras por proveedor

1. Abrir reporte de compras por proveedor.
2. Filtrar por rango de fechas y proveedor.
3. Revisar último precio, frecuencia y monto comprado.
4. Abrir detalle de un producto si se necesita comparar decisiones.

## Puntos de control transversales

Estos puntos deben revisarse en varios flujos, no solo en uno:

- trazabilidad del usuario que ejecuta la acción,
- advertencias visibles sin bloquear innecesariamente,
- cálculos monetarios consistentes,
- registro histórico en eventos financieros,
- navegación rápida entre origen y detalle.
