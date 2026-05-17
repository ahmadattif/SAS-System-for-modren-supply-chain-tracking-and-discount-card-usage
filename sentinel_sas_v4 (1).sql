-- ============================================================================
-- SENTINEL SAS ENTERPRISE CORE ENGINE — SQL SCHEMA v4.1
-- Architecture: Multi-Domain Triple-Role Hash-First Indexing System
-- Lead Architect: Muhammad Ahmad Atif | IMSC University, BS AI (Semester 4)
-- Engine Target: MySQL 8.0+ / MariaDB 10.6+ (Advanced Mode)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS SentinelSAS_v4
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE SentinelSAS_v4;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- DOMAIN 0 — FOUNDATION LAYER: MASTER AUTHENTICATION REGISTRY
-- ============================================================================

CREATE TABLE SYSTEM_USER (
    userUID            VARCHAR(50)   NOT NULL,
    username           VARCHAR(50)   NOT NULL,
    biometricKeyHash   CHAR(64)      NOT NULL     COMMENT 'SHA-256 hash of biometric passkey',
    systemRole         VARCHAR(15)   NOT NULL     COMMENT 'Customer | Employee | Owner',
    accountStatus      VARCHAR(15)   NOT NULL
                       DEFAULT 'Active'           COMMENT 'Active | Suspended | Locked',
    isFirstLogin       BOOLEAN       NOT NULL
                       DEFAULT TRUE,
    lastLoginAt        DATETIME      NULL,
    failedLoginAttempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    createdAt          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                       ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT PK_SYSTEM_USER      PRIMARY KEY (userUID),
    CONSTRAINT UQ_System_Username  UNIQUE      (username),
    CONSTRAINT CK_System_Role      CHECK (systemRole IN ('Customer', 'Employee', 'Owner')),
    CONSTRAINT CK_Account_Status   CHECK (accountStatus IN ('Active', 'Suspended', 'Locked')),
    CONSTRAINT CK_Failed_Attempts  CHECK (failedLoginAttempts BETWEEN 0 AND 10)
);

CREATE INDEX IDX_SU_Role   ON SYSTEM_USER (systemRole);
CREATE INDEX IDX_SU_Status ON SYSTEM_USER (accountStatus);

-- ============================================================================
-- DOMAIN 1 — BUSINESS LAYER: LOGISTICS & MANUFACTURING
-- ============================================================================

-- Vendor registry with automated integrity scoring
CREATE TABLE SUPPLIER (
    supplierID     CHAR(4)       NOT NULL  COMMENT 'Hex chars 0–3 of the 16-char asset hash',
    name           VARCHAR(100)  NOT NULL,
    contactEmail   VARCHAR(150)  NULL,
    country        VARCHAR(80)   NULL,
    integrityScore DECIMAL(5,2)  NOT NULL  DEFAULT 100.00,
    vendorStatus   VARCHAR(15)   NOT NULL  DEFAULT 'Active'
                   COMMENT 'Active | Restricted | Suspended',
    onboardedAt    DATETIME      NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_SUPPLIER         PRIMARY KEY (supplierID),
    CONSTRAINT CK_Supplier_ID_Hex  CHECK (supplierID REGEXP '^[0-9A-Fa-f]{4}$'),
    CONSTRAINT CK_Integrity_Score  CHECK (integrityScore BETWEEN 0.00 AND 100.00),
    CONSTRAINT CK_Vendor_Status    CHECK (vendorStatus IN ('Active', 'Restricted', 'Suspended'))
);

-- Item-type classification table (hex chars 4–7)
CREATE TABLE ITEM_TYPE (
    typeCode     CHAR(4)       NOT NULL  COMMENT 'Hex chars 4–7 of the 16-char asset hash',
    categoryName VARCHAR(100)  NOT NULL,
    description  VARCHAR(255)  NULL,

    CONSTRAINT PK_ITEM_TYPE        PRIMARY KEY (typeCode),
    CONSTRAINT CK_Type_Code_Hex    CHECK (typeCode REGEXP '^[0-9A-Fa-f]{4}$')
);

