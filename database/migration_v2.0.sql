-- Migration Script v2.0 for CRM System
-- Story 1.1: Alter Database Schema for Lead Management Logic
-- Author: Dev Agent (James)
-- Created: 2025-07-26
-- Purpose: Add ContactAttempts, AssignmentCount columns and ensure proper CustomerTemperature and supervisor_id fields

-- ================================
-- PRE-MIGRATION CHECKS
-- ================================

-- Set safe mode and backup current schema info
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;

-- Log migration start
SELECT 'Migration v2.0 Started' as Status, NOW() as Timestamp;

-- Check current database
SELECT DATABASE() as CurrentDatabase;

-- ================================
-- BACKUP EXISTING STRUCTURE
-- ================================

-- Create backup of current table structures
CREATE TABLE IF NOT EXISTS migration_backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_version VARCHAR(50),
    table_name VARCHAR(100),
    backup_info TEXT,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO migration_backup_log (migration_version, table_name, backup_info) 
VALUES ('v2.0', 'customers', 'Pre-migration backup - customers table structure');

INSERT INTO migration_backup_log (migration_version, table_name, backup_info) 
VALUES ('v2.0', 'users', 'Pre-migration backup - users table structure');

-- ================================
-- MIGRATION 1: ADD CONTACTATTEMPTS TO CUSTOMERS TABLE
-- ================================

SELECT 'Adding ContactAttempts column to customers table...' as Status;

-- Check if ContactAttempts column exists
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'ContactAttempts'
);

-- Add ContactAttempts column if it doesn't exist
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE customers ADD COLUMN ContactAttempts INT NOT NULL DEFAULT 0 COMMENT ''จำนวนครั้งที่ติดต่อลูกค้า'' AFTER CustomerTemperature',
    'SELECT ''ContactAttempts column already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for ContactAttempts
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND INDEX_NAME = 'idx_contact_attempts'
);

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE customers ADD INDEX idx_contact_attempts (ContactAttempts)',
    'SELECT ''ContactAttempts index already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- MIGRATION 2: ADD ASSIGNMENTCOUNT TO CUSTOMERS TABLE
-- ================================

SELECT 'Adding AssignmentCount column to customers table...' as Status;

-- Check if AssignmentCount column exists
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'AssignmentCount'
);

-- Add AssignmentCount column if it doesn't exist
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE customers ADD COLUMN AssignmentCount INT NOT NULL DEFAULT 0 COMMENT ''จำนวนครั้งที่ถูกแจกจ่าย'' AFTER ContactAttempts',
    'SELECT ''AssignmentCount column already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for AssignmentCount
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND INDEX_NAME = 'idx_assignment_count'
);

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE customers ADD INDEX idx_assignment_count (AssignmentCount)',
    'SELECT ''AssignmentCount index already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- MIGRATION 3: UPDATE CUSTOMERTEMPERATURE ENUM TO INCLUDE FROZEN
-- ================================

SELECT 'Updating CustomerTemperature ENUM to include FROZEN...' as Status;

-- Check current CustomerTemperature column definition
SET @column_type = (
    SELECT COLUMN_TYPE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'CustomerTemperature'
);

-- Update CustomerTemperature ENUM if it doesn't include FROZEN
SET @has_frozen = (@column_type LIKE '%FROZEN%');

SET @sql = IF(@has_frozen = 0, 
    'ALTER TABLE customers MODIFY COLUMN CustomerTemperature ENUM(''HOT'', ''WARM'', ''COLD'', ''FROZEN'') DEFAULT ''WARM'' COMMENT ''อุณหภูมิลูกค้า: HOT=สนใจมาก, WARM=สนใจ, COLD=ไม่ค่อยสนใจ, FROZEN=ไม่สนใจ''',
    'SELECT ''CustomerTemperature ENUM already includes FROZEN'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- MIGRATION 4: ENSURE SUPERVISOR_ID IN USERS TABLE
-- ================================

SELECT 'Ensuring supervisor_id field in users table...' as Status;

-- Check if supervisor_id column exists
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'supervisor_id'
);

-- Add supervisor_id column if it doesn't exist
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN supervisor_id INT NULL COMMENT ''เชื่อมโยงกับ Supervisor (users.id)'' AFTER Role',
    'SELECT ''supervisor_id column already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for supervisor_id
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND INDEX_NAME = 'idx_supervisor_id'
);

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE users ADD INDEX idx_supervisor_id (supervisor_id)',
    'SELECT ''supervisor_id index already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- MIGRATION 5: ADD FOREIGN KEY CONSTRAINT FOR SUPERVISOR_ID
-- ================================

SELECT 'Adding Foreign Key constraint for supervisor_id...' as Status;

-- Check if foreign key constraint exists
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'supervisor_id' 
    AND REFERENCED_TABLE_NAME = 'users'
);

-- Add foreign key constraint if it doesn't exist
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE users ADD CONSTRAINT fk_users_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT ''Foreign key constraint for supervisor_id already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- POST-MIGRATION VERIFICATION
-- ================================

SELECT 'Running post-migration verification...' as Status;

-- Verify customers table structure
SELECT 'Customers table verification:' as Status;
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customers' 
AND COLUMN_NAME IN ('ContactAttempts', 'AssignmentCount', 'CustomerTemperature')
ORDER BY ORDINAL_POSITION;

-- Verify users table structure
SELECT 'Users table verification:' as Status;
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'supervisor_id'
ORDER BY ORDINAL_POSITION;

-- Verify foreign key constraints
SELECT 'Foreign key verification:' as Status;
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'supervisor_id'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ================================
-- MIGRATION COMPLETION LOG
-- ================================

-- Log successful migration
INSERT INTO migration_backup_log (migration_version, table_name, backup_info) 
VALUES ('v2.0', 'migration_complete', CONCAT('Migration v2.0 completed successfully at ', NOW()));

-- Restore foreign key checks
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

SELECT 'Migration v2.0 Completed Successfully!' as Status, NOW() as Timestamp;

-- ================================
-- SUMMARY OF CHANGES
-- ================================

SELECT '=== MIGRATION v2.0 SUMMARY ===' as Summary;
SELECT 'AC1: ContactAttempts column added to customers table' as AcceptanceCriteria;
SELECT 'AC2: AssignmentCount column added to customers table' as AcceptanceCriteria;
SELECT 'AC3: CustomerTemperature ENUM updated to include FROZEN' as AcceptanceCriteria;
SELECT 'AC4: supervisor_id field added to users table with Foreign Key' as AcceptanceCriteria;

-- Show final table structures for verification
SELECT 'Final customers table structure (relevant columns):' as Info;
DESCRIBE customers;

SELECT 'Final users table structure:' as Info;
DESCRIBE users;