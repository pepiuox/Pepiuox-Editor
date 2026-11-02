-- ═══════════════════════════════════════════════════════════════════
-- PePiuoX Editor — SQL DE INSTALACIÓN LIMPIA
-- Generado: 2026-11-02 21:10 · Destino previsto: MariaDB 11 o superior
--
-- QUÉ ES
--   El esquema completo (53 tablas, 2 vistas, 4 disparadores y 5 rutinas), los
--   catálogos indispensables y los valores por defecto de una instalación
--   nueva. Ejecutarlo deja una plataforma arrancable, sin datos de nadie.
--
-- QUÉ NO ES
--   No es un respaldo. El respaldo fiel de la instalación de origen está en
--   `db/backup/`. Este archivo NO debe usarse para restaurar esa instalación.
--
-- CÓMO SE USA
--   Destino: una base de datos VACÍA, creada antes por quien instala.
--     mysql -u <usuario> -p <basededatos> < db/install/initial_database.sql
--   o por phpMyAdmin → Importar sobre la base seleccionada.
--
--   NO crea ninguna base ni cambia de base: rellena exactamente la que se le
--   indique. Antes llevaba `CREATE DATABASE` y `USE`, y con eso los datos
--   acababan en `web_editor_db` aunque se importara en otra.
--
--   NO hace falta la cuenta `root` ni privilegios de administrador. El archivo
--   no lleva cláusulas `DEFINER`, así que los disparadores, vistas y rutinas
--   quedan a nombre de quien importa. Sin esa limpieza, una cuenta normal
--   recibe «ERROR 1227: Access denied; you need the SET USER privilege» y la
--   importación se corta en el primer disparador: se crean las tablas de antes
--   y ninguna vista, disparador ni rutina.
--
--   El usuario necesita CREATE, ALTER, INDEX, REFERENCES, TRIGGER,
--   CREATE VIEW, CREATE ROUTINE, EXECUTE y SHOW VIEW para importar. Después se
--   le pueden retirar: la aplicación solo necesita SELECT, INSERT, UPDATE y
--   DELETE.
--
-- DATOS QUE NO CONTIENE, A PROPÓSITO
--   Cuentas de usuario, sesiones, inicios de sesión, intentos fallidos,
--   historial de baneos y dispositivos de confianza · aplicaciones y tokens
--   de API · archivos y sus metadatos · páginas, versiones, plantillas,
--   menús, etiquetas meta y componentes · notificaciones · analítica y
--   estadísticas · auditoría y actividad · colas de caché · estado del
--   limitador de peticiones.
--
-- EL PRIMER ADMINISTRADOR NO SE CREA AQUÍ
--   No se incluye ninguna contraseña ni hash de contraseña. La cuenta se
--   crea por consola, con la contraseña escrita por quien instala:
--     php 01-TOOLS/bootstrap/create-admin.php
--
-- Requisitos del motor: MariaDB 11+ (usa colaciones `utf8mb4_uca1400_ai_ci`,
-- propias de MariaDB; MySQL no las reconoce tal cual).
-- ═══════════════════════════════════════════════════════════════════
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-12.3.3-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: pepiuox_sqlfix_211038_b06db5
-- ------------------------------------------------------
-- Server version	12.3.3-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` varchar(500) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_activity_log_action_date` (`action`,`created_at`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `analytics_events`
--

DROP TABLE IF EXISTS `analytics_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_event_date` (`event_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analytics_events`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `analytics_events` WRITE;
/*!40000 ALTER TABLE `analytics_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `analytics_events` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `api_access_logs`
--

DROP TABLE IF EXISTS `api_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_access_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `token_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_data`)),
  `response_code` int(3) DEFAULT NULL,
  `execution_time` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `api_access_logs_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `api_applications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_access_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `api_access_logs` WRITE;
/*!40000 ALTER TABLE `api_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_access_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `api_applications`
--

DROP TABLE IF EXISTS `api_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(255) NOT NULL,
  `app_key` varchar(64) NOT NULL,
  `app_secret` varchar(255) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `api_secret` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `allowed_ips` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_ips`)),
  `rate_limit` int(11) DEFAULT 100,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `status` enum('active','suspended','revoked') DEFAULT 'active',
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_key` (`app_key`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idx_api_key` (`api_key`),
  KEY `idx_app_key` (`app_key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_applications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `api_applications` WRITE;
/*!40000 ALTER TABLE `api_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_applications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `refresh_token` varchar(255) NOT NULL,
  `token_type` enum('bearer','mac','oauth') DEFAULT 'bearer',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `scopes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scopes`)),
  `expires_at` datetime NOT NULL,
  `refresh_expires_at` datetime NOT NULL,
  `revoked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `refresh_token` (`refresh_token`),
  KEY `idx_token` (`token`),
  KEY `idx_refresh_token` (`refresh_token`),
  KEY `idx_application` (`application_id`),
  CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `api_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `api_tokens` WRITE;
/*!40000 ALTER TABLE `api_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `context` varchar(50) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=431 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `backup_schedule`
--

DROP TABLE IF EXISTS `backup_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('full','database','files') NOT NULL DEFAULT 'full',
  `frequency` enum('hourly','daily','weekly','monthly') NOT NULL DEFAULT 'daily',
  `retention_days` int(11) NOT NULL DEFAULT 30,
  `compression` enum('none','gzip','zip') NOT NULL DEFAULT 'gzip',
  `include_files` tinyint(1) DEFAULT 1,
  `include_database` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_schedule`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `backup_schedule` WRITE;
/*!40000 ALTER TABLE `backup_schedule` DISABLE KEYS */;
INSERT INTO `backup_schedule` VALUES
(1,'Backup diario automático','full','daily',30,'gzip',1,1,1,NULL,NULL,'2026-08-24 04:01:29','2026-08-24 04:01:29');
/*!40000 ALTER TABLE `backup_schedule` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `type` enum('full','database','files') NOT NULL DEFAULT 'full',
  `compression` enum('none','gzip','zip') NOT NULL DEFAULT 'gzip',
  `tables_count` int(11) DEFAULT 0,
  `status` enum('pending','running','completed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `retention_days` int(11) DEFAULT 30,
  `is_manual` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`),
  KEY `fk_backups_user` (`created_by`),
  CONSTRAINT `fk_backups_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ban_history`
--

DROP TABLE IF EXISTS `ban_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ban_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` enum('banned','unbanned','extended','modified') NOT NULL,
  `reason` text DEFAULT NULL,
  `previous_reason` text DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_ban_history_performed` (`performed_by`),
  CONSTRAINT `fk_ban_history_performed` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ban_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ban_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ban_history` WRITE;
/*!40000 ALTER TABLE `ban_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `ban_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blocked_ips`
--

DROP TABLE IF EXISTS `blocked_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` text DEFAULT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `idx_blocked_ips_expires` (`blocked_until`),
  KEY `idx_active` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_ips`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blocked_ips` WRITE;
/*!40000 ALTER TABLE `blocked_ips` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_ips` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cache_settings`
--

DROP TABLE IF EXISTS `cache_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(100) NOT NULL,
  `cache_value` longtext DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cache_settings` WRITE;
/*!40000 ALTER TABLE `cache_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `collaboration_events`
--

DROP TABLE IF EXISTS `collaboration_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collaboration_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_type` enum('content_change','cursor_move','selection','chat','join','leave','lock_acquire','lock_release') NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `sequence` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_page_seq` (`page_id`,`sequence`),
  KEY `idx_page_type` (`page_id`,`event_type`,`created_at`),
  KEY `fk_collab_event_user` (`user_id`),
  CONSTRAINT `fk_collab_event_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_collab_event_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaboration_events`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `collaboration_events` WRITE;
/*!40000 ALTER TABLE `collaboration_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `collaboration_events` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `collaboration_locks`
--

DROP TABLE IF EXISTS `collaboration_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collaboration_locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `selector` varchar(500) NOT NULL,
  `locked_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_page_selector` (`page_id`,`selector`(191)),
  KEY `idx_expires` (`expires_at`),
  KEY `fk_collab_lock_user` (`user_id`),
  CONSTRAINT `fk_collab_lock_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_collab_lock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaboration_locks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `collaboration_locks` WRITE;
/*!40000 ALTER TABLE `collaboration_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `collaboration_locks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `collaboration_messages`
--

DROP TABLE IF EXISTS `collaboration_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collaboration_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_page_time` (`page_id`,`created_at`),
  KEY `fk_collab_msg_user` (`user_id`),
  CONSTRAINT `fk_collab_msg_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_collab_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaboration_messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `collaboration_messages` WRITE;
/*!40000 ALTER TABLE `collaboration_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `collaboration_messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `collaboration_sessions`
--

DROP TABLE IF EXISTS `collaboration_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `collaboration_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `cursor_position` int(11) DEFAULT 0,
  `cursor_selector` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_heartbeat` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_page_user` (`page_id`,`user_id`),
  KEY `idx_page_active` (`page_id`,`is_active`),
  KEY `idx_heartbeat` (`last_heartbeat`),
  KEY `fk_collab_session_user` (`user_id`),
  CONSTRAINT `fk_collab_session_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_collab_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collaboration_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `collaboration_sessions` WRITE;
/*!40000 ALTER TABLE `collaboration_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `collaboration_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `comment_mentions`
--

DROP TABLE IF EXISTS `comment_mentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_mentions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `reply_id` int(11) DEFAULT NULL,
  `mentioned_user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mentioned` (`mentioned_user_id`,`comment_id`),
  KEY `fk_mention_comment` (`comment_id`),
  KEY `fk_mention_reply` (`reply_id`),
  CONSTRAINT `fk_mention_comment` FOREIGN KEY (`comment_id`) REFERENCES `component_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mention_reply` FOREIGN KEY (`reply_id`) REFERENCES `comment_replies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mention_user` FOREIGN KEY (`mentioned_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_mentions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `comment_mentions` WRITE;
/*!40000 ALTER TABLE `comment_mentions` DISABLE KEYS */;
/*!40000 ALTER TABLE `comment_mentions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `comment_replies`
--

DROP TABLE IF EXISTS `comment_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comment` (`comment_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_reply_comment` FOREIGN KEY (`comment_id`) REFERENCES `component_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reply_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment_replies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `comment_replies` WRITE;
/*!40000 ALTER TABLE `comment_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `comment_replies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `component_comments`
--

DROP TABLE IF EXISTS `component_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `component_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `component_selector` varchar(500) DEFAULT NULL COMMENT 'CSS selector del componente',
  `component_id` varchar(100) DEFAULT NULL COMMENT 'ID interno de GrapesJS',
  `position_x` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Posición X en el canvas',
  `position_y` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Posición Y en el canvas',
  `message` text NOT NULL,
  `status` enum('open','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_page_status` (`page_id`,`status`),
  KEY `idx_user` (`user_id`),
  KEY `idx_component` (`page_id`,`component_id`),
  KEY `fk_comment_resolved` (`resolved_by`),
  CONSTRAINT `fk_comment_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_resolved` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `component_comments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `component_comments` WRITE;
/*!40000 ALTER TABLE `component_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `component_comments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `components`
--

DROP TABLE IF EXISTS `components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `html` longtext DEFAULT NULL,
  `css` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `components`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `components` WRITE;
/*!40000 ALTER TABLE `components` DISABLE KEYS */;
/*!40000 ALTER TABLE `components` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `daily_stats`
--

DROP TABLE IF EXISTS `daily_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL,
  `total_users` int(11) NOT NULL DEFAULT 0,
  `new_users` int(11) NOT NULL DEFAULT 0,
  `active_users` int(11) NOT NULL DEFAULT 0,
  `total_logins` int(11) NOT NULL DEFAULT 0,
  `unique_logins` int(11) NOT NULL DEFAULT 0,
  `failed_logins` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_stat_date` (`stat_date`),
  KEY `idx_new_users` (`new_users`),
  KEY `idx_active_users` (`active_users`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_stats`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `daily_stats` WRITE;
/*!40000 ALTER TABLE `daily_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_stats` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `daily_summary`
--

DROP TABLE IF EXISTS `daily_summary`;
/*!50001 DROP VIEW IF EXISTS `daily_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `daily_summary` AS SELECT
 NULL AS `date`,
 NULL AS `total_registrations`,
 NULL AS `admin_registrations`,
 NULL AS `manager_registrations`,
 NULL AS `editor_registrations`,
 NULL AS `guest_registrations` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `file_shares`
--

DROP TABLE IF EXISTS `file_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `shared_by` int(11) NOT NULL,
  `shared_with` int(11) DEFAULT NULL,
  `share_token` varchar(64) DEFAULT NULL,
  `access_level` enum('view','download','edit') DEFAULT 'view',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `file_id` (`file_id`) USING BTREE,
  KEY `shared_by` (`shared_by`) USING BTREE,
  KEY `shared_with` (`shared_with`) USING BTREE,
  KEY `share_token` (`share_token`) USING BTREE,
  CONSTRAINT `file_shares_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `file_shares_ibfk_2` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `file_shares_ibfk_3` FOREIGN KEY (`shared_with`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_shares`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `file_shares` WRITE;
/*!40000 ALTER TABLE `file_shares` DISABLE KEYS */;
/*!40000 ALTER TABLE `file_shares` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `category` enum('document','image','audio','video','archive','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `category` (`category`) USING BTREE,
  KEY `is_public` (`is_public`) USING BTREE,
  CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `footer_designs`
--

DROP TABLE IF EXISTS `footer_designs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `footer_designs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `grapesjs_data` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_footer_designs_name` (`name`),
  KEY `idx_footer_designs_active` (`is_active`),
  KEY `idx_footer_designs_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `footer_designs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `footer_designs` WRITE;
/*!40000 ALTER TABLE `footer_designs` DISABLE KEYS */;
/*!40000 ALTER TABLE `footer_designs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `native_name` varchar(100) DEFAULT NULL,
  `flag` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `languages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES
(1,'es','Spanish','Español','🇪🇸',1,1,1),
(2,'en','English','English','🇬🇧',1,0,2),
(3,'ca','Catalan','Català','🏴',1,0,3),
(4,'fr','French','Français','🇫🇷',1,0,4),
(5,'de','German','Deutsch','🇩🇪',1,0,5),
(6,'pt','Portuguese','Português','🇵🇹',1,0,6),
(7,'it','Italian','Italiano','🇮🇹',1,0,7);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `successful` tinyint(1) DEFAULT 0,
  `attempt_time` timestamp NULL DEFAULT current_timestamp(),
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_ip` (`ip_address`,`attempt_time`),
  KEY `idx_login_attempts_user` (`username`,`attempt_time`),
  KEY `idx_attempt_time_success` (`attempt_time`,`successful`),
  KEY `idx_ip_time` (`ip_address`,`attempt_time`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `location` varchar(50) NOT NULL DEFAULT 'header',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `meta_tags`
--

DROP TABLE IF EXISTS `meta_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `tag_type` enum('basic','og','twitter') NOT NULL DEFAULT 'basic',
  `tag_name` varchar(100) NOT NULL,
  `tag_value` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_tags`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `meta_tags` WRITE;
/*!40000 ALTER TABLE `meta_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `meta_tags` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bell',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `page_autosave`
--

DROP TABLE IF EXISTS `page_autosave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_autosave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `content_php` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_page_user` (`page_id`,`user_id`),
  KEY `fk_autosave_user` (`user_id`),
  CONSTRAINT `fk_autosave_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_autosave_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_autosave`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_autosave` WRITE;
/*!40000 ALTER TABLE `page_autosave` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_autosave` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `page_cache`
--

DROP TABLE IF EXISTS `page_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `cached_content` longtext DEFAULT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `page_cache_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_cache`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_cache` WRITE;
/*!40000 ALTER TABLE `page_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_cache` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `page_menu`
--

DROP TABLE IF EXISTS `page_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `page_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 0,
  `menu_group` varchar(50) NOT NULL DEFAULT 'main',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `target` enum('_self','_blank','_parent') DEFAULT '_self',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_menu_slug` (`menu_id`,`slug`),
  UNIQUE KEY `uniq_menu_page` (`menu_id`,`page_id`),
  KEY `parent_id` (`parent_id`),
  KEY `status_published` (`status`,`is_published`),
  KEY `idx_page_menu_page` (`page_id`),
  KEY `idx_page_menu_group` (`menu_group`),
  CONSTRAINT `FK_pages_menu` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_page_menu_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_page_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `page_menu` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_menu`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_menu` WRITE;
/*!40000 ALTER TABLE `page_menu` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_menu` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `page_statistics`
--

DROP TABLE IF EXISTS `page_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `visits` int(11) DEFAULT 0,
  `version` varchar(50) DEFAULT '1.0',
  `last_visit` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_id` (`page_id`),
  CONSTRAINT `page_statistics_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_statistics`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_statistics` WRITE;
/*!40000 ALTER TABLE `page_statistics` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_statistics` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `page_versions`
--

DROP TABLE IF EXISTS `page_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `version_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_php` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `page_versions_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `page_versions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_versions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_versions` WRITE;
/*!40000 ALTER TABLE `page_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_versions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `h1` varchar(255) DEFAULT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_php` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `grapesjs_data` longtext DEFAULT NULL,
  `startpage` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 0,
  `publish_at` datetime DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `idx_pages_template` (`template_id`),
  KEY `idx_pages_parent` (`parent_id`),
  KEY `idx_pages_status` (`status`),
  KEY `idx_pages_published` (`is_published`),
  KEY `idx_pages_publish_at` (`publish_at`),
  KEY `fk_pages_menu_override` (`menu_id`),
  CONSTRAINT `fk_pages_menu_override` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pages_parent` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pages_template` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE TRIGGER `after_page_update` AFTER UPDATE ON `pages` FOR EACH ROW BEGIN
IF NEW.updated_at > OLD.updated_at THEN
        UPDATE page_statistics 
        SET version = CONCAT(
            CAST(SUBSTRING_INDEX(version, '.', 1) AS UNSIGNED) + 1,
            '.0'
        ),
        updated_at = NOW()
        WHERE page_id = NEW.id;
    END IF;
END 
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `redirects`
--

DROP TABLE IF EXISTS `redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_path` varchar(500) NOT NULL,
  `to_path` varchar(500) NOT NULL,
  `status_code` int(3) NOT NULL DEFAULT 301,
  `is_active` tinyint(1) DEFAULT 1,
  `hits` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_from` (`from_path`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `redirects`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `redirects` WRITE;
/*!40000 ALTER TABLE `redirects` DISABLE KEYS */;
/*!40000 ALTER TABLE `redirects` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `is_shared` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `fk_reports_user` (`created_by`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `secure_pages`
--

DROP TABLE IF EXISTS `secure_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secure_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_php` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `signature` varchar(255) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `version` int(11) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secure_pages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `secure_pages` WRITE;
/*!40000 ALTER TABLE `secure_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `secure_pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seo_audit`
--

DROP TABLE IF EXISTS `seo_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `score_before` int(11) DEFAULT NULL,
  `score_after` int(11) DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_page` (`page_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_audit`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seo_audit` WRITE;
/*!40000 ALTER TABLE `seo_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `seo_audit` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` enum('string','int','bool','json') DEFAULT 'string',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_group_key` (`group`,`key`)
) ENGINE=InnoDB AUTO_INCREMENT=3173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'general','site_name','PePiuoX Editor','string','2026-10-30 06:04:24'),
(2,'general','site_description','CMS profesional','string','2026-10-30 06:04:24'),
(3,'general','site_locale','es','string','2026-10-30 06:03:52'),
(4,'seo','meta_robots','index,follow','string','2026-08-24 02:03:33'),
(5,'seo','ga_tracking','','string','2026-08-24 02:03:33'),
(6,'cache','ttl','3600','int','2026-08-24 02:03:33'),
(7,'cache','enabled','1','int','2026-10-30 01:50:53'),
(23,'mail','smtp_host','','string','2026-08-24 06:04:31'),
(24,'mail','smtp_port','587','int','2026-08-24 06:04:31'),
(25,'mail','smtp_user','','string','2026-08-24 06:04:31'),
(26,'mail','smtp_pass','','string','2026-08-24 06:04:31'),
(27,'mail','from_email','','string','2026-08-24 06:04:31'),
(28,'mail','from_name','PePiuoX Editor','string','2026-08-24 06:04:31'),
(29,'seo','default_meta_title','PePiuoX Editor','string','2026-10-30 04:13:25'),
(30,'seo','default_meta_description','CMS profesional','string','2026-10-30 06:04:24'),
(31,'seo','default_robots','index, follow','string','2026-08-24 06:45:33'),
(32,'seo','default_og_image','','string','2026-08-24 06:45:33'),
(33,'seo','gsc_verification','','string','2026-08-24 06:45:33'),
(34,'seo','twitter_site','','string','2026-08-24 06:45:33'),
(38,'general','site_timezone','UTC','string','2026-10-30 04:55:00'),
(50,'public','theme','corporativo','string','2026-10-30 06:11:13'),
(51,'public','logo_alt','','string','2026-10-30 06:03:52'),
(52,'public','logo_header','','string','2026-11-02 21:10:39'),
(53,'public','logo_desktop','','string','2026-10-30 02:19:03'),
(54,'public','logo_tablet','','string','2026-10-30 02:29:03'),
(55,'public','logo_mobile','','string','2026-10-30 02:29:03'),
(56,'public','logo_menu','','string','2026-10-30 06:04:27'),
(57,'public','logo_multinivel','','string','2026-10-30 06:04:27'),
(58,'public','logo_footer','','string','2026-10-30 06:04:21'),
(59,'public','logo_sidebar','','string','2026-10-30 02:19:03'),
(60,'public','logo_dark','','string','2026-10-30 02:29:03'),
(61,'public','logo_light','','string','2026-10-30 02:19:03'),
(62,'public','favicon_ico','','string','2026-10-30 02:19:03'),
(63,'public','favicon_16','','string','2026-10-30 02:19:03'),
(64,'public','favicon_32','','string','2026-10-30 06:04:26'),
(65,'public','apple_touch_icon','','string','2026-10-30 02:19:03'),
(66,'public','color_primary','#1b3a5c','string','2026-10-30 06:11:13'),
(67,'public','color_secondary','#4a6fa5','string','2026-10-30 06:11:13'),
(68,'public','color_accent','#c9a227','string','2026-10-30 06:11:13'),
(69,'public','color_background','#f4f6f9','string','2026-10-30 06:11:13'),
(70,'public','color_section_bg','#ffffff','string','2026-10-30 06:11:13'),
(71,'public','color_text','#1b2733','string','2026-10-30 06:11:13'),
(72,'public','color_text_muted','#5a6b7b','string','2026-10-30 06:11:13'),
(73,'public','color_link','#1b3a5c','string','2026-10-30 06:11:13'),
(74,'public','color_button','#1b3a5c','string','2026-10-30 06:11:13'),
(75,'public','color_border','#d5dde5','string','2026-10-30 06:11:13'),
(76,'public','color_header_bg','#1b3a5c','string','2026-10-30 06:11:13'),
(77,'public','color_footer_bg','#14293f','string','2026-10-30 06:11:13'),
(78,'public','color_sidebar_bg','#eef2f6','string','2026-10-30 06:11:13'),
(79,'public','color_menu_bg','#1b3a5c','string','2026-10-30 06:13:21'),
(80,'public','color_multinivel_bg','#ffffff','string','2026-10-30 06:06:14'),
(81,'public','color_menu_hover','#dce5ef','string','2026-10-30 06:11:13'),
(82,'public','custom_css','','string','2026-10-30 06:04:27'),
(83,'public','custom_css_enabled','0','bool','2026-10-30 06:04:22'),
(2562,'public','color_header_text','#ffffff','string','2026-10-30 06:16:07'),
(2563,'public','color_menu_text','#ffffff','string','2026-10-30 06:16:35'),
(2564,'public','color_menu_hover_text','','string','2026-10-30 06:04:35'),
(2565,'public','color_multinivel_text','','string','2026-10-30 06:03:22'),
(2566,'public','color_footer_text','#ffffff','string','2026-10-30 06:15:31'),
(2567,'public','color_sidebar_text','','string','2026-10-30 06:03:22'),
(2568,'public','color_button_text','','string','2026-10-30 06:04:35'),
(3152,'footer','about','Editor y gestor de contenidos en PHP y MariaDB. Instalado en tu propio servidor, sin dependencias de terceros.','string','2026-11-02 21:10:40'),
(3153,'footer','copyright','© {year} {site}. Todos los derechos reservados.','string','2026-11-02 21:10:40'),
(3154,'footer','show_contact','1','bool','2026-11-02 21:10:40'),
(3155,'footer','contact_title','Contacto','string','2026-11-02 21:10:40'),
(3156,'footer','contact_email','info@pepiuox.com','string','2026-11-02 21:10:40'),
(3157,'footer','contact_note','','string','2026-11-02 21:10:40'),
(3158,'footer','columns','[{\"title\":\"Navegación\",\"links\":[{\"label\":\"Inicio\",\"href\":\"/\"},{\"label\":\"Funciones\",\"href\":\"/funciones\"},{\"label\":\"Cómo funciona\",\"href\":\"/como-funciona\"}]},{\"title\":\"Plataforma\",\"links\":[{\"label\":\"Seguridad\",\"href\":\"/seguridad\"},{\"label\":\"Quiénes somos\",\"href\":\"/quienes-somos\"}]}]','json','2026-11-02 21:10:40'),
(3159,'landing','enabled','0','bool','2026-11-02 21:10:40'),
(3160,'landing','hero_kicker','Editor y CMS web en PHP 8 y MariaDB','string','2026-11-02 21:10:40'),
(3161,'landing','hero_title','Publica, versiona y controla tu web desde un solo panel','string','2026-11-02 21:10:40'),
(3162,'landing','hero_subtitle','Contenido, diseño y seguridad en la misma herramienta, sin dependencias de terceros.','string','2026-11-02 21:10:40'),
(3163,'landing','hero_description','PePiuoX Editor reúne constructor visual, control de versiones, plantillas, menús y personalización visual completa en una plataforma que se instala en tu propio servidor. Sin licencias por usuario y sin ataduras a un servicio externo.','string','2026-11-02 21:10:40'),
(3164,'landing','hero_image','','string','2026-11-02 21:10:40'),
(3165,'landing','hero_cta_primary','{\"label\":\"Entrar al sistema\",\"href\":\"/login\"}','json','2026-11-02 21:10:40'),
(3166,'landing','hero_cta_secondary','{\"label\":\"Ver funciones\",\"href\":\"#caracteristicas\"}','json','2026-11-02 21:10:40'),
(3167,'landing','sections','{\"presentation\":{\"title\":\"Qué es PePiuoX Editor\",\"problem\":\"Mantener una web suele repartirse entre un gestor de contenidos, un maquetador y una carpeta de copias de seguridad. Cada pieza guarda su verdad y ninguna sabe de las otras.\",\"audience\":\"Equipos que publican contenido con frecuencia y no quieren depender de un desarrollador para cada cambio, pero tampoco aceptan perder control sobre el código, los datos ni el diseño.\",\"advantages\":\"Una sola base de datos, un solo panel y un solo historial. Lo que se edita se versiona, lo que se publica se puede revertir y lo que se rompe se restaura con una copia fechada.\"},\"features\":{\"title\":\"Funciones\",\"intro\":\"Los módulos que ya están en la plataforma, no una hoja de ruta.\"},\"specs\":{\"title\":\"Especificaciones técnicas\",\"intro\":\"Lo que hay que saber antes de instalarlo en un servidor.\"},\"benefits\":{\"title\":\"Qué cambia con la plataforma\",\"intro\":\"El efecto práctico de tener todo en el mismo sitio.\"},\"gallery\":{\"title\":\"El sistema por dentro\",\"intro\":\"Pantallas reales del panel. Las imágenes se cargan desde Configuración → Portada.\"}}','json','2026-11-02 21:10:40'),
(3168,'landing','features','[{\"icon\":\"bi-vector-pen\",\"title\":\"Constructor visual de bloques\",\"description\":\"Arrastra bloques ya diseñados sobre Bootstrap 5 y publica sin escribir HTML. La biblioteca de bloques es propia y coherente con el resto del panel.\"},{\"icon\":\"bi-clock-history\",\"title\":\"Versionado del contenido\",\"description\":\"Cada guardado deja una versión recuperable. Puedes comparar, restaurar o descartar sin miedo a perder el trabajo posterior.\"},{\"icon\":\"bi-layout-text-window-reverse\",\"title\":\"Plantillas reutilizables\",\"description\":\"Define una estructura una vez y utilízala en tantas páginas como necesites, con versiones propias de cada plantilla.\"},{\"icon\":\"bi-diagram-3\",\"title\":\"Menús jerárquicos\",\"description\":\"Menús multinivel con orden arrastrable, asignación por página y caché que se regenera sola al cambiar la estructura.\"},{\"icon\":\"bi-palette\",\"title\":\"Identidad visual sin tocar código\",\"description\":\"Cinco temas, dieciséis colores, diez huecos de logotipo según el contexto, favicon y CSS propio, con el contraste AA calculado en cada superficie.\"},{\"icon\":\"bi-shield-lock\",\"title\":\"Seguridad en la base\",\"description\":\"Contraseñas con bcrypt de coste 12, protección CSRF en los formularios, límite de intentos de acceso y verificación en dos pasos con códigos de recuperación.\"},{\"icon\":\"bi-graph-up-arrow\",\"title\":\"Analítica y auditoría\",\"description\":\"Visitas, estadísticas diarias y un registro de quién hizo qué, cuándo y desde dónde. Todo consultable desde el propio panel.\"},{\"icon\":\"bi-plug\",\"title\":\"API versionada\",\"description\":\"Endpoints en /api/v1 con aplicaciones, claves y tokens para que otros sistemas lean tu contenido sin entrar al panel.\"}]','json','2026-11-02 21:10:40'),
(3169,'landing','specs','[{\"label\":\"Lenguaje\",\"value\":\"PHP 8.3 o superior, con tipado estricto\"},{\"label\":\"Base de datos\",\"value\":\"MariaDB 11 o superior, mediante PDO con consultas preparadas\"},{\"label\":\"Servidor web\",\"value\":\"Apache 2.4 con mod_rewrite, o nginx con la reescritura equivalente\"},{\"label\":\"Dependencias\",\"value\":\"Ninguna en tiempo de ejecución: autocarga PSR-4 propia, sin Composer\"},{\"label\":\"Interfaz pública\",\"value\":\"Bootstrap 5.3 e iconografía Bootstrap Icons\"},{\"label\":\"Constructor\",\"value\":\"GrapesJS con biblioteca de bloques propia\"},{\"label\":\"Seguridad\",\"value\":\"Bcrypt coste 12, CSRF, límite de peticiones, 2FA TOTP, sesiones HttpOnly y SameSite\"},{\"label\":\"Interoperabilidad\",\"value\":\"API REST versionada en /api/v1 con autenticación por token\"}]','json','2026-11-02 21:10:40'),
(3170,'landing','benefits','[{\"icon\":\"bi-lightning-charge\",\"title\":\"Automatización\",\"description\":\"La caché, los menús, el sitemap y las copias programadas se regeneran sin intervención manual.\"},{\"icon\":\"bi-bezier2\",\"title\":\"Procesos más cortos\",\"description\":\"Editar, previsualizar y publicar ocurre en la misma pantalla, sin exportar ni subir archivos por FTP.\"},{\"icon\":\"bi-hdd-stack\",\"title\":\"Administración centralizada\",\"description\":\"Páginas, plantillas, menús, archivos, usuarios y ajustes comparten panel, permisos e historial.\"},{\"icon\":\"bi-clipboard-data\",\"title\":\"Control de la información\",\"description\":\"Cada acción queda auditada y cada contenido versionado: se puede saber qué cambió y volver atrás.\"}]','json','2026-11-02 21:10:40'),
(3171,'landing','gallery','[]','json','2026-11-02 21:10:40'),
(3172,'landing','footer_note','PePiuoX Editor — editor y CMS web en PHP 8 y MariaDB. Instalable en servidor propio.','string','2026-11-02 21:10:40');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `template_versions`
--

DROP TABLE IF EXISTS `template_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) NOT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_php` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `template_versions_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `template_versions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `template_versions` WRITE;
/*!40000 ALTER TABLE `template_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `template_versions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `content_html` longtext DEFAULT NULL,
  `content_css` longtext DEFAULT NULL,
  `content_php` longtext DEFAULT NULL,
  `content_js` longtext DEFAULT NULL,
  `grapesjs_data` longtext DEFAULT NULL,
  `is_system_template` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang_code` varchar(10) NOT NULL,
  `group` varchar(50) NOT NULL DEFAULT 'app',
  `key` varchar(200) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lang_group_key` (`lang_code`,`group`,`key`),
  KEY `idx_lang` (`lang_code`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `translations` WRITE;
/*!40000 ALTER TABLE `translations` DISABLE KEYS */;
INSERT INTO `translations` VALUES
(1,'es','app','dashboard','Panel de control','2026-08-24 03:32:52'),
(2,'es','app','users','Usuarios','2026-08-24 03:32:52'),
(3,'es','app','pages','Páginas','2026-08-24 03:32:52'),
(4,'es','app','templates','Plantillas','2026-08-24 03:32:52'),
(5,'es','app','menus','Menús','2026-08-24 03:32:52'),
(6,'es','app','files','Archivos','2026-08-24 03:32:52'),
(7,'es','app','roles','Roles y permisos','2026-08-24 03:32:52'),
(8,'es','app','settings','Configuración','2026-08-24 03:32:52'),
(9,'es','app','seo','SEO','2026-08-24 03:32:52'),
(10,'es','app','cache','Cache','2026-08-24 03:32:52'),
(11,'es','app','audit','Auditoría','2026-08-24 03:32:52'),
(12,'es','app','logout','Cerrar sesión','2026-08-24 03:32:52'),
(13,'es','app','save','Guardar','2026-08-24 03:32:52'),
(14,'es','app','cancel','Cancelar','2026-08-24 03:32:52'),
(15,'es','app','delete','Eliminar','2026-08-24 03:32:52'),
(16,'es','app','edit','Editar','2026-08-24 03:32:52'),
(17,'es','app','create','Crear','2026-08-24 03:32:52'),
(18,'es','app','welcome','Bienvenido','2026-08-24 03:32:52'),
(19,'es','app','login','Iniciar sesión','2026-08-24 03:32:52'),
(20,'es','app','profile','Mi perfil','2026-08-24 03:32:52'),
(21,'es','app','notifications','Notificaciones','2026-08-24 03:32:52'),
(22,'es','app','no_notifications','No tienes notificaciones','2026-08-24 03:32:52'),
(23,'es','app','mark_all_read','Marcar todo como leído','2026-08-24 03:32:52'),
(24,'es','app','view_all','Ver todas','2026-08-24 03:32:52'),
(25,'es','app','saved_successfully','Guardado correctamente','2026-08-24 03:32:52'),
(26,'es','app','error_saving','Error al guardar','2026-08-24 03:32:52'),
(27,'es','app','confirm_delete','¿Estás seguro de eliminar?','2026-08-24 03:32:52'),
(28,'es','app','yes','Sí','2026-08-24 03:32:52'),
(29,'es','app','no','No','2026-08-24 03:32:52'),
(30,'es','app','actions','Acciones','2026-08-24 03:32:52'),
(31,'es','app','status','Estado','2026-08-24 03:32:52'),
(32,'es','app','published','Publicada','2026-08-24 03:32:52'),
(33,'es','app','draft','Borrador','2026-08-24 03:32:52'),
(34,'es','app','archived','Archivada','2026-08-24 03:32:52'),
(35,'es','app','title','Título','2026-08-24 03:32:52'),
(36,'es','app','description','Descripción','2026-08-24 03:32:52'),
(37,'es','app','slug','Slug','2026-08-24 03:32:52'),
(38,'es','app','author','Autor','2026-08-24 03:32:52'),
(39,'es','app','created_at','Creado','2026-08-24 03:32:52'),
(40,'es','app','updated_at','Actualizado','2026-08-24 03:32:52'),
(41,'es','app','search','Buscar','2026-08-24 03:32:52'),
(42,'es','app','filter','Filtrar','2026-08-24 03:32:52'),
(43,'es','app','all','Todos','2026-08-24 03:32:52'),
(44,'es','auth','username','Usuario','2026-08-24 03:32:52'),
(45,'es','auth','password','Contraseña','2026-08-24 03:32:52'),
(46,'es','auth','email','Correo electrónico','2026-08-24 03:32:52'),
(47,'es','auth','remember_me','Recuérdame','2026-08-24 03:32:52'),
(48,'es','auth','forgot_password','¿Olvidaste tu contraseña?','2026-08-24 03:32:52'),
(49,'es','auth','invalid_credentials','Credenciales inválidas','2026-08-24 03:32:52'),
(50,'es','auth','must_login','Debes iniciar sesión','2026-08-24 03:32:52'),
(51,'es','profile','full_name','Nombre completo','2026-08-24 03:32:52'),
(52,'es','profile','bio','Biografía','2026-08-24 03:32:52'),
(53,'es','profile','avatar','Avatar','2026-08-24 03:32:52'),
(54,'es','profile','change_password','Cambiar contraseña','2026-08-24 03:32:52'),
(55,'es','profile','current_password','Contraseña actual','2026-08-24 03:32:52'),
(56,'es','profile','new_password','Nueva contraseña','2026-08-24 03:32:52'),
(57,'es','profile','confirm_password','Confirmar contraseña','2026-08-24 03:32:52'),
(58,'es','profile','preferences','Preferencias','2026-08-24 03:32:52'),
(59,'es','profile','language','Idioma','2026-08-24 03:32:52'),
(60,'es','profile','theme','Tema','2026-08-24 03:32:52'),
(61,'es','profile','timezone','Zona horaria','2026-08-24 03:32:52'),
(62,'en','app','dashboard','Dashboard','2026-08-24 03:32:52'),
(63,'en','app','users','Users','2026-08-24 03:32:52'),
(64,'en','app','pages','Pages','2026-08-24 03:32:52'),
(65,'en','app','templates','Templates','2026-08-24 03:32:52'),
(66,'en','app','menus','Menus','2026-08-24 03:32:52'),
(67,'en','app','files','Files','2026-08-24 03:32:52'),
(68,'en','app','roles','Roles & Permissions','2026-08-24 03:32:52'),
(69,'en','app','settings','Settings','2026-08-24 03:32:52'),
(70,'en','app','seo','SEO','2026-08-24 03:32:52'),
(71,'en','app','cache','Cache','2026-08-24 03:32:52'),
(72,'en','app','audit','Audit','2026-08-24 03:32:52'),
(73,'en','app','logout','Logout','2026-08-24 03:32:52'),
(74,'en','app','save','Save','2026-08-24 03:32:52'),
(75,'en','app','cancel','Cancel','2026-08-24 03:32:52'),
(76,'en','app','delete','Delete','2026-08-24 03:32:52'),
(77,'en','app','edit','Edit','2026-08-24 03:32:52'),
(78,'en','app','create','Create','2026-08-24 03:32:52'),
(79,'en','app','welcome','Welcome','2026-08-24 03:32:52'),
(80,'en','app','login','Login','2026-08-24 03:32:52'),
(81,'en','app','profile','My profile','2026-08-24 03:32:52'),
(82,'en','app','notifications','Notifications','2026-08-24 03:32:52'),
(83,'en','app','no_notifications','No notifications','2026-08-24 03:32:52'),
(84,'en','app','mark_all_read','Mark all as read','2026-08-24 03:32:52'),
(85,'en','app','view_all','View all','2026-08-24 03:32:52'),
(86,'en','app','saved_successfully','Saved successfully','2026-08-24 03:32:52'),
(87,'en','app','error_saving','Error saving','2026-08-24 03:32:52'),
(88,'en','app','confirm_delete','Are you sure you want to delete?','2026-08-24 03:32:52'),
(89,'en','app','yes','Yes','2026-08-24 03:32:52'),
(90,'en','app','no','No','2026-08-24 03:32:52'),
(91,'en','app','actions','Actions','2026-08-24 03:32:52'),
(92,'en','app','status','Status','2026-08-24 03:32:52'),
(93,'en','app','published','Published','2026-08-24 03:32:52'),
(94,'en','app','draft','Draft','2026-08-24 03:32:52'),
(95,'en','app','archived','Archived','2026-08-24 03:32:52'),
(96,'en','app','title','Title','2026-08-24 03:32:52'),
(97,'en','app','description','Description','2026-08-24 03:32:52'),
(98,'en','app','slug','Slug','2026-08-24 03:32:52'),
(99,'en','app','author','Author','2026-08-24 03:32:52'),
(100,'en','app','created_at','Created','2026-08-24 03:32:52'),
(101,'en','app','updated_at','Updated','2026-08-24 03:32:52'),
(102,'en','app','search','Search','2026-08-24 03:32:52'),
(103,'en','app','filter','Filter','2026-08-24 03:32:52'),
(104,'en','app','all','All','2026-08-24 03:32:52'),
(105,'en','auth','username','Username','2026-08-24 03:32:52'),
(106,'en','auth','password','Password','2026-08-24 03:32:52'),
(107,'en','auth','email','Email','2026-08-24 03:32:52'),
(108,'en','auth','remember_me','Remember me','2026-08-24 03:32:52'),
(109,'en','auth','forgot_password','Forgot your password?','2026-08-24 03:32:52'),
(110,'en','auth','invalid_credentials','Invalid credentials','2026-08-24 03:32:52'),
(111,'en','auth','must_login','You must log in','2026-08-24 03:32:52'),
(112,'en','profile','full_name','Full name','2026-08-24 03:32:52'),
(113,'en','profile','bio','Biography','2026-08-24 03:32:52'),
(114,'en','profile','avatar','Avatar','2026-08-24 03:32:52'),
(115,'en','profile','change_password','Change password','2026-08-24 03:32:52'),
(116,'en','profile','current_password','Current password','2026-08-24 03:32:52'),
(117,'en','profile','new_password','New password','2026-08-24 03:32:52'),
(118,'en','profile','confirm_password','Confirm password','2026-08-24 03:32:52'),
(119,'en','profile','preferences','Preferences','2026-08-24 03:32:52'),
(120,'en','profile','language','Language','2026-08-24 03:32:52'),
(121,'en','profile','theme','Theme','2026-08-24 03:32:52'),
(122,'en','profile','timezone','Timezone','2026-08-24 03:32:52');
/*!40000 ALTER TABLE `translations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `trusted_devices`
--

DROP TABLE IF EXISTS `trusted_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trusted_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_hash` varchar(128) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `remember_token` varchar(128) NOT NULL,
  `last_heartbeat` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime NOT NULL,
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_device` (`user_id`,`device_hash`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_trusted_devices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trusted_devices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `trusted_devices` WRITE;
/*!40000 ALTER TABLE `trusted_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `trusted_devices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `two_factor_recovery_codes`
--

DROP TABLE IF EXISTS `two_factor_recovery_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `two_factor_recovery_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `used_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_used` (`user_id`,`used_at`),
  KEY `idx_recovery_available` (`user_id`,`used_at`),
  CONSTRAINT `fk_2fa_recovery_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `two_factor_recovery_codes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `two_factor_recovery_codes` WRITE;
/*!40000 ALTER TABLE `two_factor_recovery_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `two_factor_recovery_codes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_activities`
--

DROP TABLE IF EXISTS `user_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_activities` WRITE;
/*!40000 ALTER TABLE `user_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_activities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `user_activity_summary`
--

DROP TABLE IF EXISTS `user_activity_summary`;
/*!50001 DROP VIEW IF EXISTS `user_activity_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `user_activity_summary` AS SELECT
 NULL AS `id`,
 NULL AS `username`,
 NULL AS `role`,
 NULL AS `active_days`,
 NULL AS `total_logins`,
 NULL AS `last_login`,
 NULL AS `first_login`,
 NULL AS `avg_session_duration` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user_logins`
--

DROP TABLE IF EXISTS `user_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_date` date NOT NULL,
  `login_time` time NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_success` tinyint(1) NOT NULL DEFAULT 1,
  `logout_time` datetime DEFAULT NULL,
  `session_duration` int(11) DEFAULT NULL COMMENT 'Duración en segundos',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_login_date` (`login_date`),
  KEY `idx_login_success` (`login_success`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_user_logins_success_date` (`login_success`,`login_date`),
  CONSTRAINT `fk_user_logins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_logins`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_logins` WRITE;
/*!40000 ALTER TABLE `user_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_logins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `bio` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `locale` varchar(10) NOT NULL DEFAULT 'es',
  `theme` varchar(20) NOT NULL DEFAULT 'dark',
  `timezone` varchar(50) NOT NULL DEFAULT 'Europe/Madrid',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `role` enum('Super admin','Admin','Manager','Stantard User','Guest') DEFAULT 'Guest',
  `role_id` int(11) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `is_banned` tinyint(1) DEFAULT 0,
  `banned_reason` text DEFAULT NULL,
  `banned_by` int(11) DEFAULT NULL,
  `banned_at` datetime DEFAULT NULL,
  `ban_expires_at` datetime DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_expires` datetime DEFAULT NULL,
  `recovery_token` varchar(64) DEFAULT NULL,
  `recovery_expires` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(32) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `last_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_recovery` (`recovery_token`,`recovery_expires`),
  KEY `idx_users_remember` (`remember_token`,`remember_expires`),
  KEY `idx_users_role_active` (`role`,`is_active`),
  KEY `idx_users_created_role` (`created_at`,`role`),
  KEY `idx_users_role_id` (`role_id`),
  KEY `idx_users_banned` (`is_banned`,`banned_at`),
  KEY `fk_users_banned_by` (`banned_by`),
  KEY `idx_users_locale` (`locale`),
  CONSTRAINT `fk_users_banned_by` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `users_roles` (`idRol`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, request_method, request_url, new_data)
    VALUES (
        NEW.id,
        'user_created',
        CONCAT('Usuario creado: ', NEW.username),
        (SELECT ip_address FROM user_logins WHERE user_id = NEW.id ORDER BY created_at DESC LIMIT 1),
        NULL,
        'POST',
        '/admin/users/create',
        JSON_OBJECT('username', NEW.username, 'email', NEW.email, 'role', NEW.role)
    );
END 
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE TRIGGER `after_user_update` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF OLD.username != NEW.username OR OLD.email != NEW.email OR OLD.role != NEW.role THEN
        INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, request_method, request_url, old_data, new_data)
        VALUES (
            NEW.id,
            'user_updated',
            CONCAT('Usuario actualizado: ', NEW.username),
            NULL,
            NULL,
            'PUT',
            '/admin/users/update',
            JSON_OBJECT('username', OLD.username, 'email', OLD.email, 'role', OLD.role),
            JSON_OBJECT('username', NEW.username, 'email', NEW.email, 'role', NEW.role)
        );
    END IF;
END 
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE TRIGGER `before_user_delete` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
    INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, request_method, request_url, old_data)
    VALUES (
        OLD.id,
        'user_deleted',
        CONCAT('Usuario eliminado: ', OLD.username),
        NULL,
        NULL,
        'DELETE',
        '/admin/users/delete',
        JSON_OBJECT('username', OLD.username, 'email', OLD.email, 'role', OLD.role, 'id', OLD.id)
    );
END 
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `users_permissions`
--

DROP TABLE IF EXISTS `users_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_permissions` (
  `idPer` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'General',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idPer`) USING BTREE,
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `id_UNIQUE` (`idPer`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users_permissions` WRITE;
/*!40000 ALTER TABLE `users_permissions` DISABLE KEYS */;
INSERT INTO `users_permissions` VALUES
(1,'Verify Users','Administration permission allowing for the verification of new users','Users',1),
(2,'Delete Unverified Users','Administration permission allowing the deletion of unverified users','Users',1),
(3,'Ban Users','Moderation permission allowing the banning of users','Users',1),
(4,'Assign Roles to Users','Administration permission allowing the assignment of roles to users','Users',1),
(5,'Assign Users to Roles','Administration permission allowing the assignment of users to roles','Roles',1),
(6,'Create Roles','Administration permission allowing for the creation of new roles','Roles',1),
(7,'Delete Roles','Administration permission allowing for the deletion of roles','Roles',1),
(8,'Create Permissions','Administration permission allowing for the creation of new permissions','Permissions',1),
(9,'Delete Permissions','Administration permission allowing for the deletion of permissions','Permissions',1),
(10,'Assign Permissions to Roles','Administration permission allowing the assignment of permissions to roles','Roles',1),
(11,'Edit Site Config','Administration permission allowing the editing of core site configuration (dangerous)','Administration',1),
(12,'View Permissions','Administration permission allowing the viewing of all permissions','Permissions',1),
(13,'View Roles','Administration permission allowing for the viewing of all roles','Roles',1),
(14,'View Users','Administration permission allowing for the viewing of all users','Users',1),
(15,'Delete Users','Administration permission allowing for the deletion of users','Users',1),
(16,'pages.view','Ver páginas','Pages',1),
(17,'pages.create','Crear páginas','Pages',1),
(18,'pages.edit','Editar páginas','Pages',1),
(19,'pages.delete','Eliminar páginas','Pages',1),
(20,'pages.publish','Publicar/despublicar páginas','Pages',1),
(21,'pages.restore','Restaurar versiones','Pages',1),
(22,'templates.view','Ver templates','Templates',1),
(23,'templates.create','Crear templates','Templates',1),
(24,'templates.edit','Editar templates','Templates',1),
(25,'templates.delete','Eliminar templates','Templates',1),
(26,'menus.manage','Gestionar menús','Menus',1),
(27,'seo.manage','Gestionar SEO','SEO',1),
(28,'cache.manage','Gestionar cache','Cache',1),
(29,'settings.manage','Gestionar configuración','Settings',1),
(30,'files.manage','Gestionar archivos','Files',1),
(31,'audit.view','Ver auditoría','Audit',1),
(32,'redirects.manage','Gestionar redirecciones','SEO',1),
(49,'files.view','Ver archivos','Files',1),
(50,'files.upload','Subir archivos','Files',1),
(51,'files.delete','Eliminar archivos','Files',1),
(52,'files.share','Compartir archivos','Files',1),
(53,'roles.manage','Gestionar roles','Roles',1),
(54,'2fa.manage','Configurar 2FA propio','Security',1),
(55,'profile.edit','Editar perfil propio','Profile',1),
(56,'notifications.manage','Gestionar notificaciones','Notifications',1),
(57,'api.manage','Gestionar API tokens','API',1),
(58,'components.manage','Gestionar componentes','Components',1),
(59,'translations.manage','Gestionar traducciones','i18n',1),
(60,'backups.manage','Gestionar backups','Backups',1),
(61,'backups.restore','Restaurar backups','Backups',1),
(62,'analytics.view','Ver métricas avanzadas','Analytics',1),
(63,'reports.manage','Gestionar reportes','Analytics',1),
(65,'seo.global','Gestionar SEO global del sitio','SEO',1),
(67,'security.dashboard','Ver dashboard de seguridad','Security',1),
(68,'security.ban_users','Banear/desbanear usuarios','Security',1),
(69,'users.ban','Banear y desbanear usuarios','Users',1),
(70,'users.ban.permanent','Banear usuarios permanentemente','Users',1),
(71,'users.view.ban_history','Ver historial de baneos','Users',1),
(72,'users.view','Ver usuarios','Users',1),
(73,'users.create','Crear usuarios','Users',1),
(74,'users.edit','Editar usuarios','Users',1),
(75,'users.delete','Eliminar usuarios','Users',1),
(103,'2fa.recovery.manage','Gestionar códigos de recuperación 2FA','Security',1),
(104,'trusted_devices.manage','Gestionar dispositivos de confianza','Security',1),
(105,'collaboration.use','Usar colaboración en tiempo real','Collaboration',1),
(106,'comments.view','Ver comentarios','Comments',1),
(107,'comments.create','Crear comentarios','Comments',1),
(108,'comments.resolve','Resolver comentarios','Comments',1),
(109,'comments.delete','Eliminar comentarios','Comments',1);
/*!40000 ALTER TABLE `users_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users_role_permissions`
--

DROP TABLE IF EXISTS `users_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_role_permissions` (
  `idRp` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`idRp`) USING BTREE,
  KEY `role_id` (`role_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `user_permissions_FK` FOREIGN KEY (`permission_id`) REFERENCES `users_permissions` (`idPer`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_roles_FK` FOREIGN KEY (`role_id`) REFERENCES `users_roles` (`idRol`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_role_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users_role_permissions` WRITE;
/*!40000 ALTER TABLE `users_role_permissions` DISABLE KEYS */;
INSERT INTO `users_role_permissions` VALUES
(1,1,1),
(2,1,2),
(3,1,3),
(4,1,4),
(5,1,5),
(6,1,6),
(7,1,7),
(8,1,8),
(9,1,9),
(10,1,10),
(11,1,11),
(12,1,12),
(13,1,13),
(14,1,14),
(15,1,15),
(16,2,1),
(17,2,2),
(18,2,3),
(19,2,4),
(20,2,5),
(21,2,12),
(22,2,13),
(23,2,14),
(24,2,15),
(25,3,12),
(27,3,13),
(28,3,14),
(29,1,31),
(30,1,28),
(31,1,30),
(32,1,26),
(33,1,17),
(34,1,19),
(35,1,18),
(36,1,20),
(37,1,21),
(38,1,16),
(39,1,27),
(40,1,29),
(41,1,23),
(42,1,25),
(43,1,24),
(44,1,22),
(60,2,31),
(61,2,28),
(62,2,30),
(63,2,26),
(64,2,17),
(65,2,19),
(66,2,18),
(67,2,20),
(68,2,21),
(69,2,16),
(70,2,27),
(71,2,23),
(72,2,24),
(73,2,22),
(75,1,31),
(76,1,28),
(77,1,30),
(78,1,26),
(79,1,17),
(80,1,19),
(81,1,18),
(82,1,20),
(83,1,21),
(84,1,16),
(85,1,32),
(86,1,27),
(87,1,29),
(88,1,23),
(89,1,25),
(90,1,24),
(91,1,22),
(106,2,31),
(107,2,28),
(108,2,30),
(109,2,26),
(110,2,17),
(111,2,19),
(112,2,18),
(113,2,20),
(114,2,21),
(115,2,16),
(116,2,32),
(117,2,27),
(118,2,23),
(119,2,24),
(120,2,22),
(121,1,54),
(122,1,51),
(123,1,52),
(124,1,50),
(125,1,49),
(126,1,53),
(128,2,54),
(129,2,51),
(130,2,52),
(131,2,50),
(132,2,49),
(135,1,57),
(136,1,58),
(137,1,56),
(138,1,55),
(139,1,59),
(142,2,58),
(143,2,56),
(144,2,55),
(145,1,62),
(146,1,60),
(147,1,61),
(148,1,63),
(152,2,62),
(153,2,60),
(154,2,63),
(155,1,29),
(156,2,29),
(157,1,32),
(158,1,65),
(160,2,32),
(161,2,65),
(163,1,68),
(164,1,67),
(166,2,67),
(167,1,69),
(168,1,70),
(169,1,71),
(170,2,69),
(171,2,71),
(173,1,1),
(174,1,2),
(175,1,3),
(176,1,4),
(177,1,5),
(178,1,6),
(179,1,7),
(180,1,8),
(181,1,9),
(182,1,10),
(183,1,11),
(184,1,12),
(185,1,13),
(186,1,14),
(187,1,15),
(188,1,16),
(189,1,17),
(190,1,18),
(191,1,19),
(192,1,20),
(193,1,21),
(194,1,22),
(195,1,23),
(196,1,24),
(197,1,25),
(198,1,26),
(199,1,27),
(200,1,28),
(201,1,29),
(202,1,30),
(203,1,31),
(204,1,32),
(205,1,49),
(206,1,50),
(207,1,51),
(208,1,52),
(209,1,53),
(210,1,54),
(211,1,55),
(212,1,56),
(213,1,57),
(214,1,58),
(215,1,59),
(216,1,60),
(217,1,61),
(218,1,62),
(219,1,63),
(220,1,65),
(221,1,67),
(222,1,68),
(223,1,69),
(224,1,70),
(225,1,71),
(226,1,72),
(227,1,73),
(228,1,74),
(229,1,75),
(236,2,54),
(237,2,62),
(238,2,57),
(239,2,10),
(240,2,4),
(241,2,5),
(242,2,31),
(243,2,60),
(244,2,61),
(245,2,3),
(246,2,28),
(247,2,58),
(248,2,8),
(249,2,6),
(250,2,9),
(251,2,7),
(252,2,2),
(253,2,15),
(254,2,11),
(255,2,51),
(256,2,30),
(257,2,52),
(258,2,50),
(259,2,49),
(260,2,26),
(261,2,56),
(262,2,17),
(263,2,19),
(264,2,18),
(265,2,20),
(266,2,21),
(267,2,16),
(268,2,55),
(269,2,32),
(270,2,63),
(271,2,53),
(272,2,68),
(273,2,67),
(274,2,65),
(275,2,27),
(276,2,23),
(277,2,25),
(278,2,24),
(279,2,22),
(280,2,59),
(281,2,69),
(282,2,70),
(283,2,73),
(284,2,75),
(285,2,74),
(286,2,72),
(287,2,71),
(288,2,1),
(289,2,12),
(290,2,13),
(291,2,14),
(299,3,58),
(300,3,30),
(301,3,50),
(302,3,49),
(303,3,26),
(304,3,17),
(305,3,18),
(306,3,16),
(307,3,27),
(308,3,23),
(309,3,24),
(310,3,22),
(311,3,12),
(312,3,13),
(313,3,14),
(314,1,103),
(315,1,104),
(316,1,105),
(317,2,105),
(318,1,107),
(319,1,109),
(320,1,108),
(321,1,106),
(325,2,107),
(326,2,109),
(327,2,108),
(328,2,106);
/*!40000 ALTER TABLE `users_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users_roles`
--

DROP TABLE IF EXISTS `users_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_roles` (
  `idRol` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `default_role` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`idRol`) USING BTREE,
  UNIQUE KEY `name_UNIQUE` (`name`) USING BTREE,
  UNIQUE KEY `default_role_UNIQUE` (`default_role`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users_roles` WRITE;
/*!40000 ALTER TABLE `users_roles` DISABLE KEYS */;
INSERT INTO `users_roles` VALUES
(1,'Super Admin','Master administrator of site',1,9),
(2,'Admin','Site administrator',1,5),
(3,'Manager','Manager content',1,3),
(4,'Stantard User','Default site role for standard users',1,1),
(5,'Guest','Guest visit',0,0);
/*!40000 ALTER TABLE `users_roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `visit_date` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `visits` WRITE;
/*!40000 ALTER TABLE `visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `visits` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping routines for database 'pepiuox_sqlfix_211038_b06db5'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP FUNCTION IF EXISTS `get_active_users_count` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE FUNCTION `get_active_users_count`(`days` INT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE active_count INT;
    SELECT COUNT(DISTINCT user_id) INTO active_count
    FROM user_logins
    WHERE login_date >= DATE_SUB(CURDATE(), INTERVAL days DAY)
    AND login_success = 1;
    RETURN active_count;
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP FUNCTION IF EXISTS `get_retention_rate` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE FUNCTION `get_retention_rate`(`days` INT) RETURNS decimal(5,2)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE retention_rate DECIMAL(5,2);
    SELECT 
        ROUND(
            (SELECT COUNT(DISTINCT user_id) 
             FROM user_logins 
             WHERE login_date >= DATE_SUB(CURDATE(), INTERVAL days DAY)
             AND login_success = 1) / 
            NULLIF((SELECT COUNT(*) FROM users WHERE created_at <= DATE_SUB(CURDATE(), INTERVAL days DAY)), 0) * 100, 2
        ) INTO retention_rate;
    RETURN retention_rate;
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `cleanup_old_sessions` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `cleanup_old_sessions`()
BEGIN
  DELETE FROM user_sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR));
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `compute_analytics` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `compute_analytics`(IN `p_days` INT)
BEGIN
  DECLARE start_date DATE;
  SET start_date = DATE_SUB(CURDATE(), INTERVAL p_days DAY);

  
  SELECT
    DATE(created_at) AS day,
    event_type,
    COUNT(*) AS total
  FROM analytics_events
  WHERE created_at >= start_date
  GROUP BY DATE(created_at), event_type
  ORDER BY day DESC, event_type;

  
  SELECT
    p.id, p.title, p.slug,
    COUNT(v.id) AS visits,
    COUNT(DISTINCT v.ip_address) AS unique_visitors
  FROM pages p
  LEFT JOIN visits v ON v.page_id = p.id AND v.visit_date >= start_date
  WHERE p.status = 'published'
  GROUP BY p.id
  ORDER BY visits DESC
  LIMIT 20;

  
  SELECT
    ip_address,
    COUNT(*) AS attempts,
    MAX(attempt_time) AS last_attempt
  FROM login_attempts
  WHERE attempt_time >= start_date AND successful = 0
  GROUP BY ip_address
  HAVING attempts >= 3
  ORDER BY attempts DESC
  LIMIT 20;
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `update_daily_stats` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `update_daily_stats`()
BEGIN
    DECLARE today_date DATE;
    SET today_date = CURDATE();
    
    INSERT INTO daily_stats (stat_date, total_users, new_users, active_users, total_logins, unique_logins, failed_logins)
    VALUES (
        today_date,
        (SELECT COUNT(*) FROM users),
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = today_date),
        (SELECT COUNT(DISTINCT user_id) FROM user_logins WHERE login_date >= DATE_SUB(today_date, INTERVAL 30 DAY)),
        (SELECT COUNT(*) FROM user_logins WHERE login_date = today_date AND login_success = 1),
        (SELECT COUNT(DISTINCT user_id) FROM user_logins WHERE login_date = today_date AND login_success = 1),
        (SELECT COUNT(*) FROM user_logins WHERE login_date = today_date AND login_success = 0)
    )
    ON DUPLICATE KEY UPDATE
        total_users = VALUES(total_users),
        new_users = VALUES(new_users),
        active_users = VALUES(active_users),
        total_logins = VALUES(total_logins),
        unique_logins = VALUES(unique_logins),
        failed_logins = VALUES(failed_logins);
END
;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `daily_summary`
--

/*!50001 DROP VIEW IF EXISTS `daily_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `daily_summary` AS select cast(`users`.`created_at` as date) AS `date`,count(0) AS `total_registrations`,count(case when `users`.`role` = 'Admin' then 1 end) AS `admin_registrations`,count(case when `users`.`role` = 'Manager' then 1 end) AS `manager_registrations`,count(case when `users`.`role` = 'Stantard User' then 1 end) AS `editor_registrations`,count(case when `users`.`role` = 'Guest' then 1 end) AS `guest_registrations` from `users` group by cast(`users`.`created_at` as date) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `user_activity_summary`
--

/*!50001 DROP VIEW IF EXISTS `user_activity_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_uca1400_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `user_activity_summary` AS select `u`.`id` AS `id`,`u`.`username` AS `username`,`u`.`role` AS `role`,count(distinct cast(`l`.`login_date` as date)) AS `active_days`,count(`l`.`id`) AS `total_logins`,max(`l`.`login_date`) AS `last_login`,min(`l`.`login_date`) AS `first_login`,avg(`l`.`session_duration`) AS `avg_session_duration` from (`users` `u` left join `user_logins` `l` on(`u`.`id` = `l`.`user_id` and `l`.`login_success` = 1)) group by `u`.`id`,`u`.`username`,`u`.`role` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-02 16:10:41
