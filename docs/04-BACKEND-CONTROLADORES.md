## Back-end — Controladores y rutas

Guía para controladores HTTP, requests de validación, servicios y rutas web en el SGEP.

### Estructura de controladores

```text
app/Http/Controllers/
├── ApprenticeController.php
├── EvaluationController.php
├── ImportController.php
├── ReportController.php
└── DocumentController.php
```

### Comandos Artisan útiles

```bash
# Controller básico
php artisan make:controller ApprenticeController

# Controller con métodos CRUD (resource)
php artisan make:controller ApprenticeController --resource

# Controller resource con Form Requests
php artisan make:controller ApprenticeController --resource --requests

# Model con migración
php artisan make:model Apprentice -m

# Request de validación
php artisan make:request StoreApprenticeRequest
```

### Ejemplo de controlador

```php
<?php

namespace App\Http\Controllers;

use App\Models\Apprentice;
use App\Http\Requests\StoreApprenticeRequest;
use Illuminate\Http\Request;

class ApprenticeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $aprendices = Apprentice::with('empresa', 'programa')->paginate(10);
        return view('apprentices.index', compact('aprendices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('apprentices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreApprenticeRequest $request)
    {
        $validated = $request->validated();
        Apprentice::create($validated);
        return redirect()->route('apprentices.index')
            ->with('success', 'Aprendiz creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Apprentice $apprentice)
    {
        $apprentice->load('empresa', 'programa', 'evaluaciones');
        return view('apprentices.show', compact('apprentice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Apprentice $apprentice)
    {
        return view('apprentices.edit', compact('apprentice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreApprenticeRequest $request, Apprentice $apprentice)
    {
        $validated = $request->validated();
        $apprentice->update($validated);
        return redirect()->route('apprentices.show', $apprentice)
            ->with('success', 'Aprendiz actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Apprentice $apprentice)
    {
        $apprentice->delete();
        return redirect()->route('apprentices.index')
            ->with('success', 'Aprendiz eliminado exitosamente');
    }
}
```

### Ejemplo de Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprenticeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'documento_unico' => 'required|string|max:20|unique:aprendices',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email',
            'telefono' => 'nullable|string|max:20',
            'programa_id' => 'required|exists:programas,id',
            'ficha_id' => 'required|exists:fichas,id',
            'estado' => 'required|in:pendiente,en_ejecucion,certificado',
        ];
    }

    public function messages()
    {
        return [
            'documento_unico.unique' => 'El documento ya está registrado',
            'email.email' => 'El correo no es válido',
            'programa_id.exists' => 'El programa no existe',
        ];
    }
}
```

### Definir rutas (`routes/web.php`)

```php
<?php

use App\Http\Controllers\ApprenticeController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;

// Dashboard
Route::get('/', function () {
    return view('dashboard');
});

// Aprendices
Route::resource('aprendices', ApprenticeController::class);

// Evaluaciones
Route::resource('evaluaciones', EvaluationController::class);

// Importación
Route::get('/import', [ImportController::class, 'showForm'])->name('import.form');
Route::post('/import', [ImportController::class, 'import'])->name('import.process');

// Reportes
Route::get('/reportes/maestro', [ReportController::class, 'index'])->name('reportes.maestro');
Route::post('/reportes/maestro/export', [ReportController::class, 'export'])->name('reportes.export');
```

### Services y Actions

La lógica de negocio voluminosa conviene moverla a clases en `app/Services/` o `app/Actions/` (según el patrón del proyecto).

> **Nota:** Laravel no incluye `php artisan make:service` por defecto. Crea la clase manualmente o usa un paquete/generador si el equipo lo adopta.

**Ejemplo de servicio:**

```php
<?php

namespace App\Services;

use App\Models\Apprentice;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function processCsv($filePath, $mapping)
    {
        $data = $this->readCsv($filePath, $mapping);
        $errors = $this->validate($data);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        DB::transaction(function () use ($data) {
            foreach ($data as $row) {
                Apprentice::updateOrCreate(
                    ['documento_unico' => $row['documento']],
                    $row
                );
            }
        });

        return ['success' => true, 'count' => count($data)];
    }

    private function readCsv($filePath, $mapping)
    {
        // Implementar lectura de CSV
    }

    private function validate($data)
    {
        // Implementar validaciones
        return [];
    }
}
```

### Comandos de referencia

```bash
php artisan make:controller MiController
php artisan make:model MiModelo -m
php artisan make:request MiRequest
php artisan route:list
php artisan config:clear
php artisan cache:clear
```

### Documentación relacionada

- Ver [Base de datos](./05-BASE-DATOS-GUIA.md) para migraciones y modelos.
- Ver [Arquitectura](./architecture.md) para capas del proyecto.
