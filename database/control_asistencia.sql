-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2026 a las 18:23:29
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `control_asistencia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencia`
--

CREATE TABLE `asistencia` (
  `id_asistencia` int(11) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `fech_ingr` datetime DEFAULT NULL,
  `fech_sali` datetime DEFAULT NULL,
  `horas_trab` time DEFAULT NULL,
  `horas_tard` time DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asistencia`
--

INSERT INTO `asistencia` (`id_asistencia`, `id_empleado`, `fech_ingr`, `fech_sali`, `horas_trab`, `horas_tard`, `latitud`, `longitud`) VALUES
(48, 16, '2026-04-23 10:55:43', NULL, NULL, '02:55:43', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cargo`
--

CREATE TABLE `cargo` (
  `pk_id_cargo` int(11) NOT NULL,
  `nomb_carg` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cargo`
--

INSERT INTO `cargo` (`pk_id_cargo`, `nomb_carg`) VALUES
(1, 'MÉDICO INTERNISTA'),
(2, 'MÉDICO CARDIÓLOGO'),
(3, 'MÉDICO TRAUMATÓLOGO'),
(4, 'MÉDICO PEDIATRA'),
(5, 'MÉDICO GINECO-OBSTETRA'),
(6, 'MÉDICO RADIÓLOGO'),
(7, 'MÉDICO ANESTESIÓLOGO'),
(8, 'MÉDICO CIRUJANO'),
(9, 'LICENCIADO(A) EN ENFERMERÍA'),
(10, 'TÉCNICO(A) EN ENFERMERÍA'),
(11, 'OBSTETRA'),
(12, 'TECNÓLOGO MÉDICO (LABORATORIO)'),
(13, 'TECNÓLOGO MÉDICO (REHABILITACIÓN)'),
(14, 'QUÍMICO FARMACÉUTICO'),
(15, 'TÉCNICO EN FARMACIA'),
(16, 'NUTRICIONISTA'),
(17, 'PSICÓLOGO CLÍNICO'),
(18, 'INGENIERO BIOMÉDICO'),
(19, 'TÉCNICO EN MANTENIMIENTO'),
(20, 'TÉCNICO EN ELECTROMECÁNICA'),
(21, 'TÉCNICO EN EQUIPOS MÉDICOS'),
(22, 'OPERADOR DE PLANTA DE OXÍGENO'),
(23, 'ADMINISTRADOR DE HOSPITAL'),
(24, 'JEFE DE RECURSOS HUMANOS'),
(25, 'CONTADOR'),
(26, 'SECRETARIA EJECUTIVA'),
(27, 'PERSONAL DE ADMISIÓN'),
(28, 'CAJERO(A)'),
(29, 'PERSONAL DE ARCHIVO CLÍNICO'),
(30, 'TRABAJADOR(A) SOCIAL'),
(31, 'PERSONAL DE LIMPIEZA HOSPITALARIA'),
(32, 'PERSONAL DE SEGURIDAD Y VIGILANCIA'),
(33, 'CHOFER DE AMBULANCIA'),
(34, 'PERSONAL DE LAVANDERÍA'),
(35, 'PERSONAL DE COCINA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distrito`
--

CREATE TABLE `distrito` (
  `pk_id_distrito` int(11) NOT NULL,
  `nomb_dist` varchar(100) NOT NULL,
  `id_provincia` int(11) DEFAULT NULL,
  `obsv_dist` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `distrito`
--

INSERT INTO `distrito` (`pk_id_distrito`, `nomb_dist`, `id_provincia`, `obsv_dist`) VALUES
(1, 'HUACHO', 1, NULL),
(3, 'HUAURA', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `pk_id_empleado` int(11) NOT NULL,
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
  `celu_empl` varchar(15) DEFAULT NULL,
  `emai_empl` varchar(150) DEFAULT NULL,
  `foto_empl` longblob DEFAULT NULL,
  `rostro_embedding` longtext DEFAULT NULL,
  `obsv_empl` text DEFAULT NULL,
  `esta_empl` tinyint(1) DEFAULT 1,
  `creado_el` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`pk_id_empleado`, `nomb_empl`, `apat_empl`, `amat_empl`, `dni_empl`, `gene_empl`, `id_distrito`, `dire_empl`, `id_cargo`, `id_grupo`, `fnac_empl`, `celu_empl`, `emai_empl`, `foto_empl`, `rostro_embedding`, `obsv_empl`, `esta_empl`, `creado_el`) VALUES
(1, 'Administrador', '', '', '00000000', NULL, 1, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-04-23 00:32:02'),
(16, 'Edwin Brayan', 'Benavides', 'Rimarachin', '73323799', 'M', 1, 'huacho', 3, 2, NULL, '902354183', NULL, NULL, '[-0.113392673432827,0.051643744111061096,0.0504806824028492,-0.0027470088098198175,-0.12060880661010742,0.021205872297286987,-0.12248804420232773,-0.08529100567102432,0.14831428229808807,-0.10199709236621857,0.2466098517179489,-0.006362539250403643,-0.17916607856750488,-0.08819365501403809,-0.023284433409571648,0.0814533606171608,-0.11591470241546631,-0.09960892051458359,-0.08939459919929504,-0.07377514243125916,-0.03536329045891762,0.0030539867002516985,-0.02198028936982155,-0.0064322431571781635,-0.09500902146100998,-0.3928022086620331,-0.03488174453377724,-0.14145730435848236,0.0888824313879013,-0.07403375953435898,-0.08603643625974655,0.00009419346315553412,-0.10529337078332901,-0.09090908616781235,0.028159113600850105,0.09500125050544739,-0.034553296864032745,0.01622767187654972,0.19659855961799622,-0.07419037818908691,-0.1780029535293579,0.011856243014335632,0.05689135938882828,0.24666349589824677,0.16983012855052948,0.09159156680107117,0.055358029901981354,-0.05009063705801964,0.16982874274253845,-0.19619080424308777,0.07856935262680054,0.1389542669057846,0.169120654463768,0.027376065030694008,0.1553291231393814,-0.12640279531478882,-0.05270686373114586,0.11103028059005737,-0.12577717006206512,0.055952299386262894,0.03387732431292534,-0.031650111079216,-0.07205924391746521,-0.10363755375146866,0.23245102167129517,0.08597278594970703,-0.08633211255073547,-0.11668378114700317,0.22647416591644287,-0.1237899512052536,-0.08666720986366272,0.03186865895986557,-0.08471731841564178,-0.14814957976341248,-0.2637287974357605,0.03759297356009483,0.41672396659851074,0.15988798439502716,-0.20420390367507935,0.04478857293725014,-0.026583394035696983,-0.09486094862222672,0.025518199428915977,0.06587440520524979,-0.04966079816222191,0.009226576425135136,-0.048846371471881866,-0.019745172932744026,0.1640653759241104,0.008960493840277195,0.05159028246998787,0.11098496615886688,0.02555723674595356,0.04102397337555885,0.028977928683161736,0.04559829458594322,-0.160191610455513,-0.018920980393886566,-0.14328667521476746,-0.056357719004154205,0.08250858634710312,-0.0252398569136858,0.06772252172231674,0.08592010289430618,-0.19056016206741333,0.21536590158939362,-0.028743742033839226,0.0012628386029973626,0.04636024683713913,0.04662236198782921,0.03244590759277344,-0.006462047342211008,0.1677759289741516,-0.22958555817604065,0.15918013453483582,0.12060666084289551,0.06753789633512497,0.1761416792869568,0.04162566736340523,0.02589610405266285,0.009168569929897785,0.012304640375077724,-0.22630955278873444,-0.16472220420837402,-0.05155695602297783,-0.08741392940282822,0.0205586776137352,0.08285733312368393]', NULL, 1, '2026-04-23 15:53:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo`
--

CREATE TABLE `grupo` (
  `pk_id_grupo` int(11) NOT NULL,
  `nomb_grup` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupo`
--

INSERT INTO `grupo` (`pk_id_grupo`, `nomb_grup`) VALUES
(1, 'BIOMEDICO GRUPO 1'),
(2, 'BIOMEDICO GRUPO 2'),
(3, 'BIOMEDICO GRUPO 3'),
(4, 'ELECTROMECANICOS'),
(5, 'MOBILIARIO CLINICO'),
(6, 'OXIGENO'),
(7, 'RAYOS X');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login`
--

CREATE TABLE `login` (
  `pk_id_login` int(11) NOT NULL,
  `id_empleado` int(11) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `perfil` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `login`
--

INSERT INTO `login` (`pk_id_login`, `id_empleado`, `usuario`, `clave`, `perfil`) VALUES
(2, 1, 'admin', 'admin123', 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `provincia`
--

CREATE TABLE `provincia` (
  `pk_id_provincia` int(11) NOT NULL,
  `nomb_prov` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `provincia`
--

INSERT INTO `provincia` (`pk_id_provincia`, `nomb_prov`) VALUES
(1, 'HUAURA');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `cargo`
--
ALTER TABLE `cargo`
  ADD PRIMARY KEY (`pk_id_cargo`);

--
-- Indices de la tabla `distrito`
--
ALTER TABLE `distrito`
  ADD PRIMARY KEY (`pk_id_distrito`),
  ADD KEY `id_provincia` (`id_provincia`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`pk_id_empleado`),
  ADD UNIQUE KEY `dni_empl` (`dni_empl`),
  ADD UNIQUE KEY `dni_empl_2` (`dni_empl`),
  ADD KEY `id_distrito` (`id_distrito`),
  ADD KEY `id_cargo` (`id_cargo`),
  ADD KEY `id_grupo` (`id_grupo`);

--
-- Indices de la tabla `grupo`
--
ALTER TABLE `grupo`
  ADD PRIMARY KEY (`pk_id_grupo`);

--
-- Indices de la tabla `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`pk_id_login`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_empleado` (`id_empleado`);

--
-- Indices de la tabla `provincia`
--
ALTER TABLE `provincia`
  ADD PRIMARY KEY (`pk_id_provincia`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencia`
--
ALTER TABLE `asistencia`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de la tabla `cargo`
--
ALTER TABLE `cargo`
  MODIFY `pk_id_cargo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `distrito`
--
ALTER TABLE `distrito`
  MODIFY `pk_id_distrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `pk_id_empleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `grupo`
--
ALTER TABLE `grupo`
  MODIFY `pk_id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `login`
--
ALTER TABLE `login`
  MODIFY `pk_id_login` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `provincia`
--
ALTER TABLE `provincia`
  MODIFY `pk_id_provincia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencia`
--
ALTER TABLE `asistencia`
  ADD CONSTRAINT `asistencia_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`);

--
-- Filtros para la tabla `distrito`
--
ALTER TABLE `distrito`
  ADD CONSTRAINT `distrito_ibfk_1` FOREIGN KEY (`id_provincia`) REFERENCES `provincia` (`pk_id_provincia`);

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`id_distrito`) REFERENCES `distrito` (`pk_id_distrito`),
  ADD CONSTRAINT `empleado_ibfk_2` FOREIGN KEY (`id_cargo`) REFERENCES `cargo` (`pk_id_cargo`),
  ADD CONSTRAINT `empleado_ibfk_3` FOREIGN KEY (`id_grupo`) REFERENCES `grupo` (`pk_id_grupo`);

--
-- Filtros para la tabla `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `login_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`pk_id_empleado`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
