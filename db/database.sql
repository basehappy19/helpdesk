-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.6 - MySQL Community Server - GPL
-- Server OS:                    Linux
-- HeidiSQL Version:             12.12.0.7122
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for help_desk
CREATE DATABASE IF NOT EXISTS `help_desk` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `help_desk`;

-- Dumping structure for table help_desk.agents
CREATE TABLE IF NOT EXISTS `agents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `display_th` varchar(255) NOT NULL,
  `phone_ext` varchar(32) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.agents: ~0 rows (approximately)

-- Dumping structure for table help_desk.buildings
CREATE TABLE IF NOT EXISTS `buildings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_th` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_th` (`name_th`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.buildings: ~0 rows (approximately)

-- Dumping structure for table help_desk.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) DEFAULT NULL,
  `name_th` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.departments: ~0 rows (approximately)

-- Dumping structure for table help_desk.issue_categories
CREATE TABLE IF NOT EXISTS `issue_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_type_id` bigint unsigned NOT NULL,
  `code` varchar(64) NOT NULL,
  `name_th` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `ix_cat_rt` (`request_type_id`),
  CONSTRAINT `fk_category_request_type` FOREIGN KEY (`request_type_id`) REFERENCES `request_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.issue_categories: ~14 rows (approximately)
INSERT INTO `issue_categories` (`id`, `request_type_id`, `code`, `name_th`, `created_at`) VALUES
	(1, 1, 'computer', 'คอมพิวเตอร์', '2025-10-10 19:56:26'),
	(2, 1, 'printer', 'เครื่องพิมพ์', '2025-10-10 19:56:26'),
	(3, 1, 'scanner', 'สแกนเนอร์', '2025-10-10 19:56:26'),
	(4, 1, 'internet', 'ระบบอินเตอร์เน็ต', '2025-10-10 19:56:26'),
	(5, 1, 'network', 'ระบบเครือข่าย', '2025-10-10 19:56:26'),
	(6, 1, 'install_computer', 'ติดตั้งเครื่องคอมพิวเตอร์', '2025-10-10 19:56:26'),
	(7, 1, 'install_printer', 'ติดตั้งเครื่องพิมพ์', '2025-10-10 19:56:26'),
	(8, 1, 'relocate_computer', 'ย้ายจุดติดตั้งเครื่องคอมพิวเตอร์', '2025-10-10 19:56:26'),
	(9, 2, 'printer_bw_laser', 'เครื่องพิมพ์เลเซอร์ขาวดำ', '2025-10-10 19:56:26'),
	(10, 2, 'printer_bw_multi', 'เครื่องพิมพ์มัลติฟังก์ชั่นขาวดำ', '2025-10-10 19:56:26'),
	(11, 2, 'printer_color_laser', 'เครื่องพิมพ์เลเซอร์สี', '2025-10-10 19:56:26'),
	(12, 2, 'printer_dotmatrix', 'เครื่องพิมพ์ Dotmatrix', '2025-10-10 19:56:26'),
	(13, 4, 'hosxp_software_issue', 'ปัญหาโปรแกรม HOSxP', '2025-10-10 19:56:26'),
	(14, 4, 'office_software_issue', 'ปัญหาโปรแกรมสำนักงาน', '2025-10-10 19:56:26');

-- Dumping structure for table help_desk.issue_symptoms
CREATE TABLE IF NOT EXISTS `issue_symptoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `name_th` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_symptom` (`category_id`,`name_th`),
  CONSTRAINT `fk_symptom_category` FOREIGN KEY (`category_id`) REFERENCES `issue_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.issue_symptoms: ~21 rows (approximately)