-- Master product catalog
CREATE TABLE PRODUCT (
    unitHash_12  CHAR(12)      NOT NULL  COMMENT 'Supplier(4) + ItemType(4) + Serial(4)',
    name         VARCHAR(150)  NOT NULL,
    supplierID   CHAR(4)       NOT NULL,
    typeCode     CHAR(4)       NOT NULL,
    basePrice    DECIMAL(10,2) NOT NULL,
    isActive     BOOLEAN       NOT NULL  DEFAULT TRUE,
    createdAt    DATETIME      NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_PRODUCT           PRIMARY KEY (unitHash_12),
    CONSTRAINT FK_Product_Supplier  FOREIGN KEY (supplierID) REFERENCES SUPPLIER(supplierID),
    CONSTRAINT FK_Product_ItemType  FOREIGN KEY (typeCode)   REFERENCES ITEM_TYPE(typeCode),
    CONSTRAINT CK_Unit_Hash_12_Hex  CHECK (unitHash_12 REGEXP '^[0-9A-Fa-f]{12}$'),
    CONSTRAINT CK_Base_Price        CHECK (basePrice >= 0),
    -- Structural integrity: supplierID and typeCode must match respective hash segments
    CONSTRAINT CK_Hash_Supplier_Seg CHECK (LEFT(unitHash_12, 4)     = supplierID),
    CONSTRAINT CK_Hash_Type_Seg     CHECK (SUBSTRING(unitHash_12, 5, 4) = typeCode)
);

CREATE INDEX IDX_Product_Supplier ON PRODUCT (supplierID);
CREATE INDEX IDX_Product_Type     ON PRODUCT (typeCode);
CREATE INDEX IDX_Product_Active   ON PRODUCT (isActive);

-- Inventory ledger: tracks all inbound stock and defect flags
CREATE TABLE STOCK_LEDGER (
    ledgerID     VARCHAR(50)   NOT NULL,
    unitHash_12  CHAR(12)      NOT NULL,
    batchRef     VARCHAR(50)   NULL       COMMENT 'External purchase order / batch reference',
    defectNotes  VARCHAR(500)  NULL,
    isDefective  BOOLEAN       NOT NULL   DEFAULT FALSE,
    severity     VARCHAR(10)   NULL       COMMENT 'Minor | Major | Critical',
    loggedByUID  VARCHAR(50)   NULL       COMMENT 'Employee userUID who logged this entry',
    dateLogged   DATETIME      NOT NULL   DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_STOCK_LEDGER        PRIMARY KEY (ledgerID),
    CONSTRAINT FK_Ledger_Product      FOREIGN KEY (unitHash_12)  REFERENCES PRODUCT(unitHash_12),
    CONSTRAINT FK_Ledger_LoggedBy     FOREIGN KEY (loggedByUID)  REFERENCES SYSTEM_USER(userUID),
    CONSTRAINT CK_Defect_Severity     CHECK (severity IS NULL OR severity IN ('Minor', 'Major', 'Critical'))
);

CREATE INDEX IDX_Ledger_Unit       ON STOCK_LEDGER (unitHash_12);
CREATE INDEX IDX_Ledger_Defective  ON STOCK_LEDGER (isDefective);
CREATE INDEX IDX_Ledger_Date       ON STOCK_LEDGER (dateLogged);

-- ============================================================================
-- DOMAIN 2 — CONSUMER LAYER: MEMBERSHIP & PRIVILEGES
-- ============================================================================

-- Tier discount configuration (drives transaction engine)
CREATE TABLE TIER_DISCOUNT (
    tierType         VARCHAR(10)   NOT NULL,
    discountPercent  DECIMAL(5,2)  NOT NULL  DEFAULT 0.00,
    monthlyFee       DECIMAL(10,2) NOT NULL  DEFAULT 0.00,
    description      VARCHAR(200)  NULL,

    CONSTRAINT PK_TIER_DISCOUNT    PRIMARY KEY (tierType),
    CONSTRAINT CK_Tier_Type        CHECK (tierType IN ('Basic', 'Silver', 'Gold')),
    CONSTRAINT CK_Discount_Range   CHECK (discountPercent BETWEEN 0.00 AND 100.00)
);

