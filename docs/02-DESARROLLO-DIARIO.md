## Desarrollo diario — SGEP

Guía del proceso estándar para desarrollar nuevas funcionalidades y mantener el repositorio alineado con el equipo.

> **Nota:** La rama principal de integración en este proyecto es **`dev`**. Si tu flujo usa otra rama (por ejemplo `main`), acórdalo con el equipo antes de integrar.

### 1. Sincronización inicial

Antes de programar, actualiza tu copia local con lo último del repositorio:

```bash
git checkout dev
git pull origin dev
```

### 2. Gestión de ramas

No trabajes directamente sobre `dev`. Crea una rama por tarea o historia.

**Estándar de nombres:** `feature/RF-XX-descripcion-corta`

**Ejemplo:** `feature/RF-01-importar-csv`

```bash
git checkout -b feature/RF-01-importar-csv
```

### 3. Preparación del entorno

- Confirma que **WAMP/MAMP** (o el stack acordado) está en ejecución y con el icono en verde cuando aplique.
- Si hubo cambios en dependencias en el repo, reinstala:

```bash
composer install
npm install
```

**Compilación de assets (CSS/JS):**

```bash
# Opción A: desarrollo (hot-reload mientras programas)
npm run dev

# Opción B: producción (compilación final)
npm run build
```

### 4. Ejecución y pruebas

**Servidor de desarrollo Laravel:**

```bash
php artisan serve
```

**Si los cambios no se reflejan** (`.env`, rutas, vistas):

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

**Rutas y tests:**

```bash
php artisan route:list
php artisan test
```

### 5. Guardar progreso (WIP)

Si cierras la jornada sin terminar la tarea, deja el avance subido:

```bash
git add .
git commit -m "wip(RF-01): avanzar en validación de CSV"
git push origin feature/RF-01-importar-csv
```

### Consejos rápidos

- **Detener procesos:** `Ctrl + C` en `php artisan serve` y en `npm run dev`.
- **Limpieza general:** si algo se comporta de forma extraña, suele ayudar:

```bash
php artisan optimize:clear
```

### Documentación relacionada

- Ver [Guía de contribución](./contribucion.md) para ramas, revisiones y merges.
- Ver [Instalación](./01-INSTALACION.md) para requisitos del entorno.
