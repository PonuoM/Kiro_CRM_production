-- CRM System Database Schema
-- Created for prima49_crm database
-- Character set: utf8mb4 for full Unicode support including Thai characters

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS prima49_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE prima49_crm;

-- Drop tables if they exist (for clean installation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS sales_histories;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS call_logs;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

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
    INDEX idx_status (Status)
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
    INDEX idx_order_date (OrderDate)
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
    INDEX idx_created_by (CreatedBy)
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
    INDEX idx_created_by (CreatedBy)
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
    INDEX idx_created_by (CreatedBy)
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
    INDEX idx_assign_by (AssignBy)
) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Sales assignment history';

-- Insert default admin user
-- Password: admin123 (hashed with password_hash())
INSERT INTO users (Username, Password, FirstName, LastName, Email, Role, CreatedBy) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'หลัก', 'admin@company.com', 'Admin', 'system');

-- Insert sample supervisor user
-- Password: supervisor123 (hashed with password_hash())
INSERT INTO users (Username, Password, FirstName, LastName, Email, Role, CreatedBy) VALUES 
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'หัวหน้า', 'ขาย', 'supervisor@company.com', 'Supervisor', 'admin');

-- Insert sample sale user
-- Password: sale123 (hashed with password_hash())
INSERT INTO users (Username, Password, FirstName, LastName, Email, Role, CreatedBy) VALUES 
('sale1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'พนักงาน', 'ขาย1', 'sale1@company.com', 'Sale', 'supervisor');

-- Create logs directory (MySQL cannot create directories, this is for documentation)
-- Create manually: mkdir logs

-- Set proper permissions and character set
ALTER DATABASE prima49_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Show tables created
SHOW TABLES;

-- Show table structures
DESCRIBE users;
DESCRIBE customers;
DESCRIBE call_logs;
DESCRIBE tasks;
DESCRIBE orders;
DESCRIBE sales_histories;