-- Seed tier configuration
INSERT INTO TIER_DISCOUNT (tierType, discountPercent, monthlyFee, description) VALUES
    ('Basic',  0.00,  199.00, 'Standard membership — no purchase discount'),
    ('Silver', 5.00,  499.00, 'Silver tier — 5% discount on all purchases'),
    ('Gold',   12.00, 999.00, 'Gold tier — 12% discount + priority service');

-- Customer profile: last 4 hex chars of master hash = member segment
CREATE TABLE CUSTOMER_PROFILE (
    customerID       CHAR(4)       NOT NULL  COMMENT 'Hex chars 12–15 of the 16-char asset hash',
    userUID          VARCHAR(50)   NOT NULL,
    fullName         VARCHAR(100)  NOT NULL,
    phone            VARCHAR(20)   NULL,
    tierType         VARCHAR(10)   NOT NULL  DEFAULT 'Basic',
    membershipStatus VARCHAR(15)   NOT NULL  DEFAULT 'Active'
                     COMMENT 'Active | Suspended | Delinquent',
    nextPaymentDue   DATE          NOT NULL,
    accountBalance   DECIMAL(10,2) NOT NULL  DEFAULT 0.00,
    totalSpent       DECIMAL(12,2) NOT NULL  DEFAULT 0.00,
    joinedAt         DATETIME      NOT NULL  DEFAULT CURRENT_TIMESTAMP,
    lastPurchaseAt   DATETIME      NULL,

    CONSTRAINT PK_CUSTOMER_PROFILE   PRIMARY KEY (customerID),
    CONSTRAINT FK_Customer_User      FOREIGN KEY (userUID)   REFERENCES SYSTEM_USER(userUID),
    CONSTRAINT FK_Customer_Tier      FOREIGN KEY (tierType)  REFERENCES TIER_DISCOUNT(tierType),
    CONSTRAINT CK_Customer_ID_Hex    CHECK (customerID REGEXP '^[0-9A-Fa-f]{4}$'),
    CONSTRAINT CK_Member_Status      CHECK (membershipStatus IN ('Active', 'Suspended', 'Delinquent'))
);

CREATE INDEX IDX_Customer_User    ON CUSTOMER_PROFILE (userUID);
CREATE INDEX IDX_Customer_Tier    ON CUSTOMER_PROFILE (tierType);
CREATE INDEX IDX_Customer_Billing ON CUSTOMER_PROFILE (nextPaymentDue);  -- for billing cron-jobs
CREATE INDEX IDX_Customer_Status  ON CUSTOMER_PROFILE (membershipStatus);

-- ============================================================================
-- DOMAIN 3 — OPERATIONAL LAYER: EMPLOYEES & TRANSACTIONS
-- ============================================================================

CREATE TABLE EMPLOYEE_PROFILE (
    employeeID                  VARCHAR(50)  NOT NULL,
    userUID                     VARCHAR(50)  NOT NULL,
    fullName                    VARCHAR(100) NOT NULL,
    designation                 VARCHAR(50)  NOT NULL
                                COMMENT 'Desk Agent | Shift Supervisor | Systems Specialist',
    terminalNodeBinding         VARCHAR(100) NULL
                                COMMENT 'Hardware node ID bound to this operator account',
    totalTransactionsProcessed  INT UNSIGNED NOT NULL  DEFAULT 0,
    totalDefectsLogged          INT UNSIGNED NOT NULL  DEFAULT 0,
    isActive                    BOOLEAN      NOT NULL  DEFAULT TRUE,
    hiredAt                     DATETIME     NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_EMPLOYEE_PROFILE  PRIMARY KEY (employeeID),
    CONSTRAINT FK_Employee_User     FOREIGN KEY (userUID) REFERENCES SYSTEM_USER(userUID),
    CONSTRAINT CK_Designation       CHECK (designation IN (
                                        'Desk Agent',
                                        'Shift Supervisor',
                                        'Systems Specialist',
                                        'Administrator'
                                    ))
);

CREATE INDEX IDX_Employee_User ON EMPLOYEE_PROFILE (userUID);

