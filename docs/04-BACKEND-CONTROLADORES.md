## Back-end — Controladores y rutas

Guía para desarrollo backend en SGEP (PHP puro MVC).

### Estructura real

```text
app/
├── controllers/
│   ├── AprendicesController.php
│   ├── DashboardController.php
│   ├── ImportController.php
│   ├── MomentosController.php
│   └── ReportsController.php
├── models/
├── services/
└── helpers/
```

### Flujo recomendado para crear una funcionalidad

1. Crear o actualizar el controlador en `app/controllers`.
2. Implementar lógica de negocio en `app/services` cuando crezca.
3. Consumir datos con modelos en `app/models`.
4. Renderizar la vista PHP en `app/views`.
5. Registrar la ruta en `router.php`.

### Ejemplo de controlador (PHP puro)

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Aprendiz;

final class AprendicesController
{
    public function index(): void
    {
        $items = Aprendiz::all();
        view('aprendices/index', ['aprendices' => $items]);
    }
}
```

### Ejemplo de ruta (`router.php`)

```php
<?php

$router->get('/aprendices', [\App\Controllers\AprendicesController::class, 'index']);
$router->get('/importar', [\App\Controllers\ImportController::class, 'upload']);
$router->post('/importar', [\App\Controllers\ImportController::class, 'store']);
```

### Validación en PHP puro

- Centralizar reglas en métodos privados o clases de validación.
- Validar antes de persistir.
- Retornar errores en español para mostrarlos en la vista.

### Comandos de referencia

```bash
composer install
php database/run_migrations.php
```

### Documentación relacionada

- Ver [Base de datos](./05-BASE-DATOS-GUIA.md) para migraciones SQL.
- Ver [Arquitectura](./architecture.md) para estructura general.
