-- ===================================================================
-- Customer Intelligence System Database Schema
-- Phase 1: Customer Grading and Temperature System
-- Date: 20 July 2025
-- ===================================================================

-- Add Customer Grading System columns
ALTER TABLE customers 
ADD COLUMN CustomerGrade ENUM('A', 'B', 'C', 'D') NULL COMMENT 'Customer Grade based on purchase amount',
ADD COLUMN TotalPurchase DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total purchase amount for grading',
ADD COLUMN LastPurchaseDate DATE NULL COMMENT 'Last purchase date',
ADD COLUMN GradeCalculatedDate DATETIME NULL COMMENT 'When grade was last calculated';

-- Add Customer Temperature System columns  
ALTER TABLE customers
ADD COLUMN CustomerTemperature ENUM('HOT', 'WARM', 'COLD') DEFAULT 'WARM' COMMENT 'Customer interaction temperature',
ADD COLUMN LastContactDate DATE NULL COMMENT 'Last contact date for temperature calculation',
ADD COLUMN ContactAttempts INT DEFAULT 0 COMMENT 'Number of contact attempts',
ADD COLUMN TemperatureUpdatedDate DATETIME NULL COMMENT 'When temperature was last updated';

-- Add indexes for performance
CREATE INDEX idx_customer_grade ON customers(CustomerGrade);
CREATE INDEX idx_customer_temperature ON customers(CustomerTemperature);
CREATE INDEX idx_total_purchase ON customers(TotalPurchase);
CREATE INDEX idx_last_contact ON customers(LastContactDate);

-- ===================================================================
-- Create Grade Calculation Function
-- ===================================================================

DELIMITER //

CREATE FUNCTION CalculateCustomerGrade(purchase_amount DECIMAL(10,2)) 
RETURNS CHAR(1)
READS SQL DATA
DETERMINISTIC
BEGIN
    IF purchase_amount >= 10000 THEN
        RETURN 'A';
    ELSEIF purchase_amount >= 5000 THEN
        RETURN 'B';
    ELSEIF purchase_amount >= 2000 THEN
        RETURN 'C';
    ELSE
        RETURN 'D';
    END IF;
END //

DELIMITER ;

-- ===================================================================
-- Create Temperature Calculation Function
-- ===================================================================

DELIMITER //

CREATE FUNCTION CalculateCustomerTemperature(
    last_contact DATE,
    contact_attempts INT,
    customer_status VARCHAR(50)
) 
RETURNS VARCHAR(4)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE days_since_contact INT;
    
    -- Calculate days since last contact
    IF last_contact IS NULL THEN
        SET days_since_contact = 999; -- Very old
    ELSE
        SET days_since_contact = DATEDIFF(CURDATE(), last_contact);
    END IF;
    
    -- HOT: New customers or positive status
    IF customer_status IN ('ลูกค้าใหม่', 'คุยจบ', 'สนใจ') OR days_since_contact <= 7 THEN
        RETURN 'HOT';
    
    -- COLD: Not interested or many failed attempts
    ELSEIF customer_status IN ('ไม่สนใจ', 'ติดต่อไม่ได้') OR contact_attempts >= 3 THEN
        RETURN 'COLD';
    
    -- WARM: Everything else (normal follow-up)
    ELSE
        RETURN 'WARM';
    END IF;
END //

DELIMITER ;

-- ===================================================================
-- Create Stored Procedures for Grade Management
-- ===================================================================

-- Update customer grade based on total purchases
DELIMITER //

CREATE PROCEDURE UpdateCustomerGrade(IN customer_code VARCHAR(20))
BEGIN
    DECLARE total_amount DECIMAL(10,2) DEFAULT 0.00;
    DECLARE new_grade CHAR(1);
    
    -- Calculate total purchase amount from orders (if orders table exists)
    -- For now, we'll use the existing TotalPurchase value or set to 0
    SELECT COALESCE(TotalPurchase, 0) INTO total_amount
    FROM customers 
    WHERE CustomerCode = customer_code;
    
    -- If orders table exists in the future, uncomment this:
    -- SELECT COALESCE(SUM(TotalAmount), 0) INTO total_amount
    -- FROM orders 
    -- WHERE CustomerCode = customer_code AND OrderStatus IN ('completed', 'paid');
    
    -- Calculate grade
    SET new_grade = CalculateCustomerGrade(total_amount);
    
    -- Update customer record
    UPDATE customers 
    SET 
        TotalPurchase = total_amount,
        CustomerGrade = new_grade,
        GradeCalculatedDate = NOW()
    WHERE CustomerCode = customer_code;
    
END //

DELIMITER ;

-- ===================================================================
-- Create Stored Procedures for Temperature Management
-- ===================================================================

-- Update customer temperature based on contact history
DELIMITER //

