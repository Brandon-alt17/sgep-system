# Plan de Pruebas de Software — SGEP v3.0

**Documento:** Plan de pruebas integral  
**Versión:** 1.0  
**Fuente normativa:** `docs/Requisitos-funcionales-V3.txt`, `docs/reglas_del_negocio.txt`, SRS v3.0  
**Alcance prioritario:** Importación y normalización (RF-01–RF-04) y generación documental GFPI-F-023 (RF-21–RF-24)

**Objetivo de calidad:** Cero pérdida de datos, cero duplicados no resueltos, documentos oficiales sin deformación estructural y operación **100 % local** sin exposición de datos personales (PII).

---

## 1. Estrategia de pruebas

### 1.1 Qué vamos a probar

| Dimensión | Alcance | Criterio de éxito |
|-----------|---------|-------------------|
| **Funcionalidad** | RF-01–04: carga Excel/CSV, mapeo fijo (`config/import_mapping.php`), normalización, duplicados por cédula; RF-21–24: selección de partes, Word/PDF, historial; resolución en `/importar/duplicados` | Flujos completos con mensajes claros al instructor |
| **Integridad de datos** | RN-03 (cédula única), upsert por documento, empresas/jefes, F-023 y reporte maestro alineados al perfil | BD y exportaciones coinciden con la fuente; sin sobrescritura silenciosa |
| **Rendimiento local** | Archivos hasta 5 MB (`config/import_schema.php`), importaciones 500–2000 filas, DOCX multi-segmento | En hardware del centro: import ~1000 filas &lt; 60 s; DOCX estándar &lt; 15 s |
| **Seguridad / privacidad** | Despliegue local, `storage/`, historial de importación, descargas temporales | Sin PII en logs públicos; sin archivos residuales fuera de rutas controladas |

### 1.2 Niveles de prueba

1. **Unitarias** — `Normalizer`, `AprendicesImportValidator`, `ProgramaCatalogMatcher`, validación de correo institucional.
2. **Integración** — `AprendicesImport` + MySQL de prueba; `F023Generator` + plantillas en `storage/templates/`.
3. **Sistema / E2E manual** — UI `/importar`, panel duplicados, generación desde perfil del aprendiz.
4. **Regresión** — Plantilla `public/templates/plantilla_seguimiento.xlsx` y snapshots de DOCX (hash o diff XML).

### 1.3 Entorno y datos

- Base de datos de prueba aislada (seed `database/seeds/aprendices_sample.sql` o copia anonimizada).
- Plantillas versionadas: Excel de seguimiento, `info.docx`, `m1.docx`, `m3.docx` en `storage/templates/`.
- Usuario instructor con permisos del módulo.

### 1.4 Criterios de salida (Go / No-Go)

- 100 % de casos con prioridad **Alta** aprobados.
- Cero defectos críticos abiertos en importación o F-023.
- Suite PHPUnit de normalización en verde antes de release.
- Checklist de seguridad local firmado (sin telemetría ni copia de importaciones fuera del servidor).

---

## 2. Casos de prueba

