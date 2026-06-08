-- ════════════════════════════════════════════════════════════════════
--  SENTINEL SAS v4.1 — Unified Schema, Automation & Seed Data
--  Merges: sentinel_schema.sql + Core Engine Architecture (PDF spec)
--
--  Run:  mysql -u root -p < sentinel_unified.sql
--
--  Requires: MySQL 8.0+ or MariaDB 10.6+
--  Character set: utf8mb4 / utf8mb4_unicode_ci
-- ════════════════════════════════════════════════════════════════════

-- ── Create & select database ─────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS sentinel_sas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sentinel_sas;

-- ── Safety: disable FK checks during build so table order doesn't matter ─────
SET FOREIGN_KEY_CHECKS = 0;

-- ════════════════════════════════════════════════════════════════════
--  TABLE DEFINITIONS
-- ════════════════════════════════════════════════════════════════════

-- ── 1. USERS ─────────────────────────────────────────────────────────────────
-- Core identity table. Roles: Customer | Employee | Owner.
-- accountStatus mirrors the PDF spec (Active / Suspended / Locked).
-- failedLoginAttempts: application layer should lock at ≥ 5 consecutive fails.
CREATE TABLE IF NOT EXISTS users (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username             VARCHAR(60)  NOT NULL UNIQUE,
  password_hash        VARCHAR(255) NOT NULL,                -- bcrypt via PHP password_hash()
  role                 ENUM('Customer','Employee','Owner') NOT NULL,
  account_status       ENUM('Active','Suspended','Locked') NOT NULL DEFAULT 'Active',
  is_first_login       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,  -- 1 = triggers node-auth modal
  failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,    -- lock at 5; reset on success
  created_at           DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 2. SUPPLIERS ─────────────────────────────────────────────────────────────
-- Characters 0-3 of the 16-char composite asset hash.
-- integrity_score is recomputed by TR_Defect_UpdateSupplierIntegrity below.
-- Status: Active | Restricted (auto-set when score < 85%) | Suspended (manual override).
CREATE TABLE IF NOT EXISTS suppliers (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hash_prefix     CHAR(4)      NOT NULL UNIQUE,   -- hex chars 0-3 of asset hash
  name            VARCHAR(120) NOT NULL,
  country         VARCHAR(60)  NOT NULL,
  units_supplied  INT UNSIGNED NOT NULL DEFAULT 0,
  defect_count    INT UNSIGNED NOT NULL DEFAULT 0,
  integrity_score DECIMAL(5,2) NOT NULL DEFAULT 100.00,  -- floor 0, ceiling 100
  status          ENUM('Active','Restricted','Suspended') NOT NULL DEFAULT 'Active',
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 3. ITEM_TYPES ─────────────────────────────────────────────────────────────
-- Characters 4-7 of the 16-char composite asset hash.
-- Aligns the PDF ITEM_TYPE table with the TYPE_LABELS map used in index.php.
CREATE TABLE IF NOT EXISTS item_types (
  type_code     CHAR(4)      NOT NULL PRIMARY KEY,   -- hex chars 4-7 of asset hash
  category_name VARCHAR(100) NOT NULL,
  base_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00  -- catalog retail price pre-discount
) ENGINE=InnoDB;

-- ── 4. CUSTOMERS ─────────────────────────────────────────────────────────────
-- Characters 12-15 of the 16-char hash are the member_suffix (customerID in PDF).
CREATE TABLE IF NOT EXISTS customers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL UNIQUE,
  member_suffix CHAR(4)      NOT NULL UNIQUE,        -- hex chars 12-15 of asset hash
  tier          ENUM('Gold','Silver','Basic') NOT NULL DEFAULT 'Basic',
  discount      TINYINT UNSIGNED NOT NULL DEFAULT 0, -- percentage, e.g. 12 = 12%
  status        ENUM('Active','Suspended') NOT NULL DEFAULT 'Active',
  balance       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  total_spent   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  billing_date  DATE NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 5. PURCHASE_HISTORY ──────────────────────────────────────────────────────
-- Stores the immutable full 16-char hash per sold unit.
-- The UNIQUE constraint on asset_hash enforces the anti-arbitrage rule at
-- the schema level (TR_HashLog_AntiArbitrage also handles pre-insert checks).
CREATE TABLE IF NOT EXISTS purchase_history (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id      INT UNSIGNED  NOT NULL,
  asset_hash       CHAR(16)      NOT NULL UNIQUE,  -- full 16-char composite hex
  item_description VARCHAR(200)  NOT NULL,
  transaction_ref  VARCHAR(20)   NOT NULL UNIQUE,
  purchase_date    DATE          NOT NULL,
  net_amount       DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 6. EMPLOYEES ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS employees (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL UNIQUE,
  role_title   VARCHAR(80)  NOT NULL DEFAULT 'Desk Agent',  -- Desk Agent | Shift Supervisor | Systems Specialist
  node_id      VARCHAR(50)  NOT NULL DEFAULT 'NODE-WORKSTATION-X00',
  tx_count     INT UNSIGNED NOT NULL DEFAULT 0,
  defect_count INT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 7. DEFECT_LOG ────────────────────────────────────────────────────────────
-- 12-char unit_hash = supplier(4) + type(4) + serial(4).
-- Inserting a row with isDefective semantics fires TR_Defect_UpdateSupplierIntegrity.
CREATE TABLE IF NOT EXISTS defect_log (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_hash    CHAR(12)    NOT NULL,                -- chars 0-11 of the full 16-char hash
  severity     ENUM('Minor','Major','Critical') NOT NULL,
  notes        TEXT,
  supplier_id  INT UNSIGNED NULL,                   -- nullable: prefix may not map to a known supplier
  employee_id  INT UNSIGNED NOT NULL,
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 8. FLAGGED_HASHES ────────────────────────────────────────────────────────
-- Arbitrage audit log. Rows inserted automatically by trigger or API logic.
CREATE TABLE IF NOT EXISTS flagged_hashes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_hash VARCHAR(20)  NOT NULL,
  reason     VARCHAR(300) NOT NULL,
  flagged_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 9. NODE_AUTHORIZATIONS ───────────────────────────────────────────────────
-- Immutable first-login hardware binding log (written by authorizeNode in api.php).
CREATE TABLE IF NOT EXISTS node_authorizations (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id   INT UNSIGNED NOT NULL,
  node_id       VARCHAR(50)  NOT NULL,
  authorized_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 10. OWNER_STATS ──────────────────────────────────────────────────────────
-- Single-row revenue baseline; live totals are calculated at runtime in api.php.
CREATE TABLE IF NOT EXISTS owner_stats (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  revenue    DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Re-enable FK checks now that all tables exist
SET FOREIGN_KEY_CHECKS = 1;


-- ════════════════════════════════════════════════════════════════════
--  TRIGGERS
-- ════════════════════════════════════════════════════════════════════

-- Drop existing versions before redefining to allow safe re-runs
DROP TRIGGER IF EXISTS TR_Defect_UpdateSupplierIntegrity;
DROP TRIGGER IF EXISTS TR_HashLog_AntiArbitrage;

DELIMITER $$

-- ── TR_Defect_UpdateSupplierIntegrity ────────────────────────────────────────
-- Fires AFTER INSERT on defect_log.
-- Recalculates integrityScore = 100 - (defect_count / units_supplied * 100).
-- If the new score falls below 85.00%, status is auto-set to 'Restricted'.
-- Mirrors the PDF spec section 4.1 and the equivalent application logic in
-- submitDefect() (api.php) — the trigger acts as the database-level safety net.
CREATE TRIGGER TR_Defect_UpdateSupplierIntegrity
AFTER INSERT ON defect_log
FOR EACH ROW
BEGIN
  DECLARE v_supplier_id   INT UNSIGNED;
  DECLARE v_units         INT UNSIGNED;
  DECLARE v_defects       INT UNSIGNED;
  DECLARE v_new_score     DECIMAL(5,2);
  DECLARE v_new_status    VARCHAR(15);

  -- Resolve the supplier from the first 4 chars of the unit hash
  SELECT id INTO v_supplier_id
  FROM   suppliers
  WHERE  hash_prefix = UPPER(LEFT(NEW.unit_hash, 4))
  LIMIT  1;

  -- Only proceed if a known supplier is found
  IF v_supplier_id IS NOT NULL THEN
    -- Live aggregate counts ensure accuracy even if rows arrive concurrently
    SELECT COUNT(*) INTO v_defects
    FROM   defect_log
    WHERE  supplier_id = v_supplier_id;

    SELECT units_supplied INTO v_units
    FROM   suppliers
    WHERE  id = v_supplier_id;

    -- Guard against division by zero
    IF v_units > 0 THEN
      SET v_new_score = GREATEST(0.00, ROUND(100.00 - (v_defects / v_units * 100.00), 2));
    ELSE
      SET v_new_score = 100.00;
    END IF;

    -- Enforce 85% threshold as per PDF spec section 4.1
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


-- ── TR_HashLog_AntiArbitrage ─────────────────────────────────────────────────
-- Fires BEFORE INSERT on purchase_history.
-- If the incoming asset_hash already exists in the table (status = 'Sold'),
-- the duplicate is rejected via SIGNAL and the collision is written to
-- flagged_hashes for Owner-level audit review.
-- Mirrors the PDF spec section 4.2 arbitrage deflection mechanics.
--
-- NOTE: Because BEFORE triggers cannot INSERT into an auditing table in all
-- MySQL versions without a procedure call, we use an AFTER INSERT trigger
-- that detects collisions on the flagged_hashes side. The UNIQUE constraint
-- on purchase_history.asset_hash already blocks the actual duplicate row.
-- This trigger logs the attempt to flagged_hashes for audit visibility.
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
    -- Log the interception event before rejecting
    INSERT INTO flagged_hashes (asset_hash, reason)
    VALUES (
      NEW.asset_hash,
      CONCAT(
        'Duplicate hash submission intercepted by TR_HashLog_AntiArbitrage — ',
        'transaction_ref: ', NEW.transaction_ref,
        ' — original unit already committed as Sold'
      )
    );
    -- Hard reject: raise a descriptive application-visible error
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'SENTINEL: Arbitrage deflection — this asset hash is already committed as Sold. Transaction rejected.';
  END IF;
END$$

DELIMITER ;


-- ════════════════════════════════════════════════════════════════════
--  STORED PROCEDURE
-- ════════════════════════════════════════════════════════════════════

DROP PROCEDURE IF EXISTS SP_ProcessTransaction;

DELIMITER $$

-- ── SP_ProcessTransaction ────────────────────────────────────────────────────
-- Atomically commits a new sale:
--   1. Validates the 16-char asset_hash structure (hex, correct length).
--   2. Verifies supplier prefix exists and is NOT Restricted.
--   3. Verifies customer member_suffix matches chars 12-15 of the hash.
--   4. Calculates the discounted net_amount.
--   5. Inserts into purchase_history (triggers TR_HashLog_AntiArbitrage).
--   6. Updates customers.balance, total_spent.
--   7. Updates employees.tx_count.
--
-- Parameters:
--   p_asset_hash        CHAR(16)      — full 16-char composite hex
--   p_item_description  VARCHAR(200)
--   p_transaction_ref   VARCHAR(20)   — unique external reference
--   p_purchase_date     DATE
--   p_raw_amount        DECIMAL(12,2) — pre-discount price
--   p_customer_id       INT UNSIGNED  — customers.id PK
--   p_employee_id       INT UNSIGNED  — employees.id PK
--
-- OUT p_result_code     TINYINT       — 0 = success, 1..N = error codes
-- OUT p_result_message  VARCHAR(300)  — human-readable status message

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
BEGIN
  -- Local variables
  DECLARE v_hash_upper      CHAR(16);
  DECLARE v_sup_prefix      CHAR(4);
  DECLARE v_mem_suffix      CHAR(4);
  DECLARE v_sup_status      VARCHAR(15) DEFAULT '';
  DECLARE v_sup_name        VARCHAR(120) DEFAULT '';
  DECLARE v_cust_suffix     CHAR(4) DEFAULT '';
  DECLARE v_cust_discount   TINYINT UNSIGNED DEFAULT 0;
  DECLARE v_cust_status     VARCHAR(15) DEFAULT '';
  DECLARE v_net_amount      DECIMAL(12,2);
  DECLARE v_existing_hash   INT UNSIGNED DEFAULT 0;
  DECLARE v_exit_flag       TINYINT DEFAULT 0;

  -- Declare a generic handler for any SQL exception to roll back cleanly
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_result_code    = 99;
    SET p_result_message = 'SENTINEL: Unexpected database error — transaction rolled back.';
  END;

  -- ── Step 1: Normalise and validate hash format ────────────────────
  SET v_hash_upper = UPPER(TRIM(p_asset_hash));

  IF v_hash_upper NOT REGEXP '^[0-9A-F]{16}$' THEN
    SET p_result_code    = 1;
    SET p_result_message = CONCAT('Invalid asset hash: "', v_hash_upper, '" — must be exactly 16 hexadecimal characters.');
    LEAVE sp_main;         -- forward label declared below
  END IF;

  -- Extract structural segments
  SET v_sup_prefix = SUBSTR(v_hash_upper, 1, 4);   -- chars 0-3
  SET v_mem_suffix = SUBSTR(v_hash_upper, 13, 4);  -- chars 12-15

  -- ── Step 2: Verify supplier exists and is not Restricted ──────────
  SELECT name, status
  INTO   v_sup_name, v_sup_status
  FROM   suppliers
  WHERE  hash_prefix = v_sup_prefix
  LIMIT  1;

  IF v_sup_status = '' THEN
    SET p_result_code    = 2;
    SET p_result_message = CONCAT('Unknown supplier prefix "', v_sup_prefix, '" — hash rejected.');
    LEAVE sp_main;
  END IF;

  IF v_sup_status = 'Restricted' OR v_sup_status = 'Suspended' THEN
    SET p_result_code    = 3;
    SET p_result_message = CONCAT('Supplier "', v_sup_name, '" is ', v_sup_status, '. Transaction blocked.');
    LEAVE sp_main;
  END IF;

  -- ── Step 3: Verify customer member suffix matches hash segment ────
  SELECT member_suffix, discount, status
  INTO   v_cust_suffix, v_cust_discount, v_cust_status
  FROM   customers
  WHERE  id = p_customer_id
  LIMIT  1;

  IF v_cust_suffix = '' THEN
    SET p_result_code    = 4;
    SET p_result_message = CONCAT('Customer ID ', p_customer_id, ' not found.');
    LEAVE sp_main;
  END IF;

  IF v_cust_status != 'Active' THEN
    SET p_result_code    = 5;
    SET p_result_message = CONCAT('Customer account is ', v_cust_status, '. Purchases not permitted.');
    LEAVE sp_main;
  END IF;

  IF v_cust_suffix != v_mem_suffix THEN
    -- Member suffix mismatch — flag the hash and reject
    INSERT INTO flagged_hashes (asset_hash, reason)
    VALUES (
      v_hash_upper,
      CONCAT(
        'SP_ProcessTransaction: member suffix mismatch — hash segment "', v_mem_suffix,
        '" does not match customer suffix "', v_cust_suffix,
        '" (customer_id=', p_customer_id, ')'
      )
    );
    SET p_result_code    = 6;
    SET p_result_message = CONCAT('Hash member segment "', v_mem_suffix,
      '" does not match customer suffix "', v_cust_suffix, '". Transaction flagged and rejected.');
    LEAVE sp_main;
  END IF;

  -- ── Step 4: Calculate net amount after tier discount ─────────────
  SET v_net_amount = ROUND(
    p_raw_amount * (1.00 - (v_cust_discount / 100.00)),
    2
  );

  -- ── Begin atomic transaction ──────────────────────────────────────
  START TRANSACTION;

    -- ── Step 5: Insert purchase (TR_HashLog_AntiArbitrage fires here)
    INSERT INTO purchase_history
      (customer_id, asset_hash, item_description, transaction_ref, purchase_date, net_amount)
    VALUES
      (p_customer_id, v_hash_upper, p_item_description, p_transaction_ref, p_purchase_date, v_net_amount);

    -- ── Step 6: Update customer wallet
    UPDATE customers
    SET    total_spent = total_spent + v_net_amount,
           balance     = GREATEST(0.00, balance - v_net_amount)
    WHERE  id = p_customer_id;

    -- ── Step 7: Increment employee audit counter
    UPDATE employees
    SET    tx_count = tx_count + 1
    WHERE  id = p_employee_id;

  COMMIT;

  SET p_result_code    = 0;
  SET p_result_message = CONCAT(
    'Transaction committed. Asset ', v_hash_upper,
    ' — net amount Rs.', v_net_amount,
    ' (', v_cust_discount, '% tier discount applied).'
  );

  -- Label used by LEAVE statements above (MySQL requires it at the end of BEGIN…END)
  sp_main: BEGIN END;

END$$

DELIMITER ;


-- ════════════════════════════════════════════════════════════════════
--  SEED DATA
-- ════════════════════════════════════════════════════════════════════
-- Passwords hashed with PHP password_hash($plain, PASSWORD_BCRYPT, ['cost'=>10])
-- Plain-text credentials for development only:
--   Customers : password123
--   Employees : employee123
--   Owner     : owner@secure1
-- Regenerate: php -r "echo password_hash('password123', PASSWORD_BCRYPT);"

INSERT INTO users
  (username, password_hash, role, account_status, is_first_login)
VALUES
  -- Customers
  ('areeb',  '$2y$10$2MmGvI.vP5eaVMt3lgZ6dOWcuL7q9wQ5bQb0czAbdS4OXbizQqyOy', 'Customer', 'Active', 0),
  ('zainab', '$2y$10$2MmGvI.vP5eaVMt3lgZ6dOWcuL7q9wQ5bQb0czAbdS4OXbizQqyOy', 'Customer', 'Active', 0),
  ('hamza',  '$2y$10$2MmGvI.vP5eaVMt3lgZ6dOWcuL7q9wQ5bQb0czAbdS4OXbizQqyOy', 'Customer', 'Active', 0),
  -- Employees
  ('raza',   '$2y$10$qXDsV7M5xEuAVXr9bInwYOilFbLcHzEJ/2n.EVfFqFv.EzjxHVOaW', 'Employee', 'Active', 1),
  ('sara',   '$2y$10$qXDsV7M5xEuAVXr9bInwYOilFbLcHzEJ/2n.EVfFqFv.EzjxHVOaW', 'Employee', 'Active', 1),
  ('umar',   '$2y$10$qXDsV7M5xEuAVXr9bInwYOilFbLcHzEJ/2n.EVfFqFv.EzjxHVOaW', 'Employee', 'Active', 1),
  -- Owner
  ('owner',  '$2y$10$MsPbA8R6Ul9OP7V.d.xn9eTVBhQ4j7mQHzT0hRd3hPRXFAEjBvTLq', 'Owner',    'Active', 0)
ON DUPLICATE KEY UPDATE username = username;  -- idempotent re-run guard

-- Suppliers (hash_prefix = chars 0-3 of any asset hash from this vendor)
INSERT INTO suppliers
  (hash_prefix, name, country, units_supplied, defect_count, integrity_score, status)
VALUES
  ('A4F2', 'Apex Electronics Corp.',  'Taiwan',      48, 1, 97.92,  'Active'),
  ('B811', 'Matrix Supply Logistics', 'China',        23, 4, 82.61,  'Restricted'),
  ('C9A4', 'Omega Foundry Works',     'South Korea',  31, 2, 93.55,  'Active'),
  ('D2F0', 'NovaTech Industries',     'Germany',      19, 0, 100.00, 'Active'),
  ('E7B3', 'Stellar Components Ltd.', 'Japan',        56, 7, 87.50,  'Active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Item types — aligns with TYPE_LABELS map in index.php JS
INSERT INTO item_types (type_code, category_name, base_price)
VALUES
  ('99B1', 'Solid-State Storage Units',           12400.00),
  ('44E2', 'Optical Transceiver Arrays',           8200.00),
  ('A1B2', 'Neural Accelerator Modules',           7850.00),
  ('1122', 'High-Density Memory Modules',          3200.00),
  ('AAB0', 'Peripheral Interface Controllers',     1450.00),
  ('0099', 'SATA Controller Units',                2200.00),
  ('00C3', 'Fiber Network Components',             5500.00),
  ('0000', 'Standard Inventory Bundle',             500.00)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- Customers (user_id: areeb=1, zainab=2, hamza=3 — assumes clean insert order)
INSERT INTO customers
  (user_id, member_suffix, tier, discount, status, balance, total_spent, billing_date)
VALUES
  (1, 'G7X2', 'Gold',   12, 'Active', 4500.00, 28450.00, '2026-06-15'),
  (2, 'S3B9', 'Silver',  5, 'Active', 1200.00,  9800.00, '2026-07-01'),
  (3, 'B1A0', 'Basic',   0, 'Active',    0.00,  2200.00, '2026-05-28')
ON DUPLICATE KEY UPDATE tier = VALUES(tier);

-- Purchase history
-- NOTE: asset_hash UNIQUE constraint + TR_HashLog_AntiArbitrage protect these at runtime.
INSERT INTO purchase_history
  (customer_id, asset_hash, item_description, transaction_ref, purchase_date, net_amount)
VALUES
  (1, 'A4F299B100C3G7X2', 'Enterprise Solid-State Module 3TB',   'TX-90921', '2026-05-01', 12400.00),
  (1, 'C9A444E211A9G7X2', 'Fiber Optic Transceiver Core v2',      'TX-88104', '2026-04-12',  8200.00),
  (1, 'D2F0A1B2C3D4G7X2', 'Neural Processing Accelerator Unit',  'TX-85500', '2026-03-20',  7850.00),
  (2, 'E7B3112200CCB39B', 'High-Density RAM Module 32GB',         'TX-77201', '2026-04-30',  3200.00),
  (2, 'A4F2AAB0009CB39B', 'Peripheral Interface Controller v4',   'TX-76008', '2026-04-15',  1450.00),
  (3, 'C9A4009944EFB1A0', 'Standard SATA Controller Module',      'TX-60012', '2026-03-10',  2200.00)
ON DUPLICATE KEY UPDATE item_description = VALUES(item_description);

-- Employees (user_id: raza=4, sara=5, umar=6)
INSERT INTO employees
  (user_id, role_title, node_id, tx_count, defect_count)
VALUES
  (4, 'Systems Specialist', 'NODE-WORKSTATION-X99', 14, 3),
  (5, 'Shift Supervisor',   'NODE-WORKSTATION-B04', 27, 1),
  (6, 'Desk Agent',         'NODE-WORKSTATION-C12',  8, 0)
ON DUPLICATE KEY UPDATE role_title = VALUES(role_title);

-- Defect log — references employee IDs 1/2 (raza/sara in employees table)
INSERT INTO defect_log
  (unit_hash, severity, notes, supplier_id, employee_id)
VALUES
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

-- Flagged hashes (audit events)
INSERT INTO flagged_hashes (asset_hash, reason, flagged_at)
VALUES
  ('B811FFAA0099E3D2', 'Duplicate hash resubmission — TX-77001 already committed',            '2026-05-17 09:14:00'),
  ('A4F299B100C3G7X2', 'Hash reuse attempt on returned unit — member suffix mismatch',         '2026-05-15 14:30:00')
ON DUPLICATE KEY UPDATE reason = VALUES(reason);

-- Owner stats baseline
INSERT INTO owner_stats (revenue)
SELECT 1489200.00
WHERE  NOT EXISTS (SELECT 1 FROM owner_stats LIMIT 1);


-- ════════════════════════════════════════════════════════════════════
--  VERIFICATION QUERIES  (optional — uncomment to inspect after load)
-- ════════════════════════════════════════════════════════════════════
-- SELECT 'users'            AS tbl, COUNT(*) AS rows FROM users;
-- SELECT 'suppliers'        AS tbl, COUNT(*) AS rows FROM suppliers;
-- SELECT 'item_types'       AS tbl, COUNT(*) AS rows FROM item_types;
-- SELECT 'customers'        AS tbl, COUNT(*) AS rows FROM customers;
-- SELECT 'purchase_history' AS tbl, COUNT(*) AS rows FROM purchase_history;
-- SELECT 'employees'        AS tbl, COUNT(*) AS rows FROM employees;
-- SELECT 'defect_log'       AS tbl, COUNT(*) AS rows FROM defect_log;
-- SELECT 'flagged_hashes'   AS tbl, COUNT(*) AS rows FROM flagged_hashes;
-- SELECT 'node_authorizations' AS tbl, COUNT(*) AS rows FROM node_authorizations;
-- SHOW TRIGGERS;
-- SHOW PROCEDURE STATUS WHERE Db = 'sentinel_sas';

-- ════════════════════════════════════════════════════════════════════
--  END OF SENTINEL SAS v4.1 — UNIFIED SCHEMA
-- ════════════════════════════════════════════════════════════════════
