# Trazabilidad de Bienes — PHP 8.2 + Supabase

Sistema web de trazabilidad con base de datos normalizada, catálogos en cascada, periféricos, fotografías múltiples y código único por registro.

## Requisitos

- PHP 8.2 con extensiones `pdo_pgsql` y `curl`
- Proyecto Supabase (PostgreSQL + Storage)

## Puesta en marcha

1. **Base de datos**: en el SQL Editor de Supabase, ejecuta `schema.sql`.
2. **Storage**: crea un bucket llamado `bienes-fotos` (público recomendado).
3. **Credenciales**: configura `config.php` o variables de entorno (ver sección Supabase).
4. Sube el proyecto a un hosting con PHP 8.2 o ejecuta localmente.
5. Abre `index.php` en el navegador.

## Estructura

```
├── index.php              # Interfaz (Bootstrap 5, responsive)
├── assets/app.js          # AJAX, cascada de catálogos, fotos, periféricos
├── api/
│   ├── bienes.php         # CRUD de registros_bienes
│   └── catalogos.php      # Municipios, juzgados, responsables, tipos, periféricos
├── config.php             # Credenciales separadas
├── db.php                 # Conexión PDO
├── storage.php            # Supabase Storage
├── helpers.php            # Validación, trazabilidad, relaciones
└── schema.sql             # Esquema normalizado completo
```

## Modelo de datos

```
municipios ──► juzgados ──► responsables
                                │
tipos_bienes ───────────────────┼──► registros_bienes ──► registro_perifericos ──► perifericos
                                │                    └──► fotos_bienes
```

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `municipios` | Catálogo de municipios |
| `juzgados` | Juzgados por municipio |
| `responsables` | Operarios por juzgado |
| `tipos_bienes` | 22 tipos del prompt + unidad |
| `perifericos` | Catálogo de periféricos |
| `registros_bienes` | Registro principal + snapshot histórico |
| `registro_perifericos` | Periféricos con cantidad por registro |
| `fotos_bienes` | Una o más fotos por registro |

### Snapshot histórico

Al registrar, se guardan `municipio_nombre`, `juzgado_nombre` y `responsable_nombre` para que el acta no cambie aunque se editen los catálogos después.

## Catálogos iniciales

`schema.sql` incluye:
- 22 tipos de bien
- 12 periféricos comunes
- 3 municipios, 3 juzgados y 2 responsables de ejemplo (Bogotá)

Agrega más datos en Supabase:

```sql
INSERT INTO municipios (nombre) VALUES ('Tu municipio');
INSERT INTO juzgados (municipio_id, nombre)
  SELECT id, 'Nombre del juzgado' FROM municipios WHERE nombre = 'Tu municipio';
INSERT INTO responsables (juzgado_id, nombre)
  SELECT id, 'Nombre operario' FROM juzgados WHERE nombre = 'Nombre del juzgado';
```

## Código de trazabilidad

Formato: `TRZ-2026-000001` — generado automáticamente al crear cada registro.

## API

| Método | Endpoint | Acción |
|--------|----------|--------|
| GET | `api/catalogos.php?tipo=municipios` | Listar municipios |
| GET | `api/catalogos.php?tipo=juzgados&municipio_id=1` | Juzgados por municipio |
| GET | `api/catalogos.php?tipo=responsables&juzgado_id=1` | Responsables por juzgado |
| GET | `api/catalogos.php?tipo=tipos_bienes` | Tipos de bien |
| GET | `api/catalogos.php?tipo=perifericos` | Periféricos |
| GET | `api/bienes.php` | Listar registros |
| GET | `api/bienes.php?id=1` | Detalle con periféricos y fotos |
| POST | `api/bienes.php` | Crear registro |
| PUT | `api/bienes.php` | Actualizar registro |
| DELETE | `api/bienes.php?id=1` | Eliminar registro |

## Configuración Supabase

Variables de entorno (recomendado):

```
SUPABASE_DB_HOST=db.xxxxx.supabase.co
SUPABASE_DB_PASSWORD=tu_password
SUPABASE_URL=https://xxxxx.supabase.co
SUPABASE_SERVICE_KEY=eyJhbGciOi...
```

La `service_role` key solo se usa en el backend (`storage.php`), nunca en JavaScript.
