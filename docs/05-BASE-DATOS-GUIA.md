## Base de datos — Guía práctica

Migraciones, modelos, seeders y comandos habituales en el SGEP.

### Estructura de migraciones

```text
database/migrations/
├── 2024_01_01_000001_create_programas_table.php
├── 2024_01_01_000002_create_fichas_table.php
├── 2024_01_01_000003_create_empresas_table.php
├── 2024_01_01_000004_create_aprendices_table.php
├── 2024_01_01_000005_create_evaluaciones_table.php
└── 2024_01_01_000006_create_visitas_table.php
```

### Crear migraciones con Artisan

```bash
# Crear tabla
php artisan make:migration create_aprendices_table

# Agregar columnas a una tabla existente
php artisan make:migration add_arl_fields_to_aprendices_table --table=aprendices
```

### Ejemplo de migración

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aprendices', function (Blueprint $table) {
            $table->id();
            $table->string('documento_unico', 20)->unique()->index();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email_personal')->nullable();
            $table->string('email_institucional')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion')->nullable();
            $table->foreignId('programa_id')->constrained('programas');
            $table->foreignId('ficha_id')->constrained('fichas');
            $table->foreignId('empresa_id')->nullable()->constrained('empresas');
            $table->enum('estado', ['pendiente', 'en_ejecucion', 'certificado'])->default('pendiente');
            $table->string('arl_nombre')->nullable();
            $table->string('arl_poliza')->nullable();
            $table->date('arl_fecha_afiliacion')->nullable();
            $table->enum('modalidad_practica', ['contrato', 'pasantia', 'proyecto', 'vinculacion', 'emprendimiento'])->nullable();
            $table->date('fecha_aval')->nullable();
            $table->date('fecha_registro_sofia')->nullable();
            $table->timestamp('ultima_importacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aprendices');
    }
};
```

### Ejemplo de modelo

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apprentice extends Model
{
    protected $table = 'aprendices';

    protected $fillable = [
        'documento_unico',
        'nombres',
        'apellidos',
        'email_personal',
        'email_institucional',
        'telefono',
        'direccion',
        'programa_id',
        'ficha_id',
        'empresa_id',
        'estado',
        'arl_nombre',
        'arl_poliza',
        'arl_fecha_afiliacion',
        'modalidad_practica',
        'fecha_aval',
        'fecha_registro_sofia',
        'ultima_importacion',
    ];

    protected $casts = [
        'ultima_importacion' => 'datetime',
        'fecha_aval' => 'date',
        'fecha_registro_sofia' => 'date',
        'arl_fecha_afiliacion' => 'date',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(Ficha::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
```

### Ejecutar migraciones

```bash
# Migraciones pendientes
php artisan migrate

# Revertir el último lote
php artisan migrate:rollback

# Revertir todas
php artisan migrate:reset

# Borrar tablas y volver a crear (¡cuidado en producción!)
php artisan migrate:fresh

# Fresh con datos de prueba
php artisan migrate:fresh --seed

# Estado de migraciones
php artisan migrate:status
```

### Seeders y factories

```bash
php artisan make:seeder ApprenticeSeeder
php artisan make:factory ApprenticeFactory --model=Apprentice
```

**Fragmentos de referencia:**

```php
// database/seeders/ApprenticeSeeder.php
public function run(): void
{
    Apprentice::factory()->count(50)->create();
}
```

```php
// database/factories/ApprenticeFactory.php
public function definition(): array
{
    return [
        'documento_unico' => $this->faker->unique()->numerify('##########'),
        'nombres' => $this->faker->firstName(),
        'apellidos' => $this->faker->lastName(),
        'email_personal' => $this->faker->unique()->safeEmail(),
        'estado' => $this->faker->randomElement(['pendiente', 'en_ejecucion', 'certificado']),
    ];
}
```

### Antes de crear una migración

- [ ] Nombre de tabla en plural y `snake_case`.
- [ ] Campos, tipos y nulabilidad definidos.
- [ ] Índices (`unique`, `index`) donde corresponda.
- [ ] Claves foráneas y reglas `onDelete` si aplica.

### Antes de hacer commit

- [ ] `up()` y `down()` probados localmente.
- [ ] Modelo con `$fillable` (o `$guarded`) acordado.
- [ ] Relaciones definidas en el modelo.
- [ ] Seeders/factories funcionan si los tocaste.

### Comandos útiles (resumen)

```bash
php artisan make:migration create_tabla_table
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed
php artisan make:seeder TablaSeeder
php artisan make:factory TablaFactory --model=Tabla
```

### Documentación relacionada

- Ver [Referencia de tablas](./base_de_datos.md) para el modelo de datos detallado.
- Ver [Back-end](./04-BACKEND-CONTROLADORES.md) para uso desde controladores.