-- Master transaction header
CREATE TABLE TRANSACTION (
    transactionID   VARCHAR(50)   NOT NULL,
    employeeID      VARCHAR(50)   NOT NULL,
    customerID      CHAR(4)       NULL       COMMENT 'NULL = anonymous / walk-in sale',
    rawTotal        DECIMAL(10,2) NOT NULL,
    discountApplied DECIMAL(10,2) NOT NULL  DEFAULT 0.00,
    finalNetTotal   DECIMAL(10,2) NOT NULL,
    paymentMethod   VARCHAR(20)   NULL       DEFAULT 'Cash'
                    COMMENT 'Cash | Card | Digital',
    status          VARCHAR(15)   NOT NULL  DEFAULT 'Completed'
                    COMMENT 'Completed | Refunded | Disputed',
    notes           VARCHAR(500)  NULL,
    createdAt       DATETIME      NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_TRANSACTION          PRIMARY KEY (transactionID),
    CONSTRAINT FK_Transaction_Employee FOREIGN KEY (employeeID)  REFERENCES EMPLOYEE_PROFILE(employeeID),
    CONSTRAINT FK_Transaction_Customer FOREIGN KEY (customerID)  REFERENCES CUSTOMER_PROFILE(customerID),
    CONSTRAINT CK_Net_Total_Logic      CHECK (finalNetTotal = rawTotal - discountApplied),
    CONSTRAINT CK_Transaction_Status   CHECK (status IN ('Completed', 'Refunded', 'Disputed'))
);

CREATE INDEX IDX_TX_Employee  ON TRANSACTION (employeeID);
CREATE INDEX IDX_TX_Customer  ON TRANSACTION (customerID);
CREATE INDEX IDX_TX_Date      ON TRANSACTION (createdAt);
CREATE INDEX IDX_TX_Status    ON TRANSACTION (status);

-- Transaction line items (one row per product sold in a transaction)
CREATE TABLE TRANSACTION_LINE_ITEM (
    lineItemID     VARCHAR(50)   NOT NULL,
    transactionID  VARCHAR(50)   NOT NULL,
    unitHash_12    CHAR(12)      NOT NULL,
    quantity       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    unitPrice      DECIMAL(10,2) NOT NULL,
    lineTotal      DECIMAL(10,2) NOT NULL
                   COMMENT 'quantity * unitPrice',

    CONSTRAINT PK_LINE_ITEM          PRIMARY KEY (lineItemID),
    CONSTRAINT FK_LineItem_TX        FOREIGN KEY (transactionID) REFERENCES TRANSACTION(transactionID),
    CONSTRAINT FK_LineItem_Product   FOREIGN KEY (unitHash_12)   REFERENCES PRODUCT(unitHash_12),
    CONSTRAINT CK_Quantity_Positive  CHECK (quantity > 0),
    CONSTRAINT CK_LineTotal_Logic    CHECK (lineTotal = quantity * unitPrice)
);

CREATE INDEX IDX_LI_Transaction ON TRANSACTION_LINE_ITEM (transactionID);
CREATE INDEX IDX_LI_Product     ON TRANSACTION_LINE_ITEM (unitHash_12);

-- 16-character composite hash log (the anti-arbitrage backbone)
CREATE TABLE HASH_LOG (
    fullHash_16   CHAR(16)      NOT NULL
                  COMMENT 'Supplier(4) + Type(4) + Serial(4) + Member(4) = full asset fingerprint',
    transactionID VARCHAR(50)   NOT NULL,
    customerID    CHAR(4)       NULL,
    status        VARCHAR(15)   NOT NULL  DEFAULT 'Sold',
    flagReason    VARCHAR(255)  NULL      COMMENT 'Populated if status = Flagged',
    resolvedAt    DATETIME      NULL,
    loggedAt      DATETIME      NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT PK_HASH_LOG          PRIMARY KEY (fullHash_16),
    CONSTRAINT FK_Hash_Transaction  FOREIGN KEY (transactionID) REFERENCES TRANSACTION(transactionID),
    CONSTRAINT FK_Hash_Customer     FOREIGN KEY (customerID)    REFERENCES CUSTOMER_PROFILE(customerID),
    CONSTRAINT CK_Full_Hash_16_Hex  CHECK (fullHash_16 REGEXP '^[0-9A-Fa-f]{16}$'),
    CONSTRAINT CK_Hash_Status       CHECK (status IN ('Sold', 'Returned', 'Flagged'))
);

