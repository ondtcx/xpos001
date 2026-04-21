# Bootstrap técnico del proyecto

## Propósito

Definir cómo arrancar técnicamente el proyecto de forma controlada, sin sobrecarga innecesaria y alineado con la iteración 1 ya definida.

## Objetivo del bootstrap

Dejar lista una base de proyecto que permita empezar a construir el núcleo del POS con:

- autenticación,
- SQLite configurado,
- estructura modular mínima,
- seeders base,
- layout inicial,
- primera tanda de migraciones.

## Principios de arranque

- instalar solo lo necesario,
- evitar paquetes por moda,
- asegurar una base reproducible,
- priorizar estabilidad del dominio sobre estética prematura,
- dejar el proyecto listo para crecer sin reestructurar todo.

## Stack base del bootstrap

- Laravel
- PHP compatible con la versión estable objetivo
- SQLite
- Blade
- Livewire

## Paquetes mínimos recomendados

## 1. Livewire

### Motivo

Permite construir pantallas dinámicas como venta, compra y caja sin meter complejidad excesiva en frontend.

## 2. Laravel Breeze o autenticación simple equivalente

### Motivo

Resolver login y estructura mínima de autenticación rápido.

## 3. Paquetes a evaluar después, no al inicio

- exportación Excel,
- generación avanzada de PDF,
- permisos muy sofisticados,
- lectura XML,
- paquetes de auditoría complejos.

NO metas esto en el bootstrap. Primero el núcleo.

## Configuración inicial recomendada

## 1. Crear proyecto

Crear un proyecto Laravel limpio con la versión estable vigente al momento de implementación.

## 2. Configurar base de datos SQLite

### Recomendación

- crear archivo de base local,
- configurar `.env` para SQLite,
- validar conexión con una migración mínima.

### Criterio

La base debe poder copiarse fácilmente para respaldo y recuperación.

## 3. Configurar autenticación

### Debe dejar listo

- login,
- logout,
- usuario administrador inicial,
- protección básica de rutas.

## 4. Configurar Livewire

### Debe dejar listo

- layout principal,
- navegación base,
- componente de prueba funcional.

## 5. Configurar zona horaria y locale

### Recomendación

- zona horaria coherente con la operación local del negocio,
- formato de fecha y moneda consistente en toda la interfaz.

## 6. Configurar helpers de dinero

Debe existir desde el inicio un punto central para:

- convertir dólares a centavos,
- convertir centavos a formato visible,
- evitar que cada pantalla haga su propia lógica.

## Estructura mínima que debe existir al terminar el bootstrap

```text
app/
  Domain/
  Support/
  Http/
  Livewire/
database/
  migrations/
  seeders/
resources/
  views/
docs/
  pos/
```

## Seeders base del bootstrap

## Obligatorios

- rol administrador,
- rol ayudante,
- usuario administrador inicial.

## Recomendados

- unidades base iniciales,
- categorías iniciales opcionales.

## Convenciones a implementar desde el día 1

- dinero en centavos,
- nombres en snake_case,
- estado explícito donde aplique,
- desactivación antes que borrado,
- trazabilidad mínima por usuario en eventos críticos.

## Primeras migraciones que sí deben entrar en el bootstrap ampliado

No todas deben escribirse el primer día, pero estas son las primeras del camino real:

### Bloque 1

- users
- roles
- role_user

### Bloque 2

- categories
- brands
- base_units
- products
- product_variants

### Bloque 3

- sale_presentations
- sale_prices

## Entregables del bootstrap

Al terminar esta etapa debe existir:

- proyecto Laravel funcional,
- autenticación operativa,
- SQLite conectada,
- seeders base corriendo,
- estructura de carpetas inicial,
- layout base navegable,
- primeras migraciones ejecutadas correctamente.

## Orden exacto recomendado del arranque técnico

## Paso 1 — Crear proyecto y dependencias mínimas

- crear proyecto Laravel,
- instalar autenticación base,
- instalar Livewire.

## Paso 2 — Configurar entorno local

- configurar `.env`,
- crear archivo SQLite,
- verificar conexión,
- fijar timezone y locale.

## Paso 3 — Preparar estructura del proyecto

- crear carpetas de dominio,
- crear carpetas Livewire por módulo,
- definir layout principal.

## Paso 4 — Seeders base

- crear roles,
- crear usuario admin inicial,
- validar acceso.

## Paso 5 — Migraciones del bloque inicial

- seguridad,
- catálogo base,
- presentaciones y precios.

## Paso 6 — Primera navegación funcional

- dashboard simple,
- menú base,
- CRUD mínimo de categorías,
- CRUD mínimo de marcas,
- CRUD mínimo de productos.

## Checklist de salida del bootstrap

- [ ] login funciona
- [ ] admin puede entrar
- [ ] SQLite está operativa
- [ ] migraciones corren sin error
- [ ] seeders crean usuario inicial
- [ ] layout base carga correctamente
- [ ] existe al menos un CRUD funcional del catálogo

## Qué NO hacer en esta etapa

- no empezar por reportes,
- no empezar por XML,
- no meter recargas,
- no diseñar frontend complejo antes de cerrar el dominio,
- no escribir veinte componentes sin cerrar primero el flujo base del catálogo.

## Primera meta visible para considerar el bootstrap exitoso

La primera meta real debe ser esta:

> poder iniciar sesión, entrar al sistema y crear productos, variantes y presentaciones en una base SQLite local.

Si eso no está bien, NO tiene sentido saltar a compras o ventas.

## Ruta inmediatamente posterior al bootstrap

Cuando esta base esté lista, el siguiente bloque correcto es:

1. proveedores,
2. compras,
3. lotes,
4. inventario inicial.

Después de eso, sí tiene sentido abrir ventas y FIFO.
