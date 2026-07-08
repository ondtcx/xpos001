# Diseño de solución POS para tienda

## Propósito

Este conjunto de documentos describe la propuesta funcional y técnica para construir un sistema local de gestión de tienda/POS orientado a reemplazar el uso actual de Excel con una solución más precisa, auditable y fácil de operar.

## Objetivos principales

- Registrar compras con costos variables, descuentos, impuestos y bonificaciones.
- Calcular utilidad real usando costeo por lote.
- Registrar ventas normales, ventas a fiado y abonos.
- Controlar inventario, mermas, consumo interno, vencimientos y stock inicial por regularizar.
- Manejar precios múltiples por presentación y su historial.
- Gestionar apertura y cierre de caja.
- Obtener reportes confiables por día, semana y mes.
- Dejar preparada una segunda iteración para recargas y otros extras operativos.

## Documentos

- [01-vision-mvp.md](./01-vision-mvp.md): visión del sistema, alcance del MVP y decisiones principales.
- [02-diseno-funcional.md](./02-diseno-funcional.md): módulos, pantallas, reglas funcionales y flujos operativos.
- [03-diseno-tecnico.md](./03-diseno-tecnico.md): arquitectura propuesta, componentes, decisiones técnicas y consideraciones de implementación.
- [04-modelo-de-datos.md](./04-modelo-de-datos.md): entidades, relaciones y reglas de persistencia.
- [05-plan-implementacion.md](./05-plan-implementacion.md): fases, prioridades, riesgos y orden recomendado de construcción.
- [06-preguntas-abiertas.md](./06-preguntas-abiertas.md): decisiones pendientes y aclaraciones futuras que conviene cerrar antes de implementar ciertos módulos avanzados.
- [07-esquema-sql-inicial.md](./07-esquema-sql-inicial.md): propuesta de esquema SQL inicial para el MVP.
- [08-backlog-tecnico-mvp.md](./08-backlog-tecnico-mvp.md): backlog técnico ejecutable con historias, tareas y orden recomendado.
- [09-pantallas-mvp.md](./09-pantallas-mvp.md): mapa de pantallas, campos, acciones y validaciones del MVP.
- [10-flujos-operativos-mvp.md](./10-flujos-operativos-mvp.md): recorridos operativos principales entre pantallas del sistema.
- [11-convenciones-tecnicas.md](./11-convenciones-tecnicas.md): reglas técnicas de implementación para dinero, cantidades, fechas, estados y auditoría.
- [12-estructura-proyecto.md](./12-estructura-proyecto.md): estructura inicial recomendada del proyecto y módulos de Laravel.
- [13-migraciones-iniciales.md](./13-migraciones-iniciales.md): plan realista de migraciones para la primera iteración del sistema.
- [14-plan-iteraciones.md](./14-plan-iteraciones.md): separación concreta entre iteración 1 e iteración 2, con recargas diferidas.
- [15-bootstrap-tecnico.md](./15-bootstrap-tecnico.md): arranque técnico real del proyecto Laravel, paquetes mínimos, configuración y primeras entregas.
- [16-plan-implementacion-ejecutable.md](./16-plan-implementacion-ejecutable.md): guía paso a paso para arrancar el proyecto y validar cada entrega inicial.
- [17-estado-implementacion.md](./17-estado-implementacion.md): estado real del backend construido, avances completados, decisiones aplicadas y próximos pasos.
- [18-pos-venta-rapida.md](./18-pos-venta-rapida.md): propuesta técnico-funcional de la nueva experiencia principal `POS` para venta rápida de mostrador.
- [19-pos-venta-rapida-tareas.md](./19-pos-venta-rapida-tareas.md): plan incremental de implementación para la Fase 1 de `POS`.

## Decisiones madre

1. La aplicación será **local-first**, orientada a PC, con posibilidad de exponerla a red local más adelante.
2. El almacenamiento inicial recomendado es **SQLite**.
3. El costeo será **por lote**, no por promedio.
4. La salida de inventario será **FIFO automática** con trazabilidad por lote.
5. El sistema permitirá operar aunque el inventario inicial no esté completamente regularizado, pero marcará las inconsistencias.
6. La primera etapa prioriza operación confiable; automatizaciones avanzadas como lectura XML o móvil se dejan preparadas, pero no bloquean el arranque.

## Criterio de éxito del MVP / iteración 1

El MVP será exitoso si permite:

- empezar a vender y comprar sin depender de Excel,
- conocer la utilidad real por período,
- saber el stock actual con trazabilidad razonable,
- controlar fiados y caja,
- exportar información operativa en formato básico,
- crecer por tandas sin exigir una migración histórica perfecta.

## Estado documental actual

- `16-plan-implementacion-ejecutable.md` resume el orden de ejecución y el avance alcanzado.
- `17-estado-implementacion.md` registra el estado real del sistema, lo ya implementado, limitaciones vigentes y el siguiente bloque recomendado.
- `17-estado-implementacion.md` también documenta cómo cargar el dataset demo realista de minimarket para pruebas manuales.
- La iteración 1 ya cubre el núcleo operativo; recargas, XML y otros extras siguen diferidos a iteraciones posteriores.
