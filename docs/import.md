flowchart TD
    A[📁 Usuario carga CSV/Excel] --> B{¿Archivo válido?}
    B -->|No| C[❌ Mostrar error]
    B -->|Sí| D[📊 Analizar estructura]
    D --> E[🗺️ Solicitar mapeo de columnas]
    E --> F{¿Usar plantilla guardada?}
    F -->|Sí| G[⚙️ Cargar configuración existente]
    F -->|No| H[⚙️ Configurar mapeo manual]
    G --> I[🔍 Validar datos]
    H --> I
    I --> J{¿Errores encontrados?}
    J -->|Sí| K[📋 Reportar inconsistencias]
    K --> L[¿Corregir archivo?]
    L -->|Sí| A
    L -->|No| M[⚠️ Continuar con advertencias]
    J -->|No| N[🔄 Normalizar datos]
    M --> N
    N --> O[🔗 Integrar por documento identidad]
    O --> P[💾 Guardar en BD]
    P --> Q[✅ Confirmar importación]
    Q --> R[📊 Mostrar resumen]

    style A fill:#4F46E5,color:#fff
    style Q fill:#10B981,color:#fff
    style C fill:#EF4444,color:#fff
    style K fill:#F59E0B,color:#fff