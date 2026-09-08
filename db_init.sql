-- MariaDB dump 10.19-11.3.2-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: gestion_colas
-- ------------------------------------------------------
-- Server version	11.3.2-MariaDB-1:11.3.2+maria~ubu2204

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alumnes`
--

DROP TABLE IF EXISTS `alumnes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alumnes` (
  `id_alumne` varchar(12) NOT NULL,
  `nom_alumne` varchar(50) NOT NULL,
  `cognoms_alumne` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`id_alumne`),
  UNIQUE KEY `correu_electronic` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `moduls`
--

DROP TABLE IF EXISTS `moduls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `moduls` (
  `id_modul` int(11) NOT NULL,
  `CodiModul` varchar(10) NOT NULL,
  `nom_modul` varchar(100) NOT NULL,
  `cicle_formatiu` varchar(100) NOT NULL DEFAULT 'ASIX',
  `curs` enum('1r','2n') NOT NULL DEFAULT '1r',
  PRIMARY KEY (`id_modul`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RAs`
--

DROP TABLE IF EXISTS `RAs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RAs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_modul` int(11) NOT NULL,
  `CodiModul_RA` varchar(100) NOT NULL,
  `nom_ra` varchar(255) NOT NULL,
  `cola_abierta` tinyint(1) DEFAULT 1,
  `hores_lectives` int(11) NOT NULL DEFAULT 10,
  `id_activitat_activa` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ras_moduls` (`id_modul`),
  KEY `FK_activitat_activa` (`id_activitat_activa`),
  CONSTRAINT `FK_activitat_activa` FOREIGN KEY (`id_activitat_activa`) REFERENCES `activitats_ra` (`id_activitat_conceptual`),
  CONSTRAINT `fk_ras_moduls` FOREIGN KEY (`id_modul`) REFERENCES `moduls` (`id_modul`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activitats_ra`
--

DROP TABLE IF EXISTS `activitats_ra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activitats_ra` (
  `id_activitat_conceptual` int(11) NOT NULL AUTO_INCREMENT,
  `id_ra` int(11) NOT NULL,
  `nom_activitat` varchar(255) NOT NULL,
  PRIMARY KEY (`id_activitat_conceptual`),
  KEY `id_ra` (`id_ra`),
  CONSTRAINT `activitats_ra_ibfk_1` FOREIGN KEY (`id_ra`) REFERENCES `RAs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `incidencias_acceso`
--

DROP TABLE IF EXISTS `incidencias_acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidencias_acceso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_alumne` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_incidencia` datetime DEFAULT current_timestamp(),
  `ip_origen` varchar(45) NOT NULL,
  `pagina_intentada` varchar(50) DEFAULT 'gestion.php',
  PRIMARY KEY (`id`),
  KEY `fk_incidencias_alumnes` (`id_alumne`),
  CONSTRAINT `fk_incidencias_alumnes` FOREIGN KEY (`id_alumne`) REFERENCES `alumnes` (`id_alumne`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `checks_activitat`
--

DROP TABLE IF EXISTS `checks_activitat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `checks_activitat` (
  `id_check` int(11) NOT NULL AUTO_INCREMENT,
  `id_activitat_conceptual` int(11) NOT NULL,
  `titol_check` varchar(255) NOT NULL,
  PRIMARY KEY (`id_check`),
  KEY `id_activitat_conceptual` (`id_activitat_conceptual`),
  CONSTRAINT `checks_activitat_ibfk_1` FOREIGN KEY (`id_activitat_conceptual`) REFERENCES `activitats_ra` (`id_activitat_conceptual`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activitats`
--

DROP TABLE IF EXISTS `activitats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activitats` (
  `id_activitat` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `id_ra` int(11) NOT NULL,
  PRIMARY KEY (`id_activitat`),
  KEY `fk_actividades_ras` (`id_ra`),
  CONSTRAINT `activitats_ibfk_1` FOREIGN KEY (`id_activitat`) REFERENCES `turnos` (`id_activitat`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_actividades_ras` FOREIGN KEY (`id_ra`) REFERENCES `RAs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes_activitats`
--

DROP TABLE IF EXISTS `notes_activitats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes_activitats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_alumne` varchar(12) NOT NULL,
  `id_activitat_conceptual` int(11) NOT NULL,
  `nota` decimal(4,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alumne_activitat_unic` (`id_alumne`,`id_activitat_conceptual`),
  KEY `id_activitat_conceptual` (`id_activitat_conceptual`),
  CONSTRAINT `notes_activitats_ibfk_1` FOREIGN KEY (`id_alumne`) REFERENCES `alumnes` (`id_alumne`) ON DELETE CASCADE,
  CONSTRAINT `notes_activitats_ibfk_2` FOREIGN KEY (`id_activitat_conceptual`) REFERENCES `activitats_ra` (`id_activitat_conceptual`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes_alumne`
--

DROP TABLE IF EXISTS `notes_alumne`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes_alumne` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_alumne` varchar(12) NOT NULL,
  `id_ra` int(11) NOT NULL,
  `nota` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alumne_ra_unic` (`id_alumne`,`id_ra`),
  KEY `id_ra` (`id_ra`),
  CONSTRAINT `notes_alumne_ibfk_1` FOREIGN KEY (`id_alumne`) REFERENCES `alumnes` (`id_alumne`) ON DELETE CASCADE,
  CONSTRAINT `notes_alumne_ibfk_2` FOREIGN KEY (`id_ra`) REFERENCES `RAs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes_checks_alumne`
--

DROP TABLE IF EXISTS `notes_checks_alumne`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes_checks_alumne` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_alumne` varchar(12) NOT NULL,
  `id_check` int(11) NOT NULL,
  `completat` tinyint(1) NOT NULL DEFAULT 0,
  `percentatge_aplicat` int(11) NOT NULL DEFAULT 100,
  `pregunta_realitzada` text DEFAULT NULL,
  `resposta_observacions` text DEFAULT NULL,
  `fecha_avaluacio` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resposta_text` text DEFAULT NULL,
  `resposta_fitxer_binari` longblob DEFAULT NULL,
  `resposta_fitxer_mime` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notes_checks_evaluacio` (`id_check`),
  CONSTRAINT `fk_notes_checks_evaluacio` FOREIGN KEY (`id_check`) REFERENCES `checks_activitat` (`id_check`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `profesores`
--

DROP TABLE IF EXISTS `profesores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profesores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `fecha_alta` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turnos`
--

DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_activitat` int(11) NOT NULL,
  `turno_numero` int(11) NOT NULL,
  `posicion_cola` int(11) NOT NULL,
  `estado` enum('esperando','atendiendo','atendido','cancelado') DEFAULT 'esperando',
  `resultat_prova` enum('pendent','apte','no_apte') DEFAULT 'pendent',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `hora_inicio_atencion` datetime DEFAULT NULL,
  `hora_fin_atencion` datetime DEFAULT NULL,
  `pregunta` text DEFAULT NULL,
  `respuesta` text DEFAULT NULL,
  `id_alumne` varchar(12) NOT NULL,
  `id_check_evaluacio` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asignatura_id` (`id_activitat`),
  KEY `fk_turnos_alumnes` (`id_alumne`),
  KEY `id_check_evaluacio` (`id_check_evaluacio`),
  CONSTRAINT `fk_turnos_alumnes` FOREIGN KEY (`id_alumne`) REFERENCES `alumnes` (`id_alumne`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`id_check_evaluacio`) REFERENCES `checks_activitat` (`id_check`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-08 11:05:40
