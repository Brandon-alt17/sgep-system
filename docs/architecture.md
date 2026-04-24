# Arquitectura del Sistema — SGEP

**Documento:** Diseño técnico y decisiones de arquitectura  
**Versión:** 1.0  
**Última actualización:** 2026

---

## Visión General

El SGEP es una aplicación web **monolítica de ejecución local** construida con **PHP puro (MVC propio)**. No requiere internet para funcionar. Todos los datos residen en MySQL local.

```
┌─────────────────────────────────────────────────────────┐
│                    PC del Instructor                     │
│                                                         │
│  ┌──────────────┐    ┌──────────────┐  ┌────────────┐  │
│  │  Navegador   │───▶│   SGEP PHP   │─▶│  MySQL     │  │
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
| **Backend** | PHP 8.2+ (MVC propio + PDO) | Lógica de negocio y acceso a datos |
| **Frontend** | Vistas PHP + Tailwind CSS 3 | Vistas y estilos |
| **Base de datos** | MySQL 8.0 | Almacenamiento local |
| **Importación** | PhpSpreadsheet | Lectura de CSV y .xlsx |
| **Parseo programa PDF** | Reglas regex + revisión UI | Extracción semi-automática de competencias y resultados |
| **Documentos Word** | PHPOffice/PHPWord | Generación del F-023 |
| **Exportación Excel** | PhpSpreadsheet | Reporte maestro .xlsx |
| **Entorno Windows** | WAMP 3.x | Servidor local PHP + MySQL + Apache |
| **Entorno macOS** | MAMP | Equivalente a WAMP para Mac |

---

## Patrón de Arquitectura

### MVC en PHP puro

```
sgep/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── services/
│   ├── views/
│   └── helpers/
├── config/
├── database/
│   ├── migrations/*.sql
│   └── run_migrations.php
├── public/
│   ├── index.php
│   └── css/app.css
├── storage/
├── router.php
├── composer.json
├── package.json
└── .env.example
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

## Flujo Programas Normalizados

```
Importación aprendices (.xlsx)
   │
   ├── Match estricto a catálogo programas (sin INSERT automático)
   ├── Si no hay match: aprendiz queda con programa_id NULL
   └── Registro en programa_enlaces_pendientes
       │
       ▼
Catálogo > Pendientes por enlazar
   │
   └── Resolución manual -> actualiza aprendices.programa_id

Catálogo > Importar programa (PDF/TXT)
   │
   ├── Extracción por reglas (competencia/resultado)
   ├── Revisión visual previa
   └── Persistencia en:
       - programa_importaciones_pdf
       - programa_competencias
       - programa_resultados_aprendizaje
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

- `phpoffice/phpspreadsheet` para importación/exportación Excel.
- `phpoffice/phpword` para generación de documentos.
- `vlucas/phpdotenv` para cargar `.env`.

---

## Decisiones de Arquitectura

| Decisión | Alternativa descartada | Razón |
|----------|----------------------|-------|
| **Sin Docker** | Contenedores completos | WAMP/MAMP simplifica instalación en equipos no técnicos. |
| **Monolítico (no API + SPA)** | API + SPA | Un único usuario operativo por sede no requiere separación adicional. |
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

*Ver también: [REGLAS_NEGOCIO.md](./REGLAS_NEGOCIO.md) · [modulos.md](./modulos.md) · [base_de_datos.md](./base_de_datos.md)*