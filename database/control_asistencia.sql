-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2026 a las 18:23:29
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

--SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
--START TRANSACTION;
--SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@CHARACTER_SET_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `control_asistencia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargo`
--
create database control_asistencia;
use control_asistencia;

CREATE TABLE `cargo` (
  `pk_id_cargo` int(11) NOT NULL AUTO_INCREMENT,
  `nomb_carg` varchar(100) NOT NULL,
  PRIMARY KEY (`pk_id_cargo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `cargo` (`pk_id_cargo`, `nomb_carg`) VALUES
(1, 'MÉDICO INTERNISTA'), (2, 'MÉDICO CARDIÓLOGO'), (3, 'MÉDICO TRAUMATÓLOGO'), (4, 'MÉDICO PEDIATRA'),
(5, 'MÉDICO GINECO-OBSTETRA'), (6, 'MÉDICO RADIÓLOGO'), (7, 'MÉDICO ANESTESIÓLOGO'), (8, 'MÉDICO CIRUJANO'),
(9, 'LICENCIADO(A) EN ENFERMERÍA'), (10, 'TÉCNICO(A) EN ENFERMERÍA'), (11, 'OBSTETRA'), (12, 'TECNÓLOGO MÉDICO (LABORATORIO)'),
(13, 'TECNÓLOGO MÉDICO (REHABILITACIÓN)'), (14, 'QUÍMICO FARMACÉUTICO'), (15, 'TÉCNICO EN FARMACIA'), (16, 'NUTRICIONISTA'),
(17, 'PSICÓLOGO CLÍNICO'), (18, 'INGENIERO BIOMÉDICO'), (19, 'TÉCNICO EN MANTENIMIENTO'), (20, 'TÉCNICO EN ELECTROMECÁNICA'),
(21, 'TÉCNICO EN EQUIPOS MÉDICOS'), (22, 'OPERADOR DE PLANTA DE OXÍGENO'), (23, 'ADMINISTRADOR DE HOSPITAL'), (24, 'JEFE DE RECURSOS HUMANOS'),
(25, 'CONTADOR'), (26, 'SECRETARIA EJECUTIVA'), (27, 'PERSONAL DE ADMISIÓN'), (28, 'CAJERO(A)'), (29, 'PERSONAL DE ARCHIVO CLÍNICO'),
(30, 'TRABAJADOR(A) SOCIAL'), (31, 'PERSONAL DE LIMPIEZA HOSPITALARIA'), (32, 'PERSONAL DE SEGURIDAD Y VIGILANCIA'), (33, 'CHOFER DE AMBULANCIA'),
(34, 'PERSONAL DE LAVANDERÍA'), (35, 'PERSONAL DE COCINA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distrito`
--

CREATE TABLE `distrito` (
  `pk_id_distrito` int(11) NOT NULL AUTO_INCREMENT,
  `nomb_dist` varchar(100) NOT NULL,
  `obsv_dist` text DEFAULT NULL,
  PRIMARY KEY (`pk_id_distrito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `distrito` (`pk_id_distrito`, `nomb_dist`, `obsv_dist`) VALUES (1, 'HUACHO', NULL), (3, 'HUAURA', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo`
--

CREATE TABLE `grupo` (
  `pk_id_grupo` int(11) NOT NULL AUTO_INCREMENT,
  `nomb_grup` varchar(100) NOT NULL,
  PRIMARY KEY (`pk_id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `grupo` (`pk_id_grupo`, `nomb_grup`) VALUES
(1, 'BIOMEDICO GRUPO 1'), (2, 'BIOMEDICO GRUPO 2'), (3, 'BIOMEDICO GRUPO 3'),
(4, 'ELECTROMECANICOS'), (5, 'MOBILIARIO CLINICO'), (6, 'OXIGENO'), (7, 'RAYOS X');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `pk_id_empleado` int(11) NOT NULL AUTO_INCREMENT,
  `nomb_empl` varchar(100) NOT NULL,
  `apat_empl` varchar(100) NOT NULL,
  `amat_empl` varchar(100) NOT NULL,
  `dni_empl` varchar(15) NOT NULL,
  `gene_empl` varchar(20) DEFAULT NULL,
  `id_distrito` int(11) DEFAULT NULL,
  `dire_empl` varchar(255) DEFAULT NULL,
  `id_cargo` int(11) DEFAULT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `fnac_empl` date DEFAULT NULL,
  `esta_civil` varchar(50) DEFAULT NULL,
  `nacionalidad` varchar(100) DEFAULT NULL,
  `celu_empl` varchar(15) DEFAULT NULL,
  `emai_empl` varchar(150) DEFAULT NULL,
  `foto_empl` longblob DEFAULT NULL,
  `rostro_embedding` longtext DEFAULT NULL,
  `obsv_empl` text DEFAULT NULL,
  `esta_empl` tinyint(1) DEFAULT 1,
  `creado_el` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pk_id_empleado`),
  UNIQUE KEY `dni_empl` (`dni_empl`),
  KEY `id_distrito` (`id_distrito`),
  KEY `id_cargo` (`id_cargo`),
  KEY `id_grupo` (`id_grupo`),
  CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`id_distrito`) REFERENCES `distrito` (`pk_id_distrito`),
  CONSTRAINT `empleado_ibfk_2` FOREIGN KEY (`id_cargo`) REFERENCES `cargo` (`pk_id_cargo`),
  CONSTRAINT `empleado_ibfk_3` FOREIGN KEY (`id_grupo`) REFERENCES `grupo` (`pk_id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `empleado` (`pk_id_empleado`, `nomb_empl`, `apat_empl`, `amat_empl`, `dni_empl`, `gene_empl`, `id_distrito`, `dire_empl`, `id_cargo`, `id_grupo`, `esta_empl`) VALUES
(1, 'Administrador', '', '', '00000000', NULL, 1, NULL, 1, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) DEFAULT NULL,
  `id_distrito` int(11) DEFAULT NULL,
  `fech_ingr` datetime DEFAULT NULL,
  `fech_sali` datetime DEFAULT NULL,
  `horas_trab` time DEFAULT NULL,
  `horas_tard` time DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_asistencia`),
  KEY `id_empleado` (`id_empleado`),
  KEY `id_distrito` (`id_distrito`),
  CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`),
  CONSTRAINT `asistencia_ibfk_2` FOREIGN KEY (`id_distrito`) REFERENCES `distrito` (`pk_id_distrito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login`
--

CREATE TABLE `login` (
  `pk_id_login` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `perfil` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`pk_id_login`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `login_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `login` (`pk_id_login`, `id_empleado`, `usuario`, `clave`, `perfil`) VALUES
(2, 1, 'admin', 'admin123', 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_estudios`
--

CREATE TABLE `empleado_estudios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `institucion` varchar(255) NOT NULL,
  `fecha_graduacion` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `estudios_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_bancos`
--

CREATE TABLE `empleado_bancos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `banco` varchar(100) NOT NULL,
  `tipo_cuenta` varchar(50) NOT NULL,
  `numero_cuenta` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `bancos_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_familia`
--

CREATE TABLE `empleado_familia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `parentesco` varchar(50) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `ocupacion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `familia_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_emergencia`
--

CREATE TABLE `empleado_emergencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `relacion` varchar(50) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `emergencia_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado_experiencia`
--

CREATE TABLE `empleado_experiencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empleado` int(11) NOT NULL,
  `empresa` varchar(255) NOT NULL,
  `cargo` varchar(150) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `experiencia_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
