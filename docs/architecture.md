C4Context
    title SGEP - Arquitectura del Sistema (MVP v1.0)

    Person(instructor, "Instructor", "Usuario principal del sistema")

    System_Boundary(sgep, "SGEP - Aplicación Local") {
        Container(browser, "Navegador", "Chrome/Firefox", "Interfaz web local")
        Container(laravel, "Laravel App", "PHP 8.2+", "Lógica de negocio MVC")
        ContainerDb(mysql, "MySQL", "8.0+", "Base de datos local")
        Container(files, "Sistema de Archivos", "Local", "Plantillas y documentos generados")
    }

    System_Ext(csv, "Fuente Externa", "CSV/Excel", "Exportación de Google Forms")

    Rel(instructor, browser, "Interactúa", "HTTP localhost")
    Rel(browser, laravel, "Solicitudes", "Blade Views")
    Rel(laravel, mysql, "Consultas", "Eloquent ORM")
    Rel(laravel, files, "Lee/Escribe", "PHPWord + Storage")
    Rel(laravel, csv, "Importa", "PhpSpreadsheet")

    UpdateRelStyle(instructor, browser, $offsetY="-40")
    UpdateRelStyle(laravel, mysql, $offsetY="40")

flowchart TB
    A[sgep/] --> B[app/]
    A --> C[database/]
    A --> D[resources/]
    A --> E[storage/]
    A --> F[docs/]
    A --> G[tests/]
    
    B --> B1[Actions/]
    B --> B2[Http/Controllers/]
    B --> B3[Models/]
    B --> B4[Services/]
    B --> B5[Rules/]
    
    C --> C1[migrations/]
    C --> C2[seeders/]
    C --> C3[factories/]
    
    D --> D1[views/]
    D --> D2[css/]
    
    E --> E1[app/documents/]
    E --> E2[templates/f023/]
    
    style A fill:#4F46E5,color:#fff
    style B fill:#10B981,color:#fff
    style C fill:#F59E0B,color:#fff
    style D fill:#EF4444,color:#fff

flowchart TB
    A[Instructor] -->|HTTP localhost| B[Laravel App]
    B -->|Eloquent| C[(MySQL Local)]
    B -->|PHPWord| D[Archivos .docx]
    B -->|PhpSpreadsheet| E[Archivos .xlsx]
    
    subgraph Seguridad
        B -->|CSRF Protection| B
        B -->|XSS Escape| B
        B -->|SQL Injection Safe| C
        C -->|Encrypted Fields| C
    end
    
    subgraph "Sin Conexión Externa"
        F[Internet] -.->|BLOQUEADO| B
        G[Nube] -.->|BLOQUEADO| C
    end
    
    style A fill:#4F46E5,color:#fff
    style C fill:#10B981,color:#fff
    style F fill:#EF4444,color:#fff
    style G fill:#EF4444,color:#fff