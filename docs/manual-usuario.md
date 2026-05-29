# Manual de usuario — SGEP

**Sistema de Gestión de Etapa Productiva**  
Centro de Diseño, Innovación y Tecnología Industrial — SENA Regional Risaralda

| | |
|---|---|
| **Versión del documento** | 1.0 |
| **Audiencia** | Instructores de seguimiento, coordinación académica y personal administrativo |
| **Stack** | Aplicación web (PHP), base de datos MySQL |

---

## 1. Introducción

SGEP centraliza la información de aprendices en etapa productiva: importación desde el formulario de Google (Excel), perfil del aprendiz, registro de momentos del formato GFPI-F-023, generación de documentos oficiales y exportación del reporte maestro de seguimiento para coordinación.

Este manual describe **únicamente las funciones disponibles hoy** en la aplicación. No sustituye los procedimientos institucionales del SENA ni las instrucciones de diligenciamiento de los formatos oficiales (F-023, F-165, bitácoras, etc.).

---

## 2. Alcance del sistema

### 2.1 Qué hace SGEP

| Área | Función |
|------|---------|
| **Importación** | Carga archivos Excel/CSV exportados del formulario de seguimiento; crea o actualiza aprendices; detecta duplicados por documento; gestiona conflictos y programas pendientes de enlace. |
| **Aprendices** | Listado con filtros y búsqueda; creación manual; perfil con edición; programación de visitas; filtro de datos pendientes. |
| **Momentos F-023** | Registro y edición de Momentos 1, 2, 3 y extraordinarios (EX); autocompletado desde el perfil; cambio automático de estado al guardar M3 en casos definidos. |
| **Documentos** | Generación del GFPI-F-023 (información general + momentos) en Word o PDF, desde el perfil del aprendiz. |
| **Reporte maestro** | Vista consolidada de aprendices; edición de campos del reporte; exportación a Excel según plantilla institucional. |
| **Catálogo** | Programas de formación (con competencias e importación PDF); grupos (fichas); empresas y jefes de práctica. |
| **Dashboard** | Resumen de cantidades por estado y listado de próximas visitas. |

### 2.2 Qué no hace SGEP (límites explícitos)

- **No reemplaza** SofiaPlus, correo institucional ni sistemas de certificación del SENA.
- **No envía** notificaciones automáticas por correo o SMS a aprendices o empresas.
- **No incluye** autenticación por usuario/contraseña en la versión actual (el acceso depende del entorno donde se despliegue el sistema).
- **No genera** automáticamente todos los anexos del proceso (F-165, bitácoras F-147, etc.) como documentos independientes; solo el flujo F-023 descrito en este manual.
- **No audita** quién modificó cada campo con detalle de usuario (sí registra fechas de actualización en varias tablas).

### 2.3 Importación: el sistema no “limpia” datos basura

**Punto crítico para el instructor:**

SGEP **no elimina ni corrige de forma automática** información incorrecta, incoherente o “basura” que llegue en el archivo de importación (nombres mal escritos, empresas inventadas, teléfonos inválidos, modalidades equivocadas, filas de prueba, etc.).

Lo que sí hace al importar:

- Valida **formato del archivo** (extensión, tamaño, número mínimo de columnas y encabezados críticos del formulario).
- Aplica **normalización técnica** limitada (por ejemplo: recorte de espacios, formato de algunos textos, fechas reconocibles, emparejamiento de programas cuando el nombre coincide con el catálogo).
- **Omite filas** sin datos mínimos (por ejemplo, sin documento de identidad o sin nombre).
- **Detecta duplicados** por número de documento y pide decidir qué valor conservar cuando hay conflicto con un registro existente.
- **Rellena automáticamente** solo campos vacíos en la base de datos cuando el valor entrante es compatible y no hay conflicto.

Lo que **no** hace:

- No borra aprendices ni “resetea” perfiles por importar un archivo malo.
- No infiere ni corrige errores de sentido (ej.: cambiar “Empresa XYZ123” por la empresa real).
- No valida que un teléfono sea real o que un NIT exista en la DIAN.

