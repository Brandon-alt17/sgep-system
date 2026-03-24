# Arquitectura del Sistema — SGEP

**Documento:** Diseño técnico y decisiones de arquitectura  
**Versión:** 1.0  
**Última actualización:** 2026

---

## Visión General

El SGEP es una aplicación web **monolítica de ejecución local** construida con el patrón MVC de Laravel. No requiere internet para funcionar. Todos los datos residen en MySQL local.

```
┌─────────────────────────────────────────────────────────┐
│                    PC del Instructor                     │
│                                                         │
│  ┌──────────────┐    ┌──────────────┐  ┌────────────┐  │
│  │  Navegador   │───▶│   Laravel    │─▶│  MySQL     │  │
│  │  (Chrome/FF) │    │  (WAMP/MAMP) │  │  (Local)   │  │
│  └──────────────┘    └──────────────┘  └────────────┘  │
│                             │                           │
│                    ┌────────┴────────┐                  │
│                    │   Archivos      │                  │
│                    │  generados      │                  │
│                    │ (.docx / .xlsx) │                  │
│                    └─────────────────┘                  │
└─────────────────────────────────────────────────────────┘
```

---

## Stack Tecnológico

| Capa | Tecnología | Rol |
|------|-----------|-----|
| **Backend** | Laravel 10 (PHP 8.3+) | Framework MVC principal |
| **Frontend** | Blade + Tailwind CSS 3 | Vistas y estilos |
| **Base de datos** | MySQL 8.0 | Almacenamiento local |
| **Importación** | Maatwebsite/Laravel-Excel | Lectura de CSV y .xlsx |
| **Documentos Word** | PHPOffice/PHPWord | Generación del F-023 |
| **Exportación Excel** | Maatwebsite/Laravel-Excel | Reporte maestro .xlsx |
| **Entorno Windows** | WAMP 3.x | Servidor local PHP + MySQL + Apache |
| **Entorno macOS** | MAMP | Equivalente a WAMP para Mac |

---

## Patrón de Arquitectura

### MVC con Laravel

