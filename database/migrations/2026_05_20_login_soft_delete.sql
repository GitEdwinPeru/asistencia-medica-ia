ALTER TABLE login
  ADD COLUMN IF NOT EXISTS esta_login TINYINT(1) NOT NULL DEFAULT 1;

CREATE INDEX IF NOT EXISTS idx_login_estado_usuario ON login (esta_login, usuario);