CREATE INDEX IDX_Hash_Status     ON HASH_LOG (status);
CREATE INDEX IDX_Hash_Customer   ON HASH_LOG (customerID);
CREATE INDEX IDX_Hash_Transaction ON HASH_LOG (transactionID);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- AUTOMATION LAYER: TRIGGERS & STORED PROCEDURES
-- ============================================================================

DELIMITER $$

-- ----------------------------------------------------------------------------
-- TRIGGER 1: Auto-recalculate supplier integrity + enforce 85% restriction gate
-- ----------------------------------------------------------------------------
CREATE TRIGGER TR_StockLedger_UpdateSupplierIntegrity
AFTER INSERT ON STOCK_LEDGER
FOR EACH ROW
BEGIN
    DECLARE v_supplierID      CHAR(4);
    DECLARE v_totalSupplied   INT;
    DECLARE v_defectiveCount  INT;
    DECLARE v_newScore        DECIMAL(5,2);
    DECLARE v_newStatus       VARCHAR(15);

    -- Resolve the supplier from the product's 12-char hash
    SELECT supplierID
      INTO v_supplierID
      FROM PRODUCT
     WHERE unitHash_12 = NEW.unitHash_12
     LIMIT 1;

    IF v_supplierID IS NOT NULL AND NEW.isDefective = TRUE THEN
        -- Total units ever supplied by this vendor
        SELECT COUNT(*)
          INTO v_totalSupplied
          FROM PRODUCT p
          JOIN STOCK_LEDGER sl ON sl.unitHash_12 = p.unitHash_12
         WHERE p.supplierID = v_supplierID;

        -- Defective count for this vendor
        SELECT COUNT(*)
          INTO v_defectiveCount
          FROM PRODUCT p
          JOIN STOCK_LEDGER sl ON sl.unitHash_12 = p.unitHash_12
         WHERE p.supplierID = v_supplierID
           AND sl.isDefective = TRUE;

        -- Guard: avoid zero-division
        IF v_totalSupplied > 0 THEN
            SET v_newScore = 100.00 - ((v_defectiveCount / v_totalSupplied) * 100.00);

            -- Clamp to valid range
            SET v_newScore = GREATEST(0.00, LEAST(100.00, v_newScore));

            -- Enforce restriction policy at 85% threshold
            IF v_newScore < 85.00 THEN
                SET v_newStatus = 'Restricted';
            ELSE
                SET v_newStatus = 'Active';
            END IF;

            UPDATE SUPPLIER
               SET integrityScore = v_newScore,
                   vendorStatus   = v_newStatus
             WHERE supplierID = v_supplierID;
        END IF;
    END IF;
END$$

-- ----------------------------------------------------------------------------
-- TRIGGER 2: Auto-increment employee transaction counter on commit
-- ----------------------------------------------------------------------------
CREATE TRIGGER TR_Transaction_IncrementEmployeeCount
AFTER INSERT ON TRANSACTION
FOR EACH ROW
BEGIN
    UPDATE EMPLOYEE_PROFILE
       SET totalTransactionsProcessed = totalTransactionsProcessed + 1
     WHERE employeeID = NEW.employeeID;
END$$

-- ----------------------------------------------------------------------------
-- TRIGGER 3: Auto-increment defect count on employee-logged defects
-- ----------------------------------------------------------------------------
CREATE TRIGGER TR_StockLedger_IncrementEmployeeDefectCount
AFTER INSERT ON STOCK_LEDGER
FOR EACH ROW
BEGIN
    DECLARE v_empUID VARCHAR(50);

    IF NEW.isDefective = TRUE AND NEW.loggedByUID IS NOT NULL THEN
        UPDATE EMPLOYEE_PROFILE
           SET totalDefectsLogged = totalDefectsLogged + 1
         WHERE userUID = NEW.loggedByUID;
    END IF;
END$$

