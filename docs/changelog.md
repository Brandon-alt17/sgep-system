# Changelog — SGEP

Todos los cambios notables del proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es/1.0.0/).

---

## [Sin publicar] — En desarrollo

### Añadido
- Módulo 1: Importación de archivos Excel/CSV del formulario de Google Forms
- Módulo 1: Normalización automática de datos (nombres, NITs, correos, fechas)
- Módulo 1: Detección y resolución de duplicados por número de documento
- Módulo 1: Ingreso manual de aprendices por el instructor (RF-06)
- Módulo 2: Perfil completo del aprendiz con todos los datos del formulario
- Módulo 2: Listado de aprendices con filtros por ficha, estado y programa
- Módulo 2: Dashboard con alertas de próximas visitas (30 días)
- Módulo 2: Gestión de estados del aprendiz con transiciones controladas
- Módulo 3: Formulario del Momento 2 — visita de seguimiento única por aprendiz (~3 meses)
- Módulo 3: Componente Blade reutilizable `factor-row` para los 13 factores
- Módulo 3: Formulario del Momento 1 — planeación con plan de trabajo
- Módulo 3: Autocompletado de datos del perfil en todos los momentos

---

## [1.0.0] — Por publicar el 15 de abril de 2026

### MVP — Primer lanzamiento

**Módulo 1 — Importación:**
- Importar archivos `.xlsx`, `.xls` y `.csv` del formulario de Google Forms
- Mapeo fijo de las 32 columnas del formulario del CDITI
- Normalización: nombres en Title Case, correos en minúsculas, NITs sin decimales
- Detección de duplicados por cédula con panel de resolución
- Soporte para formulario en una o dos etapas (datos personales + datos empresa)
- Ingreso manual completo por el instructor (sin depender del formulario)
- Edición de cualquier campo del perfil en cualquier momento

**Módulo 2 — Gestión de aprendices:**
- Perfil completo: datos personales, empresa, programa, campos del instructor
- Indicador visual de perfil incompleto (campos pendientes del instructor)
- Listado con filtros: ficha/grupo, estado, programa, búsqueda por nombre/cédula
- 7 estados con transiciones controladas
- Dashboard con alertas de próximas visitas ordenadas por urgencia

**Módulo 3 — Evaluación F-023:**
- Momento 1: único por aprendiz, autocompletado desde el perfil
- Módulo 3: Momento 2: único por aprendiz (~3 meses de la etapa), con próxima visita obligatoria
- 8 factores técnicos + 5 actitudinales con valoración Satisfactorio/Por mejorar
- Contadores de caracteres en tiempo real en campos de texto
- Historial de momentos en el perfil del aprendiz
- Edición libre de cualquier momento sin restricciones de fecha o estado

---

## Próximas versiones

### [1.1.0] — Fase 2

**Módulo 3 — Evaluación F-023:**
- [ ] Momento 3: evaluación final con juicio Aprobado/No aprobado
- [ ] Momento Extraordinario para períodos ampliados

**Módulo 4 — Generación de documentos:**
- [ ] Generación del GFPI-F-023 en Word (.docx) con fidelidad exacta al formato oficial
- [ ] Conversión a PDF
- [ ] Selección libre de partes a incluir en la exportación
- [ ] Historial permanente de documentos generados

**Módulo 5 — Reporte maestro:**
- [ ] Tabla consolidada de aprendices con 58 columnas y 7 grupos
- [ ] Autocompletado máximo desde los datos del sistema
- [ ] Panel lateral de edición por aprendiz
- [ ] Exportación a Excel replicando la estructura exacta del original
- [ ] Filtros al exportar (por ficha, estado, programa)

### [1.2.0] — Mejoras futuras

- [ ] Módulo de configuración del instructor y centro desde la interfaz
- [ ] CRUD de catálogos de programas y fichas
- [ ] Checklist de documentos de certificación (paz y salvo, carné, Saber T&T)
- [ ] Adaptación automática a cambios del formato F-023 (plantilla reemplazable)

---

*Este archivo se actualiza con cada versión publicada.*