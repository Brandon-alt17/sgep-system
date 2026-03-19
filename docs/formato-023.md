flowchart TB
    A[GFPI-F-023] --> B[Momento 1: Planeación]
    A --> C[Momento 2: Seguimiento]
    A --> D[Momento 3: Evaluación Final]
    
    B --> B1[Datos aprendiz]
    B --> B2[Datos empresa]
    B --> B3[Fechas etapa]
    B --> B4[ARL]
    B --> B5[Horario]
    B --> B6[Plan de trabajo]
    
    C --> C1[Fecha visita]
    C --> C2[Modalidad]
    C --> C3[Factores técnicos 8]
    C --> C4[Factores actitudinales 5]
    C --> C5[Observaciones 3 actores]
    C --> C6[Próxima visita]
    
    D --> D1[Total visitas]
    D --> D2[Retroalimentaciones]
    D --> D3[Juicio final]
    
    style A fill:#4F46E5,color:#fff
    style B fill:#10B981,color:#fff
    style C fill:#F59E0B,color:#fff
    style D fill:#EF4444,color:#fff

flowchart LR
    A[Usuario selecciona aprendiz] --> B[Click Generar F-023]
    B --> C[Selector de momentos M1/M2/M3]
    C --> D[Cargar plantilla .docx]
    D --> E[Reemplazar variables {{ }}]
    E --> F[Guardar en storage/]
    F --> G[Registrar en historial]
    G --> H[Ofrecer descarga Word/PDF]

    style A fill:#4F46E5,color:#fff
    style H fill:#10B981,color:#fff