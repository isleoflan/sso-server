/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS `iol_sso` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `iol_sso`;

CREATE TABLE IF NOT EXISTS `app` (
    `id` char(36) DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `base_url` varchar(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `app` (`id`, `title`, `description`, `base_url`) VALUES
    ('e9fca7d0-b02d-40bd-bad8-3fb3c76b9096', 'Test App', '123', 'http://sso.isleoflan.ch');
/*!40000 ALTER TABLE `app` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `global_session` (
    `id` char(36) NOT NULL,
    `user_id` char(36) DEFAULT NULL,
    `created` datetime(6) DEFAULT NULL,
    `expiration` datetime(6) DEFAULT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `intermediate_token` (
    `user_id` char(36) NOT NULL,
    `app_id` char(36) NOT NULL,
    `token` varchar(255) DEFAULT NULL,
    `expiration` datetime(6) NOT NULL,
    PRIMARY KEY (`user_id`,`app_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_request` (
    `id` char(36) NOT NULL,
    `app_id` char(36) NOT NULL,
    `redirect_url` varchar(255) NOT NULL,
    `scope` int(11) DEFAULT 0,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user` (
    `id` char(36) NOT NULL,
    `username` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `activated` datetime(6) DEFAULT NULL,
    `blocked` datetime(6) DEFAULT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` (`id`, `username`, `password`, `activated`, `blocked`) VALUES
    ('78eb93bf-079c-4dc4-868c-f3d0271950c2', 'stui', '$2y$10$MG53lQAjNLrwSXMgMipYsOD9fm3wdYZSlnbgyVqncU.t88jidGIh2', '2021-09-23 19:48:05.000000', NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `user_scope` (
    `user_id` char(36) NOT NULL,
    `scope` bigint(20) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
