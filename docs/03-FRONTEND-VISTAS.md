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

### Comandos útiles (Tailwind CSS)

```bash
# Desarrollo con observación de cambios
npm run watch:css

# Build CSS para producción
npm run build:css
```

Si hay problemas con compilación:

```bash
npm install
npm run build:css
```

En Windows (PowerShell), si hay errores de dependencias, reinstala módulos y vuelve a ejecutar `npm run watch:css`.

### Documentación relacionada

- Ver [Back-end y controladores](./04-BACKEND-CONTROLADORES.md) para rutas y datos.
- Ver [Instalación](./01-INSTALACION.md) para Node y build.