**Consecuencia operativa:** si entran datos basura, el sistema los **aceptará o dejará en conflicto** según las reglas anteriores; la corrección definitiva debe hacerse **editando el perfil del aprendiz**, el catálogo (empresas/programas) o resolviendo conflictos en la pantalla de importación. **Reimportar el mismo archivo sin corregir el Excel no sustituye una depuración manual.**

---

## 3. Acceso y navegación

### 3.1 Acceso

La URL depende de la instalación (por ejemplo: `http://servidor/sgep/public`). Consulte con el administrador del ambiente el enlace exacto y si existe control de acceso a nivel de red o servidor web.

### 3.2 Menú principal

| Opción | Descripción |
|--------|-------------|
| **Dashboard** | Resumen y próximas visitas. |
| **Aprendices** | Listado y perfiles. |
| **Importar** | Carga del Excel del formulario. |
| **Reportes** | Reporte maestro de seguimiento. |
| **Catálogo** (submenú) | Programas, Grupos, Empresas. |

---

## 4. Dashboard

Muestra:

- Cantidad de aprendices en el sistema.
- Aprendices por certificar y en ejecución (según estados registrados).
- Distribución por estado.
- Tabla de **próximas visitas** programadas en perfiles.

**Uso recomendado:** revisión diaria o semanal de visitas y estados agregados. No sustituye el listado filtrado de Aprendices para trabajo detallado.

---

## 5. Importación de aprendices

### 5.1 Archivo admitido

- Formatos: **.xlsx**, **.xls**, **.csv**
- Debe corresponder a la **plantilla / exportación del formulario** de seguimiento (estructura de columnas esperada por el sistema).
- Tamaño máximo configurado: **5 MB** (aprox.).

Puede descargar la plantilla de referencia desde la pantalla de importación si el administrador la publicó en `public/templates/plantilla_seguimiento.xlsx`.

### 5.2 Pasos

1. Ir a **Importar**.
2. Seleccionar el archivo y enviar.
3. Revisar el **resumen** (nuevos, actualizados, duplicados, conflictos, pendientes).
4. Completar acciones pendientes:
   - **Conflictos:** elegir valor actual o valor del archivo por campo.
   - **Programas por enlazar:** asignar programa del catálogo cuando el nombre del Excel no coincidió.
   - **Datos pendientes:** completar en el perfil los campos que el instructor debe llenar.

### 5.3 Resultados de una importación

| Resultado | Significado |
|-----------|-------------|
| **Nuevos** | Aprendiz creado por primera vez con ese documento. |
| **Actualizados** | Documento ya existía; se completaron campos vacíos sin conflicto. |
| **Duplicados** | Misma cédula en el archivo y en base de datos; puede haber actualización parcial. |
| **Conflictos** | Mismo aprendiz, pero al menos un campo difiere; requiere decisión manual. |
| **Programas pendientes** | El programa del Excel no está vinculado al catálogo; hay que enlazarlo. |
| **Datos pendientes del instructor** | Campos institucionales vacíos (ficha, instructor, coordinación, etc.) listados para seguimiento. |
| **Errores / advertencias** | Filas omitidas o mensajes técnicos; revisar el detalle en pantalla. |

### 5.4 Conflictos de importación

En **Importar → Gestionar conflictos** (desde el resumen de importación):

- Se listan aprendices con diferencias entre lo guardado y lo que trae el archivo.
- Por cada campo en conflicto puede elegir **conservar actual** o **usar valor del archivo**.
- Al finalizar un aprendiz, ese conflicto sale de la lista de la importación.

Si no resuelve conflictos, los datos viejos permanecen y el archivo puede volver a generar el mismo conflicto en la siguiente importación.

---

## 6. Gestión de aprendices

### 6.1 Listado

Filtros disponibles:

- Búsqueda por **nombre** o **documento**
- **Ficha** (grupo)
- **Estado** del aprendiz
- **Empresa**
- **Programa**
- **Datos pendientes** (solo aprendices con al menos un dato obligatorio incompleto según reglas del sistema)

Desde el listado puede abrir el perfil o crear un aprendiz manualmente.

### 6.2 Estados del aprendiz

Estados válidos en el sistema:

