# xpos001

Sistema POS **local-first** para tienda de barrio, orientado a reemplazar el uso de Excel con una solución auditable, confiable y fácil de operar.

> **Estado actual:** núcleo operativo funcionando (iteración 1).
> Ver [`docs/pos/17-estado-implementacion.md`](docs/pos/17-estado-implementacion.md) para el detalle de lo construido, limitaciones vigentes y próximos pasos.

## Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade + Livewire 4 + Vite
- **Base de datos:** SQLite por defecto (cambiable a MySQL/Postgres vía `.env`)
- **Tests:** PHPUnit 11
- **Exports:** `barryvdh/laravel-dompdf` (PDF), `phpoffice/phpspreadsheet` (XLSX)
- **Code style:** Laravel Pint

## Quick start

```bash
# 1. Clonar
git clone https://github.com/ondtcx/xpos001.git
cd xpos001

# 2. Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

El seeder `MinimarketDemoSeeder` carga un dataset realista de minimarket para probar manualmente (clientes, productos, stock, ventas históricas).

Si necesitás recompilar assets frontend:

```bash
cd backend
npm install
npm run dev
```

## Estructura del repo

```
xpos001/
├── backend/                # Aplicación Laravel 12
│   ├── app/                # Controladores, modelos, requests, soporte
│   ├── database/           # Migraciones, seeders, factories
│   ├── resources/          # Vistas Blade + assets frontend
│   ├── routes/             # Rutas web y API
│   └── tests/              # PHPUnit (Feature y Unit)
├── docs/pos/               # Diseño funcional y técnico (19 documentos)
└── openspec/               # Specs del workflow SDD (Spec-Driven Development)
```

## Documentación

- [`docs/pos/README.md`](docs/pos/README.md) — índice completo del diseño: visión, modelo de datos, arquitectura, planes y estado.
- [`docs/pos/17-estado-implementacion.md`](docs/pos/17-estado-implementacion.md) — qué está implementado, qué falta, próximos pasos.
- [`docs/pos/18-pos-venta-rapida.md`](docs/pos/18-pos-venta-rapida.md) — propuesta de la pantalla principal de venta rápida.
- [`docs/pos/11-convenciones-tecnicas.md`](docs/pos/11-convenciones-tecnicas.md) — reglas de dinero, cantidades, fechas, estados y auditoría.

## Decisiones de diseño (resumen)

1. **Local-first**, orientado a PC, con posibilidad de exponerlo a red local más adelante.
2. **SQLite** como almacenamiento por defecto.
3. **Costeo por lote** (no promedio ponderado).
4. **FIFO automática** para salida de inventario, con trazabilidad por lote.
5. La app puede operar aunque el inventario inicial no esté del todo regularizado, marcando las inconsistencias.
6. La primera etapa prioriza operación confiable; automatizaciones avanzadas (lectura XML, móvil, recargas) quedan para iteraciones futuras.

## Convenciones de desarrollo

- Commits en formato [Conventional Commits](https://www.conventionalcommits.org/).
- Estilo enforced por Laravel Pint (`cd backend && vendor/bin/pint`).
- Tests con PHPUnit (`cd backend && php artisan test`).
- Las decisiones de producto/técnicas se documentan vía SDD: specs en `openspec/specs/`, cambios activos en `openspec/changes/`.