| ID | Funcionalidad | Acción | Resultado esperado | Prioridad |
|----|---------------|--------|-------------------|-----------|
| CP-01 | Importación Excel | Cargar `plantilla_seguimiento.xlsx` válida con 10 filas nuevas | Estado **Exitoso**; `inserted=10`; historial registra archivo y conteo | Alta |
| CP-02 | Importación CSV | Mismo contenido exportado a `.csv` | Mismo resultado que CP-01 | Alta |
| CP-03 | Mapeo fijo (RF-02) | Verificar columna de documento y nombre completo | Campos en BD coinciden con celdas fuente | Alta |
| CP-04 | Normalización nombres (RF-03) | Fila con `  juan   PÉREZ  ` | Guardado como `Juan Pérez` | Alta |
| CP-05 | Normalización NIT | NIT `900.123.456-1.0` | Almacenado sin sufijo decimal | Media |
| CP-06 | Correo institucional | `correo_electronico_institucional = user@gmail.com` | No válido como institucional SENA; advertencia o política definida | Alta |
| CP-07 | Correo institucional válido | `aprendiz@soy.sena.edu.co` | Aceptado; minúsculas en BD | Alta |
| CP-08 | Duplicados (RF-04 / RN-03) | Reimportar misma cédula sin cambios | `duplicates++`; un solo registro; panel duplicados | Alta |
| CP-09 | Conflicto de datos | Misma cédula, teléfono distinto | `conflict_rows`; resolución manual | Alta |
| CP-10 | Campos obligatorios | Fila sin documento o sin nombre | `skipped++`; advertencia por campo | Alta |
| CP-11 | Archivo vacío | Solo encabezados | Error: sin filas de datos | Media |
| CP-12 | Tamaño máximo | Archivo &gt; 5 MB | Rechazo antes de procesar | Media |
| CP-13 | Formato inválido | Subir `.pdf` | «Formato no válido» | Alta |
| CP-14 | Archivo corrupto | `.xlsx` truncado | «Archivo dañado…»; BD sin cambios | Alta |
| CP-15 | Columnas insuficientes | Excel con 15 columnas | «Faltan columnas (15 de 30)» | Alta |
| CP-16 | Encabezados alterados | Cambiar «Nombre completo» | «Encabezados distintos a la plantilla oficial» | Alta |
| CP-17 | Programa no catalogado | Programa desconocido | Enlace pendiente; import no aborta por fila | Media |
| CP-18 | Empresa co-formadora | Empresa nueva por NIT/nombre | `findOrCreateEmpresa`; `empresa_id` asignado | Alta |
| CP-19 | Importación parcial | 5 válidas + 5 con error | Estado **Parcial** en historial | Alta |
| CP-20 | Resolución duplicados UI | Conservar BD vs aceptar archivo | Campo actualizado según decisión | Alta |
| CP-21 | F-023 información (RF-21) | Generar solo `info` en DOCX | Campos rellenados; estructura intacta (RN-16) | Alta |
| CP-22 | F-023 momentos (RF-22) | info + M1 + M2 + M3 | DOCX fusionado en orden correcto | Alta |
| CP-23 | F-023 sin momento | Marcar M2 sin registro en BD | Sin error 500; comportamiento controlado | Media |
| CP-24 | Export PDF (RF-23) | PDF con LibreOffice configurado | Contenido equivalente al DOCX | Alta |
| CP-25 | PDF sin LibreOffice | Sin `F023_LIBREOFFICE_PATH` | Mensaje claro; alternativa DOCX | Media |
| CP-26 | Historial documentos (RF-24) | Tras generar, ver historial | Registro con fecha, partes y formato | Media |
| CP-27 | Autocompletado (RF-19) | Editar perfil y regenerar info | Valores nuevos en documento | Alta |
| CP-28 | Reporte maestro (RN-28) | Exportar con filtros | Excel con **58 columnas** y orden oficial | Alta |
| CP-29 | Transacción M3 (RN-24) | Guardar M3 en «En ejecución» | Estado actualizado atómicamente | Alta |
| CP-30 | Seguridad local | Revisar logs y `/tmp` tras importar | Sin copias del Excel en logs; tmp limpiado | Alta |
| CP-31 | IDOR documentos | `aprendiz_id` ajeno en generación | Rechazo; sin filtración de datos | Alta |
| CP-32 | Concurrencia local | Dos importaciones simultáneas misma cédula | Cédula única preservada (RN-03) | Media |

---

## 3. Pruebas de borde (edge cases)

### 3.1 Importación

| Escenario | Comportamiento esperado |
|-----------|-------------------------|
| Columnas de más (35 vs 30) | Importación permitida; extras ignoradas por mapeo fijo |
| Columnas reordenadas | Falla validación de encabezados (CP-16) |
| Columnas renombradas (mismo orden) | Falla si `critical_headers` no coincide |
| Duplicado en el mismo archivo | Segunda fila: duplicado o conflicto en la misma corrida |
| Cédula con espacios/puntos | Normalización consistente antes de comparar |
| Fechas serial de Excel | Conversión vía PhpSpreadsheet |
| Filas totalmente vacías | Ignoradas sin advertencia |
| CSV con `;` y Latin-1 | Lectura correcta o error explícito |

### 3.2 GFPI-F-023

| Escenario | Comportamiento esperado |
|-----------|-------------------------|
| Plantilla SENA nueva (macros distintas) | Regresión falla; actualizar `f023_template_map.php` / inyectores |
| DOCX corrupto | Excepción capturada; sin descarga parcial |
| Campos multilínea largos | Truncado según límites BD / RN-20 |
| Solo M3 | Segmento M3 y merge según `F023DocxMerge` |

