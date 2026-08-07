SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de dades: `gestion_colas`
--
CREATE DATABASE IF NOT EXISTS `gestion_colas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestion_colas`;

-- --------------------------------------------------------
-- 1. Taula de Professors (Evita l'error de seguridad_profesor.php)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profesores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 2. Taula de Mòduls
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `moduls` (
  `id_modul` int(11) NOT NULL AUTO_INCREMENT,
  `nom_modul` varchar(150) NOT NULL,
  PRIMARY KEY (`id_modul`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. Taula d'Activitats / Pràctiques
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activitats_ra` (
  `id_activitat_conceptual` int(11) NOT NULL AUTO_INCREMENT,
  `nom_activitat` varchar(150) NOT NULL,
  PRIMARY KEY (`id_activitat_conceptual`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 4. Taula de RAs (Configuració i estat d'aula)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `RAs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `CodiModul_RA` varchar(100) NOT NULL,
  `cola_abierta` tinyint(1) DEFAULT 1,
  `id_modul` int(11) DEFAULT NULL,
  `id_activitat_activa` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_modul` (`id_modul`),
  KEY `id_activitat_activa` (`id_activitat_activa`),
  CONSTRAINT `fk_ras_moduls` FOREIGN KEY (`id_modul`) REFERENCES `moduls` (`id_modul`) ON DELETE SET NULL,
  CONSTRAINT `fk_ras_activitats` FOREIGN KEY (`id_activitat_activa`) REFERENCES `activitats_ra` (`id_activitat_conceptual`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 5. Taula d'Alumnes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alumnes` (
  `id_alumne` int(11) NOT NULL AUTO_INCREMENT,
  `nom_alumne` varchar(100) NOT NULL,
  `cognoms_alumne` varchar(100) NOT NULL,
  `email_alumne` varchar(150) NOT NULL,
  PRIMARY KEY (`id_alumne`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 6. Taula de Checks d'Activitat (Criteris d'avaluació)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `checks_activitat` (
  `id_check` int(11) NOT NULL AUTO_INCREMENT,
  `id_activitat_conceptual` int(11) NOT NULL,
  `titol_check` varchar(255) NOT NULL,
  PRIMARY KEY (`id_check`),
  KEY `id_activitat_conceptual` (`id_activitat_conceptual`),
  CONSTRAINT `fk_checks_activitat` FOREIGN KEY (`id_activitat_conceptual`) REFERENCES `activitats_ra` (`id_activitat_conceptual`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 7. Taula de Turnos (Cua d'atenció en temps real)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_activitat` int(11) DEFAULT NULL,
  `id_alumne` int(11) DEFAULT NULL,
  `id_check_evaluacio` int(11) DEFAULT NULL,
  `nombre_alumno` varchar(100) DEFAULT NULL,
  `codigo_alumno` varchar(50) DEFAULT NULL,
  `email_alumno` varchar(150) DEFAULT NULL,
  `turno_numero` int(11) NOT NULL,
  `posicion_cola` int(11) NOT NULL,
  `estado` enum('esperando','atendiendo','atendido','cancelado') DEFAULT 'esperando',
  `resultat_prova` enum('pendent','apte','no_apte') DEFAULT 'pendent',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `hora_inicio_atencion` datetime DEFAULT NULL,
  `hora_fin_atencion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_activitat` (`id_activitat`),
  KEY `id_alumne` (`id_alumne`),
  KEY `id_check_evaluacio` (`id_check_evaluacio`),
  CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`id_activitat`) REFERENCES `RAs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_turnos_alumnes` FOREIGN KEY (`id_alumne`) REFERENCES `alumnes` (`id_alumne`) ON DELETE SET NULL,
  CONSTRAINT `fk_turnos_checks` FOREIGN KEY (`id_check_evaluacio`) REFERENCES `checks_activitat` (`id_check`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 8. Taula de Notes i Històric d'Avaluacions (amb suport BLOB)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notes_checks_alumne` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_alumne` int(11) NOT NULL,
  `id_check` int(11) NOT NULL,
  `completat` tinyint(1) DEFAULT 0,
  `percentatge_aplicat` int(11) DEFAULT 100,
  `pregunta_realitzada` text DEFAULT NULL,
  `resposta_observacions` text DEFAULT NULL,
  `resposta_text` text DEFAULT NULL,
  `resposta_fitxer_binari` longblob DEFAULT NULL,
  `resposta_fitxer_mime` varchar(100) DEFAULT NULL,
  `fecha_evaluacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_alumne` (`id_alumne`),
  KEY `id_check` (`id_check`),
  CONSTRAINT `fk_notes_alumnes` FOREIGN KEY (`id_alumne`) REFERENCES `alumnes` (`id_alumne`) ON DELETE CASCADE,
  CONSTRAINT `fk_notes_checks` FOREIGN KEY (`id_check`) REFERENCES `checks_activitat` (`id_check`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 9. Taula d'Incidències d'Accés
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `incidencias_acceso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_infractor` varchar(150) NOT NULL,
  `nombre_infractor` varchar(100) NOT NULL,
  `fecha_incidencia` datetime DEFAULT current_timestamp(),
  `ip_origen` varchar(45) NOT NULL,
  `pagina_intentada` varchar(50) DEFAULT 'gestion.php',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 10. Dades Inicials per Defecte
-- --------------------------------------------------------

-- 10.1. Professor Inicial (Modifica la contrasenya segons la teva lògica de hash)
INSERT INTO `profesores` (`id`, `nombre`, `email`, `password`) VALUES
(1, 'Professor Administrador', 'admin@aula.com', '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 10.2. Mòdul Inicial
INSERT INTO `moduls` (`id_modul`, `nom_modul`) VALUES
(1, 'M07 - Desenvolupament Web en Entorn Servidor')
ON DUPLICATE KEY UPDATE `id_modul`=`id_modul`;

-- 10.3. Activitat Inicial
INSERT INTO `activitats_ra` (`id_activitat_conceptual`, `nom_activitat`) VALUES
(1, 'Pràctica 1: Gestió de Cues i Sessions')
ON DUPLICATE KEY UPDATE `id_activitat_conceptual`=`id_activitat_conceptual`;

-- 10.4. Registre de RA Actiu (Crucial: l'api_gestion.php busca sempre l'id=1)
INSERT INTO `RAs` (`id`, `CodiModul_RA`, `cola_abierta`, `id_modul`, `id_activitat_activa`) VALUES
(1, 'RA3_M07', 1, 1, 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 10.5. Checks de prova
INSERT INTO `checks_activitat` (`id_check`, `id_activitat_conceptual`, `titol_check`) VALUES
(1, 1, 'Defensa del flux OAuth2 i Nginx Proxy'),
(2, 1, 'Validació de la persistència en MySQL')
ON DUPLICATE KEY UPDATE `id_check`=`id_check`;

COMMIT;