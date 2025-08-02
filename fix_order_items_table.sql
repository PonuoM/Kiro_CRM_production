-- ===============================================
-- แก้ไขปัญหา Foreign Key สำหรับ order_items table
-- ===============================================

-- Step 1: ตรวจสอบโครงสร้าง orders table
DESCRIBE orders;

-- Step 2: ตรวจสอบ indexes ใน orders table
SHOW INDEX FROM orders;

-- Step 3: ตรวจสอบ collation
SHOW CREATE TABLE orders;

-- Step 4: สร้าง order_items table โดยไม่มี Foreign Key ก่อน
DROP TABLE IF EXISTS order_items;

CREATE TABLE order_items (
    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    DocumentNo varchar(50) NOT NULL COMMENT 'Reference to orders.DocumentNo',
    ProductCode varchar(50) DEFAULT NULL COMMENT 'Product code/SKU',
    ProductName varchar(200) NOT NULL COMMENT 'Product name',
    UnitPrice decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Price per unit',
    Quantity decimal(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Quantity of this product',
    LineTotal decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'UnitPrice * Quantity',
    ItemDiscount decimal(10,2) DEFAULT 0.00 COMMENT 'Discount for this item only',
    ItemDiscountPercent decimal(5,2) DEFAULT 0.00 COMMENT 'Discount percentage for this item',
    CreatedDate datetime DEFAULT CURRENT_TIMESTAMP,
    CreatedBy varchar(50) DEFAULT NULL,
    
    -- Indexes for performance
    INDEX idx_document_no (DocumentNo),
    INDEX idx_product_code (ProductCode),
    INDEX idx_product_name (ProductName),
    INDEX idx_created_date (CreatedDate)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Order items detail - each product line in an order';

-- Step 5: ตรวจสอบว่า orders.DocumentNo มี UNIQUE index หรือไม่
-- ถ้าไม่มี ให้เพิ่ม
-- ALTER TABLE orders ADD UNIQUE KEY uk_document_no (DocumentNo);

-- Step 6: เพิ่ม Foreign Key หลังจากตาราง order_items ถูกสร้างแล้ว
-- (รันคำสั่งนี้หลังจากตรวจสอบแล้วว่า orders.DocumentNo มี UNIQUE constraint)

-- ALTER TABLE order_items 
-- ADD CONSTRAINT fk_order_items_orders 
-- FOREIGN KEY (DocumentNo) REFERENCES orders(DocumentNo) 
-- ON DELETE CASCADE ON UPDATE CASCADE;

-- Step 7: แสดงโครงสร้างที่สร้างเสร็จแล้ว
DESCRIBE order_items;
SHOW CREATE TABLE order_items;

-- ===============================================
-- คำแนะนำ:
-- ===============================================
-- 1. รัน Step 1-4 ก่อน (สร้างตารางโดยไม่มี Foreign Key)
-- 2. ตรวจสอบ orders.DocumentNo ว่ามี UNIQUE constraint หรือไม่
-- 3. ถ้าไม่มี ให้รัน ALTER TABLE orders ADD UNIQUE KEY
-- 4. จากนั้นรัน Step 6 เพื่อเพิ่ม Foreign Key
-- ===============================================