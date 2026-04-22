## Base de datos — Guía práctica

Migraciones SQL, ejecución y buenas prácticas del SGEP (PHP puro).

### Estructura

```text
database/
├── migrations/
│   ├── 001_create_*.sql
│   ├── 002_create_*.sql
│   └── ...
└── run_migrations.php
```

### Cómo crear una migración

1. Crear un archivo SQL nuevo en `database/migrations`.
2. Usar un prefijo numérico para mantener orden (`011_...sql`, `012_...sql`, etc.).
3. Incluir sentencias SQL idempotentes cuando sea posible.

Ejemplo:

```sql
ALTER TABLE aprendices
ADD COLUMN IF NOT EXISTS arl_nombre VARCHAR(100) NULL;
```

### Ejecutar migraciones

```bash
php database/run_migrations.php
```

El script:
- carga variables de `.env`,
- recorre `database/migrations/*.sql` en orden,
- ejecuta cada sentencia SQL,
- omite errores de columna duplicada para soportar re-ejecución.

### Seed de datos (si aplica)

No hay seeders automáticos del framework. Para datos de ejemplo se usan scripts SQL directos:

```bash
mysql -u root -p sgep < database/seeds/aprendices_sample.sql
```

### Checklist antes de commit

- [ ] La migración corre en una BD limpia.
- [ ] La migración puede re-ejecutarse sin romper entorno local.
- [ ] No depende de comandos externos al proyecto.

### Documentación relacionada

- Ver [Referencia de tablas](./base_de_datos.md).
- Ver [Back-end](./04-BACKEND-CONTROLADORES.md).
