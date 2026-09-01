-- Ejecutar en el SQL Editor de Supabase
-- Sistema de trazabilidad de bienes (esquema normalizado)

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ─── Catálogos ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS municipios (
  id     SERIAL PRIMARY KEY,
  nombre TEXT NOT NULL UNIQUE,
  activo BOOLEAN NOT NULL DEFAULT true
);

CREATE TABLE IF NOT EXISTS juzgados (
  id           SERIAL PRIMARY KEY,
  municipio_id INT  NOT NULL REFERENCES municipios(id),
  nombre       TEXT NOT NULL,
  activo       BOOLEAN NOT NULL DEFAULT true,
  UNIQUE (municipio_id, nombre)
);

CREATE TABLE IF NOT EXISTS responsables (
  id         SERIAL PRIMARY KEY,
  juzgado_id INT  NOT NULL REFERENCES juzgados(id),
  nombre     TEXT NOT NULL,
  activo     BOOLEAN NOT NULL DEFAULT true,
  UNIQUE (juzgado_id, nombre)
);

CREATE TABLE IF NOT EXISTS tipos_bienes (
  id     SERIAL PRIMARY KEY,
  nombre TEXT NOT NULL UNIQUE,
  unidad TEXT NOT NULL DEFAULT 'unidad',
  activo BOOLEAN NOT NULL DEFAULT true
);

CREATE TABLE IF NOT EXISTS perifericos (
  id     SERIAL PRIMARY KEY,
  nombre TEXT NOT NULL UNIQUE,
  activo BOOLEAN NOT NULL DEFAULT true
);

-- ─── Registro principal ──────────────────────────────────────

CREATE SEQUENCE IF NOT EXISTS seq_trazabilidad START 1;

