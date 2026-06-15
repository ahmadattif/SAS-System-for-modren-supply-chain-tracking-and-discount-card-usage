-- ════════════════════════════════════════════════════════════════════
--  SENTINEL SAS v4.1 — Unified Schema, Automation & Seed Data
--  Optimized for local XAMPP execution environments (MySQL 8.0+ / MariaDB 10.4+).
--
--  Credentials & Recognition:
--  Muhammad Ahmad Atif, 4th Sem BS AI — IMSC University
-- ════════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS sentinel_sas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sentinel_sas;

SET FOREIGN_KEY_CHECKS = 0;

-- ════════════════════════════════════════════════════════════════════
--  TABLE DEFINITIONS
-- ════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS users (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username             VARCHAR(60)  NOT NULL UNIQUE,
  password_hash        VARCHAR(255) NOT NULL,
  role                 ENUM('Customer','Employee','Owner') NOT NULL,
  account_status       ENUM('Active','Suspended','Locked') NOT NULL DEFAULT 'Active',
  is_first_login       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at           DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS suppliers (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hash_prefix     CHAR(4)      NOT NULL UNIQUE,
  name            VARCHAR(120) NOT NULL,
  country         VARCHAR(60)  NOT NULL,
  units_supplied  INT UNSIGNED NOT NULL DEFAULT 0,
  defect_count    INT UNSIGNED NOT NULL DEFAULT 0,
  integrity_score DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  status          ENUM('Active','Restricted','Suspended') NOT NULL DEFAULT 'Active',
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS item_types (
  type_code     CHAR(4)      NOT NULL PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL,
  base_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL UNIQUE,
  member_suffix CHAR(4)      NOT NULL UNIQUE,
  tier          ENUM('Gold','Silver','Basic') NOT NULL DEFAULT 'Basic',
  discount      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  status        ENUM('Active','Suspended') NOT NULL DEFAULT 'Active',
  balance       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_spent   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  billing_date  DATE NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_history (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id      INT UNSIGNED  NOT NULL,
  asset_hash       CHAR(16)      NOT NULL UNIQUE,
  item_description VARCHAR(200)  NOT NULL,
  transaction_ref  VARCHAR(20)   NOT NULL UNIQUE,
  purchase_date    DATE          NOT NULL,
  net_amount       DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL UNIQUE,
  role_title   VARCHAR(80)  NOT NULL DEFAULT 'Desk Agent',
  node_id      VARCHAR(50)  NOT NULL DEFAULT 'NODE-WORKSTATION-X00',
  tx_count     INT UNSIGNED NOT NULL DEFAULT 0,
  defect_count INT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS defect_log (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_hash    CHAR(12)    NOT NULL,
  severity     ENUM('Minor','Major','Critical') NOT NULL,
  notes        TEXT,
  supplier_id  INT UNSIGNED NULL,
  employee_id  INT UNSIGNED NOT NULL,
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS flagged_hashes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_hash VARCHAR(20)  NOT NULL,
  reason     VARCHAR(300) NOT NULL,
  flagged_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS node_authorizations (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id   INT UNSIGNED NOT NULL,
  node_id       VARCHAR(50)  NOT NULL,
  authorized_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS owner_stats (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  revenue    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════════════
--  AUTOMATION LAYERS (TRIGGERS & PROCEDURES)
-- ════════════════════════════════════════════════════════════════════

DROP TRIGGER IF EXISTS TR_Defect_UpdateSupplierIntegrity;
DROP TRIGGER IF EXISTS TR_HashLog_AntiArbitrage;

DELIMITER $$

CREATE TRIGGER TR_Defect_UpdateSupplierIntegrity
AFTER INSERT ON defect_log
FOR EACH ROW
BEGIN
  DECLARE v_supplier_id   INT UNSIGNED;
  DECLARE v_units         INT UNSIGNED;
  DECLARE v_defects       INT UNSIGNED;
  DECLARE v_new_score     DECIMAL(5,2);
  DECLARE v_new_status    VARCHAR(15);

  SELECT id INTO v_supplier_id
  FROM   suppliers
  WHERE  hash_prefix = UPPER(LEFT(NEW.unit_hash, 4))
  LIMIT  1;

  IF v_supplier_id IS NOT NULL THEN
    SELECT COUNT(*) INTO v_defects
    FROM   defect_log
    WHERE  supplier_id = v_supplier_id;

    SELECT units_supplied INTO v_units
    FROM   suppliers
    WHERE  id = v_supplier_id;

    IF v_units > 0 THEN
      SET v_new_score = GREATEST(0.00, ROUND(100.00 - (v_defects / v_units * 100.00), 2));
    ELSE
      SET v_new_score = 100.00;
    END IF;

    IF v_new_score < 85.00 THEN
      SET v_new_status = 'Restricted';
    ELSE
      SET v_new_status = 'Active';
    END IF;

    UPDATE suppliers
    SET    integrity_score = v_new_score,
           status          = v_new_status
    WHERE  id = v_supplier_id;
  END IF;
END$$

CREATE TRIGGER TR_HashLog_AntiArbitrage
BEFORE INSERT ON purchase_history
FOR EACH ROW
BEGIN
  DECLARE v_existing_id INT UNSIGNED DEFAULT 0;

  SELECT id INTO v_existing_id
  FROM   purchase_history
  WHERE  asset_hash = NEW.asset_hash
  LIMIT  1;

  IF v_existing_id > 0 THEN
    INSERT INTO flagged_hashes (asset_hash, reason)
    VALUES (
      NEW.asset_hash,
      CONCAT('Duplicate hash intercept: ', NEW.transaction_ref, ' — entry committed.')
    );
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'SENTINEL ENGINE: Arbitrage detected. Transaction blocked.';
  END IF;
END$$

DELIMITER ;

-- ════════════════════════════════════════════════════════════════════
--  CORE RUNTIME TRANSACTION PROCEDURE
-- ════════════════════════════════════════════════════════════════════

DROP PROCEDURE IF EXISTS SP_ProcessTransaction;

DELIMITER $$

CREATE PROCEDURE SP_ProcessTransaction(
  IN  p_asset_hash       CHAR(16),
  IN  p_item_description VARCHAR(200),
  IN  p_transaction_ref  VARCHAR(20),
  IN  p_purchase_date    DATE,
  IN  p_raw_amount       DECIMAL(12,2),
  IN  p_customer_id      INT UNSIGNED,
  IN  p_employee_id      INT UNSIGNED,
  OUT p_result_code      TINYINT,
  OUT p_result_message   VARCHAR(300)
)
sp_main: BEGIN
  DECLARE v_hash_upper      CHAR(16);
  DECLARE v_sup_prefix      CHAR(4);
  DECLARE v_mem_suffix      CHAR(4);
  DECLARE v_sup_status      VARCHAR(15) DEFAULT '';
  DECLARE v_sup_name        VARCHAR(120) DEFAULT '';
  DECLARE v_cust_suffix     CHAR(4) DEFAULT '';
  DECLARE v_cust_discount   TINYINT UNSIGNED DEFAULT 0;
  DECLARE v_cust_status     VARCHAR(15) DEFAULT '';
  DECLARE v_net_amount      DECIMAL(12,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_result_code    = 99;
    SET p_result_message = 'SENTINEL DB ERROR: Transaction rolled back automatically.';
  END;

  SET v_hash_upper = UPPER(TRIM(p_asset_hash));

  IF v_hash_upper NOT REGEXP '^[0-9A-F]{16}$' THEN
    SET p_result_code    = 1;
    SET p_result_message = 'Format error: Must match 16 hex formatting bounds.';
    LEAVE sp_main;
  END IF;

  -- 1-indexed SQL SUBSTR handles boundaries perfectly mapping to PHP offsets
  SET v_sup_prefix = SUBSTR(v_hash_upper, 1, 4);   
  SET v_mem_suffix = SUBSTR(v_hash_upper, 13, 4);  

  SELECT name, status INTO v_sup_name, v_sup_status
  FROM   suppliers WHERE hash_prefix = v_sup_prefix LIMIT 1;

  IF v_sup_status = '' THEN
    SET p_result_code    = 2;
    SET p_result_message = 'Prefix sequence lookup failure.';
    LEAVE sp_main;
  END IF;

  IF v_sup_status IN ('Restricted', 'Suspended') THEN
    SET p_result_code    = 3;
    SET p_result_message = 'Supplier constraints block submission.';
    LEAVE sp_main;
  END IF;

  SELECT member_suffix, discount, status INTO v_cust_suffix, v_cust_discount, v_cust_status
  FROM   customers WHERE id = p_customer_id LIMIT 1;

  IF v_cust_suffix = '' THEN
    SET p_result_code    = 4;
    SET p_result_message = 'Customer identifier invalid.';
    LEAVE sp_main;
  END IF;

  IF v_cust_status != 'Active' THEN
    SET p_result_code    = 5;
    SET p_result_message = 'Customer account is locked or suspended.';
    LEAVE sp_main;
  END IF;

  IF v_cust_suffix != v_mem_suffix THEN
    INSERT INTO flagged_hashes (asset_hash, reason)
    VALUES (v_hash_upper, CONCAT('Suffix verification mismatch. Target customer suffix: ', v_cust_suffix));
    SET p_result_code    = 6;
    SET p_result_message = 'Customer identification segment mismatch.';
    LEAVE sp_main;
  END IF;

  SET v_net_amount = ROUND(p_raw_amount * (1.00 - (v_cust_discount / 100.00)), 2);

  START TRANSACTION;
    INSERT INTO purchase_history
      (customer_id, asset_hash, item_description, transaction_ref, purchase_date, net_amount)
    VALUES
      (p_customer_id, v_hash_upper, p_item_description, p_transaction_ref, p_purchase_date, v_net_amount);

    UPDATE customers
    SET    total_spent = total_spent + v_net_amount,
           balance     = GREATEST(0.00, balance - v_net_amount)
    WHERE  id = p_customer_id;

    UPDATE employees
    SET    tx_count = tx_count + 1
    WHERE  id = p_employee_id;
  COMMIT;

  SET p_result_code    = 0;
  SET p_result_message = 'Transaction successfully processed and committed to history logs.';
END$$

DELIMITER ;

-- ════════════════════════════════════════════════════════════════════
--  SEED DATA LAYERS
-- ════════════════════════════════════════════════════════════════════

INSERT INTO users (username, password_hash, role, account_status, is_first_login) VALUES
  ('areeb',  '$2y$10$2MmGvI.vP5eaVMt3lgZ6dOWcuL7q9wQ5bQb0czAbdS4OXbizQqyOy', 'Customer', 'Active', 0),
  ('zainab', '$2y$10$2MmGvI.vP5eaVMt3lgZ6dOWcuL7q9wQ5bQb0czAbdS4OXbizQqyOy', 'Customer', 'Active', 0),
  ('hamza',  '$2y$10$2MmGvI.vP5eaVMt3lgZ6dOWcuL7q9wQ5bQb0czAbdS4OXbizQqyOy', 'Customer', 'Active', 0),
  ('raza',   '$2y$10$qXDsV7M5xEuAVXr9bInwYOilFbLcHzEJ/2n.EVfFqFv.EzjxHVOaW', 'Employee', 'Active', 1),
  ('sara',   '$2y$10$qXDsV7M5xEuAVXr9bInwYOilFbLcHzEJ/2n.EVfFqFv.EzjxHVOaW', 'Employee', 'Active', 1),
  ('umar',   '$2y$10$qXDsV7M5xEuAVXr9bInwYOilFbLcHzEJ/2n.EVfFqFv.EzjxHVOaW', 'Employee', 'Active', 1),
  ('owner',  '$2y$10$MsPbA8R6Ul9OP7V.d.xn9eTVBhQ4j7mQHzT0hRd3hPRXFAEjBvTLq', 'Owner',    'Active', 0)
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO suppliers (hash_prefix, name, country, units_supplied, defect_count, integrity_score, status) VALUES
  ('A4F2', 'Apex Electronics Corp.',  'Taiwan',      48, 1, 97.92,  'Active'),
  ('B811', 'Matrix Supply Logistics', 'China',        23, 4, 82.61,  'Restricted'),
  ('C9A4', 'Omega Foundry Works',     'South Korea',  31, 2, 93.55,  'Active'),
  ('D2F0', 'NovaTech Industries',     'Germany',      19, 0, 100.00, 'Active'),
  ('E7B3', 'Stellar Components Ltd.', 'Japan',        56, 7, 87.50,  'Active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO item_types (type_code, category_name, base_price) VALUES
  ('99B1', 'Solid-State Storage Units',           12400.00),
  ('44E2', 'Optical Transceiver Arrays',           8200.00),
  ('A1B2', 'Neural Accelerator Modules',           7850.00),
  ('1122', 'High-Density Memory Modules',          3200.00),
  ('AAB0', 'Peripheral Interface Controllers',     1450.00),
  ('0099', 'SATA Controller Units',                2200.00),
  ('00C3', 'Fiber Network Components',             5500.00),
  ('0000', 'Standard Inventory Bundle',             500.00)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

INSERT INTO customers (user_id, member_suffix, tier, discount, status, balance, total_spent, billing_date) VALUES
  (1, 'G7X2', 'Gold',   12, 'Active', 4500.00, 28450.00, '2026-06-15'),
  (2, 'S3B9', 'Silver',  5, 'Active', 1200.00,  9800.00, '2026-07-01'),
  (3, 'B1A0', 'Basic',   0, 'Active',    0.00,  2200.00, '2026-05-28')
ON DUPLICATE KEY UPDATE tier = VALUES(tier);

INSERT INTO purchase_history (customer_id, asset_hash, item_description, transaction_ref, purchase_date, net_amount) VALUES
  (1, 'A4F299B100C3G7X2', 'Enterprise Solid-State Module 3TB',   'TX-90921', '2026-05-01', 12400.00),
  (1, 'C9A444E211A9G7X2', 'Fiber Optic Transceiver Core v2',      'TX-88104', '2026-04-12',  8200.00),
  (1, 'D2F0A1B2C3D4G7X2', 'Neural Processing Accelerator Unit',  'TX-85500', '2026-03-20',  7850.00),
  (2, 'E7B3112200CCB39B', 'High-Density RAM Module 32GB',         'TX-77201', '2026-04-30',  3200.00),
  (2, 'A4F2AAB0009CB39B', 'Peripheral Interface Controller v4',   'TX-76008', '2026-04-15',  1450.00),
  (3, 'C9A4009944EFB1A0', 'Standard SATA Controller Module',      'TX-60012', '2026-03-10',  2200.00)
ON DUPLICATE KEY UPDATE item_description = VALUES(item_description);

INSERT INTO employees (user_id, role_title, node_id, tx_count, defect_count) VALUES
  (4, 'Systems Specialist', 'NODE-WORKSTATION-X99', 14, 3),
  (5, 'Shift Supervisor',   'NODE-WORKSTATION-B04', 27, 1),
  (6, 'Desk Agent',         'NODE-WORKSTATION-C12',  8, 0)
ON DUPLICATE KEY UPDATE role_title = VALUES(role_title);

INSERT INTO defect_log (unit_hash, severity, notes, supplier_id, employee_id) VALUES
  ('B811FFAA0099', 'Major',    'Batch run failure — firmware voltage instability detected.',                   2, 1),
  ('B811AA110011', 'Critical', 'Complete board failure — no POST. Isolated from active stock.',               2, 1),
  ('B811CC330044', 'Minor',    'Surface corrosion on I/O port. Functional but cosmetically compromised.',     2, 2),
  ('B811EE550066', 'Major',    'Memory controller fault — intermittent read errors under load.',              2, 2),
  ('A4F299B100C3', 'Minor',    'Hairline crack on PCB edge. No functional impact. Flagged for documentation.',1, 1),
  ('C9A444E211A9', 'Minor',    'Thermal paste application uneven. Performance within spec post-reapplication.',3, 2),
  ('C9A4009944EF', 'Major',    'Controller timing desync — requires firmware patch. Returned to stock.',      3, 2),
  ('E7B3112200CC', 'Major',    'DDR bus error on stress test. Module replaced under warranty.',                5, 1),
  ('E7B3AABB1122', 'Critical', 'NAND controller non-responsive — full board failure.',                        5, 1),
  ('E7B3CCDD3344', 'Minor',    'Labeling misprint on unit. No functional impact.',                            5, 2),
  ('E7B355AA7788', 'Major',    'Voltage regulator failure at 80% load. Isolated.',                           5, 2),
  ('E7B3001100FF', 'Critical', 'Catastrophic short circuit during burn-in test.',                             5, 1)
ON DUPLICATE KEY UPDATE notes = VALUES(notes);

INSERT INTO flagged_hashes (asset_hash, reason, flagged_at) VALUES
  ('B811FFAA0099E3D2', 'Duplicate hash resubmission — TX-77001 already committed',            '2026-05-17 09:14:00'),
  ('A4F299B100C3G7X2', 'Hash reuse attempt on returned unit — member suffix mismatch',         '2026-05-15 14:30:00')
ON DUPLICATE KEY UPDATE reason = VALUES(reason);

INSERT INTO owner_stats (revenue) SELECT 1489200.00 WHERE NOT EXISTS (SELECT 1 FROM owner_stats LIMIT 1);