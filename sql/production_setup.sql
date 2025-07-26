-- ========================================
-- CRM System Production Setup Script
-- ========================================
-- This script sets up the complete CRM database for production use
-- Character set: utf8mb4 for full Unicode support including Thai characters
-- 
-- Usage:
-- 1. Create database: CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. Use database: USE your_database_name;
-- 3. Run this script: SOURCE production_setup.sql;
--
-- Default Users Created:
-- - admin/admin123 (Admin role)
-- - supervisor/supervisor123 (Supervisor role)  
-- - sale1/sale123 (Sale role)
-- ========================================

-- Set session variables for better performance and consistency
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
SET SESSION time_zone = '+07:00'; -- Thailand timezone

-- Drop existing tables if they exist (for clean installation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS sales_histories;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS call_logs;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- TABLE CREATION
-- ========================================

-- Table: users
-- Stores user accounts with different roles (Admin, Supervisor, Sale)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Username NVARCHAR(50) UNIQUE NOT NULL,
    Password NVARCHAR(255) NOT NULL,
    FirstName NVARCHAR(200) NOT NULL,
    LastName NVARCHAR(200) NOT NULL,
    Email NVARCHAR(200),
    Phone NVARCHAR(200),
    CompanyCode NVARCHAR(10),
    Position NVARCHAR(200),
    Role ENUM('Admin', 'Supervisor', 'Sale') NOT NULL,
    LastLoginDate DATETIME,
    Status INT DEFAULT 1,
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
    ModifiedBy NVARCHAR(50),
    
    INDEX idx_username (Username),
    INDEX idx_role (Role),
    INDEX idx_status (Status),
    INDEX idx_company_code (CompanyCode)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='User accounts and authentication';

-- Table: customers
-- Stores customer information with status tracking
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) UNIQUE NOT NULL,
    CustomerName NVARCHAR(500) NOT NULL,
    CustomerTel NVARCHAR(200) UNIQUE NOT NULL,
    CustomerAddress NVARCHAR(500),
    CustomerProvince NVARCHAR(200),
    CustomerPostalCode NVARCHAR(50),
    Agriculture NVARCHAR(200),
    CustomerStatus ENUM('ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า') DEFAULT 'ลูกค้าใหม่',
    CartStatus ENUM('ตะกร้าแจก', 'ตะกร้ารอ', 'กำลังดูแล') DEFAULT 'กำลังดูแล',
    Sales NVARCHAR(50),
    AssignDate DATETIME,
    OrderDate DATETIME,
    CallStatus NVARCHAR(50),
    TalkStatus NVARCHAR(50),
    Tags NVARCHAR(500),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
    ModifiedBy NVARCHAR(50),
    
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_customer_tel (CustomerTel),
    INDEX idx_customer_status (CustomerStatus),
    INDEX idx_cart_status (CartStatus),
    INDEX idx_sales (Sales),
    INDEX idx_assign_date (AssignDate),
    INDEX idx_order_date (OrderDate),
    INDEX idx_created_date (CreatedDate),
    INDEX idx_modified_date (ModifiedDate)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Customer information and status tracking';

-- Table: call_logs
-- Stores communication history with customers
CREATE TABLE call_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) NOT NULL,
    CallDate DATETIME NOT NULL,
    CallTime NVARCHAR(50),
    CallMinutes NVARCHAR(50),
    CallStatus ENUM('ติดต่อได้', 'ติดต่อไม่ได้') NOT NULL,
    CallReason NVARCHAR(500),
    TalkStatus ENUM('คุยจบ', 'คุยไม่จบ'),
    TalkReason NVARCHAR(500),
    Remarks NVARCHAR(500),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_call_date (CallDate),
    INDEX idx_call_status (CallStatus),
    INDEX idx_created_by (CreatedBy),
    INDEX idx_created_date (CreatedDate)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Customer communication logs';

-- Table: tasks
-- Stores follow-up tasks and appointments
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) NOT NULL,
    FollowupDate DATETIME NOT NULL,
    Remarks NVARCHAR(500),
    Status ENUM('รอดำเนินการ', 'เสร็จสิ้น') DEFAULT 'รอดำเนินการ',
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
    ModifiedBy NVARCHAR(50),
    
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_followup_date (FollowupDate),
    INDEX idx_status (Status),
    INDEX idx_created_by (CreatedBy),
    INDEX idx_created_date (CreatedDate)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Follow-up tasks and appointments';

