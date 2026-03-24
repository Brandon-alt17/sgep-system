## Vistas y front-end — Blade + Tailwind

Convenciones para vistas Blade, componentes y estilos en el SGEP.

### Estructura de `resources/views`

```text
resources/views/
├── layouts/
│   └── app.blade.php              # Layout base
├── components/                    # Componentes reutilizables
│   ├── alert.blade.php
│   ├── modal.blade.php
│   ├── badge.blade.php
│   └── form/
│       ├── input.blade.php
│       ├── select.blade.php
│       └── textarea.blade.php
├── dashboard.blade.php
├── apprentices/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── evaluations/
│   ├── moment1.blade.php
│   ├── moment2.blade.php
│   └── moment3.blade.php
├── import/
│   ├── upload.blade.php
│   └── mapping.blade.php
└── reports/
    └── maestro.blade.php
```

### Colores del sistema (Tailwind)

Los colores institucionales y de estado se definen en `tailwind.config.cjs`:

```js
// tailwind.config.cjs
module.exports = {
  theme: {
    extend: {
      colors: {
        'sena-green': '#009B4D',     // Verde institucional SENA
        'sena-blue': '#004B87',      // Azul institucional
        'sena-orange': '#F39200',    // Naranja institucional
        'status-pending': '#FBBF24', // Amarillo (pendiente)
        'status-active': '#10B981',  // Verde (en ejecución)
        'status-done': '#3B82F6',    // Azul (certificado)
      },
    },
  },
};
```

### Antes de empezar una vista

- [ ] Revisar si ya existe un componente reutilizable.
- [ ] Alinear con back-end: qué datos y rutas se necesitan.
- [ ] Definir estructura HTML con Tailwind.
- [ ] Incluir validaciones visuales (`@error`, mensajes).
- [ ] Probar responsive (móvil, tablet, escritorio).

### Antes de hacer commit

- [ ] Sin código comentado o temporal innecesario.
- [ ] Clases de Tailwind correctas y consistentes.
- [ ] Formularios con feedback de error visible.
- [ ] Botones con estados hover/focus.
- [ ] Tablas usables en pantallas pequeñas.
- [ ] Sin `console.log()` olvidados.

### Comandos útiles (Vite)

```bash
# Desarrollo con hot-reload
npm run dev

# Build para producción
npm run build
```

Si hay problemas con caché de Vite:

```bash
# macOS / Linux
rm -rf node_modules/.vite
npm run dev
```

En Windows (PowerShell), puedes borrar la carpeta `node_modules\.vite` desde el explorador y volver a ejecutar `npm run dev`.

### Documentación relacionada

- Ver [Back-end y controladores](./04-BACKEND-CONTROLADORES.md) para rutas y datos.
- Ver [Instalación](./01-INSTALACION.md) para Node y build.
