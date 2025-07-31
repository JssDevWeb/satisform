-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 28-07-2025 a las 07:51:44
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `centro_formacion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno`
--

DROP TABLE IF EXISTS `alumno`;
CREATE TABLE IF NOT EXISTS `alumno` (
  `ID_Alumno` varchar(9) NOT NULL,
  `ID_Usuario` int DEFAULT NULL,
  `Nombre` varchar(50) DEFAULT NULL,
  `Apellido1` varchar(50) DEFAULT NULL,
  `Apellido2` varchar(50) DEFAULT NULL,
  `Direccion` varchar(100) DEFAULT NULL,
  `Poblacion` varchar(50) DEFAULT NULL,
  `Provincia` varchar(50) DEFAULT NULL,
  `Codigo_Postal` varchar(10) DEFAULT NULL,
  `Fecha_Nacimiento` date DEFAULT NULL,
  `Nivel_Estudios` varchar(50) DEFAULT NULL,
  `Fecha_Alta` date DEFAULT NULL,
  `Fecha_Baja` date DEFAULT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  `Email` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Alumno`),
  KEY `ID_Usuario` (`ID_Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Disparadores `alumno`
--
DROP TRIGGER IF EXISTS `after_insert_alumno`;
DELIMITER $$
CREATE TRIGGER `after_insert_alumno` AFTER INSERT ON `alumno` FOR EACH ROW BEGIN
    DECLARE user_id INT;
    DECLARE rol_id INT;

    -- Obtener el ID del rol 'Alumno' desde la tabla Rol
    SELECT ID_Rol INTO rol_id FROM Rol WHERE Nombre = 'Alumno';

    -- Si el usuario no existe en Usuario, lo creamos con password NULL
    IF NOT EXISTS (SELECT 1 FROM Usuario WHERE DNI_NIE = NEW.ID_Alumno) THEN
        INSERT INTO Usuario (DNI_NIE, password, Fecha_Creacion, ID_Rol)
        VALUES (NEW.ID_Alumno, NULL, NOW(), rol_id);
    END IF;

    -- Obtener el ID_Usuario generado o existente
    SELECT ID_Usuario INTO user_id FROM Usuario WHERE DNI_NIE = NEW.ID_Alumno;

    -- Asignar el rol al usuario si aún no lo tiene
    INSERT IGNORE INTO Usuario_Rol (DNI_NIE, ID_Rol)
    VALUES (NEW.ID_Alumno, rol_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_curso`
--

DROP TABLE IF EXISTS `alumno_curso`;
CREATE TABLE IF NOT EXISTS `alumno_curso` (
  `ID_Alumno` varchar(9) NOT NULL,
  `ID_Curso` varchar(12) NOT NULL,
  `Fecha_Matricula` date NOT NULL,
  `Estado` enum('Activo','Baja','Finalizado') NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`ID_Alumno`,`ID_Curso`),
  KEY `ID_Curso` (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_horario`
--

DROP TABLE IF EXISTS `asignacion_horario`;
CREATE TABLE IF NOT EXISTS `asignacion_horario` (
  `ID_Asignacion` int NOT NULL AUTO_INCREMENT,
  `ID_Aula` int DEFAULT NULL,
  `ID_Curso` varchar(12) DEFAULT NULL,
  `Dia` varchar(15) DEFAULT NULL,
  `Hora_Inicio` time DEFAULT NULL,
  `Hora_Fin` time DEFAULT NULL,
  `Tarde_Inicio` time DEFAULT NULL,
  `Tarde_Fin` time DEFAULT NULL,
  PRIMARY KEY (`ID_Asignacion`),
  KEY `ID_Aula` (`ID_Aula`),
  KEY `ID_Curso` (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Disparadores `asignacion_horario`
--
DROP TRIGGER IF EXISTS `evitar_solapamiento_horarios_insert`;
DELIMITER $$
CREATE TRIGGER `evitar_solapamiento_horarios_insert` BEFORE INSERT ON `asignacion_horario` FOR EACH ROW BEGIN
    DECLARE solapado INT;

    -- Verifica si el nuevo horario se solapa con alguno existente en el mismo aula y día
    SET solapado = (
        SELECT COUNT(*)
        FROM Asignacion_Horario
        WHERE ID_Aula = NEW.ID_Aula
          AND Dia = NEW.Dia
          AND (
              (NEW.Hora_Inicio >= Hora_Inicio AND NEW.Hora_Inicio < Hora_Fin)
              OR (NEW.Hora_Fin > Hora_Inicio AND NEW.Hora_Fin <= Hora_Fin)
              OR (Hora_Inicio >= NEW.Hora_Inicio AND Hora_Inicio < NEW.Hora_Fin)
              OR (Hora_Fin > NEW.Hora_Inicio AND Hora_Fin <= NEW.Hora_Fin)
          )
    );

    -- Si encuentra un solapamiento, lanza un error
    IF solapado > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '❌ Error: Solapamiento de horarios en el aula (INSERT).';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `evitar_solapamiento_horarios_update`;
DELIMITER $$
CREATE TRIGGER `evitar_solapamiento_horarios_update` BEFORE UPDATE ON `asignacion_horario` FOR EACH ROW BEGIN
    DECLARE solapado INT;

    -- Verifica si el horario actualizado se solapa con otro existente en la misma aula y día, excluyendo la fila actual
    SET solapado = (
        SELECT COUNT(*)
        FROM Asignacion_Horario
        WHERE ID_Aula = NEW.ID_Aula
          AND Dia = NEW.Dia
          AND ID_Asignacion <> OLD.ID_Asignacion  -- Excluir la fila que se está modificando
          AND (
              (NEW.Hora_Inicio >= Hora_Inicio AND NEW.Hora_Inicio < Hora_Fin)
              OR (NEW.Hora_Fin > Hora_Inicio AND NEW.Hora_Fin <= Hora_Fin)
              OR (Hora_Inicio >= NEW.Hora_Inicio AND Hora_Inicio < NEW.Hora_Fin)
              OR (Hora_Fin > NEW.Hora_Inicio AND Hora_Fin <= NEW.Hora_Fin)
          )
    );

    -- Si hay solapamiento, se lanza un error
    IF solapado > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '❌ Error: Solapamiento de horarios en el aula (UPDATE).';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignatura`
--

DROP TABLE IF EXISTS `asignatura`;
CREATE TABLE IF NOT EXISTS `asignatura` (
  `ID_Asignatura` varchar(10) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `ID_Curso` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`ID_Asignatura`),
  KEY `ID_Curso` (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aula`
--

DROP TABLE IF EXISTS `aula`;
CREATE TABLE IF NOT EXISTS `aula` (
  `ID_Aula` int NOT NULL AUTO_INCREMENT,
  `Capacidad` int DEFAULT NULL,
  `Nombre` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Aula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `control_integridad`
--

DROP TABLE IF EXISTS `control_integridad`;
CREATE TABLE IF NOT EXISTS `control_integridad` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ID_Registro` int NOT NULL,
  `Fecha_Comprobacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `Hash_Original` varchar(255) DEFAULT NULL,
  `Hash_Calculado` varchar(255) DEFAULT NULL,
  `Estado` enum('Válido','Alterado') DEFAULT 'Válido',
  `Observaciones` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso`
--

DROP TABLE IF EXISTS `curso`;
CREATE TABLE IF NOT EXISTS `curso` (
  `ID_Curso` varchar(12) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `Tipo` enum('Oficial','Privado') DEFAULT NULL,
  `Tipo_cuota` enum('Hora','Mensual','Total','Gratuito') DEFAULT NULL,
  `Duracion_Horas` int DEFAULT NULL,
  `Precio_Curso` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso_modulo`
--

DROP TABLE IF EXISTS `curso_modulo`;
CREATE TABLE IF NOT EXISTS `curso_modulo` (
  `ID_Curso` varchar(12) NOT NULL,
  `ID_Modulo` varchar(10) NOT NULL,
  PRIMARY KEY (`ID_Curso`,`ID_Modulo`),
  KEY `ID_Modulo` (`ID_Modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diploma_certificado`
--

DROP TABLE IF EXISTS `diploma_certificado`;
CREATE TABLE IF NOT EXISTS `diploma_certificado` (
  `ID_Diploma` int NOT NULL AUTO_INCREMENT,
  `Tipo` enum('Diploma','Certificado') DEFAULT NULL,
  `ID_Alumno` varchar(9) DEFAULT NULL,
  `ID_Curso` varchar(12) DEFAULT NULL,
  `Fecha_Emisión` date DEFAULT NULL,
  PRIMARY KEY (`ID_Diploma`),
  KEY `ID_Alumno` (`ID_Alumno`),
  KEY `ID_Curso` (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas`
--

DROP TABLE IF EXISTS `encuestas`;
CREATE TABLE IF NOT EXISTS `encuestas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `formulario_id` int NOT NULL COMMENT 'ID del formulario utilizado',
  `token_id` int UNSIGNED DEFAULT NULL,
  `ID_Alumno` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ID_Modulo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de envío de la encuesta',
  `ip_cliente` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci DEFAULT NULL COMMENT 'Dirección IP del cliente (IPv4 o IPv6)',
  `user_agent` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci DEFAULT NULL COMMENT 'User agent del navegador (máximo 500 caracteres)',
  `tiempo_completado` int DEFAULT NULL COMMENT 'Tiempo en segundos para completar la encuesta',
  `es_anonima` tinyint(1) DEFAULT '1' COMMENT 'Indica si la encuesta es anónima',
  `hash_session` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci DEFAULT NULL COMMENT 'Hash de sesión para control anti-spam',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_id` (`token_id`),
  KEY `idx_encuestas_formulario` (`formulario_id`),
  KEY `idx_encuestas_fecha` (`fecha_envio`),
  KEY `idx_encuestas_ip` (`ip_cliente`),
  KEY `idx_encuestas_hash` (`hash_session`),
  KEY `idx_encuestas_fecha_curso` (`fecha_envio`),
  KEY `fk_encuesta_alumno` (`ID_Alumno`),
  KEY `fk_encuesta_modulo` (`ID_Modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci COMMENT='Registro de encuestas completadas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formularios`
--

DROP TABLE IF EXISTS `formularios`;
CREATE TABLE IF NOT EXISTS `formularios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci NOT NULL COMMENT 'Nombre descriptivo del formulario',
  `ID_Modulo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `descripcion` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci DEFAULT NULL COMMENT 'Descripción del formulario (máximo 1000 caracteres)',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado del formulario (activo/inactivo)',
  `permite_respuestas_anonimas` tinyint(1) DEFAULT '1' COMMENT 'Permite respuestas sin identificación',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última modificación',
  `creado_por` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci NOT NULL DEFAULT 'admin' COMMENT 'Usuario que creó el formulario',
  PRIMARY KEY (`id`),
  KEY `idx_formularios_curso` (`ID_Modulo`),
  KEY `idx_formularios_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci COMMENT='Formularios de encuesta por curso';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formulario_profesores`
--

DROP TABLE IF EXISTS `formulario_profesores`;
CREATE TABLE IF NOT EXISTS `formulario_profesores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `formulario_id` int NOT NULL COMMENT 'ID del formulario',
  `profesor_id` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `orden` int NOT NULL DEFAULT '0' COMMENT 'Orden de aparición del profesor en el formulario',
  `activo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado de la relación (activo/inactivo)',
  `fecha_asignacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de asignación del profesor',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_formulario_profesor` (`formulario_id`,`profesor_id`),
  KEY `idx_curso_profesores_formulario` (`formulario_id`),
  KEY `idx_curso_profesores_profesor` (`profesor_id`),
  KEY `idx_curso_profesores_orden` (`orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci COMMENT='Relación entre formularios y profesores';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formulario_tokens`
--

DROP TABLE IF EXISTS `formulario_tokens`;
CREATE TABLE IF NOT EXISTS `formulario_tokens` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `formulario_id` int NOT NULL COMMENT 'FK a la tabla formularios.id',
  `participant_identifier` varchar(255) NOT NULL COMMENT 'Email del alumno participante',
  `token_hash` varchar(255) NOT NULL COMMENT 'Hash del token (NUNCA almacenar en texto plano)',
  `status` enum('new','sent','completed','expired') NOT NULL DEFAULT 'new' COMMENT 'Estado del ciclo de vida del token',
  `uses_left` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Usos restantes del token',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha de caducidad del token',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del token',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Fecha en que se usó el token',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uidx_token_hash` (`token_hash`),
  KEY `idx_participant_formulario` (`participant_identifier`,`formulario_id`),
  KEY `fk_tokens_formulario` (`formulario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Gestiona los tokens de un solo uso para las encuestas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_horario`
--

DROP TABLE IF EXISTS `historial_horario`;
CREATE TABLE IF NOT EXISTS `historial_horario` (
  `ID_Historial` int NOT NULL AUTO_INCREMENT,
  `ID_Registro` int NOT NULL,
  `Campo_Modificado` varchar(50) DEFAULT NULL,
  `Valor_Anterior` time DEFAULT NULL,
  `Valor_Nuevo` time DEFAULT NULL,
  `Fecha_Cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Modificado_Por` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Historial`),
  KEY `ID_Registro` (`ID_Registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--

DROP TABLE IF EXISTS `horario`;
CREATE TABLE IF NOT EXISTS `horario` (
  `ID_Horario` int NOT NULL AUTO_INCREMENT,
  `Día` varchar(15) DEFAULT NULL,
  `Hora_Inicio` time DEFAULT NULL,
  `Hora_Fin` time DEFAULT NULL,
  PRIMARY KEY (`ID_Horario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulo`
--

DROP TABLE IF EXISTS `modulo`;
CREATE TABLE IF NOT EXISTS `modulo` (
  `ID_Modulo` varchar(10) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `Duracion_Horas` int DEFAULT NULL,
  PRIMARY KEY (`ID_Modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nota`
--

DROP TABLE IF EXISTS `nota`;
CREATE TABLE IF NOT EXISTS `nota` (
  `ID_Nota` int NOT NULL AUTO_INCREMENT,
  `ID_Alumno` varchar(9) NOT NULL,
  `ID_Curso` varchar(12) NOT NULL,
  `ID_Modulo` varchar(10) DEFAULT NULL,
  `ID_Unidad_Formativa` varchar(6) DEFAULT NULL,
  `Tipo_Nota` enum('Modulo','Unidad_Formativa') NOT NULL,
  `Calificación` decimal(4,2) NOT NULL,
  `Fecha_Registro` date DEFAULT (curdate()),
  PRIMARY KEY (`ID_Nota`),
  KEY `ID_Alumno` (`ID_Alumno`),
  KEY `ID_Curso` (`ID_Curso`),
  KEY `ID_Modulo` (`ID_Modulo`),
  KEY `ID_Unidad_Formativa` (`ID_Unidad_Formativa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion_alumno`
--

DROP TABLE IF EXISTS `notificacion_alumno`;
CREATE TABLE IF NOT EXISTS `notificacion_alumno` (
  `ID_Notificacion` int NOT NULL AUTO_INCREMENT,
  `ID_Alumno` varchar(9) NOT NULL,
  `Mensaje` text NOT NULL,
  `Fecha_Envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Notificacion`),
  KEY `ID_Alumno` (`ID_Alumno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion_curso`
--

DROP TABLE IF EXISTS `notificacion_curso`;
CREATE TABLE IF NOT EXISTS `notificacion_curso` (
  `ID_Notificacion` int NOT NULL AUTO_INCREMENT,
  `ID_Curso` varchar(12) NOT NULL,
  `Mensaje` text NOT NULL,
  `Fecha_Envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Notificacion`),
  KEY `ID_Curso` (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion_personal`
--

DROP TABLE IF EXISTS `notificacion_personal`;
CREATE TABLE IF NOT EXISTS `notificacion_personal` (
  `ID_Notificacion` int NOT NULL AUTO_INCREMENT,
  `ID_Personal` varchar(9) NOT NULL,
  `Mensaje` text NOT NULL,
  `Fecha_Envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Notificacion`),
  KEY `ID_Personal` (`ID_Personal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion_profesor`
--

DROP TABLE IF EXISTS `notificacion_profesor`;
CREATE TABLE IF NOT EXISTS `notificacion_profesor` (
  `ID_Notificacion` int NOT NULL AUTO_INCREMENT,
  `ID_Profesor` varchar(9) NOT NULL,
  `Mensaje` text NOT NULL,
  `Fecha_Envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Notificacion`),
  KEY `ID_Profesor` (`ID_Profesor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_no_docente`
--

DROP TABLE IF EXISTS `personal_no_docente`;
CREATE TABLE IF NOT EXISTS `personal_no_docente` (
  `ID_Personal` varchar(9) NOT NULL,
  `ID_Usuario` int DEFAULT NULL,
  `Nombre` varchar(50) DEFAULT NULL,
  `Apellido1` varchar(50) DEFAULT NULL,
  `Apellido2` varchar(50) DEFAULT NULL,
  `Direccion` varchar(100) DEFAULT NULL,
  `Poblacion` varchar(50) DEFAULT NULL,
  `Provincia` varchar(50) DEFAULT NULL,
  `Codigo_Postal` varchar(10) DEFAULT NULL,
  `Fecha_Nacimiento` date DEFAULT NULL,
  `Nivel_Estudios` varchar(50) DEFAULT NULL,
  `Fecha_Alta` date DEFAULT NULL,
  `Fecha_Baja` date DEFAULT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  `Email` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ID_Personal`),
  KEY `ID_Usuario` (`ID_Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Disparadores `personal_no_docente`
--
DROP TRIGGER IF EXISTS `after_insert_personal`;
DELIMITER $$
CREATE TRIGGER `after_insert_personal` AFTER INSERT ON `personal_no_docente` FOR EACH ROW BEGIN
    DECLARE user_id INT;
    DECLARE rol_id INT;

    -- Obtener el ID del rol 'Personal_No_Docente'
    SELECT ID_Rol INTO rol_id FROM Rol WHERE Nombre = 'Personal_No_Docente';

    -- Si el usuario no existe en Usuario, lo insertamos con password NULL
    IF NOT EXISTS (SELECT 1 FROM Usuario WHERE DNI_NIE = NEW.ID_Personal) THEN
        INSERT INTO Usuario (DNI_NIE, password, Fecha_Creacion, ID_Rol)
        VALUES (NEW.ID_Personal, NULL, NOW(), rol_id);
    END IF;

    -- Obtener el ID_Usuario
    SELECT ID_Usuario INTO user_id FROM Usuario WHERE DNI_NIE = NEW.ID_Personal;

    -- Asignar el rol al usuario si aún no lo tiene
    INSERT IGNORE INTO Usuario_Rol (DNI_NIE, ID_Rol)
    VALUES (NEW.ID_Personal, rol_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas`
--

DROP TABLE IF EXISTS `preguntas`;
CREATE TABLE IF NOT EXISTS `preguntas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `texto` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci NOT NULL COMMENT 'Texto de la pregunta (máximo 1000 caracteres)',
  `seccion` enum('curso','profesor') CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci NOT NULL COMMENT 'Sección a la que pertenece la pregunta',
  `tipo` enum('escala','texto','opcion_multiple') CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci NOT NULL DEFAULT 'escala' COMMENT 'Tipo de pregunta',
  `opciones` json DEFAULT NULL COMMENT 'Opciones para preguntas de opción múltiple (formato JSON)',
  `orden` int NOT NULL DEFAULT '0' COMMENT 'Orden de aparición de la pregunta',
  `es_obligatoria` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Indica si la pregunta es obligatoria',
  `activa` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Estado de la pregunta (activa/inactiva)',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creación del registro',
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última modificación',
  PRIMARY KEY (`id`),
  KEY `idx_preguntas_seccion` (`seccion`),
  KEY `idx_preguntas_activa` (`activa`),
  KEY `idx_preguntas_orden` (`orden`),
  KEY `idx_preguntas_tipo` (`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci COMMENT='Catálogo de preguntas para encuestas';

--
-- Volcado de datos para la tabla `preguntas`
--

INSERT INTO `preguntas` (`id`, `texto`, `seccion`, `tipo`, `opciones`, `orden`, `es_obligatoria`, `activa`, `fecha_creacion`, `fecha_modificacion`) VALUES
(12, 'Valoración global del curso recibido de SELECT LOGICAL,S.L.', 'curso', 'escala', NULL, 1, 1, 1, '2025-07-15 07:27:21', '2025-07-17 07:15:15'),
(13, 'El programa del curso cubre mis expectativas.', 'curso', 'escala', NULL, 2, 1, 1, '2025-07-15 07:27:21', '2025-07-15 07:27:21'),
(14, 'La entrega y presentación del material (manuales, etc) es la adecuada.', 'curso', 'escala', NULL, 3, 1, 1, '2025-07-15 07:27:21', '2025-07-15 07:27:21'),
(15, 'Los contenidos del curso han ampliado mis conocimientos.', 'curso', 'escala', NULL, 4, 1, 1, '2025-07-15 07:27:21', '2025-07-17 07:15:42'),
(16, 'Los equipos informáticos y auxiliares son prácticos.', 'curso', 'escala', NULL, 5, 1, 1, '2025-07-15 07:27:21', '2025-07-17 07:15:59'),
(17, 'Se utiliza variedad de medios didácticos y  ejercicios prácticos.', 'curso', 'escala', NULL, 6, 1, 1, '2025-07-15 07:27:21', '2025-07-15 07:27:21'),
(18, 'La información y documentación recibida es práctica.', 'curso', 'escala', NULL, 7, 1, 1, '2025-07-15 07:27:21', '2025-07-17 07:16:21'),
(19, 'El personal que le atiende, da un buen servicio.', 'curso', 'escala', NULL, 8, 1, 1, '2025-07-15 07:27:21', '2025-07-15 07:27:21'),
(20, 'Las instalaciones son adecuadas a la enseñanza y prácticas.', 'curso', 'escala', NULL, 9, 1, 1, '2025-07-15 07:27:21', '2025-07-17 07:17:17'),
(21, 'Nivel de satisfacción por los productos y servicios recibidos de SELECT LOGICAL, S.L. con respecto a los competidores más directos que conoce.', 'curso', 'escala', NULL, 10, 1, 1, '2025-07-15 07:27:21', '2025-07-17 07:18:35'),
(22, 'Aspectos que consideran deberíamos mejorar, especialmente si se ha calificado con regular o deficiente:', 'curso', 'texto', NULL, 11, 0, 1, '2025-07-15 07:27:21', '2025-07-17 07:22:00'),
(23, 'Sugerencias para la mejora continua:', 'curso', 'texto', NULL, 12, 0, 1, '2025-07-15 07:27:21', '2025-07-17 07:22:07'),
(24, 'Los profesores exponen con claridad.', 'profesor', 'escala', NULL, 13, 1, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30'),
(25, 'Los profesores se atienen al contenido del programa del curso.', 'profesor', 'escala', NULL, 14, 1, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30'),
(26, 'Los profesores trasmiten entusiasmo y motivación.', 'profesor', 'escala', NULL, 15, 1, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30'),
(27, 'Las clases son participativas.', 'profesor', 'escala', NULL, 16, 1, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30'),
(28, 'El profesor revisa el nivel de los avances y adecua el ritmo a la clase a los conocimientos adquiridos.', 'profesor', 'escala', NULL, 17, 1, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30'),
(29, 'Los profesores tienen preparadas las clases y conocen en profundidad la materia que explican.', 'profesor', 'escala', NULL, 18, 1, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30'),
(30, 'El comportamiento y la actitud de los profesores es correcto', 'profesor', 'escala', NULL, 19, 1, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30'),
(31, 'Aspectos que consideran debería mejorar el profesor/a, especialmente si se ha calificado con regular o deficiente:', 'profesor', 'texto', NULL, 20, 0, 1, '2025-07-15 07:27:21', '2025-07-28 07:50:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor`
--

DROP TABLE IF EXISTS `profesor`;
CREATE TABLE IF NOT EXISTS `profesor` (
  `ID_Profesor` varchar(9) NOT NULL,
  `ID_Usuario` int DEFAULT NULL,
  `Nombre` varchar(50) DEFAULT NULL,
  `Apellido1` varchar(50) DEFAULT NULL,
  `Apellido2` varchar(50) DEFAULT NULL,
  `Direccion` varchar(100) DEFAULT NULL,
  `Poblacion` varchar(50) DEFAULT NULL,
  `Provincia` varchar(50) DEFAULT NULL,
  `Codigo_Postal` varchar(10) DEFAULT NULL,
  `Fecha_Nacimiento` date DEFAULT NULL,
  `Nivel_Estudios` varchar(50) DEFAULT NULL,
  `Fecha_Alta` date DEFAULT NULL,
  `Fecha_Baja` date DEFAULT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `Especialidad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ID_Profesor`),
  KEY `ID_Usuario` (`ID_Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Disparadores `profesor`
--
DROP TRIGGER IF EXISTS `after_insert_profesor`;
DELIMITER $$
CREATE TRIGGER `after_insert_profesor` AFTER INSERT ON `profesor` FOR EACH ROW BEGIN
    DECLARE user_id INT;
    DECLARE rol_id INT;

    -- Obtener el ID del rol 'Profesor'
    SELECT ID_Rol INTO rol_id FROM Rol WHERE Nombre = 'Profesor';

    -- Si el usuario no existe, se inserta en Usuario con password NULL
    IF NOT EXISTS (SELECT 1 FROM Usuario WHERE DNI_NIE = NEW.ID_Profesor) THEN
        INSERT INTO Usuario (DNI_NIE, password, Fecha_Creacion, ID_Rol)
        VALUES (NEW.ID_Profesor, NULL, NOW(), rol_id);
    END IF;

    -- Obtener el ID_Usuario
    SELECT ID_Usuario INTO user_id FROM Usuario WHERE DNI_NIE = NEW.ID_Profesor;

    -- Insertar en Usuario_Rol si aún no tiene el rol
    INSERT IGNORE INTO Usuario_Rol (DNI_NIE, ID_Rol)
    VALUES (NEW.ID_Profesor, rol_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor_curso`
--

DROP TABLE IF EXISTS `profesor_curso`;
CREATE TABLE IF NOT EXISTS `profesor_curso` (
  `ID_Profesor` varchar(9) NOT NULL,
  `ID_Curso` varchar(12) NOT NULL,
  `Fecha_Matricula` date NOT NULL,
  `Estado` enum('Activo','Baja','Finalizado') NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`ID_Profesor`,`ID_Curso`),
  KEY `ID_Curso` (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recibo`
--

DROP TABLE IF EXISTS `recibo`;
CREATE TABLE IF NOT EXISTS `recibo` (
  `ID_Recibo` int NOT NULL AUTO_INCREMENT,
  `ID_Alumno` varchar(9) DEFAULT NULL,
  `ID_Curso` varchar(12) DEFAULT NULL,
  `Importe` decimal(10,2) DEFAULT NULL,
  `Fecha_Emision` date DEFAULT NULL,
  `Fecha_Pago` date DEFAULT NULL,
  `Periodo` varchar(7) NOT NULL,
  `Estado` enum('Pendiente','Cobrado') DEFAULT 'Pendiente',
  PRIMARY KEY (`ID_Recibo`),
  KEY `ID_Alumno` (`ID_Alumno`),
  KEY `ID_Curso` (`ID_Curso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_horario`
--

DROP TABLE IF EXISTS `registro_horario`;
CREATE TABLE IF NOT EXISTS `registro_horario` (
  `ID_Registro` int NOT NULL AUTO_INCREMENT,
  `ID_Usuario` varchar(20) NOT NULL,
  `Fecha` date NOT NULL,
  `Hora_Entrada_Manana` time DEFAULT NULL,
  `Hora_Salida_Manana` time DEFAULT NULL,
  `Hora_Entrada_Tarde` time DEFAULT NULL,
  `Hora_Salida_Tarde` time DEFAULT NULL,
  `Hash_Integridad` char(64) DEFAULT NULL,
  `Tipo_Jornada` enum('Completa','Parcial_Mañana','Parcial_Tarde','Turno') DEFAULT 'Completa',
  `Tipo_Dia` enum('Ordinario','Vacaciones','Baja','Asuntos_Propios','Permiso') DEFAULT 'Ordinario',
  `Observaciones` text,
  `Justificante_URL` varchar(255) DEFAULT NULL,
  `Fecha_Registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_Registro`),
  KEY `FK_Registro_Usuario` (`ID_Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas`
--

DROP TABLE IF EXISTS `respuestas`;
CREATE TABLE IF NOT EXISTS `respuestas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `encuesta_id` int NOT NULL COMMENT 'ID de la encuesta',
  `pregunta_id` int NOT NULL COMMENT 'ID de la pregunta respondida',
  `profesor_id` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `valor_int` tinyint DEFAULT NULL COMMENT 'Valor numérico para preguntas de escala (1-5)',
  `valor_text` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish2_ci DEFAULT NULL COMMENT 'Valor de texto para preguntas abiertas (máximo 500 caracteres)',
  `valor_json` json DEFAULT NULL COMMENT 'Valor en formato JSON para respuestas complejas',
  `fecha_respuesta` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de la respuesta',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_respuesta_unica` (`encuesta_id`,`pregunta_id`,`profesor_id`),
  KEY `idx_respuestas_encuesta` (`encuesta_id`),
  KEY `idx_respuestas_pregunta` (`pregunta_id`),
  KEY `idx_respuestas_profesor` (`profesor_id`),
  KEY `idx_respuestas_valor_int` (`valor_int`),
  KEY `idx_respuestas_encuesta_pregunta` (`encuesta_id`,`pregunta_id`),
  KEY `idx_respuestas_profesor_valor` (`profesor_id`,`valor_int`),
  KEY `idx_respuestas_valor_text` (`valor_text`(100))
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci COMMENT='Respuestas individuales de las encuestas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

DROP TABLE IF EXISTS `rol`;
CREATE TABLE IF NOT EXISTS `rol` (
  `ID_Rol` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`ID_Rol`),
  UNIQUE KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_formativa`
--

DROP TABLE IF EXISTS `unidad_formativa`;
CREATE TABLE IF NOT EXISTS `unidad_formativa` (
  `ID_Unidad_Formativa` varchar(6) NOT NULL,
  `Nombre` varchar(100) DEFAULT NULL,
  `ID_Modulo` varchar(10) DEFAULT NULL,
  `Duracion_Unidad` int DEFAULT NULL,
  `ID_Profesor` varchar(9) DEFAULT NULL,
  PRIMARY KEY (`ID_Unidad_Formativa`),
  KEY `ID_Modulo` (`ID_Modulo`),
  KEY `fk_unidad_profesor` (`ID_Profesor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `ID_Usuario` int NOT NULL AUTO_INCREMENT,
  `DNI_NIE` varchar(9) NOT NULL,
  `password` varchar(150) DEFAULT NULL,
  `Fecha_Creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ID_Rol` int NOT NULL,
  PRIMARY KEY (`ID_Usuario`),
  UNIQUE KEY `DNI_NIE` (`DNI_NIE`),
  KEY `ID_Rol` (`ID_Rol`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rol`
--

DROP TABLE IF EXISTS `usuario_rol`;
CREATE TABLE IF NOT EXISTS `usuario_rol` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `DNI_NIE` varchar(9) NOT NULL,
  `ID_Rol` int NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `DNI_NIE` (`DNI_NIE`),
  KEY `ID_Rol` (`ID_Rol`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumno`
--
ALTER TABLE `alumno`
  ADD CONSTRAINT `alumno_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `rol` (`ID_Rol`);

--
-- Filtros para la tabla `alumno_curso`
--
ALTER TABLE `alumno_curso`
  ADD CONSTRAINT `alumno_curso_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `alumno_curso_ibfk_2` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignacion_horario`
--
ALTER TABLE `asignacion_horario`
  ADD CONSTRAINT `asignacion_horario_ibfk_1` FOREIGN KEY (`ID_Aula`) REFERENCES `aula` (`ID_Aula`),
  ADD CONSTRAINT `asignacion_horario_ibfk_2` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`);

--
-- Filtros para la tabla `asignatura`
--
ALTER TABLE `asignatura`
  ADD CONSTRAINT `asignatura_ibfk_1` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`);

--
-- Filtros para la tabla `curso_modulo`
--
ALTER TABLE `curso_modulo`
  ADD CONSTRAINT `curso_modulo_ibfk_1` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `curso_modulo_ibfk_2` FOREIGN KEY (`ID_Modulo`) REFERENCES `modulo` (`ID_Modulo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `diploma_certificado`
--
ALTER TABLE `diploma_certificado`
  ADD CONSTRAINT `diploma_certificado_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`),
  ADD CONSTRAINT `diploma_certificado_ibfk_2` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`);

--
-- Filtros para la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD CONSTRAINT `fk_encuesta_alumno` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_encuesta_modulo` FOREIGN KEY (`ID_Modulo`) REFERENCES `modulo` (`ID_Modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_encuesta_token` FOREIGN KEY (`token_id`) REFERENCES `formulario_tokens` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `formularios`
--
ALTER TABLE `formularios`
  ADD CONSTRAINT `fk_formulario_modulo` FOREIGN KEY (`ID_Modulo`) REFERENCES `modulo` (`ID_Modulo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `formulario_profesores`
--
ALTER TABLE `formulario_profesores`
  ADD CONSTRAINT `fk_formprof_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `profesor` (`ID_Profesor`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `formulario_tokens`
--
ALTER TABLE `formulario_tokens`
  ADD CONSTRAINT `fk_tokens_formulario` FOREIGN KEY (`formulario_id`) REFERENCES `formularios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_horario`
--
ALTER TABLE `historial_horario`
  ADD CONSTRAINT `historial_horario_ibfk_1` FOREIGN KEY (`ID_Registro`) REFERENCES `registro_horario` (`ID_Registro`) ON DELETE CASCADE;

--
-- Filtros para la tabla `nota`
--
ALTER TABLE `nota`
  ADD CONSTRAINT `nota_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nota_ibfk_2` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nota_ibfk_3` FOREIGN KEY (`ID_Modulo`) REFERENCES `modulo` (`ID_Modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nota_ibfk_4` FOREIGN KEY (`ID_Unidad_Formativa`) REFERENCES `unidad_formativa` (`ID_Unidad_Formativa`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificacion_alumno`
--
ALTER TABLE `notificacion_alumno`
  ADD CONSTRAINT `notificacion_alumno_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificacion_curso`
--
ALTER TABLE `notificacion_curso`
  ADD CONSTRAINT `notificacion_curso_ibfk_1` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificacion_personal`
--
ALTER TABLE `notificacion_personal`
  ADD CONSTRAINT `notificacion_personal_ibfk_1` FOREIGN KEY (`ID_Personal`) REFERENCES `personal_no_docente` (`ID_Personal`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificacion_profesor`
--
ALTER TABLE `notificacion_profesor`
  ADD CONSTRAINT `notificacion_profesor_ibfk_1` FOREIGN KEY (`ID_Profesor`) REFERENCES `profesor` (`ID_Profesor`) ON DELETE CASCADE;

--
-- Filtros para la tabla `personal_no_docente`
--
ALTER TABLE `personal_no_docente`
  ADD CONSTRAINT `personal_no_docente_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `rol` (`ID_Rol`);

--
-- Filtros para la tabla `profesor`
--
ALTER TABLE `profesor`
  ADD CONSTRAINT `profesor_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `rol` (`ID_Rol`);

--
-- Filtros para la tabla `profesor_curso`
--
ALTER TABLE `profesor_curso`
  ADD CONSTRAINT `profesor_curso_ibfk_1` FOREIGN KEY (`ID_Profesor`) REFERENCES `profesor` (`ID_Profesor`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `profesor_curso_ibfk_2` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recibo`
--
ALTER TABLE `recibo`
  ADD CONSTRAINT `recibo_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`),
  ADD CONSTRAINT `recibo_ibfk_2` FOREIGN KEY (`ID_Curso`) REFERENCES `curso` (`ID_Curso`);

--
-- Filtros para la tabla `registro_horario`
--
ALTER TABLE `registro_horario`
  ADD CONSTRAINT `FK_Registro_Usuario` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`DNI_NIE`) ON DELETE CASCADE;

--
-- Filtros para la tabla `respuestas`
--
ALTER TABLE `respuestas`
  ADD CONSTRAINT `fk_respuesta_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `profesor` (`ID_Profesor`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `unidad_formativa`
--
ALTER TABLE `unidad_formativa`
  ADD CONSTRAINT `fk_unidad_profesor` FOREIGN KEY (`ID_Profesor`) REFERENCES `profesor` (`ID_Profesor`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `unidad_formativa_ibfk_1` FOREIGN KEY (`ID_Modulo`) REFERENCES `modulo` (`ID_Modulo`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`ID_Rol`) REFERENCES `rol` (`ID_Rol`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD CONSTRAINT `usuario_rol_ibfk_1` FOREIGN KEY (`DNI_NIE`) REFERENCES `usuario` (`DNI_NIE`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usuario_rol_ibfk_2` FOREIGN KEY (`ID_Rol`) REFERENCES `rol` (`ID_Rol`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
