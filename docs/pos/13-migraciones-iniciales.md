# Migraciones iniciales reales

## Propósito

Definir qué tablas deben existir en la **primera iteración real** del sistema, en qué orden deben crearse y qué queda explícitamente fuera para evitar una implementación sobrecargada.

## Decisión vigente

Las **recargas** se dejan para la **segunda iteración**.

Eso significa que la primera iteración se enfoca en consolidar el núcleo transaccional:

- seguridad,
- catálogo,
- proveedores,
- compras,
- inventario,
- ventas,
- fiado,
- caja.

## Objetivo de la iteración 1

Permitir operar la tienda con control confiable de:

- productos y variantes,
- compras por lote,
- inventario inicial,
- ventas,
- consumo FIFO,
- fiado y abonos,
- apertura y cierre de caja,
- reportes básicos del núcleo.

## Qué NO entra en esta primera tanda de migraciones

- recargas,
- retornables avanzados,
- XML de facturas,
- adjuntos sofisticados,
- auditoría extendida,
- reglas avanzadas de alertas.

## Principios de corte

- crear primero dependencias maestras,
- después entidades transaccionales,
- después relaciones derivadas,
- dejar fuera todo lo que no sea esencial para operar el negocio desde el día 1.

## Orden recomendado de migraciones

## Grupo 1 — Seguridad

### Tablas

- `users`
- `roles`
- `role_user`

### Motivo

Todo lo demás necesita trazabilidad mínima por usuario.

## Grupo 2 — Catálogo base

### Tablas

- `categories`
- `brands`
- `base_units`
- `products`
- `product_variants`
- `sale_presentations`
- `sale_prices`

### Motivo

No se puede comprar ni vender correctamente sin un catálogo bien modelado.

## Grupo 3 — Proveedores

### Tablas

- `suppliers`
- `supplier_variant_refs`

### Motivo

Las compras deben poder enlazarse a proveedor desde el inicio, aunque algunas compras queden sin proveedor.

## Grupo 4 — Compras

### Tablas

- `purchases`
- `purchase_items`
- `purchase_allocations`

### Motivo

Las compras son el origen principal del costo real.

## Grupo 5 — Inventario y lotes

### Tablas

- `opening_inventory_entries`
- `inventory_lots`
- `inventory_movements`

### Motivo

Sin lotes e inventario no existe utilidad real ni trazabilidad del stock.

## Grupo 6 — Caja

### Tablas

- `cash_sessions`
- `cash_movements`

### Motivo

La caja debe existir antes de cerrar la implementación de ventas y abonos, porque esas operaciones impactan dinero real.

## Grupo 7 — Clientes, ventas y fiado

### Tablas

- `customers`
- `sales`
- `sale_items`
- `sale_item_lot_consumptions`
- `sale_payments`
- `receivables`
- `receivable_payments`

### Motivo

Este bloque cierra el ciclo de operación diaria.

## Esquema mínimo de nombres de migración

## Seguridad

- `create_users_table`
- `create_roles_table`
- `create_role_user_table`

## Catálogo

- `create_categories_table`
- `create_brands_table`
- `create_base_units_table`
- `create_products_table`
- `create_product_variants_table`
- `create_sale_presentations_table`
- `create_sale_prices_table`

## Proveedores

- `create_suppliers_table`
- `create_supplier_variant_refs_table`

## Compras

- `create_purchases_table`
- `create_purchase_items_table`
- `create_purchase_allocations_table`

## Inventario

- `create_opening_inventory_entries_table`
- `create_inventory_lots_table`
- `create_inventory_movements_table`

## Caja

- `create_cash_sessions_table`
- `create_cash_movements_table`

## Ventas y fiado

- `create_customers_table`
- `create_sales_table`
- `create_sale_items_table`
- `create_sale_item_lot_consumptions_table`
- `create_sale_payments_table`
- `create_receivables_table`
- `create_receivable_payments_table`

## Columnas monetarias en implementación real

Aunque en el documento SQL conceptual aparezcan como decimales, en la implementación real de la iteración 1 se recomienda usar columnas enteras para dinero, por ejemplo:

- `price_amount`
- `subtotal_amount`
- `total_amount`
- `discount_amount`
- `tax_amount`
- `pending_amount`

### Ejemplo

En vez de guardar `$3.50`, guardar `350`.

## Columnas de cantidades

Para esta iteración, conviene mantener cantidades como decimal controlado en base de datos cuando el dominio todavía pueda requerirlo, por ejemplo:

- `quantity`
- `initial_quantity`
- `available_quantity`
- `conversion_factor`

## Dependencias críticas entre tablas

- `products` depende de `categories` y `brands` opcionalmente.
- `product_variants` depende de `products` y `base_units`.
- `sale_presentations` depende de `product_variants`.
- `sale_prices` depende de `sale_presentations` y `users`.
- `purchase_items` depende de `purchases` y `product_variants`.
- `inventory_lots` depende de `product_variants` y opcionalmente `purchase_items`.
- `inventory_movements` depende de `product_variants`, opcionalmente `inventory_lots` y `users`.
- `sales` depende opcionalmente de `customers`, `cash_sessions` y `users`.
- `sale_items` depende de `sales` y opcionalmente de `sale_presentations` y `product_variants`.
- `sale_item_lot_consumptions` depende de `sale_items` e `inventory_lots`.
- `receivables` depende de `customers` y `sales`.
- `receivable_payments` depende de `receivables`, opcionalmente de `cash_sessions` y `users`.

## Restricciones que conviene dejar para aplicación, no solo para BD

No todo debe resolverse con constraint SQL. Estas reglas deben quedar en servicios de dominio:

- no fiar sin cliente,
- no generar dos precios vigentes para una misma presentación,
- no consumir lotes fuera de política FIFO salvo ajuste autorizado,
- no cerrar caja dos veces,
- no dejar un abono incoherente con el saldo,
- no confirmar compra sin generar lotes y movimientos.

## Seeders mínimos de la iteración 1

- rol administrador,
- rol ayudante,
- usuario administrador inicial,
- unidades base mínimas,
- categorías base opcionales si el usuario las quiere predefinidas.

## Recomendación práctica de implementación

No escribas veinte migraciones antes de levantar el primer flujo. Lo correcto es:

1. crear migraciones del grupo 1 al 3,
2. validar catálogo y proveedores,
3. crear compras e inventario,
4. cerrar ventas, fiado y caja,
5. recién después añadir iteración 2.
