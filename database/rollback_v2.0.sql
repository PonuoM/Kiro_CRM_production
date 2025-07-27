-- Rollback Script for Migration v2.0
-- Story 1.1: Alter Database Schema - ROLLBACK PROCEDURES
-- Author: Dev Agent (James)
-- Created: 2025-07-26
-- Purpose: Safely rollback migration v2.0 changes if needed

-- ================================
-- ROLLBACK SAFETY CHECKS
-- ================================

-- Set safe mode
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;

-- Log rollback start
SELECT 'Migration v2.0 ROLLBACK Started' as Status, NOW() as Timestamp;

-- Check current database
SELECT DATABASE() as CurrentDatabase;

-- ================================
-- PRE-ROLLBACK VERIFICATION
-- ================================

-- Verify we have something to rollback
SELECT 'Checking for migration v2.0 artifacts...' as Status;

-- Check if migration columns exist
SET @contact_attempts_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'ContactAttempts'
);

SET @assignment_count_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'AssignmentCount'
);

SET @supervisor_id_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'supervisor_id'
);

-- Display what will be rolled back
SELECT CASE 
    WHEN @contact_attempts_exists = 1 THEN 'Will remove ContactAttempts column' 
    ELSE 'ContactAttempts column not found - nothing to rollback'
END as ContactAttemptsRollback;

SELECT CASE 
    WHEN @assignment_count_exists = 1 THEN 'Will remove AssignmentCount column' 
    ELSE 'AssignmentCount column not found - nothing to rollback'
END as AssignmentCountRollback;

SELECT CASE 
    WHEN @supervisor_id_exists = 1 THEN 'Will remove supervisor_id column and FK constraint' 
    ELSE 'supervisor_id column not found - nothing to rollback'
END as SupervisorIdRollback;

-- ================================
-- BACKUP DATA BEFORE ROLLBACK
-- ================================

-- Create rollback backup table for ContactAttempts data
CREATE TABLE IF NOT EXISTS rollback_backup_contact_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50),
    ContactAttempts INT,
    backup_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Backup ContactAttempts data if column exists
