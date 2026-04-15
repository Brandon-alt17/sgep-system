# Guia de iconos SVG

Esta guia define como gestionar iconos para mantener consistencia visual y facilitar cambios globales.

## Ubicacion

- Carpeta de iconos: `app/views/components/Icons/`
- Formato: archivos `.svg` individuales

Ejemplos:

- `app/views/components/Icons/dashboard.svg`
- `app/views/components/Icons/importar.svg`

## Convencion de nombres

- Usar minusculas y guion bajo bajo solo si es necesario.
- El nombre del archivo debe coincidir con el nombre usado en `ui_icon()`.
- Evitar espacios y caracteres especiales.

Ejemplo:

- Archivo: `app/views/components/Icons/reportes.svg`
- Uso: `ui_icon('reportes')`

## Uso en vistas

El helper centralizado esta en:

- `app/views/components/icons.php`

Uso recomendado:

```php
<span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4">
    <?= ui_icon('dashboard') ?>
</span>
```

No uses `[&_svg]:fill-current` en el contenedor si el icono es de **trazo** (`stroke`): el `fill` forzado rellena las formas cerradas y deforma el icono. En esos casos deja `fill="none"` y `stroke="currentColor"` en el `.svg`.

## Reglas de diseno recomendadas

- `viewBox`: usar `0 0 24 24` para mantener escala uniforme.
- Iconos de relleno: `fill="currentColor"` en el `<svg>` (o paths) para heredar el color del texto.
- Iconos de trazo: `fill="none"` y `stroke="currentColor"`; el color sigue el `color` del padre (`text-*`).
- Trazos simples: priorizar iconos pequenos y legibles.
- Peso visual consistente entre iconos de una misma zona (sidebar, botones, etc).

## Buenas practicas

- Cambios globales: edita solo el archivo `.svg` correspondiente.
- No insertar SVG largos en las vistas; usa siempre `ui_icon()`.
- Mantener iconos semanticamente claros (ej. `importar.svg` para acciones de carga).
- Si agregas un icono nuevo, prueba su contraste en estados normal, hover y activo.

## Checklist al agregar iconos nuevos

1. Crear `app/views/components/Icons/<nombre>.svg`
2. Verificar que el SVG renderiza correctamente en 16px y 20px
3. Usar en vista con `ui_icon('<nombre>')`
4. Validar consistencia con el resto del set

