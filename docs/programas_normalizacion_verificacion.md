## Verificación normalización de programas

Ejecutar después de `php database/run_migrations.php`.

### 1) Confirmar ausencia de duplicados canónicos

```sql
SELECT nombre, nivel, COUNT(*) AS total
FROM programas
GROUP BY nombre, nivel
HAVING COUNT(*) > 1;
```

Esperado: `0` filas.

### 2) Confirmar aprendiz vinculado a programas existentes

```sql
SELECT a.id, a.numero_documento, a.programa_id
FROM aprendices a
LEFT JOIN programas p ON p.id = a.programa_id
WHERE a.programa_id IS NOT NULL AND p.id IS NULL;
```

Esperado: `0` filas.

### 3) Confirmar niveles en formato esperado

```sql
SELECT nivel, COUNT(*) AS total
FROM programas
GROUP BY nivel
ORDER BY total DESC;
```

Esperado: valores mayormente en `Técnico`, `Tecnólogo` o vacío (`''`) cuando no aplique.

### 4) Pruebas funcionales recomendadas

- Importar archivo con variantes de nombre (`tec.`, mayúsculas/minúsculas, tildes).
- Importar caso sin nivel y un único programa candidato -> vinculación automática.
- Importar caso sin nivel y múltiples candidatos por nombre -> se marca en `programa_pending_rows` y no asigna `programa_id`.
- Repetir importación del mismo archivo -> no debe crear nuevos registros en `programas`.
