# Plan de implementación ejecutable

## Propósito

Convertir la documentación de diseño en una guía práctica de arranque, paso a paso, con entregables verificables. La idea es reducir improvisación y asegurar que cada bloque construido deje una base útil para el siguiente.

## Filosofía de ejecución

- construir en verticales pequeñas,
- validar cada paso antes de abrir el siguiente,
- no avanzar de módulo si el anterior todavía es frágil,
- priorizar núcleo transaccional sobre extras,
- evitar “hacer mucho” sin cerrar nada.

## Resultado esperado de esta guía

Al terminar estos pasos debe existir un proyecto local que permita:

- iniciar sesión,
- administrar catálogo base,
- tener convenciones financieras listas,
- dejar preparada la base para compras e inventario.

> Nota de contexto: esta guía nació como plan de arranque. El proyecto ya superó este punto; por eso, al final del documento se mantiene un bloque de **estado real de avance** para no perder trazabilidad entre lo planeado y lo construido.

## Etapa 0 — Preparación del entorno

## Objetivo

Verificar que la máquina de desarrollo puede sostener el proyecto sin fricción básica.

## Tareas

1. Verificar versión de PHP compatible con Laravel objetivo.
2. Verificar Composer.
3. Verificar Node.js solo si se necesita para assets del stack elegido.
4. Verificar extensión SQLite de PHP.
5. Confirmar que el entorno local puede ejecutar Artisan.

## Validación de salida

- PHP disponible,
- Composer operativo,
- SQLite habilitado,
- no hay bloqueos básicos del entorno.

## Etapa 1 — Crear proyecto base

## Objetivo

Levantar la base limpia del proyecto.

## Tareas

1. Crear proyecto Laravel.
2. Generar archivo `.env` local.
3. Crear archivo de base SQLite.
4. Configurar conexión a SQLite.
5. Ajustar `APP_NAME`, timezone y locale.

## Comandos orientativos

```bash
composer create-project laravel/laravel .
php artisan key:generate
```

> Los comandos exactos pueden variar según la versión elegida, pero el objetivo es el mismo: proyecto Laravel limpio y funcional.

## Validación de salida

- `php artisan` responde,
- la app levanta,
- la conexión SQLite queda configurada.

## Entregable

- proyecto Laravel funcional en local.

## Etapa 2 — Autenticación y base de acceso

## Objetivo

Tener entrada segura al sistema sin perder tiempo reinventando login.

## Tareas

1. Instalar autenticación simple.
2. Crear flujo de login/logout.
3. Verificar protección de rutas.
4. Dejar una pantalla privada mínima después del login.

## Validación de salida

- usuario puede iniciar sesión,
- rutas privadas requieren autenticación,
- existe pantalla inicial autenticada.

## Entregable

- autenticación funcional.

## Etapa 3 — Livewire y layout base

## Objetivo

Preparar la base de las pantallas dinámicas del sistema.

## Tareas

1. Instalar Livewire.
2. Crear layout principal.
3. Crear navegación base del sistema.
4. Crear dashboard simple autenticado.

## Validación de salida

- Livewire funciona,
- dashboard carga,
- el usuario puede navegar desde un layout coherente.

## Entregable

- shell inicial de la aplicación.

## Etapa 4 — Convenciones y soporte técnico

## Objetivo

Evitar que los cálculos y formatos empiecen a dispersarse desde el día 1.

## Tareas

1. Crear helper o servicio de dinero.
2. Definir conversión de dólares ↔ centavos.
3. Definir utilidades de formateo para interfaz.
4. Dejar documentadas constantes o enums base para estados críticos si el stack lo permite.

## Validación de salida

- existe una utilidad central para dinero,
- no hay lógica financiera repetida en vistas,
- el equipo sabe que el dinero se maneja en centavos.

## Entregable

- soporte técnico base consistente.

## Etapa 5 — Seguridad del dominio: roles y seeders

## Objetivo

Preparar control mínimo de acceso y usuario inicial.

## Tareas

1. Crear migraciones de `roles` y `role_user`.
2. Crear modelo y relaciones.
3. Crear seeder de roles base.
4. Crear seeder de usuario administrador inicial.
5. Probar asignación de roles.

## Validación de salida

- existe rol administrador,
- existe rol ayudante,
- existe usuario admin inicial,
- el acceso básico por rol puede evaluarse.

## Entregable

- seguridad mínima lista para crecer.

## Etapa 6 — Catálogo base: tablas y modelos

## Objetivo

Levantar la base de datos y modelos del catálogo antes de abrir compras o ventas.

## Tareas

1. Crear migraciones de:
   - categories
   - brands
   - base_units
   - products
   - product_variants
   - sale_presentations
   - sale_prices
2. Crear modelos y relaciones.
3. Crear factories o seeders mínimos si ayudan a probar.
4. Ejecutar migraciones.

## Validación de salida

- las migraciones corren limpias,
- relaciones básicas responden,
- catálogo ya existe a nivel de base de datos.

## Entregable

- núcleo del catálogo listo.

## Etapa 7 — CRUDs mínimos del catálogo

## Objetivo

Validar el primer flujo real usable por usuario.

## Tareas

1. CRUD de categorías.
2. CRUD de marcas.
3. CRUD de unidades base.
4. CRUD de productos.
5. CRUD de variantes.
6. CRUD de presentaciones.
7. Alta de precio vigente con historial.

## Validación de salida

