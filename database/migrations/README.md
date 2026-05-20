# Migraciones

Guarda aqui los cambios futuros de base de datos con el formato:

```text
YYYY_MM_DD_descripcion.sql
```

Ejecuta las migraciones en orden con:

```bat
scripts\run_migrations.bat
```

Cada archivo debe ser idempotente cuando sea posible (`IF NOT EXISTS`) para poder ejecutarlo en ambientes de prueba sin duplicar columnas o indices.
