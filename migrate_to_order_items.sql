-- =====================================
-- Migration Script: Orders → Order Items
-- =====================================
-- เปลี่ยนจาก orders table เดียว เป็น orders + order_items
-- รักษา backward compatibility

-- Phase 1: เพิ่ม TotalItems column ใน orders table
-- =============================================

ALTER TABLE orders 
ADD COLUMN TotalItems int DEFAULT 0 COMMENT 'Number of different products in this order'
AFTER SubtotalAmount;

-- Phase 2: สร้าง order_items table
-- ================================

CREATE TABLE IF NOT EXISTS order_items (
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
    INDEX idx_created_date (CreatedDate),
    
    -- Foreign Key constraint
    CONSTRAINT fk_order_items_orders 
        FOREIGN KEY (DocumentNo) REFERENCES orders(DocumentNo) 
        ON DELETE CASCADE ON UPDATE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Order items detail - each product line in an order';

-- Phase 3: สร้าง View สำหรับ Reports
-- =================================

CREATE OR REPLACE VIEW order_items_report AS
SELECT 
    -- Order Header Information
    o.id as OrderId,
    o.DocumentNo,
    o.CustomerCode,
    o.DocumentDate,
    o.PaymentMethod,
    o.OrderBy,
    o.CreatedBy,
    o.CreatedDate,
    
    -- Order Totals
    o.TotalItems,
    o.Quantity as OrderQuantity,
    o.SubtotalAmount as OrderSubtotal,
    o.DiscountAmount as OrderDiscount,
    o.DiscountPercent as OrderDiscountPercent,
    o.DiscountRemarks,
    o.Price as OrderTotal,
    
    -- Item Details
    oi.id as ItemId,
    oi.ProductCode,
    oi.ProductName,
    oi.UnitPrice,
    oi.Quantity as ItemQuantity,
    oi.LineTotal,
    oi.ItemDiscount,
    oi.ItemDiscountPercent,
    
    -- Calculated Fields
    (oi.LineTotal - IFNULL(oi.ItemDiscount, 0)) as ItemNetTotal,
    CASE 
        WHEN o.SubtotalAmount > 0 THEN ROUND((oi.LineTotal / o.SubtotalAmount) * 100, 2)
        ELSE 0 
    END as ItemPercentOfOrder
    
FROM orders o
LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo
ORDER BY o.DocumentDate DESC, o.DocumentNo, oi.id;

-- Phase 4: Migration ข้อมูลเก่า (ถ้ามี ProductsDetail JSON)
-- =======================================================

DELIMITER $$

CREATE PROCEDURE MigrateExistingOrdersToItems()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE doc_no VARCHAR(50);
    DECLARE products_detail LONGTEXT;
    DECLARE created_by VARCHAR(50);
    DECLARE created_date DATETIME;
    
    -- Cursor สำหรับดึงข้อมูล orders ที่มี ProductsDetail
    DECLARE order_cursor CURSOR FOR 
        SELECT DocumentNo, ProductsDetail, CreatedBy, CreatedDate 
        FROM orders 
        WHERE ProductsDetail IS NOT NULL 
        AND ProductsDetail != '' 
        AND DocumentNo NOT IN (SELECT DISTINCT DocumentNo FROM order_items);
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN order_cursor;
    
    read_loop: LOOP
        FETCH order_cursor INTO doc_no, products_detail, created_by, created_date;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- ตรวจสอบว่า ProductsDetail เป็น JSON ที่ valid หรือไม่
        IF JSON_VALID(products_detail) THEN
            -- สร้าง order_items จาก JSON data
            INSERT INTO order_items (
                DocumentNo, 
                ProductCode, 
                ProductName, 
                UnitPrice, 
                Quantity, 
                LineTotal,
                CreatedBy,
                CreatedDate
            )
            SELECT 
                doc_no,
                JSON_UNQUOTE(JSON_EXTRACT(product_json.value, '$.code')) as ProductCode,
                JSON_UNQUOTE(JSON_EXTRACT(product_json.value, '$.name')) as ProductName,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(product_json.value, '$.price')) AS DECIMAL(10,2)) as UnitPrice,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(product_json.value, '$.quantity')) AS DECIMAL(10,2)) as Quantity,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(product_json.value, '$.price')) AS DECIMAL(10,2)) * 
                CAST(JSON_UNQUOTE(JSON_EXTRACT(product_json.value, '$.quantity')) AS DECIMAL(10,2)) as LineTotal,
                created_by,
                created_date
            FROM JSON_TABLE(
                products_detail, 
                '$[*]' COLUMNS (
                    value JSON PATH '$'
                )
            ) AS product_json;
            
            -- อัพเดต TotalItems ใน orders table
            UPDATE orders 
            SET TotalItems = (
                SELECT COUNT(*) 
                FROM order_items 
                WHERE DocumentNo = doc_no
            )
            WHERE DocumentNo = doc_no;
            
        END IF;
        
    END LOOP;
    
    CLOSE order_cursor;
    
