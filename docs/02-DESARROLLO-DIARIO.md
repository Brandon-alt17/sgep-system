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

**Servidor local (opciones):**

- Con Apache (recomendado): `http://localhost/sgep/public`
- Con servidor embebido:

```bash
php -S localhost:8000 -t public
```

**Migraciones cuando cambie BD:**

```bash
php database/run_migrations.php
```

### 5. Guardar progreso (WIP)

Si cierras la jornada sin terminar la tarea, deja el avance subido:

```bash
git add .
git commit -m "wip(RF-01): avanzar en validación de CSV"
git push origin feature/RF-01-importar-csv
```

### Consejos rápidos

- **Detener procesos:** `Ctrl + C` en `php -S ...` y en `npm run dev`.
- **Si no ves cambios de frontend:** volver a ejecutar `npm run build` o mantener `npm run dev` activo.

### Documentación relacionada

- Ver [Guía de contribución](./contribucion.md) para ramas, revisiones y merges.
- Ver [Instalación](./01-INSTALACION.md) para requisitos del entorno.
