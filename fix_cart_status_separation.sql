-- Fix Cart Status Separation Issue
-- Add CartStatus field to customers table for proper basket separation
-- Created: 2025-07-29

SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS=0;

SELECT 'Adding CartStatus field to fix basket separation...' as Status;

-- Check if CartStatus column exists
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'CartStatus'
);

-- Add CartStatus column if it doesn't exist
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE customers ADD COLUMN CartStatus ENUM(''ตะกร้ารอ'', ''ตะกร้าแจก'', ''ลูกค้าแจกแล้ว'') DEFAULT ''ตะกร้ารอ'' COMMENT ''สถานะตะกร้า: ตะกร้ารอ=waiting basket, ตะกร้าแจก=distribution basket, ลูกค้าแจกแล้ว=assigned to sales'' AFTER CustomerStatus',
    'SELECT ''CartStatus column already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for CartStatus
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND INDEX_NAME = 'idx_cart_status'
);

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE customers ADD INDEX idx_cart_status (CartStatus)',
    'SELECT ''CartStatus index already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing data: Set proper CartStatus based on current Sales assignment
UPDATE customers SET 
    CartStatus = CASE 
        WHEN Sales IS NOT NULL AND Sales != '' THEN 'ลูกค้าแจกแล้ว'
        ELSE 'ตะกร้ารอ'
    END
WHERE CartStatus IS NULL OR CartStatus = '';

-- Add composite index for better performance
SET @composite_index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND INDEX_NAME = 'idx_cart_sales_status'
);

SET @sql = IF(@composite_index_exists = 0, 
    'ALTER TABLE customers ADD INDEX idx_cart_sales_status (CartStatus, Sales)',
    'SELECT ''Composite index already exists'' as Info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the change
SELECT 'Verification - CartStatus column info:' as Status;
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'customers' 
AND COLUMN_NAME = 'CartStatus';

-- Show distribution of CartStatus values
SELECT 'Current CartStatus distribution:' as Status;
SELECT CartStatus, COUNT(*) as Count 
FROM customers 
GROUP BY CartStatus;

SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;

SELECT 'CartStatus field added successfully!' as Status, NOW() as Timestamp;