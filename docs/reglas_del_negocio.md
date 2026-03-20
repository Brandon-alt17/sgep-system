# 📋 Reglas de Negocio — SGEP

**Documento:** Restricciones y comportamientos del dominio  
**Versión:** 1.0  

---

## Índice

- [Dominio 1 — Formulario y datos del aprendiz (RN-01 a RN-06)](#dominio-1)
- [Dominio 2 — Momentos del F-023 (RN-07 a RN-15)](#dominio-2)
- [Dominio 3 — Generación del documento F-023 (RN-16 a RN-22)](#dominio-3)
- [Dominio 4 — Estados del aprendiz (RN-23 a RN-25)](#dominio-4)
- [Dominio 5 — Reporte maestro (RN-26 a RN-28)](#dominio-5)

---

## Ciclo de vida del aprendiz

```
[Pre-etapa]        Aprendiz llena formulario Google Forms → Excel
      │
      ▼
[Momento 1]        ~15 días de iniciada la práctica — Planeación (único)
      │
      ▼
[Momento 2]        ~3 meses — Seguimiento (único por aprendiz)
      │
      ▼
[Momento 3]        ~6 meses — Evaluación final (único, 2 páginas)
      │
      ▼ (opcional)
[M. Extraordinario] Si el período se amplió
      │
      ▼
[Certificación]    Reporte maestro → coordinadora
```

---

<a name="dominio-1"></a>
## Dominio 1 — Formulario y datos del aprendiz

### RN-01 — El formulario del aprendiz es la fuente primaria de datos

El perfil del aprendiz se construye a partir del formulario que diligencia antes de iniciar la etapa productiva (exportado desde Google Forms u otra plataforma como Excel/CSV). Ningún dato del perfil se inventa — si no viene del formulario, lo ingresa el instructor manualmente.

### RN-02 — El formulario puede dividirse en una o dos etapas

Si el aprendiz no conoce los datos de su empresa al momento de iniciar, el instructor puede enviar el formulario en dos partes:
- **Parte 1:** datos personales del aprendiz
- **Parte 2:** datos de la empresa (cuando el aprendiz ya los conozca)

Al importar la segunda parte, el sistema detecta la cédula existente y **completa los campos vacíos sin sobreescribir los ya diligenciados**.

### RN-03 — El número de documento es la clave única de cada aprendiz

> ⚠️ No puede existir dos aprendices con el mismo número de documento.

Si al importar se detecta una cédula ya registrada, el sistema muestra ambos registros y permite al instructor:
- Conservar el existente
- Actualizar con los nuevos datos
- Fusionar campos vacíos

**El sistema nunca crea un duplicado silenciosamente.**

### RN-04 — El instructor puede registrar o editar cualquier aprendiz manualmente

Cuando un aprendiz no puede diligenciar el formulario (sin acceso a internet, situación de discapacidad, inicio inmediato de práctica), el instructor puede crear el registro directamente en el sistema.

**Campos mínimos obligatorios para crear un registro:**
- Nombre completo
- Tipo de documento
- Número de documento
- Programa de formación
- Número de ficha

El resto puede completarse después desde el perfil.

### RN-05 — Los campos exclusivos del instructor son obligatorios para completar el perfil

Los siguientes campos solo puede conocerlos el instructor y se marcan como **pendientes** hasta completarse:

| Campo | Por qué solo el instructor lo sabe |
|-------|-----------------------------------|
| Número de ficha (coordinación) | El aprendiz llena la ficha del formulario, pero la ficha asignada por coordinación puede diferir |
| Jefe de grupo | Asignación interna del SENA |
| Coordinación asignada | Asignación interna del SENA |
| Instructor de seguimiento | Asignación interna del SENA |
| Estado ARL confirmado | El instructor verifica en campo |

El sistema muestra un indicador de **"perfil incompleto"** hasta que todos estos campos estén diligenciados.

### RN-06 — La normalización se aplica automáticamente al importar

Al importar cualquier archivo, el sistema aplica:

| Dato | Transformación |
|------|---------------|
| Nombres | Formato título (Primera Letra Mayúscula) |
| Correos | Minúsculas, eliminar espacios |
| NITs | Eliminar decimales (`901163080.7` → `901163080`) |
| Fechas | Estandarizar a `DD/MM/AAAA` |
| Espacios extra | Trim en todos los campos de texto |

Si la normalización cambia un valor, el sistema muestra el original y el normalizado para confirmación del instructor.

---

<a name="dominio-2"></a>
## Dominio 2 — Momentos del formato GFPI-F-023

### RN-07 — El Momento 1 se registra una sola vez por aprendiz

El Momento 1 (Planeación) corresponde a la primera visita, aproximadamente 15 días después de que el aprendiz inicia en la empresa.

> ❌ Si el instructor intenta crear un segundo Momento 1 para el mismo aprendiz, el sistema lo **bloquea** y redirige a editar el existente.

Los datos de empresa y ARL se **autocompletan** desde el perfil del aprendiz.

### RN-08 — El Momento 2 se registra una sola vez por aprendiz

El Momento 2 (Seguimiento) corresponde a la visita de mitad de etapa, aproximadamente a los 3 meses de iniciada la práctica.

> ❌ Si el instructor intenta crear un segundo Momento 2 para el mismo aprendiz, el sistema lo **bloquea** y redirige a editar el existente.

Es el único momento en que se evalúan los factores técnicos y actitudinales en el seguimiento intermedio. Al guardarlo, la próxima fecha de visita apunta al Momento 3.

### RN-09 — El Momento 3 se registra una sola vez y cierra el ciclo

El Momento 3 (Evaluación final) se realiza al cierre de la etapa productiva.

> ❌ El Momento 3 no puede crearse si no existe al menos un Momento 2 registrado.

Al guardar el Momento 3:
- Juicio **"Aprobado"** → estado del aprendiz cambia a `Por certificar`
- Juicio **"No aprobado"** → estado cambia a `Pendiente por comité de evaluación`

### RN-10 — El Momento Extraordinario es el único momento repetible y solo aplica si el período se amplió

El Momento Extraordinario es el **único de los cuatro momentos que puede registrarse más de una vez**. Ocurre cuando el aprendiz debe permanecer más tiempo del previsto en la empresa. Es opcional y poco frecuente.

**Requiere obligatoriamente:**
- Motivo de la ampliación
- Compromisos del instructor
- Compromisos del aprendiz
- Compromisos del co-formador

### RN-11 — Cualquier momento puede editarse sin restricciones

No hay restricción de fecha, hora ni estado para editar un momento ya guardado. El sistema registra la **fecha y hora de la última modificación** de cada momento.

> ℹ️ Si el aprendiz ya está en estado "Certificado", el sistema muestra un aviso pero **permite continuar** con la edición.

### RN-12 — Los datos del perfil se autocompletan en todos los momentos

Al abrir el formulario de cualquier momento, el sistema pre-llena automáticamente:

- Nombre del aprendiz
- Datos de la empresa y jefe inmediato
- Datos del instructor de seguimiento
- Estado y nombre de ARL
- Fechas de inicio/fin de etapa (si están registradas)

> ❌ Ningún dato disponible en el perfil debe reingresarse manualmente en los momentos.

### RN-13 — Los factores de evaluación son fijos y no configurables

Los factores están definidos por el formato oficial del SENA y **no pueden modificarse**.

**Factores técnicos (8):**
1. Aplicación de conocimiento
2. Mejora continua
3. Fortalecimiento ocupacional
4. Oportunidad y calidad
5. Responsabilidad ambiental
6. Administración de recursos
7. Seguridad y salud en el trabajo
8. Documentación etapa productiva

**Factores actitudinales (5):**
1. Relaciones interpersonales
2. Trabajo en equipo
3. Solución de problemas
4. Cumplimiento
5. Organización

### RN-14 — Todos los factores requieren valoración obligatoria para guardar

> ❌ El botón **"Guardar"** permanece **deshabilitado** hasta que:
> - Todos los factores (técnicos y actitudinales) tengan valoración seleccionada (`Satisfactorio` o `Por mejorar`)
> - La observación del instructor esté diligenciada

Las observaciones individuales de cada factor son **opcionales**.

### RN-15 — La próxima fecha de visita es obligatoria al guardar Momentos 1 y 2

Al finalizar el Momento 1, el instructor **debe** registrar la fecha estimada del Momento 2. Al finalizar el Momento 2, debe registrar la fecha estimada del Momento 3. Esta fecha alimenta las alertas del dashboard.

> ❌ Sin próxima fecha, el momento no puede guardarse.

El Momento 3 no requiere próxima fecha porque es el cierre del ciclo regular. Si hay un Momento Extraordinario posterior, su fecha se registra en ese formulario.

El campo muestra el rango válido: entre hoy y la fecha estimada de fin de etapa.

---

<a name="dominio-3"></a>
## Dominio 3 — Generación del documento GFPI-F-023

### RN-16 — El formato visual del F-023 es inalterable

El documento generado debe ser **idéntico** al formato oficial del SENA.

> ❌ **Nunca** se agregan páginas adicionales ni se expanden filas más allá de lo definido en la plantilla original.

Si el texto ingresado es demasiado largo para una celda, el sistema lo ajusta dentro de la celda. Los campos de texto extenso tienen un **límite de caracteres** visible en el formulario.

### RN-17 — El Momento 3 ocupa exactamente 2 páginas

El Momento 3 es el único momento de dos páginas en el formato oficial. El sistema garantiza que el documento generado contenga **exactamente esas dos páginas**.

Los campos de retroalimentación del Momento 3 tienen límites de caracteres calculados para respetar el espacio disponible.

### RN-18 — El instructor elige qué partes incluir en cada exportación

Al exportar el F-023, el instructor puede seleccionar cualquier combinación de:

- [ ] Página de información general (primera página)
- [ ] Momento 1
- [ ] Momento 2 — Seguimiento
- [ ] Momento 2 — Visita N…
- [ ] Momento 3
- [ ] Momento Extraordinario (si existe)

Todas las partes disponibles aparecen **pre-marcadas por defecto**.

### RN-19 — Se puede exportar en Word (.docx) o PDF

El instructor elige el formato de salida al exportar:

| Formato | Uso recomendado |
|---------|----------------|
| **Word (.docx)** | Permite edición manual posterior si se requiere un ajuste |
| **PDF** | Formato para entrega oficial al SENA |

Ambos formatos son visualmente idénticos.

### RN-20 — Los datos se editan en el sistema, no en el documento generado

El flujo correcto es:

```
Editar datos en SGEP  →  Exportar documento  →  Entregar
```

> ❌ No está contemplado que el instructor edite el Word generado y esos cambios vuelvan al sistema.

Si necesita corregir algo, debe hacerlo en el SGEP y volver a exportar.

### RN-21 — El historial de documentos generados es permanente

Cada exportación queda registrada con:
- Aprendiz
- Partes incluidas
- Formato (Word/PDF)
- Fecha y hora de generación

El instructor puede **volver a descargar** cualquier versión anterior. El historial **no se puede eliminar**.

### RN-22 — Los campos de texto tienen límites de caracteres fijos

Los campos de texto extenso (observaciones, competencias, actividades, evidencias, retroalimentaciones) muestran un **contador de caracteres en tiempo real**.

Al alcanzar el límite definido por el espacio de la plantilla oficial, el sistema **impide** ingresar más texto.

---

<a name="dominio-4"></a>
## Dominio 4 — Estados del aprendiz

### RN-23 — Los estados siguen una secuencia lógica con transiciones controladas

| Estado | Descripción |
|--------|-------------|
| `Pendiente por iniciar` | Registrado pero no ha comenzado la práctica |
| `En ejecución` | Realizando la etapa productiva activamente |
| `Aplazada` | Pausa temporal — alertas congeladas, datos intactos |
| `Finalizada` | Terminó el período en la empresa |
| `Por certificar` | Momento 3 aprobado, pendiente de trámite |
| `Certificado` | Proceso completo |
| `Pendiente por comité` | Momento 3 con juicio "No aprobado" |

Las transiciones **hacia atrás** en el ciclo requieren confirmación y motivo obligatorio.

### RN-24 — El Momento 3 cambia el estado automáticamente

Al guardar el Momento 3:

```
Juicio "Aprobado"    →  estado cambia a "Por certificar"
Juicio "No aprobado" →  estado cambia a "Pendiente por comité de evaluación"
```

> ❌ El sistema no permite guardar el Momento 3 sin seleccionar el juicio de evaluación.

### RN-25 — El estado "Aplazada" congela las alertas sin eliminar datos

Cuando un aprendiz pasa a estado `Aplazada`:
- Sus alertas de próxima visita **desaparecen** del dashboard
- Todos sus momentos y datos se **conservan intactos**

Al reactivar a `En ejecución`, las alertas vuelven a aparecer basándose en la última fecha de próxima visita registrada.

El instructor debe registrar:
- Motivo del aplazamiento
- Fecha estimada de reactivación (opcional)

---

<a name="dominio-5"></a>
## Dominio 5 — Reporte maestro

### RN-26 — El reporte se autocompeta con todos los datos disponibles

El reporte maestro (equivalente al Excel que se entrega a la coordinadora) debe autocompletar el mayor número posible de columnas desde los datos ya registrados:

| Grupo de columnas | Fuente de datos |
|-------------------|----------------|
| Aprendices y grupos | Perfil del aprendiz |
| Información del aprendiz | Formulario importado |
| Información de etapa productiva | Perfil + Momento 1 |
| Proceso documental del seguimiento | Momentos registrados |
| Documentos para certificación | Estado del aprendiz |
| Información del instructor | Perfil del aprendiz (campos instructor) |

Las celdas **autocompletadas** se distinguen visualmente de las **editables manualmente**.

### RN-27 — El reporte es editable en cualquier momento sin restricciones

El instructor puede editar cualquier campo del reporte en cualquier momento:
- Sin restricción de fecha
- Sin restricción de estado del aprendiz
- Los campos modificados manualmente muestran la **fecha de la última edición**

La edición se realiza desde un **panel lateral por aprendiz**, no directamente en la tabla.

### RN-28 — La exportación Excel replica la estructura exacta del original

El archivo Excel exportado debe tener:
- Las mismas **58 columnas** organizadas en **7 grupos**
- Los mismos colores de encabezado por grupo
- El mismo orden que el documento que la coordinadora ya conoce

El instructor puede exportar con filtros aplicados (solo una ficha, solo aprendices en ejecución, etc.).

---

## Resumen rápido

| RN | Dominio | Regla en una línea |
|----|---------|--------------------|
| RN-01 | Datos | El formulario del aprendiz es la única fuente primaria. |
| RN-02 | Datos | El formulario puede ser uno o dos según disponibilidad de datos. |
| RN-03 | Datos | La cédula es clave única — no se permiten duplicados silenciosos. |
| RN-04 | Datos | El instructor puede crear o editar cualquier aprendiz manualmente. |
| RN-05 | Datos | Los campos del instructor quedan pendientes hasta completarse. |
| RN-06 | Datos | La normalización se aplica automáticamente al importar. |
| RN-07 | F-023 | Momento 1: único por aprendiz. |
| RN-08 | F-023 | Momento 2: único por aprendiz, se registra a mitad de la etapa. |
| RN-09 | F-023 | Momento 3: único, cierra el ciclo, determina el estado final. |
| RN-10 | F-023 | Momento Extraordinario: el único repetible, solo si el período se amplió. |
| RN-11 | F-023 | Cualquier momento puede editarse sin restricciones. |
| RN-12 | F-023 | El perfil autocompleta los datos en todos los momentos. |
| RN-13 | F-023 | Los factores son fijos: 8 técnicos + 5 actitudinales. |
| RN-14 | F-023 | Todos los factores requieren valoración obligatoria para guardar. |
| RN-15 | F-023 | La próxima fecha de visita es obligatoria al guardar M1 y M2. |
| RN-16 | Documento | El formato es inalterable — nunca se agregan páginas extra. |
| RN-17 | Documento | El Momento 3 ocupa exactamente 2 páginas. |
| RN-18 | Documento | El instructor elige libremente qué partes exportar. |
| RN-19 | Documento | Se exporta en Word o PDF. |
| RN-20 | Documento | Los datos se editan en el sistema, no en el documento generado. |
| RN-21 | Documento | El historial de documentos es permanente y re-descargable. |
| RN-22 | Documento | Cada campo de texto tiene un límite de caracteres fijo. |
| RN-23 | Estados | Los estados siguen una secuencia con transiciones controladas. |
| RN-24 | Estados | El Momento 3 cambia el estado automáticamente. |
| RN-25 | Estados | "Aplazada" congela alertas sin eliminar datos. |
| RN-26 | Reporte | El reporte se autocompeta con todos los datos disponibles. |
| RN-27 | Reporte | El reporte es editable en cualquier momento. |
| RN-28 | Reporte | La exportación Excel replica la estructura exacta del original. |

---

*Ver también: [ARQUITECTURA.md](./ARQUITECTURA.md) · [MODULOS.md](./MODULOS.md) · [API_REFERENCIA.md](./API_REFERENCIA.md)*