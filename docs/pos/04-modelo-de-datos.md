# Modelo de datos inicial

## Objetivo

Definir las entidades principales del MVP y sus relaciones para soportar el negocio con precisión suficiente desde la primera versión.

## Principios del modelo

1. Separar claramente catálogo, operaciones, caja y cuentas por cobrar.
2. Modelar costos por lote.
3. Guardar historial cuando un dato tenga impacto financiero.
4. Permitir estados imperfectos durante el arranque.

## Entidades principales

## 1. Seguridad

### users

- id
- name
- email o usuario
- password_hash
- is_active
- created_at
- updated_at

### roles

- id
- name
- description

### role_user

- user_id
- role_id

## 2. Catálogo

### categories

- id
- name
- is_active

### brands

- id
- name
- is_active

### base_units

- id
- name
- symbol

### products

- id
- name
- category_id
- brand_id
- internal_code
- photo_path
- status
- notes
- created_at
- updated_at

### product_variants

- id
- product_id
- name
- sku
- barcode
- base_unit_id
- tracks_expiration
- is_returnable
- is_active
- notes

### sale_presentations

- id
- variant_id
- name
- conversion_factor
- is_default
- is_active

### sale_prices

- id
- sale_presentation_id
- price
- min_price
- suggested_margin_percent
- starts_at
- ends_at
- created_by
- reason

## 3. Proveedores

### suppliers

- id
- name
- tax_id opcional
- phone
- address
- notes
- is_active

### supplier_variant_refs

- id
- supplier_id
- variant_id
- supplier_product_name opcional
- supplier_code opcional
- last_purchase_price opcional
- last_purchase_at opcional

## 4. Compras

### purchases

- id
- supplier_id nullable
- invoice_number nullable
- purchased_at
- payment_type
- is_credit
- subtotal
- global_discount_amount
- global_tax_amount
- extra_costs_amount
- total_amount
- attachment_path nullable
- source_type (manual, xml_draft, import)
- notes
- created_by

### purchase_items

- id
- purchase_id
- variant_id
- quantity
- unit_cost_base
- line_subtotal
- line_discount_amount
- tax_vat_amount
- tax_fixed_amount
- tax_other_amount
- gift_quantity
- total_cost_amount
- expiration_date nullable
- notes

### purchase_allocations

Entidad opcional para representar prorrateos globales.

- id
- purchase_id
- purchase_item_id
- allocation_type
- amount

## 5. Inventario y lotes

### inventory_lots

- id
- variant_id
- purchase_item_id nullable
- origin_type (purchase, opening_balance, adjustment)
- origin_id nullable
- received_at
- expiration_date nullable
- initial_quantity
- available_quantity
- bonus_quantity
- unit_cost_final
- suggested_sale_price nullable
- is_estimated
- status

### inventory_movements

- id
- variant_id
- lot_id nullable
- movement_type
- quantity
- unit_cost nullable
- reference_type
- reference_id
- movement_at
- notes
- created_by

### opening_inventory_entries

- id
- variant_id
- quantity
- estimated_unit_cost
- recorded_at
- is_audited
- notes
- created_by

## 6. Ventas

### sales

- id
- sold_at
- customer_id nullable
- cash_session_id nullable
- subtotal
- discount_amount
- total_amount
- paid_amount
- credit_amount
- status
- notes
- created_by

### sale_items

- id
- sale_id
- item_type (product, topup, deposit, returnable)
- sale_presentation_id nullable
- variant_id nullable
- description_snapshot
- quantity
- unit_price
- subtotal
- total_cost nullable
- total_profit nullable
- has_cost_warning

### sale_item_lot_consumptions

- id
- sale_item_id
- lot_id
- quantity
- unit_cost
- total_cost

### sale_payments

- id
- sale_id
- payment_method
- amount
- received_at
- notes

## 7. Clientes y cuentas por cobrar

### customers

- id
- name
- phone nullable
- address nullable
- notes nullable
- is_active

### receivables

- id
- customer_id
- sale_id
- original_amount
- pending_amount
- opened_at
- status

### receivable_payments

- id
- receivable_id
- cash_session_id nullable
- amount
- payment_method
- paid_at
- notes
- created_by

## 8. Caja

### cash_sessions

- id
- opened_by
- opened_at
- opening_amount
- status
- closed_at nullable
- expected_cash_amount nullable
- counted_cash_amount nullable
- expected_transfer_amount nullable
- difference_amount nullable
- closing_notes nullable

### cash_movements

- id
- cash_session_id
- movement_type
- amount
- payment_method nullable
- reference_type nullable
- reference_id nullable
- notes
- created_by
- created_at

## 9. Recargas

### topup_balances

- id
- provider_name
- current_balance
- updated_at

### topup_loads

- id
- topup_balance_id
- loaded_amount
- loaded_at
- payment_method
- notes
- created_by

### topup_sales

- id
- sale_item_id
- phone_number nullable
- operator_name nullable
- topup_amount
- commission_amount
- net_platform_discount nullable

## 10. Retornables (fase posterior)

### returnable_containers

- id
- variant_id
- container_type
- deposit_price
- is_active

### returnable_movements

- id
- customer_id nullable
- sale_id nullable
- container_id
- movement_type
- quantity
- amount
- moved_at
- notes

## Relaciones clave

- Un producto tiene muchas variantes.
- Una variante tiene muchas presentaciones de venta.
- Una presentación tiene muchos precios históricos.
- Una compra tiene muchos ítems.
- Un ítem de compra genera uno o varios lotes.
- Una venta tiene muchos ítems.
- Un ítem de venta puede consumir varios lotes.
- Un cliente puede tener muchas cuentas por cobrar.
- Una caja tiene muchos movimientos.
- Una recarga vendida cuelga de un ítem de venta.

## Reglas de consistencia recomendadas

1. `available_quantity` nunca debe moverse sin generar `inventory_movements`.
2. Si `credit_amount > 0`, la venta debe tener `customer_id`.
3. Un `sale_item` de tipo `topup` debe tener registro asociado en `topup_sales`.
4. Un cierre de caja no debe existir si la sesión sigue abierta.
5. Un lote estimado debe estar explícitamente marcado.
6. Un precio no debe destruir el histórico anterior; debe cerrar vigencia y crear un nuevo registro.

## Índices importantes

- barcode en variantes,
- internal_code en productos,
- sold_at en ventas,
- purchased_at en compras,
- expiration_date en lotes,
- customer_id en cuentas por cobrar,
- cash_session_id en movimientos de caja.

## Notas de evolución

- El soporte XML de facturas puede crear una entidad `purchase_import_batches` en el futuro.
- Si luego se requiere multi-sucursal, habrá que introducir `store_id` en entidades operativas.
- Si el volumen crece, SQLite puede migrarse a PostgreSQL sin cambiar la semántica del dominio.