INSERT INTO `issue_symptoms` (`id`, `category_id`, `name_th`, `created_at`) VALUES
	(1, 1, 'เครื่องค้าง', '2025-10-10 20:06:01'),
	(2, 2, 'กระดาษติด', '2025-10-10 20:06:01'),
	(3, 3, 'สแกนเอกสารไม่ได้', '2025-10-10 20:06:01'),
	(4, 4, 'เข้าใช้เน็ตไม่ได้', '2025-10-10 20:06:01'),
	(5, 5, 'เข้าใช้เครือข่าย LAN ไม่ได้', '2025-10-10 20:06:01'),
	(6, 9, 'ตลับหมึกเลเซอร์ขาวดำ 052H', '2025-10-10 20:06:01'),
	(7, 10, 'ตลับหมึกเลเซอร์ขาวดำ 052H', '2025-10-10 20:06:01'),
	(8, 11, 'ตลับหมึกเลเซอร์สีดำ 046BK', '2025-10-10 20:06:01'),
	(9, 11, 'ตลับหมึกเลเซอร์สีแดง 046M', '2025-10-10 20:06:01'),
	(10, 11, 'ตลับหมึกเลเซอร์สีเหลือง 046Y', '2025-10-10 20:06:01'),
	(11, 11, 'ตลับหมึกเลเซอร์สีฟ้า 046C', '2025-10-10 20:06:01'),
	(12, 12, 'ผ้าหมึก LQ-590', '2025-10-10 20:06:01'),
	(13, 13, 'เชื่อมต่อฐานข้อมูลไม่ได้', '2025-10-10 20:06:01'),
	(14, 13, 'พิมพ์ใบนัดไม่ได้', '2025-10-10 20:06:01'),
	(15, 13, 'พิมพ์ใบรับรองแพทย์ไม่ได้', '2025-10-10 20:06:01'),
	(16, 13, 'ข้อมูลที่บันทึกในระบบผิดพลาด', '2025-10-10 20:06:02'),
	(17, 14, 'เข้าใช้งานโปรแกรมไม่ได้', '2025-10-10 20:06:02'),
	(18, 14, 'โปรแกรมหมดอายุ', '2025-10-10 20:06:02'),
	(19, 14, 'เชื่อมต่อฐานข้อมูลไม่ได้', '2025-10-10 20:06:02'),
	(20, 14, 'พิมพ์ใบนัดไม่ได้', '2025-10-10 20:06:02'),
	(21, 14, 'พิมพ์ใบรับรองแพทย์ไม่ได้', '2025-10-10 20:06:02');

-- Dumping structure for table help_desk.request_types
CREATE TABLE IF NOT EXISTS `request_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `name_th` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.request_types: ~4 rows (approximately)
INSERT INTO `request_types` (`id`, `code`, `name_th`, `created_at`) VALUES
	(1, 'install_move_device', 'ติดตั้ง/ย้ายอุปกรณ์', '2025-10-10 19:09:00'),
	(2, 'replace_printer_ink', 'เปลี่ยนหมึกพิมพ์', '2025-10-10 19:09:00'),
	(3, 'add_network_point', 'ขอเพิ่มจุดบริการเครือข่าย LAN/Wifi', '2025-10-10 19:09:00'),
	(4, 'software_issue', 'ปัญหาโปรแกรม', '2025-10-10 19:09:00');

-- Dumping structure for table help_desk.service_points
CREATE TABLE IF NOT EXISTS `service_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `building_id` bigint unsigned NOT NULL,
  `floor_label` varchar(32) NOT NULL,
  `name_th` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_point` (`building_id`,`floor_label`,`name_th`),
  CONSTRAINT `fk_sp_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.service_points: ~0 rows (approximately)

-- Dumping structure for table help_desk.tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `request_type_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `symptom_id` bigint unsigned DEFAULT NULL,
  `status_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `building_id` bigint unsigned NOT NULL,
  `service_point_id` bigint unsigned NOT NULL,
  `phone_ext` varchar(32) NOT NULL,
  `reporter_name_th` varchar(255) NOT NULL,
  `description_th` text,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `assigned_agent_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `ix_ticket_created` (`created_at`),
  KEY `ix_ticket_status` (`status_id`,`created_at`),
  KEY `ix_ticket_category` (`category_id`,`symptom_id`),
  KEY `ix_ticket_location` (`building_id`,`service_point_id`),
  KEY `fk_ticket_reqtype` (`request_type_id`),
  KEY `fk_ticket_symptom` (`symptom_id`),
  KEY `fk_ticket_dept` (`department_id`),
  KEY `fk_ticket_sp` (`service_point_id`),
  KEY `fk_ticket_agent` (`assigned_agent_id`),
  CONSTRAINT `fk_ticket_agent` FOREIGN KEY (`assigned_agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_build` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_category` FOREIGN KEY (`category_id`) REFERENCES `issue_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_reqtype` FOREIGN KEY (`request_type_id`) REFERENCES `request_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_sp` FOREIGN KEY (`service_point_id`) REFERENCES `service_points` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_status` FOREIGN KEY (`status_id`) REFERENCES `ticket_statuses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_symptom` FOREIGN KEY (`symptom_id`) REFERENCES `issue_symptoms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.tickets: ~0 rows (approximately)