| Estado | Uso típico |
|--------|------------|
| Pendiente por iniciar | Aún no inicia etapa productiva en seguimiento. |
| En ejecución | En desarrollo de la etapa. |
| Aplazada | Pausa formal registrada. |
| Finalizada | Etapa culminada; pendiente trámites. |
| Por certificar | Listo para proceso de certificación. |
| Certificado | Certificación registrada. |
| Pendiente por comité | Caso remitido a comité. |

El estado puede cambiar **manualmente** en el perfil o **automáticamente** al guardar el Momento 3 (M3), según reglas del módulo de momentos.

### 6.3 Perfil del aprendiz

En el perfil puede:

- Ver datos personales, académicos, empresa y jefe de práctica.
- **Editar datos del aprendiz** (modal): corrección principal cuando la importación dejó información incorrecta.
- **Programar visitas** (fechas de próxima visita).
- Acceder a **momentos** F-023 (crear/editar M1, M2, M3, EX).
- **Generar documento GFPI-F-023** (Word/PDF).
- Ir al **reporte maestro** (vista global).

Los campos sin valor muestran *“Dato no registrado”*; no se inventan valores automáticamente en pantalla.

### 6.4 Creación manual

**Aprendices → Crear** permite registrar un aprendiz sin importación. El documento debe ser **único**. Es la vía adecuada para casos puntuales o cuando no vienen en el Excel.

---

## 7. Momentos de evaluación (GFPI-F-023)

### 7.1 Tipos de momento

| Código | Descripción | Cantidad por aprendiz |
|--------|-------------|------------------------|
| **M1** | Planeación / seguimiento inicial | Uno |
| **M2** | Seguimiento intermedio | Uno |
| **M3** | Evaluación final | Uno (puede actualizar estado del aprendiz) |
| **EX** | Extraordinario | Varios permitidos |

### 7.2 Registro y edición

- Desde el perfil: enlace a crear o editar cada momento.
- Los formularios precargan datos del perfil cuando existen (empresa, programa, fechas, etc.).
- Los momentos **pueden editarse** después de guardados (sin bloqueo por fecha en la versión actual).

### 7.3 Buenas prácticas

- Completar primero el **perfil** y la **información general F-023** antes de exportar el documento final.
- Verificar empresa, jefe y fechas en el perfil; el momento hereda esos datos.

---

## 8. Generación de documentos GFPI-F-023

Disponible solo desde el **perfil del aprendiz** (no desde un menú global suelto).

### 8.1 Pasos

1. Abrir perfil del aprendiz.
2. **Generar documento GFPI-F-023**.
3. Marcar bloques a incluir: información general, M1, M2, M3, EX (según existan o como plantilla vacía).
4. Elegir **Word (.docx)** o **PDF**.
5. Descargar el archivo generado.

### 8.2 Información general F-023

Formulario aparte (**documentos/info**) para campos de la sección de información general. Al guardar, también actualiza instructor de seguimiento en el perfil cuando esos campos se diligencian ahí.

### 8.3 PDF

El PDF se genera con **LibreOffice** en el mismo equipo donde corre el SGEP (no viene incluido en WAMP/MAMP). Debe instalarse aparte o configurarse en `.env` la ruta a `soffice.exe` / `libreoffice`.

Si LibreOffice no está disponible, use **Word (.docx)**. El administrador puede comprobar la instalación con `php scripts/check_pdf_converter.php`.

---

## 9. Reporte maestro de seguimiento

### 9.1 Vista en pantalla

Tabla amplia con grupos de columnas: aprendices y grupos, reglamento, datos del aprendiz, etapa productiva, documentos de seguimiento, certificación e instructor.

- Clic en una fila abre panel lateral para marcar documentos y campos editables del reporte (guardados en `reporte_campos`).
- Filtros en la interfaz pueden estar en evolución; la exportación acepta parámetros de URL (`estado`, `ficha`, `programa_id`) cuando estén disponibles.

### 9.2 Exportar a Excel

Botón **Exportar a Excel**:

- Usa la plantilla institucional **Reporte Maestro.xlsx**.
- Mantiene encabezados y estructura de columnas (requisito de coordinación).
- Rellena datos desde la base de datos y campos editados en el reporte.
- Las filas adicionales copian formato de fila (columnas A y B con fondo verde según plantilla).
- La columna de **referencia de modalidad** al final del archivo **no se sobrescribe** (lista desplegable / referencias de la plantilla).

