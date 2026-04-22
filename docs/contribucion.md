# Guía de Contribución — SGEP

**Para:** Equipo de desarrollo (7 personas)  
**Versión:** 1.0

---

## Tabla de contenidos

- [Roles del equipo](#roles-del-equipo)
- [Flujo de trabajo con Git](#flujo-de-trabajo-con-git)
- [Convenciones de código](#convenciones-de-código)
- [Estructura de commits](#estructura-de-commits)
- [Cómo agregar una funcionalidad](#cómo-agregar-una-funcionalidad)
- [Checklist antes de hacer merge](#checklist-antes-de-hacer-merge)

---

## Roles del equipo

| Persona | Rol | Área principal |
|---------|-----|----------------|
| **A** | Backend | Controladores, lógica de negocio, validaciones |
| **B** | Frontend | Vistas PHP, layout, formularios, componentes UI |
| **C** | Base de datos | Migraciones SQL, modelos PDO, relaciones, queries |
| **D** | QA / Componentes | Testing con datos reales, componentes reutilizables |
| **E** | Git / Demo | Gestión del repo, merges, instalación en campo, demo |
| **F** | Backend | |
| **G** | Frontend | |

> Los roles definen el área de foco, no de exclusividad. Cualquier miembro puede ayudar fuera de su área si es necesario — pero cada tarea tiene un responsable claro.

---

## Flujo de trabajo con Git

### Ramas

```
dev                    ← Solo código que funciona y ha sido revisado
│
├── feat/importacion    ← En desarrollo por A
├── feat/perfil         ← En desarrollo por B
├── feat/momentos       ← En desarrollo por A+B
└── fix/nit-decimales   ← Corrección puntual
```

### Reglas

- **Nadie hace push directo a `dev`**
- Cada tarea nueva = rama nueva desde `dev`
- Merge a `dev` solo después de revisión de A o E
- Merge al menos una vez cada 24 horas (no acumular días de trabajo sin integrar)
- Antes de crear una rama nueva, hacer `git pull origin dev`

### Flujo diario

```bash
# 1. Actualizar dev local
git checkout dev
git pull origin dev

# 2. Crear o retomar tu rama
git checkout -b feat/nombre-de-tarea
# o si ya existe:
git checkout feat/nombre-de-tarea

# 3. Trabajar... commits frecuentes (mínimo 1 por sesión)
git add .
git commit -m "feat(importacion): normalizar correos a minúsculas"

# 4. Subir tu rama
git push origin feat/nombre-de-tarea

# 5. Cuando la tarea está lista: abrir Pull Request hacia dev
# E o A hacen el merge después de revisar
```

---

## Convenciones de código

> Estas convenciones se definieron el Día 1. Todos los miembros deben seguirlas para que el código sea consistente.

### Controladores

```php
// Correcto: PascalCase, singular, sufijo Controller
class AprendizController extends Controller {}
class MomentoController extends Controller {}
class ImportacionController extends Controller {}

// Incorrecto
class aprendizesController {}
class ControladorAprendices {}
```

### Métodos de controlador (convención recomendada)

```php
public function index()   // GET /aprendices          → lista
public function create()  // GET /aprendices/create   → formulario nuevo
public function store()   // POST /aprendices         → guardar nuevo
public function show()    // GET /aprendices/{id}     → ver perfil
public function edit()    // GET /aprendices/{id}/edit → formulario editar
public function update()  // PUT /aprendices/{id}     → actualizar
public function destroy() // DELETE /aprendices/{id}  → eliminar
```

### Rutas

```php
// Correcto: snake_case plural en la URL, resourceful cuando aplique
Route::resource('aprendices', AprendizController::class);
Route::resource('aprendices.momentos', MomentoController::class);

// Rutas adicionales con nombres descriptivos
Route::post('importar', [ImportacionController::class, 'store'])->name('importar.store');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
```

### Modelos

```php
// Correcto: PascalCase, singular
class Aprendiz extends Model {}
class Momento extends Model {}
class FactorValoracion extends Model {}

// Nombres de tabla: snake_case plural
// aprendices, momentos, factores_valoracion
```

### Vistas PHP

```
app/views/
├── aprendices/
│   ├── index.php               ← Listado
│   ├── create.php              ← Formulario nuevo
│   ├── show.php                ← Perfil
│   └── edit.php                ← Formulario edición
├── momentos/
│   ├── create.php
│   └── edit.php
├── dashboard.php
├── import/
│   └── upload.php
└── components/
    └── page_header.php         ← Componente reutilizable
```

### Variables en vistas PHP

```php
// Correcto: camelCase para variables, snake_case para campos del modelo
view('aprendices/show', [
    'aprendiz' => $aprendiz,
    'momentos' => $momentos,
    'proximas' => $proximasVisitas,
]);

// En la vista:
<?= e((string) $aprendiz['nombre']) ?>
<?= e((string) $aprendiz['numero_documento']) ?>
```

### Mensajes al usuario

```php
// Correcto: siempre en español
flash('success', 'Aprendiz registrado correctamente.');
flash('error', 'El número de documento ya está registrado.');
flash('warning', 'Se encontraron duplicados. Por favor revise.');
```

---

## Estructura de commits

Usar el formato [Conventional Commits](https://www.conventionalcommits.org/):

```
type(scope): description in English

[cuerpo opcional]
```

### Tipos

| Tipo | Cuándo usarlo |
|------|--------------|
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de bug |
| `refactor` | Refactorización sin cambio funcional |
| `docs` | Solo cambios en documentación |
| `style` | Cambios de formato (espacios, comas) sin afectar lógica |
| `test` | Agregar o corregir pruebas |
| `chore` | Tareas de mantenimiento (deps, config) |

### Ejemplos

```bash
# Buenos commits
git commit -m "feat(import): add NIT normalization with decimal cleanup"
git commit -m "fix(moments): enforce required next-visit date validation"
git commit -m "feat(dashboard): show alerts for visits in the next 30 days"
git commit -m "refactor(apprentice): extract duplicate-detection logic into service"
git commit -m "docs(database): add description for reporte_campos table"

# Malos commits
git commit -m "cambios"
git commit -m "arreglos varios"
git commit -m "wip"
git commit -m "actualizacion"
```

---

## Cómo agregar una funcionalidad

Ejemplo: agregar el filtro de búsqueda por nombre en el listado de aprendices.

### 1. Crear la rama

```bash
git checkout dev && git pull
git checkout -b feat/listado-busqueda-nombre
```

### 2. Agregar la lógica en el controlador

```php
// app/Http/Controllers/AprendizController.php
public function index(Request $request)
{
    $aprendices = Aprendiz::with('empresa')
        ->when($request->busqueda, function ($query, $busqueda) {
            $query->where('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('numero_documento', 'like', "%{$busqueda}%");
        })
        ->paginate(25);

    return view('aprendices.index', compact('aprendices'));
}
```

### 3. Actualizar la vista

```php
<!-- app/views/aprendices/index.php -->
<form method="GET" action="/aprendices">
    <input type="text" name="busqueda" value="<?= e((string) ($_GET['busqueda'] ?? '')) ?>"
           placeholder="Buscar por nombre o cédula...">
    <button type="submit">Buscar</button>
</form>
```

### 4. Probar manualmente con datos reales

- Buscar un nombre que exista en los 412 registros
- Buscar un nombre que no exista → lista vacía
- Buscar parte de un nombre → resultados parciales
- Buscar una cédula completa → 1 resultado

### 5. Commit y push

```bash
git add .
git commit -m "feat(listado): agregar búsqueda por nombre y cédula"
git push origin feat/listado-busqueda-nombre
```

### 6. Abrir Pull Request

- E o A revisan el PR
- Si está bien → merge a dev
- Si hay correcciones → se hacen en la misma rama y se vuelve a subir

---

## Checklist antes de hacer merge

Antes de pedir que se haga merge de tu rama a `dev`, verifica:

### Funcionalidad
- [ ] La funcionalidad hace lo que debe hacer
- [ ] Probé con los 412 registros reales (no solo con datos inventados)
- [ ] Los casos edge funcionan: campos vacíos, caracteres especiales, duplicados

### Código
- [ ] Sigo las convenciones de nombres (controladores, rutas, vistas)
- [ ] No hay `var_dump()`, `dd()` ni `console.log()` olvidados
- [ ] Los mensajes al usuario están en español
- [ ] Los campos de formulario tienen validación en el backend

### Git
- [ ] Hice `git pull origin dev` antes de crear la rama
- [ ] Mis commits tienen mensajes descriptivos en inglés
- [ ] No subí archivos de configuración local (`.env`, `node_modules/`, `vendor/`)

### Base de datos
- [ ] Si agregué una tabla o columna, creé la migración SQL correspondiente
- [ ] `php database/run_migrations.php` corre sin errores en un entorno limpio

---

## Archivos que NUNCA se suben al repo

El `.gitignore` ya los excluye, pero por claridad:

```
.env                    ← Configuración local (DB password, APP_KEY)
vendor/                 ← Dependencias PHP (se instalan con composer install)
node_modules/           ← Dependencias JS (se instalan con npm install)
storage/app/            ← Archivos generados (F-023, reportes)
storage/logs/           ← Logs de la aplicación
.DS_Store               ← Metadatos de macOS
Thumbs.db               ← Metadatos de Windows
```

---

*Ver también: [README.md](../README.md) · [architecture.md](./architecture.md)*