-- Dumping structure for table help_desk.ticket_attachments
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(128) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_att_ticket` (`ticket_id`,`uploaded_at`),
  CONSTRAINT `fk_att_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.ticket_attachments: ~0 rows (approximately)

-- Dumping structure for table help_desk.ticket_comments
CREATE TABLE IF NOT EXISTS `ticket_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `message_th` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_cmt_ticket` (`ticket_id`,`created_at`),
  KEY `fk_cmt_author` (`author_id`),
  CONSTRAINT `fk_cmt_author` FOREIGN KEY (`author_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cmt_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.ticket_comments: ~0 rows (approximately)

-- Dumping structure for table help_desk.ticket_statuses
CREATE TABLE IF NOT EXISTS `ticket_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `name_th` varchar(255) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.ticket_statuses: ~0 rows (approximately)

-- Dumping structure for table help_desk.ticket_status_logs
CREATE TABLE IF NOT EXISTS `ticket_status_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `from_status` bigint unsigned DEFAULT NULL,
  `to_status` bigint unsigned NOT NULL,
  `note_th` text,
  `changed_by` bigint unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_log_ticket` (`ticket_id`,`changed_at`),
  KEY `fk_log_from` (`from_status`),
  KEY `fk_log_to` (`to_status`),
  KEY `fk_log_by` (`changed_by`),
  CONSTRAINT `fk_log_by` FOREIGN KEY (`changed_by`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_log_from` FOREIGN KEY (`from_status`) REFERENCES `ticket_statuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_log_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_log_to` FOREIGN KEY (`to_status`) REFERENCES `ticket_statuses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table help_desk.ticket_status_logs: ~0 rows (approximately)

-- Dumping structure for trigger help_desk.trg_tickets_bi_chain
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_tickets_bi_chain` BEFORE INSERT ON `tickets` FOR EACH ROW BEGIN
  DECLARE v_cat_rt BIGINT;
  DECLARE v_sym_cat BIGINT;

  IF NEW.category_id IS NOT NULL THEN
    SELECT request_type_id INTO v_cat_rt
    FROM issue_categories WHERE id = NEW.category_id;

    IF v_cat_rt IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid category: no request_type mapped';
    END IF;

    IF NEW.request_type_id IS NOT NULL AND NEW.request_type_id <> v_cat_rt THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'request_type_id mismatches category';
    END IF;

    SET NEW.request_type_id = v_cat_rt;
  END IF;

  IF NEW.symptom_id IS NOT NULL THEN
    SELECT category_id INTO v_sym_cat
    FROM issue_symptoms WHERE id = NEW.symptom_id;

    IF v_sym_cat IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid symptom';
    END IF;

    IF NEW.category_id IS NULL THEN
      SET NEW.category_id = v_sym_cat;
      SELECT request_type_id INTO v_cat_rt FROM issue_categories WHERE id = v_sym_cat;
      SET NEW.request_type_id = v_cat_rt;
    ELSEIF NEW.category_id <> v_sym_cat THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'symptom does not belong to the given category';
    END IF;
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger help_desk.trg_tickets_bu_chain
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `trg_tickets_bu_chain` BEFORE UPDATE ON `tickets` FOR EACH ROW BEGIN
  DECLARE v_cat_rt BIGINT;
  DECLARE v_sym_cat BIGINT;

  IF NEW.category_id IS NOT NULL AND NEW.category_id <> OLD.category_id THEN
    SELECT request_type_id INTO v_cat_rt
    FROM issue_categories WHERE id = NEW.category_id;

    IF v_cat_rt IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid category: no request_type mapped';
    END IF;

    IF NEW.request_type_id IS NOT NULL AND NEW.request_type_id <> v_cat_rt THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'request_type_id mismatches category';
    END IF;

    SET NEW.request_type_id = v_cat_rt;
  END IF;

  IF NEW.symptom_id <> OLD.symptom_id AND NEW.symptom_id IS NOT NULL THEN
    SELECT category_id INTO v_sym_cat
    FROM issue_symptoms WHERE id = NEW.symptom_id;

    IF v_sym_cat IS NULL THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid symptom';
    END IF;

    IF NEW.category_id IS NULL THEN
      SET NEW.category_id = v_sym_cat;
      SELECT request_type_id INTO v_cat_rt FROM issue_categories WHERE id = v_sym_cat;
      SET NEW.request_type_id = v_cat_rt;
    ELSEIF NEW.category_id <> v_sym_cat THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'symptom does not belong to the given category';
    END IF;
  END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
