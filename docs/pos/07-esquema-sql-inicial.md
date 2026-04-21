# Esquema SQL inicial del MVP

## Propósito

Este documento aterriza el modelo conceptual en una propuesta de esquema SQL inicial, pensado para una primera implementación sobre SQLite. No busca cubrir todos los refinamientos futuros, sino definir una base sólida y coherente para arrancar.

## Criterios de diseño

- claves primarias enteras autoincrementales,
- claves foráneas explícitas,
- campos monetarios como `DECIMAL(12,2)` a nivel de diseño,
- timestamps en entidades relevantes,
- historial donde exista impacto financiero,
- estados explícitos en vez de ambigüedad implícita.

> Nota: SQLite no implementa `DECIMAL` de la misma forma que otros motores. A nivel de implementación real se deberá definir si se usarán enteros en centavos o casting controlado. A nivel de diseño del dominio, se documenta como decimal por claridad.

## 1. Seguridad

```sql
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  full_name TEXT NOT NULL,
  password_hash TEXT NOT NULL,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  description TEXT
);

CREATE TABLE role_user (
  user_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

## 2. Catálogo

```sql
CREATE TABLE categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE brands (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE base_units (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  symbol TEXT NOT NULL
);

CREATE TABLE products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  category_id INTEGER,
  brand_id INTEGER,
  internal_code TEXT,
  photo_path TEXT,
  status TEXT NOT NULL DEFAULT 'active',
  notes TEXT,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (brand_id) REFERENCES brands(id)
);

CREATE UNIQUE INDEX idx_products_internal_code ON products(internal_code);

CREATE TABLE product_variants (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  sku TEXT,
  barcode TEXT,
  base_unit_id INTEGER NOT NULL,
  tracks_expiration INTEGER NOT NULL DEFAULT 0,
  is_returnable INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  notes TEXT,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (base_unit_id) REFERENCES base_units(id)
);

CREATE UNIQUE INDEX idx_variants_sku ON product_variants(sku);
CREATE UNIQUE INDEX idx_variants_barcode ON product_variants(barcode);

CREATE TABLE sale_presentations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  variant_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  conversion_factor DECIMAL(12,3) NOT NULL,
  is_default INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id)
);