CREATE PROCEDURE UpdateCustomerTemperature(IN customer_code VARCHAR(20))
BEGIN
    DECLARE last_contact DATE;
    DECLARE attempts INT DEFAULT 0;
    DECLARE current_status VARCHAR(50);
    DECLARE new_temperature VARCHAR(4);
    
    -- Get customer info
    SELECT LastContactDate, ContactAttempts, CustomerStatus 
    INTO last_contact, attempts, current_status
    FROM customers 
    WHERE CustomerCode = customer_code;
    
    -- Calculate temperature
    SET new_temperature = CalculateCustomerTemperature(last_contact, attempts, current_status);
    
    -- Update customer record
    UPDATE customers 
    SET 
        CustomerTemperature = new_temperature,
        TemperatureUpdatedDate = NOW()
    WHERE CustomerCode = customer_code;
    
END //

DELIMITER ;

-- ===================================================================
-- Create Batch Update Procedures
-- ===================================================================

-- Update all customer grades
DELIMITER //

CREATE PROCEDURE UpdateAllCustomerGrades()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE customer_code VARCHAR(20);
    DECLARE customer_cursor CURSOR FOR 
        SELECT CustomerCode FROM customers WHERE CustomerCode IS NOT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN customer_cursor;
    
    read_loop: LOOP
        FETCH customer_cursor INTO customer_code;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        CALL UpdateCustomerGrade(customer_code);
    END LOOP;
    
    CLOSE customer_cursor;
    
    SELECT CONCAT('Updated grades for all customers at ', NOW()) as result;
END //

DELIMITER ;

-- Update all customer temperatures
DELIMITER //

CREATE PROCEDURE UpdateAllCustomerTemperatures()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE customer_code VARCHAR(20);
    DECLARE customer_cursor CURSOR FOR 
        SELECT CustomerCode FROM customers WHERE CustomerCode IS NOT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN customer_cursor;
    
    read_loop: LOOP
        FETCH customer_cursor INTO customer_code;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        CALL UpdateCustomerTemperature(customer_code);
    END LOOP;
    
    CLOSE customer_cursor;
    
    SELECT CONCAT('Updated temperatures for all customers at ', NOW()) as result;
END //

DELIMITER ;

-- ===================================================================
-- Create Triggers for Automatic Updates
-- ===================================================================

-- Note: Order-related triggers are commented out because orders table doesn't exist yet
-- These can be enabled later when orders table is created

-- DELIMITER //
-- CREATE TRIGGER UpdateGradeOnOrderChange
-- AFTER INSERT ON orders
-- FOR EACH ROW
-- BEGIN
--     CALL UpdateCustomerGrade(NEW.CustomerCode);
-- END //

-- CREATE TRIGGER UpdateGradeOnOrderUpdate  
-- AFTER UPDATE ON orders
-- FOR EACH ROW
-- BEGIN
--     IF OLD.OrderStatus != NEW.OrderStatus OR OLD.TotalAmount != NEW.TotalAmount THEN
--         CALL UpdateCustomerGrade(NEW.CustomerCode);
--     END IF;
-- END //
-- DELIMITER ;

-- Note: Call log trigger is commented out because call_logs table doesn't exist yet
-- This can be enabled later when call_logs table is created

-- DELIMITER //
-- CREATE TRIGGER UpdateTemperatureOnCallLog
-- AFTER INSERT ON call_logs
-- FOR EACH ROW
-- BEGIN
--     -- Update last contact date and contact attempts
--     UPDATE customers 
--     SET 
--         LastContactDate = CURDATE(),
--         ContactAttempts = ContactAttempts + 1
--     WHERE CustomerCode = NEW.CustomerCode;
--     
--     -- Update temperature
--     CALL UpdateCustomerTemperature(NEW.CustomerCode);
-- END //
-- DELIMITER ;

-- ===================================================================
-- Initial Data Migration
-- ===================================================================

-- Set default values for existing customers
UPDATE customers 
SET 
    CustomerGrade = 'D',
    TotalPurchase = 0.00,
    CustomerTemperature = 'WARM',
    ContactAttempts = 0,
    GradeCalculatedDate = NOW(),
    TemperatureUpdatedDate = NOW()
WHERE CustomerGrade IS NULL;

-- Update grades based on existing orders (if orders table exists)
-- CALL UpdateAllCustomerGrades();

-- Update temperatures based on existing status
-- CALL UpdateAllCustomerTemperatures();

-- ===================================================================
-- Sample Queries for Testing
-- ===================================================================

-- Check grade distribution
-- SELECT CustomerGrade, COUNT(*) as count FROM customers GROUP BY CustomerGrade;

-- Check temperature distribution  
-- SELECT CustomerTemperature, COUNT(*) as count FROM customers GROUP BY CustomerTemperature;

-- Get A-grade HOT customers
-- SELECT * FROM customers WHERE CustomerGrade = 'A' AND CustomerTemperature = 'HOT';

-- ===================================================================
-- Performance Monitoring Views
-- ===================================================================

CREATE VIEW customer_intelligence_summary AS
SELECT 
    CustomerGrade,
    CustomerTemperature,
    COUNT(*) as customer_count,
    AVG(TotalPurchase) as avg_purchase,
    SUM(TotalPurchase) as total_revenue
FROM customers 
GROUP BY CustomerGrade, CustomerTemperature
ORDER BY CustomerGrade, CustomerTemperature;

-- ===================================================================
-- END OF SCHEMA
-- ===================================================================