END$$

DELIMITER ;

-- Phase 5: Migration ข้อมูลเก่าแบบง่าย (ถ้าไม่มี JSON)
-- ===================================================

-- สำหรับ orders ที่ไม่มี ProductsDetail แต่มี Products field
INSERT INTO order_items (
    DocumentNo,
    ProductCode,
    ProductName,
    UnitPrice,
    Quantity,
    LineTotal,
    CreatedBy,
    CreatedDate
)
SELECT 
    DocumentNo,
    NULL as ProductCode,  -- ไม่มีข้อมูล ProductCode เก่า
    COALESCE(Products, 'สินค้าไม่ระบุ') as ProductName,
    CASE 
        WHEN Quantity > 0 THEN ROUND(SubtotalAmount / Quantity, 2)
        ELSE SubtotalAmount 
    END as UnitPrice,
    COALESCE(Quantity, 1) as Quantity,
    COALESCE(SubtotalAmount, Price, 0) as LineTotal,
    CreatedBy,
    CreatedDate
FROM orders 
WHERE DocumentNo NOT IN (SELECT DISTINCT DocumentNo FROM order_items)
AND (ProductsDetail IS NULL OR ProductsDetail = '');

-- อัพเดต TotalItems สำหรับ orders ที่ migrate แล้ว
UPDATE orders o
SET TotalItems = (
    SELECT COUNT(*) 
    FROM order_items oi 
    WHERE oi.DocumentNo = o.DocumentNo
)
WHERE TotalItems = 0;

-- Phase 6: สร้าง Triggers สำหรับรักษาความสอดคล้อง
-- =================================================

DELIMITER $$

-- Trigger อัพเดต TotalItems เมื่อมีการเพิ่ม order_items
CREATE TRIGGER tr_order_items_insert 
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET TotalItems = (
        SELECT COUNT(*) 
        FROM order_items 
        WHERE DocumentNo = NEW.DocumentNo
    ),
    Quantity = (
        SELECT SUM(Quantity) 
        FROM order_items 
        WHERE DocumentNo = NEW.DocumentNo
    ),
    SubtotalAmount = (
        SELECT SUM(LineTotal) 
        FROM order_items 
        WHERE DocumentNo = NEW.DocumentNo
    )
    WHERE DocumentNo = NEW.DocumentNo;
END$$

-- Trigger อัพเดต TotalItems เมื่อมีการแก้ไข order_items
CREATE TRIGGER tr_order_items_update 
AFTER UPDATE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET TotalItems = (
        SELECT COUNT(*) 
        FROM order_items 
        WHERE DocumentNo = NEW.DocumentNo
    ),
    Quantity = (
        SELECT SUM(Quantity) 
        FROM order_items 
        WHERE DocumentNo = NEW.DocumentNo
    ),
    SubtotalAmount = (
        SELECT SUM(LineTotal) 
        FROM order_items 
        WHERE DocumentNo = NEW.DocumentNo
    )
    WHERE DocumentNo = NEW.DocumentNo;
END$$

-- Trigger อัพเดต TotalItems เมื่อมีการลบ order_items
CREATE TRIGGER tr_order_items_delete 
AFTER DELETE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET TotalItems = (
        SELECT COUNT(*) 
        FROM order_items 
        WHERE DocumentNo = OLD.DocumentNo
    ),
    Quantity = (
        SELECT COALESCE(SUM(Quantity), 0) 
        FROM order_items 
        WHERE DocumentNo = OLD.DocumentNo
    ),
    SubtotalAmount = (
        SELECT COALESCE(SUM(LineTotal), 0) 
        FROM order_items 
        WHERE DocumentNo = OLD.DocumentNo
    )
    WHERE DocumentNo = OLD.DocumentNo;
END$$

DELIMITER ;

-- Phase 7: Test Queries
-- ====================

-- ตรวจสอบผลลัพธ์หลัง migration
SELECT 
    'Orders Summary' as TableType,
    COUNT(*) as RecordCount,
    SUM(TotalItems) as TotalItemsSum
FROM orders

UNION ALL

SELECT 
    'Order Items Summary' as TableType,
    COUNT(*) as RecordCount,
    COUNT(DISTINCT DocumentNo) as UniqueOrders
FROM order_items;

-- ตัวอย่างข้อมูลจาก View
SELECT * FROM order_items_report LIMIT 5;

-- =====================================
-- คำแนะนำในการใช้งาน:
-- =====================================

-- 1. รัน Phase 1-3 เพื่อสร้างโครงสร้าง
-- 2. รัน Phase 4-5 เพื่อ migrate ข้อมูลเก่า
-- 3. รัน Phase 6 เพื่อสร้าง triggers
-- 4. รัน Phase 7 เพื่อตรวจสอบผลลัพธ์
-- 5. ทดสอบ API ใหม่
-- 6. อัพเดต frontend ให้แสดงข้อมูลแยกรายการ