CREATE TABLE sale_prices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sale_presentation_id INTEGER NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  min_price DECIMAL(12,2),
  suggested_margin_percent DECIMAL(8,2),
  starts_at TEXT NOT NULL,
  ends_at TEXT,
  created_by INTEGER NOT NULL,
  reason TEXT,
  FOREIGN KEY (sale_presentation_id) REFERENCES sale_presentations(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

## 3. Proveedores

```sql
CREATE TABLE suppliers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  tax_id TEXT,
  phone TEXT,
  address TEXT,
  notes TEXT,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE supplier_variant_refs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  supplier_id INTEGER NOT NULL,
  variant_id INTEGER NOT NULL,
  supplier_product_name TEXT,
  supplier_code TEXT,
  last_purchase_price DECIMAL(12,2),
  last_purchase_at TEXT,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  FOREIGN KEY (variant_id) REFERENCES product_variants(id)
);

CREATE UNIQUE INDEX idx_supplier_variant_unique ON supplier_variant_refs(supplier_id, variant_id);
```

## 4. Compras

```sql
CREATE TABLE purchases (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  supplier_id INTEGER,
  invoice_number TEXT,
  purchased_at TEXT NOT NULL,
  payment_type TEXT NOT NULL,
  is_credit INTEGER NOT NULL DEFAULT 0,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  global_discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  global_tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  extra_costs_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  attachment_path TEXT,
  source_type TEXT NOT NULL DEFAULT 'manual',
  notes TEXT,
  created_by INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE purchase_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  purchase_id INTEGER NOT NULL,
  variant_id INTEGER NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_cost_base DECIMAL(12,4) NOT NULL,
  line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  line_discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_vat_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_fixed_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_other_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  gift_quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  total_cost_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  expiration_date TEXT,
  notes TEXT,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  FOREIGN KEY (variant_id) REFERENCES product_variants(id)
);

CREATE TABLE purchase_allocations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  purchase_id INTEGER NOT NULL,
  purchase_item_id INTEGER NOT NULL,
  allocation_type TEXT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id)
);
```

## 5. Inventario y lotes

```sql
CREATE TABLE opening_inventory_entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  variant_id INTEGER NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  estimated_unit_cost DECIMAL(12,4) NOT NULL,
  recorded_at TEXT NOT NULL,
  is_audited INTEGER NOT NULL DEFAULT 0,
  notes TEXT,
  created_by INTEGER NOT NULL,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE inventory_lots (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  variant_id INTEGER NOT NULL,
  purchase_item_id INTEGER,
  origin_type TEXT NOT NULL,
  origin_id INTEGER,
  received_at TEXT NOT NULL,
  expiration_date TEXT,
  initial_quantity DECIMAL(12,3) NOT NULL,
  available_quantity DECIMAL(12,3) NOT NULL,
  bonus_quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  unit_cost_final DECIMAL(12,4) NOT NULL,
  suggested_sale_price DECIMAL(12,2),
  is_estimated INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  FOREIGN KEY (variant_id) REFERENCES product_variants(id),
  FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id)
);

CREATE INDEX idx_inventory_lots_variant_available ON inventory_lots(variant_id, available_quantity);
CREATE INDEX idx_inventory_lots_expiration ON inventory_lots(expiration_date);

CREATE TABLE inventory_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  variant_id INTEGER NOT NULL,
  lot_id INTEGER,
  movement_type TEXT NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_cost DECIMAL(12,4),
  reference_type TEXT NOT NULL,
  reference_id INTEGER,
  movement_at TEXT NOT NULL,
  notes TEXT,
  created_by INTEGER NOT NULL,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id),
  FOREIGN KEY (lot_id) REFERENCES inventory_lots(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

## 6. Caja

```sql
CREATE TABLE cash_sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  opened_by INTEGER NOT NULL,
  opened_at TEXT NOT NULL,
  opening_amount DECIMAL(12,2) NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  closed_at TEXT,
  expected_cash_amount DECIMAL(12,2),
  counted_cash_amount DECIMAL(12,2),
  expected_transfer_amount DECIMAL(12,2),
  difference_amount DECIMAL(12,2),
  closing_notes TEXT,
  FOREIGN KEY (opened_by) REFERENCES users(id)
);

CREATE TABLE cash_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  cash_session_id INTEGER NOT NULL,
  movement_type TEXT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method TEXT,
  reference_type TEXT,
  reference_id INTEGER,
  notes TEXT,
  created_by INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

## 7. Ventas

```sql
CREATE TABLE customers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  phone TEXT,
  address TEXT,
  notes TEXT,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE sales (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sold_at TEXT NOT NULL,
  customer_id INTEGER,
  cash_session_id INTEGER,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  credit_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'confirmed',
  notes TEXT,
  created_by INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE INDEX idx_sales_sold_at ON sales(sold_at);

CREATE TABLE sale_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sale_id INTEGER NOT NULL,
  item_type TEXT NOT NULL,
  sale_presentation_id INTEGER,
  variant_id INTEGER,
  description_snapshot TEXT NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  total_cost DECIMAL(12,2),
  total_profit DECIMAL(12,2),
  has_cost_warning INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (sale_presentation_id) REFERENCES sale_presentations(id),
  FOREIGN KEY (variant_id) REFERENCES product_variants(id)
);

CREATE TABLE sale_item_lot_consumptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sale_item_id INTEGER NOT NULL,
  lot_id INTEGER NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_cost DECIMAL(12,4) NOT NULL,
  total_cost DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (sale_item_id) REFERENCES sale_items(id),
  FOREIGN KEY (lot_id) REFERENCES inventory_lots(id)
);

CREATE TABLE sale_payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sale_id INTEGER NOT NULL,
  payment_method TEXT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  received_at TEXT NOT NULL,
  notes TEXT,
  FOREIGN KEY (sale_id) REFERENCES sales(id)
);
```