SET @sql = IF(@contact_attempts_exists = 1, 
    'INSERT INTO rollback_backup_contact_attempts (CustomerCode, ContactAttempts) SELECT CustomerCode, ContactAttempts FROM customers WHERE ContactAttempts > 0',
    'SELECT ''No ContactAttempts data to backup'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create rollback backup table for AssignmentCount data
CREATE TABLE IF NOT EXISTS rollback_backup_assignment_count (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50),
    AssignmentCount INT,
    backup_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Backup AssignmentCount data if column exists
SET @sql = IF(@assignment_count_exists = 1, 
    'INSERT INTO rollback_backup_assignment_count (CustomerCode, AssignmentCount) SELECT CustomerCode, AssignmentCount FROM customers WHERE AssignmentCount > 0',
    'SELECT ''No AssignmentCount data to backup'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create rollback backup table for supervisor_id data
CREATE TABLE IF NOT EXISTS rollback_backup_supervisor_id (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    supervisor_id INT,
    backup_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Backup supervisor_id data if column exists
SET @sql = IF(@supervisor_id_exists = 1, 
    'INSERT INTO rollback_backup_supervisor_id (user_id, supervisor_id) SELECT id, supervisor_id FROM users WHERE supervisor_id IS NOT NULL',
    'SELECT ''No supervisor_id data to backup'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Data backup completed for rollback safety' as Status;

-- ================================
-- ROLLBACK 1: REMOVE FOREIGN KEY CONSTRAINT
-- ================================

SELECT 'Removing Foreign Key constraint for supervisor_id...' as Status;

-- Check if foreign key constraint exists
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'supervisor_id' 
    AND REFERENCED_TABLE_NAME = 'users'
);

-- Remove foreign key constraint if it exists
SET @sql = IF(@fk_exists = 1, 
    'ALTER TABLE users DROP FOREIGN KEY fk_users_supervisor',
    'SELECT ''Foreign key constraint for supervisor_id does not exist'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- ROLLBACK 2: REMOVE SUPERVISOR_ID COLUMN
-- ================================

SELECT 'Removing supervisor_id column from users table...' as Status;

-- Remove supervisor_id index first
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND INDEX_NAME = 'idx_supervisor_id'
);

SET @sql = IF(@index_exists = 1, 
    'ALTER TABLE users DROP INDEX idx_supervisor_id',
    'SELECT ''supervisor_id index does not exist'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove supervisor_id column
SET @sql = IF(@supervisor_id_exists = 1, 
    'ALTER TABLE users DROP COLUMN supervisor_id',
    'SELECT ''supervisor_id column does not exist'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- ROLLBACK 3: REVERT CUSTOMERTEMPERATURE ENUM
-- ================================

SELECT 'Reverting CustomerTemperature ENUM (removing FROZEN)...' as Status;

-- Check current CustomerTemperature column definition
SET @column_type = (
    SELECT COLUMN_TYPE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'CustomerTemperature'
);

-- Check if any customers have FROZEN temperature
SET @frozen_count = 0;
SET @temp_column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'CustomerTemperature'
);

-- Get count of FROZEN records if column exists
SET @sql = IF(@temp_column_exists = 1, 
    'SELECT COUNT(*) FROM customers WHERE CustomerTemperature = ''FROZEN'' INTO @frozen_count',
    'SELECT 0 INTO @frozen_count'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Warn about FROZEN data
SELECT CASE 
    WHEN @frozen_count > 0 THEN CONCAT('WARNING: ', @frozen_count, ' customers have FROZEN temperature - they will be set to COLD') 
    ELSE 'No FROZEN temperature data found'
END as FrozenDataWarning;

-- Update FROZEN records to COLD before removing FROZEN from ENUM
SET @sql = IF(@frozen_count > 0, 
    'UPDATE customers SET CustomerTemperature = ''COLD'' WHERE CustomerTemperature = ''FROZEN''',
    'SELECT ''No FROZEN records to update'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Revert CustomerTemperature ENUM to original values
SET @has_frozen = (@column_type LIKE '%FROZEN%');

SET @sql = IF(@has_frozen = 1, 
    'ALTER TABLE customers MODIFY COLUMN CustomerTemperature ENUM(''HOT'', ''WARM'', ''COLD'') DEFAULT ''WARM'' COMMENT ''อุณหภูมิลูกค้า: HOT=สนใจมาก, WARM=สนใจ, COLD=ไม่ค่อยสนใจ''',
    'SELECT ''CustomerTemperature ENUM does not include FROZEN'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- ROLLBACK 4: REMOVE ASSIGNMENTCOUNT COLUMN
-- ================================

SELECT 'Removing AssignmentCount column from customers table...' as Status;

-- Remove AssignmentCount index first
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND INDEX_NAME = 'idx_assignment_count'
);

SET @sql = IF(@index_exists = 1, 
    'ALTER TABLE customers DROP INDEX idx_assignment_count',
    'SELECT ''AssignmentCount index does not exist'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove AssignmentCount column
SET @sql = IF(@assignment_count_exists = 1, 
    'ALTER TABLE customers DROP COLUMN AssignmentCount',
    'SELECT ''AssignmentCount column does not exist'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- ROLLBACK 5: REMOVE CONTACTATTEMPTS COLUMN
-- ================================

SELECT 'Removing ContactAttempts column from customers table...' as Status;

-- Remove ContactAttempts index first
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND INDEX_NAME = 'idx_contact_attempts'
);

SET @sql = IF(@index_exists = 1, 
    'ALTER TABLE customers DROP INDEX idx_contact_attempts',
    'SELECT ''ContactAttempts index does not exist'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove ContactAttempts column
SET @sql = IF(@contact_attempts_exists = 1, 
    'ALTER TABLE customers DROP COLUMN ContactAttempts',
    'SELECT ''ContactAttempts column does not exist'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================
-- POST-ROLLBACK VERIFICATION
-- ================================

SELECT 'Running post-rollback verification...' as Status;

-- Verify columns are removed
SELECT 'Customers table verification (should NOT have migration columns):' as Status;
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customers' 
AND COLUMN_NAME IN ('ContactAttempts', 'AssignmentCount', 'CustomerTemperature')
ORDER BY ORDINAL_POSITION;

-- Verify users table
SELECT 'Users table verification (should NOT have supervisor_id):' as Status;
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'users' 
AND COLUMN_NAME = 'supervisor_id'
ORDER BY ORDINAL_POSITION;

-- ================================
-- ROLLBACK COMPLETION LOG
-- ================================

-- Log successful rollback
INSERT INTO migration_backup_log (migration_version, table_name, backup_info) 
VALUES ('v2.0_ROLLBACK', 'rollback_complete', CONCAT('Migration v2.0 ROLLBACK completed successfully at ', NOW()));

-- Restore foreign key checks
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

SELECT 'Migration v2.0 ROLLBACK Completed Successfully!' as Status, NOW() as Timestamp;

-- ================================
-- ROLLBACK SUMMARY
-- ================================

SELECT '=== ROLLBACK v2.0 SUMMARY ===' as Summary;
SELECT 'Rolled back: ContactAttempts column removed from customers' as RollbackAction;
SELECT 'Rolled back: AssignmentCount column removed from customers' as RollbackAction;
SELECT 'Rolled back: CustomerTemperature ENUM reverted (FROZEN removed)' as RollbackAction;
SELECT 'Rolled back: supervisor_id column and FK constraint removed from users' as RollbackAction;

-- Show data recovery information
SELECT '=== DATA RECOVERY INFORMATION ===' as DataRecovery;
SELECT 'Backed up data is available in these tables:' as Info;
SELECT '- rollback_backup_contact_attempts' as BackupTable;
SELECT '- rollback_backup_assignment_count' as BackupTable;
SELECT '- rollback_backup_supervisor_id' as BackupTable;

SELECT 'Database schema has been reverted to pre-migration v2.0 state' as FinalStatus;