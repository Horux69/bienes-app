-- Checklist de preparación del bien al registrar
ALTER TABLE registros_bienes
  ADD COLUMN IF NOT EXISTS limpieza  BOOLEAN NOT NULL DEFAULT false,
  ADD COLUMN IF NOT EXISTS embalado  BOOLEAN NOT NULL DEFAULT false,
  ADD COLUMN IF NOT EXISTS rotulado  BOOLEAN NOT NULL DEFAULT false,
  ADD COLUMN IF NOT EXISTS foto      BOOLEAN NOT NULL DEFAULT false;

COMMENT ON COLUMN registros_bienes.limpieza IS 'Equipo limpiado';
COMMENT ON COLUMN registros_bienes.embalado IS 'Equipo embalado en plástico';
COMMENT ON COLUMN registros_bienes.rotulado IS 'Rótulo impreso y colocado';
COMMENT ON COLUMN registros_bienes.foto IS 'Fotografías cargadas';
