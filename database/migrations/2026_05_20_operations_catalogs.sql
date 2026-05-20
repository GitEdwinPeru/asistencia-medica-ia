ALTER TABLE cargo
  ADD COLUMN IF NOT EXISTS esta_carg TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE grupo
  ADD COLUMN IF NOT EXISTS esta_grup TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE distrito
  ADD COLUMN IF NOT EXISTS esta_dist TINYINT(1) NOT NULL DEFAULT 1;

CREATE INDEX IF NOT EXISTS idx_asistencia_fecha ON asistencia (fech_ingr);
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_fecha ON asistencia (id_empleado, fech_ingr);
CREATE INDEX IF NOT EXISTS idx_asistencia_sede_fecha ON asistencia (id_distrito, fech_ingr);

ALTER TABLE empleado
  ADD INDEX IF NOT EXISTS idx_empleado_cargo (id_cargo),
  ADD INDEX IF NOT EXISTS idx_empleado_grupo (id_grupo),
  ADD INDEX IF NOT EXISTS idx_empleado_distrito (id_distrito);
