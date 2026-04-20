## Vistas y front-end — PHP + Tailwind

Convenciones para vistas PHP, componentes y estilos en el SGEP.

### Estructura de `app/views`

```text
app/views/
├── components/
│   ├── icons.php
│   ├── page_header.php
│   ├── sidebar.php
│   └── ui.php
├── dashboard.php
├── aprendices/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── show.php
├── momentos/
│   └── create.php
├── import/
│   ├── upload.php
│   └── preview.php
└── reports/
    └── maestro.php
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
- [ ] Incluir validaciones visuales y mensajes de error.
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