```
sgep/
├── composer.json                    ✅ TODAS las deps PHP
├── composer.lock                    ✅ Versiones exactas
├── package.json                     ✅ Deps JS (Tailwind, Alpine)
├── package-lock.json                ✅ Versiones exactas JS
├── vite.config.js                   ✅ Bundler config
├── .env.example                     ✅ Plantilla (NO subir .env real)
├── .gitignore                       ✅ Excluir vendor/, node_modules/, .env
│
├── app/
│   ├── Actions/                     ✅ NUEVO - Casos de uso
│   │   ├── ImportApprenticesAction.php
│   │   ├── NormalizeDataAction.php
│   │   ├── GenerateF023Action.php
│   │   └── ExportReporteMaestroAction.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ApprenticeController.php
│   │   │   ├── EvaluationController.php
│   │   │   ├── ImportController.php
│   │   │   ├── ReportController.php
│   │   │   └── DocumentController.php
│   │   │
│   │   └── Requests/
│   │       ├── ImportApprenticesRequest.php
│   │       ├── StoreEvaluationRequest.php
│   │       └── UpdateApprenticeRequest.php
│   │
│   ├── Models/
│   │   ├── Apprentice.php
│   │   ├── Company.php
│   │   ├── Program.php
│   │   ├── Ficha.php
│   │   ├── Evaluation.php
│   │   ├── Visit.php
│   │   └── GeneratedDocument.php
│   │
│   ├── Services/                    ✅ CRÍTICO - No fase 2
│   │   ├── ImportService.php
│   │   ├── NormalizationService.php
│   │   ├── DocumentGenerationService.php
│   │   └── ReportService.php
│   │
│   ├── Rules/                       ✅ NUEVO - Validaciones custom
│   │   ├── UniqueDocumentRule.php
│   │   ├── ValidEmailInstitutionalRule.php
│   │   └── DocumentoFormatoRule.php
│   │
│   └── Providers/
│       └── AppServiceProvider.php
│
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_programas_table.php
│   │   ├── 2024_01_01_000002_create_fichas_table.php
│   │   ├── 2024_01_01_000003_create_empresas_table.php
│   │   ├── 2024_01_01_000004_create_aprendices_table.php
│   │   ├── 2024_01_01_000005_create_evaluaciones_table.php
│   │   ├── 2024_01_01_000006_create_visitas_table.php
│   │   └── 2024_01_01_000007_create_generated_documents_table.php
│   │
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── ProgramSeeder.php
│   │   ├── FichaSeeder.php
│   │   └── ApprenticeSeeder.php
│   │
│   └── factories/                   ✅ NUEVO
│       ├── ProgramFactory.php
│       ├── ApprenticeFactory.php
│       └── EvaluationFactory.php
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php        # Layout base
│   │   │
│   │   ├── components/
│   │   │   ├── factor-row.blade.php # Fila de factor técnico/actitudinal
│   │   │   ├── alert.blade.php
│   │   │   └── modal.blade.php
│   │   │
│   │   ├── dashboard.blade.php
│   │   ├── apprentices/
│   │   │   ├── index.blade.php      # Listado
│   │   │   ├── show.blade.php       # Perfil
│   │   │   └── edit.blade.php
│   │   │
│   │   ├── evaluations/
│   │   │   ├── create.blade.php
│   │   │   ├── edit.blade.php
│   │   │   └── moment1.blade.php
│   │   │
│   │   ├── reports/
│   │   │   └── maestro.blade.php    # Reporte maestro
│   │   │
│   │   └── documents/
│   │       └── generate.blade.php   # Generar F-023
│   │
│   └── css/
│       └── app.css                  # Importa Tailwind
│
├── routes/
│   └── web.php                      ✅ TODAS las rutas aquí
│
├── storage/
│   ├── app/
│   │   ├── documents/               # Documentos generados
│   │   ├── templates/               # Plantillas F-023
│   │   │   └── f023/
│   │   │       └── GFPI-F-023_V06.docx
│   │   └── imports/                 # CSVs importados
│   │
│   ├── logs/
│   ├── framework/
│   └── bootstrap/
│
├── public/                          ✅ CRÍTICO - No mencionado
│   ├── index.php                    # Entry point
│   ├── robots.txt
│   └── uploads/                     # Si suben archivos
│
├── config/
│   ├── app.php
│   ├── database.php
│   └── sgep.php                     # Configuración específica SGEP
│
├── tests/                           ✅ NUEVO - Recomendado
│   ├── Feature/
│   │   ├── ImportTest.php
│   │   ├── EvaluationTest.php
│   │   └── DocumentGenerationTest.php
│   │
│   └── Unit/
│       └── NormalizationTest.php
│
├── vendor/                          ❌ NO subir al repo
└── node_modules/                    ❌ NO subir al repo
```

---

## Modelo de Datos

### Diagrama de entidades

```
┌─────────────────┐       ┌──────────────────┐
│    programas    │       │     empresas     │
├─────────────────┤       ├──────────────────┤
│ id              │       │ id               │
│ codigo          │       │ nombre           │
│ nombre          │       │ nit              │
│ nivel           │       │ direccion        │
│ modalidad       │       │ ciudad           │
└────────┬────────┘       │ correo_org       │
         │                │ nombre_jefe      │
         │ pertenece a    │ cargo_jefe       │
         ▼                │ correo_jefe      │
┌─────────────────┐       │ telefono_jefe    │
│   aprendices    │       │ nombre_contacto2 │
├─────────────────┤       │ correo_contacto2 │
│ id              │◀──────┤ id               │
│ nombre          │       └──────────────────┘
│ tipo_documento  │
│ numero_documento│◀── clave única (sin duplicados)
│ telefono        │
│ correo_personal │
│ correo_inst     │
│ alternativa_ep  │       ┌──────────────────┐
│ fecha_sofia     │       │    momentos      │
│ modalidad_ep    │       ├──────────────────┤
│ estado          │──────▶│ id               │
│ ficha           │       │ aprendiz_id (FK) │
│ jefe_grupo      │       │ tipo (M1/M2/M3/EX│
│ coordinacion    │       │ numero_visita    │
│ instructor_seg  │       │ fecha_visita     │
│ telefono_inst   │       │ modalidad        │
│ tipo_asistencia │       │ enlace_grabacion │
│ empresa_id (FK) │       │ proxima_visita   │
│ programa_id (FK)│       │ obs_instructor   │
└─────────────────┘       │ obs_aprendiz     │
                          │ obs_coformador   │
                          │ juicio_final     │
                          └────────┬─────────┘
                                   │ tiene muchos
                                   ▼
                          ┌──────────────────┐
                          │factores_valoracion│
                          ├──────────────────┤
                          │ id               │
                          │ momento_id (FK)  │
                          │ tipo_factor      │
                          │ nombre_factor    │
                          │ valoracion (S/PM)│
                          │ observacion      │
                          └──────────────────┘

┌──────────────────────┐
│  documentos_generados│
├──────────────────────┤
│ id                   │
│ aprendiz_id (FK)     │
│ partes_incluidas     │
│ formato (docx/pdf)   │
│ ruta_archivo         │
│ created_at           │
└──────────────────────┘
```