## 8. Cuentas por cobrar

```sql
CREATE TABLE receivables (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  sale_id INTEGER NOT NULL,
  original_amount DECIMAL(12,2) NOT NULL,
  pending_amount DECIMAL(12,2) NOT NULL,
  opened_at TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (sale_id) REFERENCES sales(id)
);

CREATE TABLE receivable_payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  receivable_id INTEGER NOT NULL,
  cash_session_id INTEGER,
  amount DECIMAL(12,2) NOT NULL,
  payment_method TEXT NOT NULL,
  paid_at TEXT NOT NULL,
  notes TEXT,
  created_by INTEGER NOT NULL,
  FOREIGN KEY (receivable_id) REFERENCES receivables(id),
  FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

## 9. Recargas

```sql
CREATE TABLE topup_balances (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  provider_name TEXT NOT NULL,
  current_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  updated_at TEXT NOT NULL
);

CREATE TABLE topup_loads (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  topup_balance_id INTEGER NOT NULL,
  loaded_amount DECIMAL(12,2) NOT NULL,
  loaded_at TEXT NOT NULL,
  payment_method TEXT NOT NULL,
  notes TEXT,
  created_by INTEGER NOT NULL,
  FOREIGN KEY (topup_balance_id) REFERENCES topup_balances(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE topup_sales (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sale_item_id INTEGER NOT NULL,
  phone_number TEXT,
  operator_name TEXT,
  topup_amount DECIMAL(12,2) NOT NULL,
  commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  platform_discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (sale_item_id) REFERENCES sale_items(id)
);
```

## 10. Retornables (fase posterior)

```sql
CREATE TABLE returnable_containers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  variant_id INTEGER NOT NULL,
  container_type TEXT NOT NULL,
  deposit_price DECIMAL(12,2) NOT NULL,
  is_active INTEGER NOT NULL DEFAULT 1,
  FOREIGN KEY (variant_id) REFERENCES product_variants(id)
);

CREATE TABLE returnable_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER,
  sale_id INTEGER,
  container_id INTEGER NOT NULL,
  movement_type TEXT NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  moved_at TEXT NOT NULL,
  notes TEXT,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (sale_id) REFERENCES sales(id),
  FOREIGN KEY (container_id) REFERENCES returnable_containers(id)
);
```

## Restricciones lógicas a implementar en aplicación

Estas reglas no dependen solo de SQL y deben validarse en servicios o casos de uso:

1. No permitir `credit_amount > 0` sin `customer_id`.
2. No permitir cerrar caja dos veces.
3. Toda venta de producto físico debe intentar consumir lotes por FIFO.
4. Si no hay stock suficiente, marcar advertencia operativa.
5. Cada actualización de precio debe cerrar vigencia del precio anterior.
6. Cada abono debe disminuir saldo pendiente sin volverlo negativo.
7. Cada recarga vendida debe impactar el saldo disponible de la plataforma.
8. Todo movimiento que afecte inventario debe dejar rastro en `inventory_movements`.

## Tablas candidatas para una segunda iteración

- `audit_logs`
- `purchase_import_batches`
- `tax_definitions`
- `alert_rules`
- `product_images`
- `attachments`

## Recomendación de implementación

No intentes crear todas las tablas avanzadas y todos los flujos especiales desde la primera migración real. Lo correcto es:

1. crear núcleo de seguridad, catálogo, compras, lotes, ventas, fiado, caja y recargas,
2. validar operación,
3. agregar refinamientos como retornables avanzados y XML cuando el núcleo esté estable.
