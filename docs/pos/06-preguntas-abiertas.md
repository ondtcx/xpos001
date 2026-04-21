# Preguntas abiertas y decisiones futuras

## Propósito

Este documento lista temas que NO bloquean el MVP base, pero que conviene definir antes de implementar módulos avanzados o automatizaciones específicas.

## 1. Recargas

- Confirmar si en la práctica cada operadora maneja una comisión distinta o si la plataforma devuelve una sola comisión uniforme.
- Definir si la operadora debe registrarse siempre o solo cuando aplique.
- Confirmar si habrá necesidad de anular o corregir recargas ya registradas y cómo se reflejaría eso en el saldo.

## 2. Retornables

- Definir el flujo exacto cuando el cliente deja más dinero que el valor del envase.
- Confirmar si el depósito se devuelve completo, parcialmente o según el caso.
- Definir si el envase prestado sin devolución genera deuda al cliente o solo pérdida operativa.
- Confirmar si habrá necesidad de historial por tipo de envase y por cliente.

## 3. Facturas y XML

- Verificar ejemplos reales de XML de factura electrónica usados por proveedores.
- Confirmar si todos los XML relevantes traen detalle suficiente por producto.
- Definir qué campos del XML serán confiables para autocompletar y cuáles deberán revisarse manualmente.

## 4. Impuestos de compras

- Confirmar lista exacta de impuestos frecuentes que deben modelarse desde el MVP.
- Identificar reglas reales para productos exentos versus gravados en los casos más comunes.
- Definir si algunos impuestos requieren mostrarse siempre por separado en reportes.

## 5. Precios

- Confirmar qué productos deben usar con más frecuencia la sugerencia automática de cambio de precio.
- Definir si habrá reglas distintas por categoría, por ejemplo huevos o tabacos.
- Confirmar hasta qué punto un ayudante puede modificar precios en venta y bajo qué permisos.

## 6. Operación y respaldo

- Definir estrategia de respaldo de la base local: manual, diaria automática o ambas.
- Confirmar si se necesita restauración sencilla desde copia de seguridad para un usuario no técnico.
- Definir cuándo será necesario exponer la aplicación a red local para uso desde teléfono.

## 7. Migración inicial

- Revisar una muestra real del Excel actual para mapear columnas y detectar duplicados relevantes.
- Definir una estrategia de normalización mínima para nombres de productos parecidos.
- Confirmar si conviene crear una plantilla de importación intermedia para limpiar datos antes de cargar.

## Recomendación

Estas preguntas no deben detener el arranque del núcleo del sistema. Deben tratarse como decisiones de refinamiento para fases posteriores o para módulos con mayor complejidad operativa.