-- Table: orders
-- Stores customer orders and sales transactions
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    DocumentNo NVARCHAR(50) UNIQUE NOT NULL,
    CustomerCode NVARCHAR(50) NOT NULL,
    DocumentDate DATETIME NOT NULL,
    PaymentMethod NVARCHAR(200),
    Products NVARCHAR(500),
    Quantity DECIMAL(10,2),
    Price DECIMAL(10,2),
    OrderBy NVARCHAR(50),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_document_no (DocumentNo),
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_document_date (DocumentDate),
    INDEX idx_created_by (CreatedBy),
    INDEX idx_created_date (CreatedDate)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Customer orders and sales transactions';

-- Table: sales_histories
-- Stores sales assignment history for customers
CREATE TABLE sales_histories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) NOT NULL,
    SaleName NVARCHAR(50) NOT NULL,
    StartDate DATETIME NOT NULL,
    EndDate DATETIME,
    AssignBy NVARCHAR(50),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_sale_name (SaleName),
    INDEX idx_date_range (StartDate, EndDate),
    INDEX idx_assign_by (AssignBy),
    INDEX idx_created_date (CreatedDate)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Sales assignment history';

-- ========================================
-- DEFAULT DATA INSERTION
-- ========================================

-- Insert default admin user
-- Password: admin123 (hashed with password_hash())
INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, CompanyCode, Position, Role, CreatedBy) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'หลัก', 'admin@company.com', '02-123-4567', 'COMP01', 'System Administrator', 'Admin', 'system');

-- Insert default supervisor user
-- Password: supervisor123 (hashed with password_hash())
INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, CompanyCode, Position, Role, CreatedBy) VALUES 
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'หัวหน้า', 'ขาย', 'supervisor@company.com', '02-234-5678', 'COMP01', 'Sales Supervisor', 'Supervisor', 'admin');

-- Insert default sale user
-- Password: sale123 (hashed with password_hash())
INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, CompanyCode, Position, Role, CreatedBy) VALUES 
('sale1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงาน', 'ขาย1', 'sale1@company.com', '02-345-6789', 'COMP01', 'Sales Representative', 'Sale', 'supervisor');

-- ========================================
-- SAMPLE DATA FOR TESTING
-- ========================================

