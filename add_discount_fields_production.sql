-- Add discount fields to orders table for production
-- Database: primacom_CRM
-- Run this script to add discount functionality to orders

USE primacom_CRM;

-- Add discount fields to orders table
ALTER TABLE orders 
ADD COLUMN DiscountAmount DECIMAL(10,2) DEFAULT 0 COMMENT 'Discount amount in currency',
ADD COLUMN DiscountPercent DECIMAL(5,2) DEFAULT 0 COMMENT 'Discount percentage (0-100)',
ADD COLUMN DiscountRemarks NVARCHAR(500) DEFAULT '' COMMENT 'Discount remarks or reason',
ADD COLUMN ProductsDetail JSON COMMENT 'Detailed products information in JSON format',
ADD COLUMN SubtotalAmount DECIMAL(10,2) DEFAULT 0 COMMENT 'Subtotal before discount';

-- Add index for discount fields
ALTER TABLE orders 
ADD INDEX idx_discount_amount (DiscountAmount),
ADD INDEX idx_discount_percent (DiscountPercent);

-- Show updated table structure
DESCRIBE orders;

-- Verify the changes
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'primacom_CRM' 
AND TABLE_NAME = 'orders' 
AND COLUMN_NAME IN ('DiscountAmount', 'DiscountPercent', 'DiscountRemarks', 'ProductsDetail', 'SubtotalAmount');