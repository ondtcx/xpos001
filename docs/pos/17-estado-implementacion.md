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

### Stock actual

Estado: ✅ implementado

Incluye:

- resumen por producto/variante de `SUM(inventory_lots.available_quantity)`,
- incluye variantes activas sin stock (agotadas), no solo las que tienen lotes,
- es la pantalla que abre el menú `Inventario` por defecto; `Inventario inicial` y `Lotes` quedan interlinkeadas desde ahí.

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
- reversa trazable de abonos cuando una venta total se anula,
- envejecimiento de cuentas abiertas en rangos `0-7`, `8-30` y `31+` días,
- métricas operativas de seguimiento en detalle de cobranza,
- visibilidad de último abono, avance de cobro y prioridad por antigüedad.

Limitaciones actuales:

- sin estados de cuenta exportables formales,
- sin alertas automáticas o acciones proactivas de cobranza todavía.

## 10. Caja

Estado: ✅ implementado en versión inicial y refinada

Incluye:

- apertura,
- movimientos,
- cierre,
- diferencias.

Incluye adicionalmente:

- vínculo automático de pagos de venta con caja abierta,
- vínculo automático de abonos con caja abierta,
- movimientos manuales de gasto, retiro e ingreso extraordinario,
- cálculo de efectivo esperado y transferencias esperadas al cierre,
- consolidado histórico de sesiones cerradas por período,
- visibilidad de diferencias históricas,
- métricas de exactitud, faltantes, sobrantes y peor desvío,
- análisis neto por tipo de movimiento y método de pago.

Limitaciones actuales:

- no hay reapertura o corrección de cierre,
- no existen permisos finos aún para apertura/cierre,
- no hay dashboard gráfico; la lectura histórica sigue siendo tabular/operativa.

## 11. Recargas

Estado: ⏸ diferido a iteración 2

## 12. Reportes operativos

Estado: ✅ implementado en versión inicial y extendida

Incluye:

- ventas del día o rango,
- utilidad confiable del día o rango,
- compras brutas, anuladas y netas del período,
- stock actual total,
- productos por agotarse,
- fiados pendientes,
- abonos brutos, revertidos y netos,
- cierres de caja del período,
- margen por producto,
- compras por proveedor,
- movimiento por lote,
- resumen de caja por método y tipo,
- separación explícita entre bruto, anulado/revertido y neto en ventas/compras/cobranza,
- utilidad y margen principales excluyendo líneas con warnings de costo/stock,
- exportación real a Excel (`.xlsx`) para ventas, compras y cobranza,
- exportación real a PDF para ventas, compras y cobranza,
- workbooks multihoja por dominio para resumen + detalle,
- botones operativos de CSV / Excel / PDF coexistiendo en la pantalla de reportes.

Limitaciones actuales:

- sin dashboards gráficos,
- sin filtros avanzados por categoría/marca/usuario,
- algunas métricas siguen en versión operativa inicial, no analítica avanzada,
- lotes, movimientos de lote y caja todavía no tienen exportación formal en Excel/PDF.

## Observaciones importantes

- Breeze ejecutó instalación/build frontend automáticamente durante el scaffold inicial.
- A partir de ese punto no se han disparado builds adicionales.
- La implementación actual ya es suficiente para empezar a operar catálogo, proveedores, compras, inventario inicial, ventas, fiado, caja y reportes básicos.
- El registro público quedó desactivado; los usuarios ya no pueden crearse desde `/register`.
- Los reportes ya cuentan con exportación formal en CSV, Excel y PDF para ventas, compras y cobranza; la cobertura de exportes más finos para caja/lotes queda como fase posterior.
- Ya existen mejoras de UX operativa en compras, ventas, inventario inicial y dashboard para reducir fricción en tareas frecuentes.
- Compras ya tiene separación clara entre flujo rápido y detallado, con cálculo encapsulado fuera del controller.
- Compras ya impactan inventario real por lotes; cada compra confirmada crea lotes y movimientos de inventario. La confusión de navegación ya se resolvió: existe `Stock actual` (`inventory-stock.index`), que lista todas las variantes activas con su disponibilidad real incluyendo las agotadas, y el menú `Inventario` la abre por defecto; `Inventario inicial` y `Lotes` quedan como vistas secundarias interlinkeadas entre sí.
- Ventas ya tiene anulación total controlada y búsqueda POS en vivo; la anulación parcial sigue diferida por complejidad de caja/fiado/abonos.
- Ventas funciona para casos completos, pero la pantalla principal todavía expone demasiada complejidad por defecto para el flujo habitual de mostrador: fecha editable, campos de pago mixto, override manual y confirmaciones de warning visibles aun cuando no aplican.
- Caja ya cuenta con visibilidad consolidada por período y lectura histórica de diferencias; la siguiente prioridad ya no es caja/cobranza sino el refinamiento UX transversal del núcleo.
- Cuentas por cobrar ya exponen envejecimiento operativo y métricas de seguimiento; lo pendiente ahí pasa más por exportación formal y automatización de alertas que por visibilidad básica.

## Hallazgos recientes de operación y UX

### Inventario / stock — resuelto

- El sistema **sí** lleva compras a inventario mediante lotes y movimientos (`purchase_entry`) tanto en compras rápidas como detalladas.
- La confusión de navegación quedó resuelta: `Inventario inicial` (`opening_inventory_entries`), `Stock actual` (resumen por producto/variante, incluyendo agotados) y `Lotes` (detalle trazable) son ahora tres vistas separadas e interlinkeadas, con el menú `Inventario` abriendo `Stock actual` por defecto.
- Alcance deliberadamente NO tocado: las 3 queries que calculan `SUM(inventory_lots.available_quantity)` (`PosController`, `PosSaleDraftBuilder`, `ReportController`) siguen duplicadas sin un service/scope compartido — quedó fuera de alcance a pedido explícito (solo UI/navegación, no refactor del cálculo).

