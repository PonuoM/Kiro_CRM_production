-- ===================================================================
-- Safe Intelligence Columns Addition Script
-- Only adds columns that don't exist yet
-- Date: 20 July 2025
-- ===================================================================

-- Check and add CustomerGrade column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "CustomerGrade"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN CustomerGrade ENUM("A", "B", "C", "D") NULL COMMENT "Customer Grade based on purchase amount"',
    'SELECT "CustomerGrade column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add TotalPurchase column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "TotalPurchase"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN TotalPurchase DECIMAL(10,2) DEFAULT 0.00 COMMENT "Total purchase amount for grading"',
    'SELECT "TotalPurchase column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add LastPurchaseDate column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "LastPurchaseDate"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN LastPurchaseDate DATE NULL COMMENT "Last purchase date"',
    'SELECT "LastPurchaseDate column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add GradeCalculatedDate column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "GradeCalculatedDate"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN GradeCalculatedDate DATETIME NULL COMMENT "When grade was last calculated"',
    'SELECT "GradeCalculatedDate column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add CustomerTemperature column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "CustomerTemperature"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN CustomerTemperature ENUM("HOT", "WARM", "COLD") DEFAULT "WARM" COMMENT "Customer interaction temperature"',
    'SELECT "CustomerTemperature column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add LastContactDate column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "LastContactDate"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN LastContactDate DATE NULL COMMENT "Last contact date for temperature calculation"',
    'SELECT "LastContactDate column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add ContactAttempts column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "ContactAttempts"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN ContactAttempts INT DEFAULT 0 COMMENT "Number of contact attempts"',
    'SELECT "ContactAttempts column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add TemperatureUpdatedDate column if it doesn't exist
SET @sql = 'SELECT COUNT(*) INTO @col_exists FROM INFORMATION_SCHEMA.COLUMNS 
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "customers" AND COLUMN_NAME = "TemperatureUpdatedDate"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE customers ADD COLUMN TemperatureUpdatedDate DATETIME NULL COMMENT "When temperature was last updated"',
    'SELECT "TemperatureUpdatedDate column already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes only if they don't exist
CREATE INDEX IF NOT EXISTS idx_customer_grade ON customers(CustomerGrade);
CREATE INDEX IF NOT EXISTS idx_customer_temperature ON customers(CustomerTemperature);
CREATE INDEX IF NOT EXISTS idx_total_purchase ON customers(TotalPurchase);
CREATE INDEX IF NOT EXISTS idx_last_contact ON customers(LastContactDate);

-- Create or replace the summary view
CREATE OR REPLACE VIEW customer_intelligence_summary AS
SELECT 
    CustomerGrade,
    CustomerTemperature,
    COUNT(*) as customer_count,
    AVG(TotalPurchase) as avg_purchase,
    SUM(TotalPurchase) as total_revenue
FROM customers 
GROUP BY CustomerGrade, CustomerTemperature
ORDER BY CustomerGrade, CustomerTemperature;

-- Initialize default values for existing customers
UPDATE customers 
SET 
    CustomerGrade = COALESCE(CustomerGrade, 'D'),
    TotalPurchase = COALESCE(TotalPurchase, 0.00),
    CustomerTemperature = COALESCE(CustomerTemperature, 'WARM'),
    ContactAttempts = COALESCE(ContactAttempts, 0),
    GradeCalculatedDate = COALESCE(GradeCalculatedDate, NOW()),
    TemperatureUpdatedDate = COALESCE(TemperatureUpdatedDate, NOW())
WHERE CustomerGrade IS NULL OR CustomerTemperature IS NULL;

SELECT 'Intelligence system setup completed successfully!' as result;