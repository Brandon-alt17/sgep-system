# 🗃️ Base de Datos — SGEP

**Documento:** Referencia completa de tablas, columnas y relaciones  
**Motor:** MySQL 8.0  
**Versión:** 1.0

---

## Tablas

- [aprendices](#aprendices)
- [empresas](#empresas)
- [programas](#programas)
- [momentos](#momentos)
- [factores_valoracion](#factores_valoracion)
- [documentos_generados](#documentos_generados)
- [reporte_campos](#reporte_campos)

---

## aprendices

Tabla principal. Cada fila es un aprendiz único. La clave de unicidad es `numero_documento`.

| Columna | Tipo | Nulo | Default | Descripción |
|---------|------|------|---------|-------------|
| `id` | `bigint unsigned` | No | AUTO | Clave primaria |
| `nombre` | `varchar(200)` | No | — | Nombre completo (formato título) |
| `tipo_documento` | `enum` | No | — | `CC`, `TI`, `CE`, `PEP`, `PPT` |
| `numero_documento` | `varchar(20)` | No | — | **Clave única** (RN-03) |
| `telefono` | `varchar(20)` | Sí | null | Celular o teléfono de contacto |
| `correo_personal` | `varchar(150)` | No | — | Correo personal del aprendiz |
| `correo_institucional` | `varchar(150)` | Sí | null | Correo `@soy.sena.edu.co` o alternativo |
| `alternativa_ep` | `varchar(100)` | No | — | Tipo de vinculación: contrato, pasantía, proyecto, etc. |
| `fecha_sofia` | `date` | Sí | null | Fecha de registro en SofiaPlus |
| `tipo_asistencia` | `varchar(100)` | Sí | null | Lenguaje de señas, apoyo visual, etc. |
| `sugerencias` | `text` | Sí | null | Comentarios del formulario |
| `modalidad` | `enum` | No | `Presencial` | `Presencial`, `Virtual` |
| `estado` | `enum` | No | `Pendiente por iniciar` | Ver estados en RN-23 |
| `ficha` | `varchar(20)` | Sí | null | Número de ficha del instructor (RN-05) |
| `jefe_grupo` | `varchar(100)` | Sí | null | Jefe de grupo SENA (RN-05) |
| `coordinacion` | `varchar(100)` | Sí | null | Coordinación asignada (RN-05) |
| `instructor_seguimiento` | `varchar(150)` | Sí | null | Nombre del instructor (RN-05) |
| `telefono_instructor` | `varchar(20)` | Sí | null | Teléfono del instructor (RN-05) |
| `arl_estado` | `enum` | Sí | null | `Afiliado`, `En espera`, `No aplica` |
| `arl_nombre` | `varchar(100)` | Sí | null | Nombre de la ARL |
| `arl_fecha_afiliacion` | `date` | Sí | null | Fecha de afiliación a ARL |
| `empresa_id` | `bigint unsigned` | Sí | null | FK → empresas.id |
| `programa_id` | `bigint unsigned` | Sí | null | FK → programas.id |
| `created_at` | `timestamp` | No | — | Fecha de creación del registro |
| `updated_at` | `timestamp` | No | — | Fecha de última modificación |

**Índices:**
```sql
UNIQUE INDEX idx_numero_documento (numero_documento)
INDEX idx_estado (estado)
INDEX idx_ficha (ficha)
INDEX idx_empresa_id (empresa_id)
```

---

## empresas

Datos del ente co-formador donde el aprendiz realiza su práctica.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | `bigint unsigned` | No | Clave primaria |
| `nombre` | `varchar(200)` | No | Razón social de la empresa |
| `nit` | `varchar(20)` | Sí | NIT sin puntos ni decimales (normalizado) |
| `direccion` | `varchar(250)` | Sí | Dirección de la empresa |
| `ciudad` | `varchar(100)` | Sí | Ciudad sede de la práctica |
| `correo_org` | `varchar(150)` | Sí | Correo organizacional/institucional |
| `nombre_jefe` | `varchar(200)` | No | Nombre del jefe inmediato/co-formador |
| `cargo_jefe` | `varchar(150)` | Sí | Cargo del jefe inmediato |
| `correo_jefe` | `varchar(150)` | Sí | Correo del jefe inmediato |
| `telefono_jefe` | `varchar(20)` | Sí | Teléfono del jefe inmediato |
| `nombre_contacto2` | `varchar(200)` | Sí | Nombre de contacto secundario (RRHH) |
| `correo_contacto2` | `varchar(150)` | Sí | Correo de contacto secundario |
| `created_at` | `timestamp` | No | — |
| `updated_at` | `timestamp` | No | — |

> ℹ️ Una misma empresa puede tener múltiples aprendices. La empresa se guarda por aprendiz (no se deduplica en el MVP) para preservar los datos exactos del formulario de cada uno.

---

## programas

Catálogo de programas de formación del SENA.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | `bigint unsigned` | No | Clave primaria |
| `codigo` | `varchar(20)` | No | Código del programa (ej: `2692988`) |
| `nombre` | `varchar(250)` | No | Nombre oficial del programa |
| `nivel` | `enum` | No | `Técnico`, `Tecnólogo`, `Operario`, `Especialización` |
| `modalidad` | `enum` | No | `Presencial`, `Virtual` |
| `created_at` | `timestamp` | No | — |
| `updated_at` | `timestamp` | No | — |

> ℹ️ Los programas se precargan con seeders. En el MVP no hay interfaz CRUD para esta tabla.

---

## momentos

Registra cada visita del instructor al aprendiz. Un aprendiz puede tener múltiples momentos de tipo M2.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | `bigint unsigned` | No | Clave primaria |
| `aprendiz_id` | `bigint unsigned` | No | FK → aprendices.id |
| `tipo` | `enum` | No | `M1`, `M2`, `M3`, `EX` |
| `numero_visita` | `tinyint` | Sí | null | Solo aplica para Momento Extraordinario (EX). M1, M2 y M3 son únicos por definición. |
| `fecha_visita` | `date` | No | Fecha de la visita realizada |
| `modalidad_visita` | `enum` | No | `Presencial`, `Virtual` |
| `enlace_grabacion` | `varchar(500)` | Sí | URL de la grabación (si es virtual) |
| `ciudad_diligenciamiento` | `varchar(100)` | Sí | Ciudad donde se diligenció |
| `modalidad_diligenciamiento` | `enum` | Sí | `Presencial`, `Virtual` |
| `proxima_visita` | `date` | Sí | Próxima fecha programada (RN-15, oblig. en M1 y M2) |
| `obs_instructor` | `text` | No | Observación del instructor (oblig., RN-14) |
| `obs_aprendiz` | `text` | Sí | Observación del aprendiz |
| `obs_coformador` | `text` | Sí | Observación del co-formador |
| `juicio_final` | `enum` | Sí | Solo M3: `Aprobado`, `No aprobado` |
| `created_at` | `timestamp` | No | — |
| `updated_at` | `timestamp` | No | Fecha de última edición del momento |

**Índices:**
```sql
INDEX idx_aprendiz_tipo (aprendiz_id, tipo)
INDEX idx_proxima_visita (proxima_visita)   -- usado por el dashboard de alertas
```

**Constraints:**
```sql
-- Solo puede existir un M1 por aprendiz (RN-07)
UNIQUE INDEX idx_unico_m1 (aprendiz_id, tipo) WHERE tipo = 'M1'

-- Solo puede existir un M2 por aprendiz (RN-08)
UNIQUE INDEX idx_unico_m2 (aprendiz_id, tipo) WHERE tipo = 'M2'

-- Solo puede existir un M3 por aprendiz (RN-09)
UNIQUE INDEX idx_unico_m3 (aprendiz_id, tipo) WHERE tipo = 'M3'

-- El Momento Extraordinario (EX) no tiene constraint de unicidad — es el único repetible (RN-10)
```

---

## factores_valoracion

Almacena la valoración de cada factor técnico y actitudinal por momento.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | `bigint unsigned` | No | Clave primaria |
| `momento_id` | `bigint unsigned` | No | FK → momentos.id |
| `tipo_factor` | `enum` | No | `tecnico`, `actitudinal` |
| `indice` | `tinyint` | No | Posición del factor (1–8 técnico, 1–5 actitudinal) |
| `nombre_factor` | `varchar(150)` | No | Nombre del factor (fijo, RN-13) |
| `valoracion` | `enum` | No | `S` (Satisfactorio), `PM` (Por mejorar) |
| `observacion` | `text` | Sí | Observación opcional por factor |

> ℹ️ Cada momento M2 o M3 genera exactamente 13 filas en esta tabla (8 técnicos + 5 actitudinales).

---

## documentos_generados

Historial de todos los documentos F-023 exportados. No se puede eliminar (RN-21).

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | `bigint unsigned` | No | Clave primaria |
| `aprendiz_id` | `bigint unsigned` | No | FK → aprendices.id |
| `partes_incluidas` | `json` | No | Array con las partes seleccionadas |
| `formato` | `enum` | No | `docx`, `pdf` |
| `ruta_archivo` | `varchar(500)` | No | Ruta local del archivo generado |
| `created_at` | `timestamp` | No | Fecha y hora de generación |

---

## reporte_campos

Campos que el instructor edita manualmente en el reporte maestro que no se pueden inferir de otras tablas.

| Columna | Tipo | Nulo | Descripción |
|---------|------|------|-------------|
| `id` | `bigint unsigned` | No | Clave primaria |
| `aprendiz_id` | `bigint unsigned` | No | FK → aprendices.id (unique) |
| `acuerdo_007` | `varchar(100)` | Sí | Número o estado del Acuerdo 007 |
| `acuerdo_009` | `varchar(100)` | Sí | Número o estado del Acuerdo 009 |
| `vencimiento_007` | `date` | Sí | Fecha de vencimiento |
| `f165_recibido` | `boolean` | No | `false` |
| `f165_fecha` | `date` | Sí | Fecha de recepción |
| `paz_y_salvo` | `boolean` | No | `false` |
| `carne_recibido` | `boolean` | No | `false` |
| `saber_tt` | `boolean` | No | `false` |
| `notas_reporte` | `text` | Sí | Notas adicionales del instructor |
| `updated_at` | `timestamp` | No | Fecha de última edición manual |

---

## Relaciones Eloquent

```php
// Aprendiz
class Aprendiz extends Model {
    public function empresa(): BelongsTo
    public function programa(): BelongsTo
    public function momentos(): HasMany          // ordenados por fecha_visita
    public function momentoUno(): HasOne         // tipo = 'M1'
    public function momentoTres(): HasOne        // tipo = 'M3'
    public function documentos(): HasMany
    public function reporteCampos(): HasOne
}

// Momento
class Momento extends Model {
    public function aprendiz(): BelongsTo
    public function factores(): HasMany          // ordenados por tipo + indice
    public function factoresTecnicos(): HasMany  // tipo = 'tecnico'
    public function factoresActitudinales(): HasMany // tipo = 'actitudinal'
}
```

---

## Queries frecuentes

```php
// Dashboard: aprendices con visita próxima en los siguientes 30 días
Momento::whereNotNull('proxima_visita')
    ->where('proxima_visita', '<=', now()->addDays(30))
    ->where('proxima_visita', '>=', now())
    ->with('aprendiz')
    ->orderBy('proxima_visita')
    ->get();

// Perfil completo con todos los datos relacionados
Aprendiz::with(['empresa', 'programa', 'momentos.factores', 'documentos'])
    ->findOrFail($id);

// Listado con filtros
Aprendiz::with('empresa')
    ->when($ficha, fn($q) => $q->where('ficha', $ficha))
    ->when($estado, fn($q) => $q->where('estado', $estado))
    ->when($busqueda, fn($q) => $q->where(function($q) use ($busqueda) {
        $q->where('nombre', 'like', "%$busqueda%")
          ->orWhere('numero_documento', 'like', "%$busqueda%");
    }))
    ->paginate(25);
```

---

## Migraciones

```
database/migrations/
├── 2026_xx_xx_create_programas_table.php
├── 2026_xx_xx_create_empresas_table.php
├── 2026_xx_xx_create_aprendices_table.php
├── 2026_xx_xx_create_momentos_table.php
├── 2026_xx_xx_create_factores_valoracion_table.php
├── 2026_xx_xx_create_documentos_generados_table.php
└── 2026_xx_xx_create_reporte_campos_table.php
```

Correr todas las migraciones:
```bash
php artisan migrate
```

Correr con seeders (412 aprendices del CDITI + programas del catálogo):
```bash
php artisan migrate --seed
```

---

*Ver también: [ARQUITECTURA.md](./ARQUITECTURA.md) · [MODULOS.md](./MODULOS.md)*