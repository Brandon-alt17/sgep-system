flowchart LR
    A[Google Form] -->|Export| B[CSV/Excel]
    B -->|Importar| C[SGEP]
    C -->|Normalizar| D[(MySQL)]
    D -->|Evaluar| C
    C -->|Generar| E[GFPI-F-023.docx]
    E -->|Entregar| F[Instructor]

    style A fill:#4F46E5,color:#fff
    style C fill:#10B981,color:#fff
    style D fill:#F59E0B,color:#fff
    style E fill:#EF4444,color:#fff

sequenceDiagram
    participant U as Usuario
    participant C as EvaluationController
    participant V as ValidationService
    participant M as EvaluationModel
    participant DB as MySQL
    participant D as DocumentService

    U->>C: POST /evaluaciones (datos M2)
    C->>V: validate(data)
    V-->>C: ValidationResult
    
    alt Datos inválidos
        C-->>U: 422 {errors}
    else Datos válidos
        C->>M: create(data)
        M->>DB: INSERT evaluaciones
        DB-->>M: id
        M-->>C: Evaluation
        C->>D: generateF023(apprentice_id)
        D->>D: Load PHPWord template
        D->>D: Replace variables
        D-->>C: document_path
        C-->>U: 200 {success, document_url}
    end