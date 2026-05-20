CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `asistencia_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_distrito` int(11) DEFAULT NULL,
  `hora_entrada` time NOT NULL DEFAULT '08:15:00',
  `tolerancia_minutos` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_el` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_asistencia_config_distrito` (`id_distrito`),
  CONSTRAINT `asistencia_config_ibfk_1` FOREIGN KEY (`id_distrito`) REFERENCES `distrito` (`pk_id_distrito`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `asistencia_config` (`id_distrito`, `hora_entrada`, `tolerancia_minutos`, `activo`)
SELECT NULL, '08:15:00', 0, 1
WHERE NOT EXISTS (SELECT 1 FROM `asistencia_config` WHERE `id_distrito` IS NULL);

CREATE TABLE IF NOT EXISTS `auditoria_eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) DEFAULT NULL,
  `accion` varchar(80) NOT NULL,
  `entidad` varchar(80) NOT NULL,
  `entidad_id` varchar(80) DEFAULT NULL,
  `detalle` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `creado_el` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_entidad` (`entidad`, `entidad_id`),
  KEY `idx_auditoria_actor` (`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