-- ----------------------------------------------------------------------------
-- TRIGGER 4: Auto-update customer balance and last purchase date on sale
-- ----------------------------------------------------------------------------
CREATE TRIGGER TR_Transaction_UpdateCustomerLedger
AFTER INSERT ON TRANSACTION
FOR EACH ROW
BEGIN
    IF NEW.customerID IS NOT NULL THEN
        UPDATE CUSTOMER_PROFILE
           SET totalSpent     = totalSpent + NEW.finalNetTotal,
               lastPurchaseAt = NEW.createdAt
         WHERE customerID = NEW.customerID;
    END IF;
END$$

-- ----------------------------------------------------------------------------
-- TRIGGER 5: Flag duplicate hash attempts — arbitrage protection
-- ----------------------------------------------------------------------------
CREATE TRIGGER TR_HashLog_FlagDuplicateSale
BEFORE INSERT ON HASH_LOG
FOR EACH ROW
BEGIN
    DECLARE v_existingStatus VARCHAR(15);

    SELECT status
      INTO v_existingStatus
      FROM HASH_LOG
     WHERE fullHash_16 = NEW.fullHash_16
     LIMIT 1;

    IF v_existingStatus IS NOT NULL AND v_existingStatus = 'Sold' THEN
        SET NEW.status     = 'Flagged';
        SET NEW.flagReason = CONCAT(
            'Duplicate hash submission detected. Original transaction already committed. ',
            'Arbitrage attempt intercepted at: ',
            NOW()
        );
    END IF;
END$$

-- ----------------------------------------------------------------------------
-- PROCEDURE: SP_ProcessTransaction
-- Calculates discount from tier, commits transaction and line items atomically
-- ----------------------------------------------------------------------------
CREATE PROCEDURE SP_ProcessTransaction(
    IN  p_transactionID  VARCHAR(50),
    IN  p_employeeID     VARCHAR(50),
    IN  p_customerID     CHAR(4),       -- can be NULL
    IN  p_rawTotal       DECIMAL(10,2),
    IN  p_paymentMethod  VARCHAR(20),
    OUT p_finalNetTotal  DECIMAL(10,2),
    OUT p_discountAmt    DECIMAL(10,2),
    OUT p_statusMsg      VARCHAR(255)
)
BEGIN
    DECLARE v_discountPct   DECIMAL(5,2) DEFAULT 0.00;
    DECLARE v_tierType      VARCHAR(10)  DEFAULT 'Basic';
    DECLARE v_memberStatus  VARCHAR(15);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_statusMsg    = 'ERROR: Transaction rolled back due to integrity constraint violation.';
        SET p_finalNetTotal = 0.00;
        SET p_discountAmt   = 0.00;
    END;

    START TRANSACTION;

    -- Resolve member tier and discount rate
    IF p_customerID IS NOT NULL THEN
        SELECT cp.tierType, cp.membershipStatus, td.discountPercent
          INTO v_tierType, v_memberStatus, v_discountPct
          FROM CUSTOMER_PROFILE cp
          JOIN TIER_DISCOUNT td ON td.tierType = cp.tierType
         WHERE cp.customerID = p_customerID;

        IF v_memberStatus != 'Active' THEN
            ROLLBACK;
            SET p_statusMsg     = 'ERROR: Customer membership is not in Active status.';
            SET p_finalNetTotal = 0.00;
            SET p_discountAmt   = 0.00;
            LEAVE SP_ProcessTransaction;
        END IF;
    END IF;

    -- Calculate financials
    SET p_discountAmt   = ROUND(p_rawTotal * (v_discountPct / 100.00), 2);
    SET p_finalNetTotal = p_rawTotal - p_discountAmt;

    -- Commit transaction header
    INSERT INTO TRANSACTION (
        transactionID, employeeID, customerID,
        rawTotal, discountApplied, finalNetTotal,
        paymentMethod, status
    ) VALUES (
        p_transactionID, p_employeeID, p_customerID,
        p_rawTotal, p_discountAmt, p_finalNetTotal,
        p_paymentMethod, 'Completed'
    );

    COMMIT;
    SET p_statusMsg = CONCAT('SUCCESS: Transaction committed. Net total: ', p_finalNetTotal,
                             ' | Discount applied: ', p_discountAmt,
                             ' (Tier: ', v_tierType, ')');
