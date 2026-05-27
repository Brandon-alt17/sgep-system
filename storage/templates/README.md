# Plantillas GFPI-F-023

Archivos Word **sin datos personales** usados por la exportación (`F023Generator`).

Los nombres deben coincidir con `config/f023_template_map.php`:

| Archivo      | Uso                          |
|-------------|------------------------------|
| `info.docx` | Información general          |
| `m1.docx`   | Momento 1 – Planeación       |
| `m2.docx`   | Momento 2 y examen (EX)      |
| `m3_p1.docx`| Momento 3 – parte 1          |
| `m3_p2.docx`| Momento 3 – parte 2          |
| `Reporte Maestro.xlsx` | Plantilla del reporte de seguimiento maestro (exportación Excel) |

## Instalación

Tras clonar el repositorio, estas plantillas ya vienen en esta carpeta. No hace falta copiarlas a mano.

Si falta algún archivo (por ejemplo tras un merge conflictivo), restáurelo desde Git:

```bash
git checkout -- storage/templates/
```

## Edición

- No subir plantillas con nombres, documentos o datos de aprendices rellenados.
- LibreOffice/Word crea archivos `.~lock.*` al abrir un `.docx`; están en `.gitignore` y no deben commitearse.
- Tras cambiar el diseño de una plantilla, probar exportación Word desde **Aprendices → Generar GFPI-F-023**.

## Requisitos PHP

- Extensión `zip` habilitada (`php -m | grep zip`)
- `composer install` (PhpWord)
