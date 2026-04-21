# Convenciones técnicas de implementación

## Propósito

Definir reglas técnicas obligatorias para evitar errores silenciosos en dinero, cantidades, fechas, estados y trazabilidad. Este documento convierte decisiones de arquitectura en convenciones concretas de implementación.

## 1. Manejo de dinero

## Regla principal

Todos los valores monetarios deben almacenarse internamente como **enteros en centavos**.

### Ejemplos

- $1.00 → `100`
- $0.15 → `15`
- $3.50 → `350`
- $12.99 → `1299`

## ¿Cómo se sabe dónde va el decimal?

Porque la convención fija es esta:

- el valor almacenado representa la cantidad total de **centavos**,
- para mostrarlo al usuario se divide entre `100`,
- para guardar un valor ingresado en dólares se multiplica por `100`.

### Ejemplo práctico

Si el sistema guarda `125`, eso significa 125 centavos.

Entonces:

- `125 / 100 = 1.25`
- se muestra como **$1.25**

Si el usuario escribe **$4.70**:

- `4.70 * 100 = 470`
- se guarda `470`

NO hay ambigüedad porque la unidad de almacenamiento ya quedó definida: **centavos**.

## Ventajas

- evita errores por decimales flotantes,
- protege cálculos de utilidad, impuestos y caja,
- simplifica comparaciones y sumas.

## Campos que deben usar centavos

- precios de venta,
- costos unitarios monetarios,
- subtotales,
- impuestos,
- descuentos,
- totales,
- saldos de fiado,
- movimientos de caja,
- saldo de recargas,
- comisión de recargas.

## 2. Manejo de cantidades

## Regla principal

Las cantidades físicas NO deben guardarse como float binario. Deben manejarse como decimales controlados o enteros escalados según el caso.

### Recomendación práctica

- para cantidades discretas simples, usar enteros cuando sea razonable,
- para cantidades con fracción, usar decimal con escala fija definida por el dominio.

### Casos del negocio

- huevos: normalmente entero,
- botellas: entero,
- cajetillas/cajas: entero,
- presentaciones derivadas: se traducen a factor de conversión,
- si algún producto futuro requiere fracción, definir escala explícita.

## Regla de implementación

La unidad base y el factor de conversión deben ser suficientes para evitar fracciones innecesarias en la mayoría de operaciones.

## 3. Fechas y horas

## Regla principal

Persistir fechas y horas en formato estándar consistente.

### Recomendación

- usar `datetime` en UTC internamente si el stack lo permite de forma limpia,
- mostrar en zona horaria local del negocio,
- si toda la operación es local y sin sincronización externa, mantener consistencia antes que sofisticación innecesaria.

## Campos sensibles a fecha/hora

- compras,
- ventas,
- abonos,
- movimientos de caja,
- movimientos de inventario,
- vigencia de precios,
- vencimientos.

## 4. Estados explícitos

## Regla principal

No usar booleanos ambiguos cuando el negocio necesita más de dos estados.

### Ejemplos

- producto: `active`, `inactive`, `discontinued`
- caja: `open`, `closed`
- venta: `confirmed`, `voided`, `draft` si se habilita futuro
- lote: `active`, `depleted`, `expired`, `adjusted`
- cuenta por cobrar: `open`, `paid`, `cancelled`

## 5. Historial obligatorio

No sobrescribir sin rastro cuando el dato afecta decisiones o dinero.

### Debe haber historial en

- precios de venta,
- consumo de lotes,
- abonos,
- movimientos de caja,
- movimientos de inventario,
- compras.

## 6. Cálculo y redondeo

## Regla principal

Todo redondeo debe ser explícito y consistente. Nunca dejar que la librería o el lenguaje decidan solos.

### Recomendación

- trabajar el dinero en centavos,
- calcular primero a nivel interno,
- formatear al final para mostrar,
- documentar cualquier redondeo especial en impuestos prorrateados.

## 7. Reglas para impuestos y prorrateos

### Principio

Los valores globales deben prorratearse con criterio reproducible.

### Recomendación inicial

- prorratear en proporción al peso económico de la línea,
- excluir o tratar distinto productos exentos cuando corresponda,
- guardar evidencia del prorrateo aplicado.

## 8. Convenciones de nombres

### Tablas

- plural en snake_case,
- nombres explícitos y de negocio.

### Columnas

- snake_case,
- prefijos `_at` para fechas de evento,
- sufijo `_amount` para dinero,
- sufijo `_quantity` para cantidades.

### Ejemplos

- `total_amount`
- `pending_amount`
- `available_quantity`
- `sold_at`

## 9. Auditoría mínima

### Regla principal

Toda operación crítica debe guardar al menos:

- usuario,
- fecha/hora,
- referencia del registro afectado.

### Operaciones críticas

- compras,
- ventas,
- cambios de precio,
- ajustes de inventario,
- abonos,
- cierres de caja.

## 10. Soft delete vs desactivación

### Regla principal

Para entidades de negocio visibles, preferir **desactivación** antes que borrado físico.

### Aplicar a

- productos,
- variantes,
- categorías,
- marcas,
- proveedores,
- clientes,
- usuarios.

## 11. Validaciones críticas de aplicación

Estas validaciones no deben confiarse solo a base de datos:

- no fiar sin cliente,
- no dejar dos precios vigentes activos en la misma presentación,
- no cerrar caja dos veces,
- no registrar abono inválido,
- no romper trazabilidad FIFO,
- no permitir modificaciones sensibles a usuarios sin permiso.

## 12. Formateo en interfaz

### Dinero

- mostrar siempre en dólares con dos decimales,
- usar separador consistente,
- mostrar símbolo de moneda en contextos de resumen.

### Cantidades

- mostrar sin decimales cuando no hagan falta,
- mostrar fracción solo si el producto realmente la usa.

## 13. Respaldos

### Regla recomendada

Al trabajar localmente con SQLite, debe existir política mínima de respaldo.

### Recomendación inicial

- respaldo diario del archivo de base de datos,
- nombre de archivo con fecha,
- al menos una carpeta de respaldo local clara,
- idealmente copia manual adicional periódica en unidad externa.