- se puede crear producto base,
- se puede crear variante,
- se puede crear presentación,
- se puede registrar precio vigente,
- el histórico no se pierde al cambiar precio.

## Entregable

- primer vertical funcional completo.

## Etapa 8 — Endurecimiento del catálogo

## Objetivo

Cerrar errores básicos antes de abrir módulos más sensibles.

## Tareas

1. Validar unicidad de códigos.
2. Validar una sola presentación por defecto.
3. Validar una sola vigencia activa por presentación.
4. Implementar desactivación en vez de borrado donde aplique.
5. Revisar mensajes de error y usabilidad mínima.

## Validación de salida

- el catálogo no se rompe con casos obvios,
- la UX básica es clara,
- el sistema ya puede sostener carga inicial de productos.

## Entregable

- catálogo suficientemente estable para pasar a proveedores y compras.

## Etapa 9 — Proveedores

## Objetivo

Preparar el primer módulo de apoyo a compras.

## Tareas

1. Migraciones de `suppliers` y `supplier_variant_refs`.
2. Modelos y relaciones.
3. CRUD de proveedores.
4. Pantalla de relación proveedor-variante.

## Validación de salida

- se puede registrar proveedor,
- se puede asociar variante a proveedor,
- queda lista la base para compras.

## Entregable

- proveedores funcionales.

## Etapa 10 — Compras e inventario inicial

## Objetivo

Abrir el primer flujo financiero serio del sistema.

## Tareas

1. Migraciones de compras.
2. Migraciones de lotes e inventario.
3. Servicios de creación de compra.
4. Servicios de creación de lotes.
5. Pantalla de compra modo rápido.
6. Pantalla de inventario inicial.

## Validación de salida

- una compra crea lotes,
- el inventario inicial crea stock estimado,
- ya existe base para calcular costo real.

## Entregable

- primer flujo económico central listo.

## Comandos de referencia por bloque

## Crear modelo con migración

```bash
php artisan make:model Category -m
php artisan make:model Brand -m
php artisan make:model Product -m
```

## Crear seeder

```bash
php artisan make:seeder RolesTableSeeder
php artisan make:seeder AdminUserSeeder
```

## Ejecutar migraciones y seeders

```bash
php artisan migrate
php artisan db:seed
```

## Crear componente Livewire

```bash
php artisan make:livewire Catalog/ProductForm
php artisan make:livewire Catalog/ProductIndex
```

> Estos comandos son ilustrativos. Los nombres finales deben alinearse con la convención real adoptada.

## Reglas para no perder el foco

1. No abrir ventas antes de que compras e inventario generen lotes confiables.
2. No abrir recargas en iteración 1.
3. No empezar reportes avanzados antes de validar datos base.
4. No meter permisos complejos antes de roles mínimos funcionales.
5. No escribir CRUDs masivos sin cerrar primero el vertical del catálogo.

## Señales de avance sano

Vas bien si puedes decir esto con evidencia:

- ya entro al sistema,
- ya creo productos correctamente,
- ya tengo variantes y presentaciones,
- ya guardo precios con historial,
- ya tengo proveedores listos,
- ya una compra me genera lotes.

## Señales de que te estás desviando

- tienes muchas pantallas pero ninguna termina el flujo,
- ya estás pensando en XML sin tener compras manuales sólidas,
- ya estás pensando en móvil sin tener catálogo estable,
- ya estás armando reportes sin datos confiables.

## Ruta después de esta guía

Después de ejecutar este arranque, el siguiente documento que conviene convertir en trabajo real es:

- `13-migraciones-iniciales.md`
- luego `08-backlog-tecnico-mvp.md`
- luego `09-pantallas-mvp.md`

Ese orden mantiene el dominio por encima de la improvisación.

## Estado real de avance

- ✅ Etapa 0 — entorno validado
- ✅ Etapa 1 — proyecto Laravel creado en `backend/`
- ✅ Etapa 2 — autenticación base instalada
- ✅ Etapa 3 — layout/navegación inicial operativa
- ✅ Etapa 4 — helper monetario y convención de centavos aplicada
- ✅ Etapa 5 — roles y usuario administrador inicial
- ✅ Etapa 6 — migraciones y modelos base del catálogo
- ✅ Etapa 7 — CRUD inicial del catálogo
- ✅ Etapa 8 — endurecimiento básico del catálogo
- ✅ Etapa 9 — proveedores iniciales
- ✅ Etapa 10 — compras rápidas, inventario inicial y lotes
- ✅ Bloque siguiente — ventas, fiado y abonos en versión inicial
- ✅ Bloque siguiente — caja en versión inicial integrada con ventas y abonos
- ✅ Bloque siguiente — reportes operativos del núcleo en versión inicial
- ✅ Bloque siguiente — endurecimiento crítico inicial de ventas, abonos y caja
- ✅ Bloque siguiente — exportaciones básicas iniciales (CSV y vista imprimible)
- ✅ Bloque siguiente — refinamientos iniciales de UX en compras, ventas e inventario
- ✅ Bloque siguiente — compras detalladas con prorrateos, edición/anulación controlada y tests base
- ✅ Bloque siguiente — ventas refinadas con anulación total, búsqueda rápida, override de precio y warnings explícitos
- ⏳ Siguiente bloque: refinamientos finales de UX/visibilidad operativa y reportabilidad fina del núcleo
- ⏳ Luego: exportaciones más completas
- ⏸ Después: iteración 2 (recargas y extras)