### Ventas de mostrador

- La venta actual sirve como flujo completo y trazable, pero no está optimizada todavía para la operación repetitiva del día.
- El flujo habitual deseable quedó identificado así:
  - agregar producto,
  - ajustar cantidad,
  - efectivo por defecto,
  - cliente anónimo por defecto,
  - guardar.
- Todo lo excepcional debería aparecer por **divulgación progresiva** y no por defecto:
  - fecha manual,
  - cliente nominal,
  - pagos mixtos,
  - override de precio,
  - warnings de stock/costo,
  - cálculo de vuelto.
- La dirección recomendada es mantener el dominio actual (`CreateSaleService`) y crear una **interfaz adicional de venta rápida** en lugar de duplicar la lógica de negocio o reemplazar de golpe la pantalla completa.

### POS de mostrador

- Ya existe una implementación funcional de `POS` como interfaz adicional de venta rápida.
- `POS` hoy ya permite:
  - búsqueda y agregado rápido de productos,
  - ajuste de cantidades,
  - cobro en efectivo,
  - cobro en transferencia simple,
  - cobro mixto efectivo + transferencia,
  - cálculo opcional de vuelto en efectivo,
  - fiado total o parcial desde efectivo,
  - transición a `Venta completa` cuando el caso lo requiere.
- La base operativa quedó validada: el flujo de mostrador ya es útil y más directo que la pantalla completa para muchos casos habituales.
- La deuda de consistencia de estado quedó resuelta:
  - el botón de cliente ahora indica su propio estado activo (igual que las tabs de método de pago), con `aria-expanded`/`aria-pressed` en los tres controles,
  - cerrar el buscador de cliente sin elegir ya no deja una búsqueda vieja filtrando en silencio la próxima vez que se abre,
  - el panel lateral tiene `max-height` + scroll interno, ya no crece sin límite al acumular paneles,
  - la selección de cliente ahora hace búsqueda real contra el servidor (antes filtraba en el navegador un array con todos los clientes activos sin límite) y permite alta rápida de cliente sin salir del flujo de cobro.
- Conclusión actual: el frente `POS` ya no está bloqueado por reglas de negocio ni por consistencia de estado del checkout. Deuda conocida y explícitamente aceptada: el CRUD de clientes de administración (`CustomerController`, `customers/form.blade.php`) sigue sin soportar el campo `document` — el alta rápida del POS sí lo soporta, pero vive en un endpoint aislado (`PosController::storeCustomer`) que no comparte código con el CRUD general.

## Dataset demo para pruebas manuales

Estado: ✅ disponible como seeder manual

Existe un dataset demo repetible orientado a **minimarket / abarrotes** para probar la aplicación sin depender todavía de importación inicial desde Excel.

### Qué carga

- roles base y usuario administrador,
- usuario operativo demo tipo `assistant`,
- catálogo pequeño pero realista con precios plausibles en USD,
- proveedores y clientes,
- inventario inicial puntual,
- compras rápidas y detalladas,
- una compra anulada para revisar corrección,
- ventas de contado, transferencia y fiado,
- un abono registrado,
- una venta con warning explícito controlado,
- una caja histórica cerrada con diferencia leve,
- una caja actual abierta para entrar y operar de inmediato.

### Cómo ejecutarlo

Desde `backend/`:

```bash
php artisan db:seed --class=Database\\Seeders\\MinimarketDemoSeeder
```

### Credenciales demo

- admin: `admin` / `admin12345`
- cajero: `cajero` / `cajero12345`

### Criterio de uso

- El seeder demo es **manual**, no corre dentro de `DatabaseSeeder`.
- `DatabaseSeeder` sigue reservado para bootstrap mínimo y estable del sistema.
- Esto evita mezclar datos base con datos ficticios de prueba.

### Archivo clave

- `backend/database/seeders/MinimarketDemoSeeder.php`
- `backend/tests/Feature/MinimarketDemoSeederTest.php`

## Decisiones cerradas

- **Fase 2 de Epic 12 (exportación formal Excel/PDF de lotes, movimientos de lote y caja): no se abre** salvo que aparezca una necesidad operativa concreta. Ventas, compras y cobranza ya tienen exportación formal (CSV/Excel/PDF); ampliarla a caja/lotes sin un caso de uso real sería completar por completar. Revisar esta decisión solo si surge un pedido operativo explícito, no por ciclo de refinamiento.

## Próximo bloque recomendado

1. `venta rápida de mostrador`: seguir reduciendo la complejidad expuesta por defecto en la pantalla completa de ventas (fecha editable, pagos mixtos, overrides — ver sección "Ventas de mostrador" más arriba), reutilizando el dominio actual sin duplicar lógica de negocio
2. después reevaluar el siguiente frente funcional priorizado del backlog

## Enfoque actual recomendado

- consolidar primero la fidelidad del núcleo antes de abrir módulos nuevos,
- priorizar que cualquier nueva exportación adicional responda a una necesidad operativa concreta, no a completar por completar (ver "Decisiones cerradas"),
- tratar la `venta rápida` como refinamiento del núcleo POS, reutilizando el dominio actual y ocultando por defecto lo excepcional,
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
