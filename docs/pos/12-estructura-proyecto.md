# Estructura inicial del proyecto

## Propósito

Definir cómo organizar el proyecto para que el crecimiento no se vuelva caótico desde la primera iteración.

## Stack base

- Laravel
- SQLite
- Blade + Livewire

## Enfoque

Monolito modular organizado por dominio, no por accidente del framework.

## Estructura recomendada

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
  Support/
  Http/
    Controllers/
    Requests/
  Livewire/
    Catalog/
    Purchasing/
    Inventory/
    Sales/
    Receivables/
    Cash/
    TopUps/
    Reporting/
database/
  migrations/
  seeders/
resources/
  views/
    layouts/
    components/
    pages/
docs/
  pos/
```

## Criterio por capa

## 1. Domain

Contiene reglas de negocio y conceptos del dominio.

### Ejemplos

- servicios para consumir lotes FIFO,
- cálculo de costo final de compra,
- apertura/cierre de caja,
- registro de abonos,
- actualización de saldo de recargas.

## 2. Http

Contiene entrada web tradicional:

- controladores,
- form requests,
- middleware.

## 3. Livewire

Contiene pantallas interactivas del sistema.

### Ejemplos

- pantalla de venta,
- compra con líneas dinámicas,
- cierre de caja,
- carga de inventario inicial.

## 4. Support

Contiene utilidades compartidas que NO son dominio puro.

### Ejemplos

- formateo de dinero,
- helpers de fechas,
- conversión de centavos,
- utilidades de importación.

## Módulos sugeridos

## Catalog

- categorías,
- marcas,
- productos,
- variantes,
- presentaciones,
- historial de precios.

## Purchasing

- compras,
- líneas de compra,
- prorrateos,
- adjuntos,
- futura lectura XML.

## Inventory

- lotes,
- inventario inicial,
- movimientos,
- ajustes,
- vencimientos.

## Sales

- ventas,
- líneas,
- pagos,
- consumo de lotes,
- advertencias de margen y stock.

## Receivables

- clientes,
- cuentas por cobrar,
- abonos,
- alertas de deuda.

## Cash

- apertura,
- movimientos,
- cierre,
- diferencias.

## TopUps

- saldo plataforma,
- cargas,
- ventas de recarga,
- comisión.

## Suppliers

- proveedores,
- relación proveedor-producto,
- consultas de historial.

## Reporting

- consultas agregadas,
- exportaciones,
- panel de indicadores.

## Security

- usuarios,
- roles,
- permisos.

## Migraciones

## Recomendación

Crear migraciones por grupos funcionales, no una sola migración gigante.

### Orden recomendado

1. seguridad,
2. catálogo,
3. proveedores,
4. compras,
5. inventario,
6. caja,
7. ventas,
8. cuentas por cobrar,
9. recargas,
10. tablas futuras opcionales.

## Seeders iniciales

### Sugeridos

- rol administrador,
- rol ayudante,
- usuario administrador inicial,
- unidades base mínimas,
- categorías base opcionales.

## Servicios de dominio recomendados

### Ejemplos iniciales

- `CreatePurchaseService`
- `AllocatePurchaseAmountsService`
- `CreateInventoryLotService`
- `ConsumeLotsFifoService`
- `RegisterSaleService`
- `RegisterCreditPaymentService`
- `OpenCashSessionService`
- `CloseCashSessionService`
- `RegisterTopUpSaleService`
- `RegisterTopUpLoadService`

## Requests / validaciones sugeridas

- `StoreProductRequest`
- `StoreVariantRequest`
- `StorePurchaseRequest`
- `StoreOpeningInventoryRequest`
- `StoreSaleRequest`
- `StoreReceivablePaymentRequest`
- `OpenCashSessionRequest`
- `CloseCashSessionRequest`

## Componentes Livewire sugeridos

- `Sales/SaleScreen`
- `Purchasing/PurchaseForm`
- `Inventory/OpeningInventoryForm`
- `Cash/CashOpenForm`
- `Cash/CashCloseForm`
- `Receivables/CustomerStatement`
- `TopUps/TopUpLoadForm`
- `Catalog/ProductForm`
- `Catalog/VariantForm`

## Convenciones de implementación importantes

- evitar lógica de negocio compleja en controladores,
- evitar cálculos financieros directamente en la vista,
- centralizar reglas sensibles en servicios,
- usar DTOs o estructuras claras si los formularios crecen,
- mantener las consultas de reportes separadas del flujo transaccional.

## Orden realista para empezar código

### Etapa 1

- crear proyecto Laravel,
- configurar SQLite,
- autenticación,
- roles,
- layout base.

### Etapa 2

- catálogo,
- productos,
- variantes,
- presentaciones,
- precios.

### Etapa 3

- compras,
- lotes,
- inventario inicial.

### Etapa 4

- ventas,
- pagos,
- FIFO,
- fiado.

### Etapa 5

- caja,
- recargas,
- reportes iniciales.

## Qué evitar desde el inicio

- sobrearquitectura,
- patrones innecesarios si no resuelven un problema real,
- múltiples capas vacías sin uso,
- módulos “future-proof” gigantes que retrasan el MVP,
- lógica financiera dispersa en muchos lugares.