CREATE TABLE IF NOT EXISTS registros_bienes (
  id                 SERIAL PRIMARY KEY,
  codigo             VARCHAR(20) NOT NULL UNIQUE,
  municipio_id       INT  NOT NULL REFERENCES municipios(id),
  juzgado_id         INT  NOT NULL REFERENCES juzgados(id),
  responsable_id     INT  NOT NULL REFERENCES responsables(id),
  tipo_bien_id       INT  NOT NULL REFERENCES tipos_bienes(id),
  cantidad           INT  NOT NULL DEFAULT 1 CHECK (cantidad > 0),
  observaciones      TEXT,
  fecha_registro     DATE NOT NULL DEFAULT CURRENT_DATE,
  -- Snapshot histórico (no cambia aunque se editen los catálogos)
  municipio_nombre   TEXT NOT NULL,
  juzgado_nombre     TEXT NOT NULL,
  responsable_nombre TEXT NOT NULL,
  limpieza           BOOLEAN NOT NULL DEFAULT false,
  embalado           BOOLEAN NOT NULL DEFAULT false,
  rotulado           BOOLEAN NOT NULL DEFAULT false,
  foto               BOOLEAN NOT NULL DEFAULT false,
  created_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at         TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS registro_perifericos (
  id               SERIAL PRIMARY KEY,
  registro_bien_id INT NOT NULL REFERENCES registros_bienes(id) ON DELETE CASCADE,
  periferico_id    INT NOT NULL REFERENCES perifericos(id),
  cantidad         INT NOT NULL DEFAULT 1 CHECK (cantidad > 0),
  UNIQUE (registro_bien_id, periferico_id)
);

CREATE TABLE IF NOT EXISTS fotos_bienes (
  id               SERIAL PRIMARY KEY,
  registro_bien_id INT  NOT NULL REFERENCES registros_bienes(id) ON DELETE CASCADE,
  ruta             TEXT NOT NULL,
  nombre_archivo   TEXT NOT NULL,
  created_at       TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ─── Índices ─────────────────────────────────────────────────

CREATE INDEX IF NOT EXISTS idx_juzgados_municipio      ON juzgados(municipio_id);
CREATE INDEX IF NOT EXISTS idx_responsables_juzgado    ON responsables(juzgado_id);
CREATE INDEX IF NOT EXISTS idx_registros_codigo        ON registros_bienes(codigo);
CREATE INDEX IF NOT EXISTS idx_registros_fecha         ON registros_bienes(fecha_registro DESC);
CREATE INDEX IF NOT EXISTS idx_registros_municipio     ON registros_bienes(municipio_id);
CREATE INDEX IF NOT EXISTS idx_registros_juzgado       ON registros_bienes(juzgado_id);
CREATE INDEX IF NOT EXISTS idx_registro_perifericos_rb ON registro_perifericos(registro_bien_id);
CREATE INDEX IF NOT EXISTS idx_fotos_registro          ON fotos_bienes(registro_bien_id);

-- ─── Datos iniciales: tipos de bien ─────────────────────────

INSERT INTO tipos_bienes (nombre, unidad) VALUES
  ('Escritorio en L (cajonero) o lineal', 'unidad'),
  ('Descansa pies', 'unidad'),
  ('Silla Ejecutiva, Presidencial o Fija', 'unidad'),
  ('Computador', 'unidad'),
  ('Impresoras', 'unidad'),
  ('Scanner', 'unidad'),
  ('Estanteria', 'unidad'),
  ('Rodante', 'unidad'),
  ('Mesa auxiliar o para impresora', 'unidad'),
  ('Tándem', 'unidad'),
  ('Televisor o monitor industrial', 'unidad'),
  ('Biblioteca y toguero - Biblioteca con vidrio', 'unidad'),
  ('Mobiliario sala de Audiencia', 'unidad'),
  ('Equipo tecnológico de sala de audiencia', 'unidad'),
  ('Rack', 'unidad'),
  ('Archivadores', 'unidad'),
  ('Aire acondicionado', 'unidad'),
  ('UPS', 'unidad'),
  ('Mostrador', 'unidad'),
  ('Nevera', 'unidad'),
  ('Archivo', 'unidad'),
  ('Insumos de aseo', 'lote')
ON CONFLICT (nombre) DO NOTHING;

-- ─── Datos iniciales: periféricos comunes ───────────────────

INSERT INTO perifericos (nombre) VALUES
  ('Teclado'),
  ('Mouse'),
  ('Monitor'),
  ('Cargador'),
  ('Cable de red'),
  ('Cable HDMI'),
  ('Base para laptop'),
  ('Audífonos'),
  ('Webcam'),
  ('Escáner'),
  ('Impresora'),
  ('Tarjeta de red')
ON CONFLICT (nombre) DO NOTHING;

-- ─── Datos de ejemplo (edítalos o agrégalos según tu región) ─

INSERT INTO municipios (nombre) VALUES
  ('Bogotá'),
  ('Medellín'),
  ('Cali')
ON CONFLICT (nombre) DO NOTHING;

INSERT INTO juzgados (municipio_id, nombre)
SELECT m.id, j.nombre
FROM municipios m
CROSS JOIN (VALUES
  ('Juzgado 001 Civil Municipal'),
  ('Juzgado 002 Penal Municipal'),
  ('Juzgado 003 Laboral')
) AS j(nombre)
WHERE m.nombre = 'Bogotá'
ON CONFLICT (municipio_id, nombre) DO NOTHING;

INSERT INTO responsables (juzgado_id, nombre)
SELECT j.id, r.nombre
FROM juzgados j
INNER JOIN municipios m ON m.id = j.municipio_id
CROSS JOIN (VALUES
  ('Operario Demo 1'),
  ('Operario Demo 2')
) AS r(nombre)
WHERE m.nombre = 'Bogotá' AND j.nombre = 'Juzgado 001 Civil Municipal'
ON CONFLICT (juzgado_id, nombre) DO NOTHING;

-- En Supabase Storage, crea un bucket llamado "bienes-fotos" (público recomendado).

-- ─── Usuarios del sistema (login) ────────────────────────────
-- Ejecutar también: migrations/001_usuarios.sql
