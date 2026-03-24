---
name: "Tarea base de datos"
about: "Migraciones, modelos, seeders, índices o integridad referencial."
title: "[DB] "
labels: ["database"]
---

## Cambio de esquema o datos

Describe tablas/columnas/relaciones que entran, cambian o salen.

## Compatibilidad

- ¿Hay datos en producción local que deban migrarse sin `migrate:fresh`?
- ¿Requiere backfill o seeder nuevo?

## Modelo y relaciones

Modelos Eloquent afectados (`app/Models/...`) y relaciones (`belongsTo`, `hasMany`, etc.).

## Referencia

Enlace o sección de `docs/base_de_datos.md` o diagrama acordado.

## Verificación

- [ ] `php artisan migrate` (y rollback si aplica) probado en local
- [ ] Índices y FK acordes al volumen esperado (cédulas, búsquedas frecuentes)
