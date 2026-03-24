---
name: "Tarea back-end"
about: "Controladores, validaciones, servicios, rutas o lógica de dominio en Laravel."
title: "[Back] "
labels: ["backend"]
---

## Objetivo técnico

Qué debe hacer el servidor (reglas, persistencia, respuesta HTTP).

## Área afectada (si ya la conoces)

- Rutas: `routes/web.php` (u otras)
- Controladores: `app/Http/Controllers/...`
- Requests / Rules: `app/Http/Requests`, `app/Rules`
- Servicios / Actions: `app/Services`, `app/Actions`

## Contrato esperado

- **Entrada:** query/body/validaciones relevantes
- **Salida:** vista, JSON, redirect, descarga de archivo, etc.
- **Efectos:** BD, colas, archivos en `storage/`

## Seguridad y datos

¿Datos sensibles, permisos futuros, validaciones de negocio? Enlaza RN o `docs/reglas_del_negocio.md` si aplica.

## Verificación

- [ ] `php artisan route:list` incluye las rutas nuevas o cambiadas
- [ ] Prueba manual o `php artisan test` (si hay cobertura)