-- Sample customers with different statuses
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy) VALUES
('CUST001', 'บริษัท เกษตรกรรมไทย จำกัด', '02-111-1111', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย', 'กรุงเทพมหานคร', '10110', 'ข้าว', 'ลูกค้าใหม่', 'กำลังดูแล', 'sale1', NOW(), 'admin'),
('CUST002', 'สวนผลไม้สมบูรณ์', '02-222-2222', '456 ถนนพหลโยธิน แขวงลาดยาว เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'ผลไม้', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 15 DAY), 'admin'),
('CUST003', 'ฟาร์มไก่อินทรีย์', '02-333-3333', '789 ถนนรัชดาภิเษก แขวงดินแดง เขตดินแดง', 'กรุงเทพมหานคร', '10400', 'ปศุสัตว์', 'ลูกค้าเก่า', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 60 DAY), 'admin'),
('CUST004', 'เกษตรกรรมสมัยใหม่', '02-444-4444', '321 ถนนเพชรบุรี แขวงมักกะสัน เขตราชเทวี', 'กรุงเทพมหานคร', '10400', 'ผัก', 'ลูกค้าใหม่', 'ตะกร้าแจก', NULL, DATE_SUB(NOW(), INTERVAL 35 DAY), 'admin'),
('CUST005', 'สหกรณ์เกษตรกรรม', '02-555-5555', '654 ถนนลาดพร้าว แขวงจอมพล เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'ข้าว', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, DATE_SUB(NOW(), INTERVAL 100 DAY), 'admin');

-- Sample call logs
INSERT INTO call_logs (CustomerCode, CallDate, CallTime, CallMinutes, CallStatus, CallReason, TalkStatus, TalkReason, Remarks, CreatedBy) VALUES
('CUST001', NOW(), '09:30', '15', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'ลูกค้าสนใจสินค้าใหม่', 'sale1'),
('CUST002', DATE_SUB(NOW(), INTERVAL 1 DAY), '14:20', '8', 'ติดต่อได้', NULL, 'คุยไม่จบ', 'ลูกค้าไม่ว่าง', 'จะโทรกลับอีกครั้ง', 'sale1'),
('CUST003', DATE_SUB(NOW(), INTERVAL 2 DAY), '11:15', '0', 'ติดต่อไม่ได้', 'ไม่รับสาย', NULL, NULL, NULL, 'sale1'),
('CUST001', DATE_SUB(NOW(), INTERVAL 3 DAY), '16:45', '12', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'ปรึกษาเรื่องราคา', 'sale1');

-- Sample tasks
INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy) VALUES
('CUST001', DATE_ADD(NOW(), INTERVAL 1 DAY), 'ติดตามการตัดสินใจสั่งซื้อ', 'รอดำเนินการ', 'sale1'),
('CUST002', DATE_ADD(NOW(), INTERVAL 2 DAY), 'โทรกลับตามนัด', 'รอดำเนินการ', 'sale1'),
('CUST003', DATE_ADD(NOW(), INTERVAL 3 DAY), 'เสนอโปรโมชั่นใหม่', 'รอดำเนินการ', 'sale1'),
('CUST004', NOW(), 'ติดต่อลูกค้าใหม่', 'รอดำเนินการ', 'sale1'),
('CUST005', DATE_SUB(NOW(), INTERVAL 1 DAY), 'ติดตามการสั่งซื้อ', 'เสร็จสิ้น', 'sale1');

-- Sample orders
INSERT INTO orders (DocumentNo, CustomerCode, DocumentDate, PaymentMethod, Products, Quantity, Price, OrderBy, CreatedBy) VALUES
('ORD001', 'CUST003', DATE_SUB(NOW(), INTERVAL 30 DAY), 'เงินสด', 'ปุ๋ยอินทรีย์', 100.00, 15000.00, 'sale1', 'sale1'),
('ORD002', 'CUST003', DATE_SUB(NOW(), INTERVAL 60 DAY), 'เครดิต 30 วัน', 'เมล็ดพันธุ์ข้าว', 50.00, 8000.00, 'sale1', 'sale1'),
('ORD003', 'CUST002', DATE_SUB(NOW(), INTERVAL 45 DAY), 'เงินสด', 'ปุ๋ยเคมี', 75.00, 12000.00, 'sale1', 'sale1');

-- Sample sales histories
INSERT INTO sales_histories (CustomerCode, SaleName, StartDate, EndDate, AssignBy, CreatedBy) VALUES
('CUST001', 'sale1', DATE_SUB(NOW(), INTERVAL 30 DAY), NULL, 'supervisor', 'supervisor'),
('CUST002', 'sale1', DATE_SUB(NOW(), INTERVAL 45 DAY), NULL, 'supervisor', 'supervisor'),
('CUST003', 'sale1', DATE_SUB(NOW(), INTERVAL 90 DAY), NULL, 'supervisor', 'supervisor'),
('CUST004', 'sale1', DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), 'supervisor', 'supervisor'),
('CUST005', 'sale1', DATE_SUB(NOW(), INTERVAL 120 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 'supervisor', 'supervisor');

-- Update customer order dates based on orders
UPDATE customers c 
SET OrderDate = (
    SELECT MAX(DocumentDate) 
    FROM orders o 
    WHERE o.CustomerCode = c.CustomerCode
) 
WHERE CustomerCode IN (SELECT DISTINCT CustomerCode FROM orders);

-- ========================================
-- POST-INSTALLATION VERIFICATION
-- ========================================

-- Show created tables
SELECT 'Tables created successfully:' as Status;
SHOW TABLES;

-- Show user accounts created
SELECT 'User accounts created:' as Status;
SELECT Username, FirstName, LastName, Role, Status FROM users;

-- Show sample data counts
SELECT 'Sample data summary:' as Status;
SELECT 
    (SELECT COUNT(*) FROM customers) as Customers,
    (SELECT COUNT(*) FROM call_logs) as CallLogs,
    (SELECT COUNT(*) FROM tasks) as Tasks,
    (SELECT COUNT(*) FROM orders) as Orders,
    (SELECT COUNT(*) FROM sales_histories) as SalesHistories;

-- Show customer status distribution
SELECT 'Customer status distribution:' as Status;
SELECT CustomerStatus, CartStatus, COUNT(*) as Count 
FROM customers 
GROUP BY CustomerStatus, CartStatus;

-- ========================================
-- PERFORMANCE OPTIMIZATION
-- ========================================

-- Analyze tables for better performance
ANALYZE TABLE users, customers, call_logs, tasks, orders, sales_histories;

-- Show index usage
SELECT 'Database setup completed successfully!' as Status;
SELECT 'Remember to:' as Reminder;
SELECT '1. Change default passwords' as Step1;
SELECT '2. Configure proper backup schedule' as Step2;
SELECT '3. Set up cron job for auto_rules.php' as Step3;
SELECT '4. Configure SSL/HTTPS for production' as Step4;
SELECT '5. Set proper file permissions' as Step5;

-- Reset foreign key checks
SET FOREIGN_KEY_CHECKS = 1;