**Importante:** el Excel exportado refleja lo que hay en el sistema. Si hay datos basura en perfiles, **saldrán en el reporte** hasta que se corrijan manualmente.

---

## 10. Catálogo

### 10.1 Programas de formación

- Listado y detalle de programas (código, nombre, nivel, modalidad).
- **Nuevo programa** manual o **importar desde PDF** (extracción asistida; requiere revisión humana antes de guardar).
- Edición de competencias asociadas.
- **Pendientes por enlazar:** aprendices cuyo programa del Excel no se reconoció; debe asignarse el programa correcto del catálogo.

### 10.2 Grupos

Vista de **fichas** con cantidad de aprendices. Enlace al listado de aprendices filtrado por ficha.

### 10.3 Empresas

- Alta y edición de empresas (nombre, dirección, ciudad, contactos).
- **Jefes de práctica** por empresa (varios jefes posibles; el aprendiz referencia uno).
- Vista de aprendices vinculados a la empresa.

Sin empresa o jefe bien registrados, el perfil del aprendiz y los documentos F-023 quedarán incompletos.

---

## 11. Flujos de trabajo recomendados

### 11.1 Inicio de vigencia / nueva cohorte

1. Actualizar **catálogo** (programas y empresas frecuentes).
2. **Importar** Excel del formulario.
3. Resolver **conflictos** y **programas pendientes**.
4. Revisar filtro **Datos pendientes** en Aprendices y completar perfiles.
5. Registrar **M1** y generar F-023 según cronograma institucional.

### 11.2 Corrección de datos erróneos post-importación

1. **No** confiar en reimportar el mismo Excel sin corregirlo.
2. Abrir **perfil del aprendiz → Editar datos**.
3. Ajustar empresa/jefe en catálogo si aplica.
4. Volver a generar documentos o exportar reporte si es necesario.

### 11.3 Cierre y certificación

1. Completar **M3** en el perfil.
2. Verificar estado del aprendiz (automático o manual).
3. Actualizar campos de certificación en **reporte maestro** (panel lateral).
4. Exportar **reporte maestro** para coordinación.

---

## 12. Resolución de problemas frecuentes

| Situación | Qué hacer |
|-----------|-----------|
| La importación rechaza el archivo | Verificar extensión, tamaño y que sea exportación del formulario con columnas esperadas. |
| Muchos conflictos tras importar | Normal si el Excel difiere del perfil; resolver uno a uno o corregir Excel y volver a importar **después** de alinear criterios. |
| Programa no vinculado | Catálogo → Programas → Pendientes por enlazar. |
| Documento F-023 con campos vacíos | Completar información general y momento; revisar perfil del aprendiz. |
| PDF no se genera | Probar exportación Word; consultar administrador (LibreOffice en servidor). |
| Reporte Excel sin formato en filas nuevas | Actualizar a última versión del sistema; filas deben heredar estilo de plantilla. |
| Dato incorrecto en reporte | Corregir en perfil o panel del reporte; el sistema no autocorrige importaciones pasadas. |

---

## 13. Glosario breve

| Término | Definición |
|---------|------------|
| **Aprendiz** | Persona en etapa productiva registrada en SGEP (clave: número de documento). |
| **Ficha / grupo** | Identificador de grupo de formación en SofiaPlus. |
| **Momento** | Registro de evaluación F-023 (M1, M2, M3 o EX). |
| **Conflicto de importación** | Diferencia entre dato guardado y dato del archivo para el mismo documento. |
| **Datos pendientes** | Campos obligatorios del instructor o del seguimiento aún vacíos. |
| **Reporte maestro** | Consolidado Excel de seguimiento para coordinación. |
| **GFPI-F-023** | Formato de planeación, seguimiento y evaluación de etapa productiva. |

---

## 14. Soporte y actualizaciones

Para incidencias técnicas (error 500, permisos de escritura en `storage/`, plantillas faltantes), contacte al equipo que administra el servidor y el repositorio del proyecto.

Las mejoras funcionales se documentan en `docs/changelog.md` del repositorio.

---

*Documento generado para la versión operativa actual de SGEP. Ante diferencias entre este manual y la pantalla, prevalece el comportamiento de la aplicación desplegada.*