### 3.3 Seguridad de datos (local)

- Historial de importación no accesible sin autenticación.
- `storage/documents/` sin listado de directorio.
- Respuestas AJAX sin datos de otras fichas.
- Restore de BD tras importación masiva: cédulas únicas consistentes.

---

## 4. Automatización con PHPUnit

### 4.1 Qué hay hoy

| Artefacto | Descripción |
|-----------|-------------|
| `phpunit.xml` | Configuración de suites `tests/Unit` y `tests/Feature` |
| `tests/Unit/NormalizerCorreoInstitucionalTest.php` | Valida dominio `@*.sena.edu.co` y `normalizeRow()` en correos |
| `app/helpers/Normalizer.php` | `isCorreoInstitucionalSenaValid()` |

**Ejecutar:**

```bash
composer install
./vendor/bin/phpunit
# Solo normalización de correo:
./vendor/bin/phpunit tests/Unit/NormalizerCorreoInstitucionalTest.php
```

### 4.2 Qué significa «extender PHPUnit»

No es cambiar PHPUnit como herramienta, sino **añadir más clases de prueba** que cubran otros módulos críticos, igual que `NormalizerCorreoInstitucionalTest`, para que parte del plan deje de ser solo manual.

Ejemplo concreto — **extender a `AprendicesImportValidator`**:

| Qué haríamos | Para qué sirve |
|--------------|----------------|
| Crear `tests/fixtures/import/plantilla_ok.xlsx` (mínima, 2 filas) | Datos reproducibles sin tocar producción |
| Crear `tests/fixtures/import/columnas_insuficientes.xlsx`, `encabezados_mal.xlsx`, `corrupto.bin` | Cubrir CP-14, CP-15, CP-16 sin UI |
| Añadir `tests/Unit/AprendicesImportValidatorTest.php` | Instanciar `AprendicesImportValidator::validate()` y afirmar `valid === false` y el mensaje de error esperado |

Eso automatiza la **puerta de entrada** de la importación (RF-01 / RF-02) antes de ejecutar `AprendicesImport` contra MySQL.

Otras extensiones recomendadas (futuras):

- `NormalizerTest` — NIT, nombres, programas.
- `AprendicesImportTest` — integración con BD SQLite/MySQL de prueba (CP-08, CP-09).
- `F023GeneratorTest` — genera DOCX golden y compara hash o fragmentos XML (CP-21, CP-22).

La validación de correo en `Normalizer` **ya está implementada y probada**; falta **enlazarla** en importación (advertencia por fila) y en formularios del servidor si el negocio exige rechazo explícito (CP-06, CP-07).

---

## 5. Trazabilidad SRS → pruebas

| Requisito / regla | Casos principales |
|-----------------|-------------------|
| RF-01, RF-02 | CP-01, CP-02, CP-03, CP-15, CP-16 |
| RF-03 | CP-04, CP-05, CP-06, CP-07, PHPUnit `NormalizerCorreoInstitucionalTest` |
| RF-04, RN-03 | CP-08, CP-09, CP-20, CP-32 |
| RF-21, RN-16, RN-17 | CP-21, CP-22, edge F-023 |
| RF-22, RF-23 | CP-22, CP-23, CP-24, CP-25 |
| RF-24 | CP-26 |
| RN-24 | CP-29 |
| RN-28 | CP-28 |
| Privacidad local | CP-30, CP-31, §3.3 |

---

## 6. Cronograma sugerido (QA)

| Fase | Duración | Entregable |
|------|----------|------------|
| Preparación fixtures | 1 día | Excel válido, corrupto, duplicados, perfil F-023 completo |
| Casos Alta | 2 días | Matriz CP-01–CP-31 ejecutada |
| Automatización + regresión plantillas | 1 día | PHPUnit verde + snapshot DOCX |
| Cierre | 0,5 día | Informe de defectos + Go/No-Go |

---

## 7. Referencias en el repositorio

- Importación: `app/imports/AprendicesImport.php`, `app/imports/AprendicesImportValidator.php`
- Esquema: `config/import_schema.php`, `config/import_mapping.php`
- Documentos: `app/exports/F023Generator.php`, `config/f023_template_map.php`
- Módulos funcionales: `docs/modulos.md`
- Manual de usuario: `docs/manual-usuario.md`