### Estados del aprendiz y transiciones

```
[Pendiente por iniciar]
        │
        ▼ (instructor confirma inicio)
  [En ejecución]
        │
        ├──▶ [Aplazada] ──▶ [En ejecución]  (reactivación)
        │
        ▼ (al guardar Momento 3)
  [Finalizada]
        │
        ├── juicio "Aprobado"    ──▶ [Por certificar] ──▶ [Certificado]
        └── juicio "No aprobado" ──▶ [Pendiente por comité de evaluación]
```

---

## Flujo de Datos Principal

```
Google Forms
    │
    │ Exporta .xlsx con 32 columnas fijas
    ▼
[Pantalla de importación]
    │
    ├── Normalización (nombres, NITs, correos, fechas)
    ├── Detección de duplicados por cédula
    └── Resolución manual si hay conflicto
    │
    ▼
[BD: tabla aprendices + empresas]
    │
    ├──▶ [Perfil del aprendiz] ──▶ edición libre
    │
    ├──▶ [Formulario Momento 1] ──▶ único por aprendiz
    │
    ├──▶ [Formulario Momento 2] ──▶ único por aprendiz
    │
    ├──▶ [Formulario Momento 3] ──▶ único, cambia estado
    │
    ├──▶ [Generar F-023 .docx/.pdf]
    │
    └──▶ [Reporte maestro .xlsx para coordinadora]
```

---

## Dependencias Principales

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^10.0",
    "maatwebsite/excel": "^3.1",
    "phpoffice/phpword": "^1.1"
  },
  "require-dev": {
    "laravel/sail": "^1.0",
    "fakerphp/faker": "^1.9"
  }
}
```

---

## Decisiones de Arquitectura

| Decisión | Alternativa descartada | Razón |
|----------|----------------------|-------|
| **Sin Docker** | Laravel Sail | Hyper-V no disponible en PCs corporativos. WAMP es más simple para usuarios no técnicos. |
| **Monolítico (no API + SPA)** | Laravel API + Vue/React | El instructor es el único usuario. No hay necesidad de API externa ni PWA. Blade es suficiente y mucho más rápido de desarrollar. |
| **Mapeo fijo de columnas** | Mapeo dinámico configurable | El formulario de Google Forms siempre exporta las mismas 32 columnas. No hay variación que justifique UI de mapeo. |
| **Multi-usuario pospuesto** | Auth con roles desde MVP | Un PC, un instructor. Agregar auth en MVP agrega complejidad sin valor inmediato. |
| **vendor/ incluida en ZIP** | Composer en PC del directivo | El directivo no tiene Composer ni acceso a internet confiable. La carpeta va empaquetada. |

---

## URL de Acceso Local

> **Nota:** Entorno recomendado oficial: **WAMP (Windows)** y **MAMP (macOS)**.  
> **XAMPP** queda como alternativa opcional.

| Entorno | URL |
|---------|-----|
| **WAMP (Windows)** | `http://localhost/sgep/public` |
| **MAMP (macOS)** | `http://localhost:8888/sgep/public` |
| **XAMPP** | `http://localhost/sgep/public` |

---

*Ver también: [REGLAS_NEGOCIO.md](./REGLAS_NEGOCIO.md) · [MODULOS.md](./MODULOS.md) · [BASE_DE_DATOS.md](./BASE_DE_DATOS.md)*