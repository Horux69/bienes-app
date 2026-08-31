-- Ejecutar en el SQL Editor de Supabase
-- Tabla de usuarios del sistema (login propio, contraseñas con bcrypt en PHP)

CREATE TABLE IF NOT EXISTS usuarios (
  id            SERIAL PRIMARY KEY,
  nombre        TEXT NOT NULL,
  email         TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  rol           VARCHAR(20) NOT NULL DEFAULT 'operario'
                CHECK (rol IN ('admin', 'operario')),
  activo        BOOLEAN NOT NULL DEFAULT true,
  created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios (email);
CREATE INDEX IF NOT EXISTS idx_usuarios_activo ON usuarios (activo);

-- El primer administrador se crea con tools/crear_admin.php (una sola vez).
