# Patrones de UI (vistas hijas)

## Volver junto al título (vistas hijas)

Las vistas secundarias (formularios o flujos bajo un contexto padre, p. ej. perfil del aprendiz o edición en catálogo) no usan un botón «Volver» en la zona de acciones del encabezado genérico. En su lugar se usa un bloque horizontal con:

- Enlace con `ui_button_icon_classes()`, ícono `ui_icon('arrow')` y `aria-label` descriptivo.
- Título en `<h2 class="m-0 text-2xl font-semibold text-app-text">` y subtítulo opcional en `<p class="m-0 text-sm text-app-muted">`, con `pl-4` en el contenedor del texto respecto a la flecha.

Ejemplo de referencia: `app/views/documentos/info.php` y `app/views/documents/generate.php` (rama con aprendiz). El partial `components/page_header` solo expone `title`, `subtitle` y `actions`; no incluye navegación «atrás».
