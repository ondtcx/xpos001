# Diseño técnico

## Objetivo técnico

Implementar una solución local-first, mantenible y simple de desplegar, que pueda operar en una sola computadora sin depender de internet y que deje abierta la puerta a expansión futura.

## Stack recomendado

- **Backend / aplicación**: Laravel.
- **Base de datos**: SQLite.
- **UI del panel**: Blade + Livewire.
- **Exportación**: librerías de Excel/PDF en una fase posterior del MVP.

## Justificación

### Por qué Laravel + SQLite

- acelera desarrollo de CRUDs, validaciones y flujos administrativos,
- reduce complejidad operacional,
- funciona bien en entorno local,
- permite crecer después a PostgreSQL,
- encaja mejor con un sistema transaccional administrativo que con una app móvil-first.

### Por qué NO empezar con una arquitectura más compleja

- el problema principal es de modelado del negocio, no de escalabilidad extrema,
- una mala base de datos con una arquitectura sofisticada sigue siendo una mala solución,
- la prioridad es salir de Excel con control y trazabilidad.

## Estilo arquitectónico sugerido

### Monolito modular

Se recomienda un monolito modular con separación por contextos de negocio:

- catálogo,
- compras,
- inventario,
- ventas,
- cuentas por cobrar,
- caja,
- recargas,
- proveedores,
- reportes,
- seguridad.

Esto evita el sobrecoste de microservicios y mantiene una separación conceptual saludable.

## Contextos funcionales y técnicos

### 1. Catálogo

Responsable de productos base, variantes, presentaciones, categorías, marcas y códigos.

### 2. Compras

Responsable de compras, líneas de compra, impuestos, descuentos y creación de lotes.

### 3. Inventario

Responsable de lotes, movimientos, inventario inicial, ajustes, mermas y vencimientos.

### 4. Ventas

Responsable de la transacción de venta, pagos y consumo de lotes.

### 5. Cuentas por cobrar

Responsable de fiados, saldos y abonos.

### 6. Caja

Responsable de apertura, movimientos y cierre diario.

### 7. Recargas

Responsable de cargas a plataforma, recargas vendidas, saldo disponible y comisión.

## Decisiones técnicas clave

## 1. Persistir historial, no sobrescribir

No se deben sobrescribir sin rastro:

- precios,
- costos,
- saldos críticos,
- cierres,
- asignaciones de lote.

Debe existir historia suficiente para auditoría básica.

## 2. Costeo derivado de movimientos

La utilidad debe calcularse usando el costo registrado en lotes y la relación venta-lote. No debe inferirse a partir de “último precio de compra”.

## 3. Operaciones transaccionales

Deben ejecutarse en transacción atómica operaciones como:

- guardar compra y crear lotes,
- guardar venta y consumir lotes,
- registrar abono y actualizar saldo,
- registrar cierre de caja.

## 4. Soporte a estados operativos imperfectos

El sistema debe permitir estados como:

- producto no regularizado,
- inventario inicial estimado,
- venta con stock insuficiente,
- venta con costo pendiente.

Esto debe modelarse explícitamente, no ocultarse.

## 5. Preparar extensibilidad para XML

La capa de compras debe admitir un flujo futuro de importación:

1. leer XML,
2. mapear líneas,
3. proponer borrador de compra,
4. permitir corrección manual,
5. confirmar compra.

## Estructura de módulos sugerida

```text
app/
  Domain/
    Catalog/
    Purchasing/
    Inventory/
    Sales/
    Receivables/
    Cash/
    TopUps/
    Suppliers/
    Reporting/
    Security/
  Application/
  Infrastructure/
  Http/
```

No es obligatorio crear esta estructura exacta desde el día 1, pero sí conviene pensar en separación por dominio para evitar crecimiento caótico.

## Integridad y validaciones clave

- una presentación debe tener factor de conversión mayor que 0,
- una variante activa debe pertenecer a un producto base,
- un lote no puede nacer sin referencia al origen,
- una venta fiada debe tener cliente asociado,
- un abono no puede exceder el saldo pendiente sin manejo explícito,
- una recarga vendida no puede dejar saldo de plataforma inconsistente,
- un cierre no debe duplicarse para la misma sesión,
- movimientos sensibles deben registrar usuario y fecha.

## Consideraciones de UI

- formularios cortos y rápidos,
- teclas rápidas en venta cuando sea posible,
- búsqueda por nombre, código interno o código de barras,
- alertas visibles, no escondidas,
- acciones destructivas minimizadas.

## Estrategia de despliegue inicial

- correr la aplicación localmente en la PC principal,
- respaldar la base SQLite de forma periódica,
- considerar un mecanismo sencillo de backup diario,
- cuando se requiera acceso desde teléfono, exponer la aplicación en red local sin rediseñar el dominio.

## Riesgos técnicos a controlar

- meter demasiados casos especiales en la primera iteración,
- no separar bien venta, fiado y caja,
- usar promedio en vez de lotes por presión de tiempo,
- modelar presentaciones como productos totalmente distintos sin jerarquía,
- permitir cambios manuales sin rastro histórico.
