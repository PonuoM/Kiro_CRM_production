-- Fix Missing AssignDate in Database
-- Updates AssignDate for customers that have Sales assigned but missing AssignDate
-- Run this in phpMyAdmin or MySQL command line

-- Display current status before fix
SELECT 
    'Current Status Before Fix' as status,
    COUNT(*) as total_customers,
    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' THEN 1 END) as assigned_customers,
    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' AND (AssignDate IS NULL OR AssignDate = '') THEN 1 END) as missing_assign_date,
    COUNT(CASE WHEN Sales IS NULL OR Sales = '' THEN 1 END) as unassigned_customers
FROM customers;

-- Show examples of customers that will be fixed
SELECT 
    'Customers that will be fixed' as info,
    CustomerCode,
    CustomerName,
    Sales,
    CreatedDate,
    AssignDate
FROM customers 
WHERE Sales IS NOT NULL 
AND Sales != '' 
AND (AssignDate IS NULL OR AssignDate = '')
LIMIT 10;

-- Update AssignDate for customers with Sales but missing AssignDate
-- Use CreatedDate as default AssignDate
UPDATE customers 
SET 
    AssignDate = CreatedDate,
    ModifiedDate = NOW(),
    ModifiedBy = 'system_fix'
WHERE Sales IS NOT NULL 
AND Sales != '' 
AND (AssignDate IS NULL OR AssignDate = '');

-- Show how many rows were affected
SELECT ROW_COUNT() as rows_updated;

-- Display status after fix
SELECT 
    'Status After Fix' as status,
    COUNT(*) as total_customers,
    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' THEN 1 END) as assigned_customers,
    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' AND AssignDate IS NOT NULL AND AssignDate != '' THEN 1 END) as customers_with_assign_date,
    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' AND (AssignDate IS NULL OR AssignDate = '') THEN 1 END) as still_missing_assign_date,
    COUNT(CASE WHEN Sales IS NULL OR Sales = '' THEN 1 END) as unassigned_customers
FROM customers;

-- Verify the fix by showing some updated records
SELECT 
    'Verification - Updated Records' as info,
    CustomerCode,
    CustomerName,
    Sales,
    CreatedDate,
    AssignDate,
    ModifiedDate,
    ModifiedBy
FROM customers 
WHERE ModifiedBy = 'system_fix'
AND AssignDate IS NOT NULL
LIMIT 10;