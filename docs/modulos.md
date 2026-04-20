# Módulos del Sistema — SGEP

**Documento:** Descripción funcional de cada módulo  
**Versión:** 1.0

---

## Índice

- [Módulo 1 — Importación](#módulo-1--importación-y-normalización)
- [Módulo 2 — Gestión de aprendices](#módulo-2--gestión-de-aprendices)
- [Módulo 3 — Evaluación F-023](#módulo-3--evaluación-f-023)
- [Módulo 4 — Generación de documentos](#módulo-4--generación-de-documentos)
- [Módulo 5 — Reporte maestro](#módulo-5--reporte-maestro)
- [Estado de desarrollo](#estado-de-desarrollo)

---

## Módulo 1 — Importación y normalización

**Fase:** MVP · **Prioridad:** Crítica

### ¿Qué hace?

Permite cargar el archivo Excel exportado desde Google Forms y convierte los datos crudos en registros limpios en la base de datos.

### Pantallas

- `GET /importar` — Formulario de carga de archivo
- `POST /importar` — Procesa el archivo y muestra resultados
- `GET /importar/duplicados` — Resuelve conflictos de cédulas repetidas

### Flujo

```
Instructor selecciona el archivo Excel
        │
        ▼
Sistema lee las 32 columnas del formulario (mapeo fijo)
        │
        ▼
Normalización: nombres, correos, NITs, fechas
        │
        ▼
¿La cédula ya existe en la BD?
    ├── No  →  Crea el registro
    └── Sí  →  Muestra panel de resolución de duplicados
        │
        ▼
Tabla de resultados: exitosos / errores por fila
```

### Requerimientos cubiertos

| RF | Descripción |
|----|-------------|
| RF-01 | Importar archivos Excel y CSV |
| RF-02 | Mapeo fijo de las 32 columnas |
| RF-03 | Normalización automática de datos |
| RF-04 | Detectar y resolver duplicados por cédula |
| RF-05 | Soportar formulario en una o dos etapas |

### Clase principal

```php
// app/imports/AprendicesImport.php
class AprendicesImport
{
    public function importRow(array $row): ?array
    {
        // Normalización + detección de duplicados
    }
}
```

---

## Módulo 2 — Gestión de aprendices

**Fase:** MVP · **Prioridad:** Crítica

### ¿Qué hace?

Centraliza toda la información de un aprendiz en un perfil único. Permite ver, crear, editar y gestionar el estado de cada aprendiz.

### Pantallas

| Ruta | Descripción |
|------|-------------|
| `GET /aprendices` | Listado con filtros y búsqueda |
| `GET /aprendices/create` | Formulario de ingreso manual |
| `POST /aprendices` | Guardar nuevo aprendiz |
| `GET /aprendices/{id}` | Perfil completo del aprendiz |
| `GET /aprendices/{id}/edit` | Editar datos del perfil |
| `PUT /aprendices/{id}` | Actualizar perfil |
| `GET /dashboard` | Dashboard con alertas de próximas visitas |

### Estructura del perfil

```
Perfil del Aprendiz
│
├── Datos personales
│   ├── Nombre completo, tipo y número de documento
│   ├── Teléfono, correos (personal e institucional)
│   └── Alternativa de etapa productiva
│
├── Datos académicos
│   ├── Programa, ficha, modalidad
│   └── Fecha de registro en SofiaPlus
│
├── Empresa co-formadora
│   ├── Nombre, NIT, dirección, ciudad
│   ├── Correo organizacional
│   ├── Jefe inmediato (nombre, cargo, correo, teléfono)
│   └── Contacto secundario (RRHH)
│
├── Campos del instructor [marcados como pendientes]
│   ├── Ficha/coordinación, Jefe de grupo, Coordinación
│   ├── Instructor de seguimiento, Teléfono del instructor
│   └── Estado ARL confirmado
│
├── Estado actual
│   └── [dropdown con los 7 estados + historial de cambios]
│
└── Historial de momentos
    ├── Momento 1 (si existe)
    ├── Momento 2 (si existe)
    ├── Momento 3 (si existe)
    └── Momento Extraordinario (si existe)
```

### Filtros del listado

- **Por ficha/grupo**
- **Por estado** (Pendiente / En ejecución / Aplazada / Finalizada / Por certificar / Certificado)
- **Por programa de formación**
- **Búsqueda** por nombre o número de documento

### Dashboard

El dashboard muestra:
- 4 tarjetas con conteo de aprendices por estado
- Lista de aprendices con **próxima visita en los siguientes 30 días**, ordenados por fecha más próxima (urgente primero)
- Indicador de perfiles incompletos (campos del instructor pendientes)

### Requerimientos cubiertos

| RF | Descripción |
|----|-------------|
| RF-06 | Ingreso manual por el instructor |
| RF-07 | Edición libre de cualquier campo del perfil |
| RF-08 | Completar campos exclusivos del instructor |
| RF-09 | Perfil completo del aprendiz |
| RF-10 | Estados con transiciones controladas |
| RF-11 | Historial de momentos en el perfil |
| RF-12 | Listado con filtros |
| RF-13 | Dashboard con alertas de próximas visitas |

---

## Módulo 3 — Evaluación F-023

**Fase:** MVP · **Prioridad:** Crítica

### ¿Qué hace?

Permite registrar los momentos del formato GFPI-F-023 directamente en el sistema. Los datos del perfil del aprendiz se autocompletan automáticamente.

### Pantallas

| Ruta | Descripción |
|------|-------------|
| `GET /aprendices/{id}/momentos/create?tipo=M1` | Nuevo Momento 1 |
| `GET /aprendices/{id}/momentos/create?tipo=M2` | Nueva visita de seguimiento |
| `GET /aprendices/{id}/momentos/create?tipo=M3` | Evaluación final |
| `POST /aprendices/{id}/momentos` | Guardar momento |
| `GET /aprendices/{id}/momentos/{mid}/edit` | Editar momento existente |
| `PUT /aprendices/{id}/momentos/{mid}` | Actualizar momento |

### Estructura del Momento 2 (más complejo)

```
Formulario Momento 2 — Visita de seguimiento
│
├── Cabecera (autocompletada desde perfil)
│   ├── Nombre del aprendiz, programa, ficha
│   ├── Empresa, jefe inmediato, ARL
│   └── Datos del instructor de seguimiento
│
├── Datos de la visita
│   ├── Número de visita (automático)
│   ├── Fecha de la visita
│   ├── Modalidad (presencial / virtual)
│   ├── Enlace de grabación (si es virtual)
│   ├── Ciudad y modalidad de diligenciamiento
│   └── Próxima fecha de visita [OBLIGATORIA]
│
├── Factores técnicos (× 8) [componente reutilizable]
│   └── Cada factor: nombre + [◉ Satisfactorio / ○ Por mejorar] + textarea (contador)
│
├── Factores actitudinales (× 5) [componente reutilizable]
│   └── Misma estructura que técnicos
│
└── Observaciones
    ├── Del instructor [OBLIGATORIA, con contador de caracteres]
    ├── Del aprendiz [opcional]
    └── Del co-formador [opcional]
```

### Validaciones críticas

```php
// Todas estas condiciones deben cumplirse para guardar un M2
$rules = [
    'fecha_visita'     => 'required|date',
    'proxima_visita'   => 'required|date|after:fecha_visita',  // RN-15
    'obs_instructor'   => 'required|string|max:500',
    'factores'         => 'required|array|size:13',            // RN-14
    'factores.*.valoracion' => 'required|in:S,PM',             // RN-14
];

// Además: bloquear si ya existe un M2 para este aprendiz (RN-08)
// (igual que M1 — solo puede existir uno)
if ($aprendiz->momentos()->where('tipo', 'M2')->exists()) {
    return redirect()->back()->with('error', 'Este aprendiz ya tiene un Momento 2 registrado.');
}
```

### Componente reutilizable

```php
<!-- app/views/components/factor_row.php -->
<!-- Usado 8 veces para técnicos + 5 para actitudinales -->
<div class="factor-row">
    <span><?= e($nombre) ?></span>
    <label><input type="radio" name="factores[<?= (int) $index ?>][valoracion]" value="S"> Satisfactorio</label>
    <label><input type="radio" name="factores[<?= (int) $index ?>][valoracion]" value="PM"> Por mejorar</label>
    <textarea name="factores[<?= (int) $index ?>][observacion]" maxlength="<?= (int) $limite ?>"></textarea>
</div>
```

### Requerimientos cubiertos

| RF | Descripción |
|----|-------------|
| RF-14 | Momento 1 — Planeación (único) |
| RF-15 | Momento 2 — Seguimiento (único por aprendiz) |
| RF-16 | Momento 3 — Evaluación final (único, cierra ciclo) |
| RF-17 | Momento Extraordinario (post-MVP) |
| RF-18 | Edición de cualquier momento sin restricciones |
| RF-19 | Autocompletado desde el perfil |
| RF-20 | Contador de caracteres y límite en campos de texto |

---

## Módulo 4 — Generación de documentos

**Fase:** Post-MVP · **Prioridad:** Crítica

### ¿Qué hace?

Genera el documento GFPI-F-023 oficial con fidelidad exacta al formato del SENA, en Word o PDF, con las partes que el instructor seleccione.

### Pantallas

| Ruta | Descripción |
|------|-------------|
| `GET /aprendices/{id}/generar` | Pantalla de selección de partes |
| `POST /aprendices/{id}/generar` | Genera y descarga el documento |
| `GET /aprendices/{id}/documentos` | Historial de documentos generados |

### Pantalla de selección

```
Generar F-023 — [Nombre del aprendiz]

Partes a incluir:
- [x] Página de información general
- [x] Momento 1 — Planeación
- [x] Momento 2 — Seguimiento
- [x] Momento 3 — Evaluación final
- [ ] Momento Extraordinario 1 (si existe)
- [ ] Momento Extraordinario 2 (si existe)
- [x] Momento 3 — Evaluación final
- [ ] Momento Extraordinario (no existe)

Formato: ◉ Word (.docx)  ○ PDF

[Generar y descargar]
```

### Generación con PHPWord

```php
// app/Services/GeneradorF023Service.php
class GeneradorF023Service
{
    public function generar(Aprendiz $aprendiz, array $partes, string $formato): string
    {
        $template = new TemplateProcessor(storage_path('plantillas/F-023-v06.docx'));

        // Llenar variables del template
        $template->setValue('nombre_aprendiz', $aprendiz->nombre);
        // ...

        // El template garantiza que el formato no se deforma (RN-16)
        $rutaDocx = tempnam(sys_get_temp_dir(), 'F023_');
        $template->saveAs($rutaDocx);

        if ($formato === 'pdf') {
            return $this->convertirAPdf($rutaDocx);
        }

        return $rutaDocx;
    }
}
```

### Requerimientos cubiertos

| RF | Descripción |
|----|-------------|
| RF-21 | Generar F-023 con fidelidad exacta |
| RF-22 | Seleccionar partes a incluir |
| RF-23 | Exportar en Word o PDF |
| RF-24 | Historial de documentos generados |

---

## Módulo 5 — Reporte maestro

**Fase:** Post-MVP · **Prioridad:** Crítica

### ¿Qué hace?

Genera la tabla consolidada de todos los aprendices del instructor, autocompletada con todos los datos disponibles en el sistema, y la exporta al Excel de 58 columnas que recibe la coordinadora.

### Pantallas

| Ruta | Descripción |
|------|-------------|
| `GET /reporte` | Vista del reporte con todos los aprendices |
| `GET /reporte/aprendiz/{id}` | Panel lateral de edición por aprendiz |
| `POST /reporte/aprendiz/{id}` | Guardar ediciones del reporte |
| `GET /reporte/exportar` | Descarga el Excel final |

### Estructura del reporte (7 grupos · 58 columnas)

| Grupo | Columnas aproximadas | Fuente en SGEP |
|-------|---------------------|----------------|
| Aprendices y grupos | Ficha, grupo, nombre, cédula | Perfil |
| Reglamento | Acuerdo 007, 009, vencimientos | Manual (instructor) |
| Información del aprendiz | Datos personales, correos | Perfil / Formulario |
| Información de la etapa | Empresa, modalidad, fechas, ARL | Perfil / Momento 1 |
| Proceso documental seguimiento | F-165, bitácoras, visitas | Momentos registrados |
| Documentos para certificación | F-023, paz y salvo, carné, Saber T&T | Estado del aprendiz |
| Información del instructor | Nombre, teléfono, correo | Perfil instructor |

### Exportación

```php
// app/Exports/ReporteMaestroExport.php
class ReporteMaestroExport implements FromQuery, WithHeadings, WithStyles
{
    public function query(): Builder
    {
        return Aprendiz::with(['empresa', 'programa', 'momentos'])
            ->when($this->filtros['ficha'], fn($q, $f) => $q->where('ficha', $f))
            ->when($this->filtros['estado'], fn($q, $e) => $q->where('estado', $e));
    }
}
```

### Requerimientos cubiertos

| RF | Descripción |
|----|-------------|
| RF-25 | Reporte maestro con autocompletado máximo |
| RF-26 | Edición desde panel lateral sin restricciones |
| RF-27 | Filtros por ficha, estado y programa |
| RF-28 | Exportación Excel con estructura exacta del original |

---

## Estado de desarrollo

| Módulo | Estado | Fase |
|--------|--------|------|
| Módulo 1 — Importación | En desarrollo | MVP |
| Módulo 2 — Aprendices | En desarrollo | MVP |
| Módulo 3 — Evaluación F-023 | En desarrollo | MVP |
| Módulo 4 — Documentos | ⏳ Pendiente | Post-MVP |
| Módulo 5 — Reporte maestro | ⏳ Pendiente | Post-MVP |

**Leyenda:** Completado · En desarrollo · Pendiente

---

*Ver también: [ARQUITECTURA.md](./ARQUITECTURA.md) · [REGLAS_NEGOCIO.md](./REGLAS_NEGOCIO.md) · [BASE_DE_DATOS.md](./BASE_DE_DATOS.md)*