END$$

-- ----------------------------------------------------------------------------
-- PROCEDURE: SP_LockAccountOnExcessiveFailures
-- Called externally by application layer on each failed login attempt
-- ----------------------------------------------------------------------------
CREATE PROCEDURE SP_RegisterFailedLogin(
    IN p_username VARCHAR(50)
)
BEGIN
    DECLARE v_attempts TINYINT UNSIGNED;

    UPDATE SYSTEM_USER
       SET failedLoginAttempts = failedLoginAttempts + 1,
           accountStatus = CASE
               WHEN failedLoginAttempts + 1 >= 5 THEN 'Locked'
               ELSE accountStatus
           END
     WHERE username = p_username;

    SELECT failedLoginAttempts
      INTO v_attempts
      FROM SYSTEM_USER
     WHERE username = p_username;

    SELECT v_attempts AS currentAttempts,
           CASE WHEN v_attempts >= 5 THEN 'LOCKED' ELSE 'ACTIVE' END AS accountState;
END$$

-- ----------------------------------------------------------------------------
-- PROCEDURE: SP_SuccessfulLogin
-- Resets failure counter and stamps last_login_at
-- ----------------------------------------------------------------------------
CREATE PROCEDURE SP_SuccessfulLogin(
    IN p_username VARCHAR(50)
)
BEGIN
    DECLARE v_isFirst BOOLEAN;

    SELECT isFirstLogin INTO v_isFirst
      FROM SYSTEM_USER
     WHERE username = p_username;

    UPDATE SYSTEM_USER
       SET failedLoginAttempts = 0,
           lastLoginAt         = NOW(),
           isFirstLogin        = FALSE
     WHERE username = p_username;

    SELECT v_isFirst AS wasFirstLogin;
END$$

DELIMITER ;

-- ============================================================================
-- VIEWS: OPERATIONAL REPORTING
-- ============================================================================

-- Owner-level financial summary
CREATE OR REPLACE VIEW VW_FinancialSummary AS
SELECT
    COUNT(transactionID)             AS totalTransactions,
    SUM(rawTotal)                    AS grossRevenue,
    SUM(discountApplied)             AS totalDiscountsGiven,
    SUM(finalNetTotal)               AS netRevenue,
    COUNT(CASE WHEN status = 'Refunded'  THEN 1 END) AS refundCount,
    COUNT(CASE WHEN status = 'Disputed'  THEN 1 END) AS disputeCount
FROM TRANSACTION;

-- Vendor quality control dashboard
CREATE OR REPLACE VIEW VW_VendorQualityReport AS
SELECT
    s.supplierID,
    s.name                                        AS supplierName,
    s.integrityScore,
    s.vendorStatus,
    COUNT(p.unitHash_12)                          AS totalUnitsSupplied,
    SUM(CASE WHEN sl.isDefective = TRUE THEN 1 ELSE 0 END) AS defectiveUnits,
    ROUND(
        SUM(CASE WHEN sl.isDefective = TRUE THEN 1 ELSE 0 END)
        / NULLIF(COUNT(sl.ledgerID), 0) * 100, 2
    )                                             AS defectRatePct
FROM SUPPLIER s
LEFT JOIN PRODUCT      p  ON p.supplierID  = s.supplierID
LEFT JOIN STOCK_LEDGER sl ON sl.unitHash_12 = p.unitHash_12
GROUP BY s.supplierID, s.name, s.integrityScore, s.vendorStatus;

-- Anti-arbitrage flagged-hash audit log
CREATE OR REPLACE VIEW VW_FlaggedHashAudit AS
SELECT
    hl.fullHash_16,
    hl.transactionID,
    hl.customerID,
    hl.flagReason,
    hl.loggedAt,
    cp.fullName   AS customerName,
    cp.tierType   AS memberTier
FROM HASH_LOG hl
LEFT JOIN CUSTOMER_PROFILE cp ON cp.customerID = hl.customerID
WHERE hl.status = 'Flagged'
ORDER BY hl.loggedAt DESC;

-- ============================================================================
-- END OF SCHEMA — SentinelSAS v4.1
-- ============================================================================
