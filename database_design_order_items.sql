-- ===================================
-- Order Items Database Design
-- แยก Orders เป็น Header และ Detail
-- ===================================

-- 1. Orders Table (Header) - ข้อมูลหลักของใบสั่งซื้อ
ALTER TABLE orders 
MODIFY COLUMN Price decimal(10,2) COMMENT 'Total order amount after discount',
MODIFY COLUMN Quantity decimal(10,2) COMMENT 'Total quantity of all items',
MODIFY COLUMN Products varchar(500) COMMENT 'Summary of products (for backward compatibility)',
ADD COLUMN TotalItems int DEFAULT 0 COMMENT 'Number of different products in this order';

-- 2. สร้างตาราง Order Items (Detail) - รายการสินค้าแต่ละชิ้น
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
    
    -- Indexes
    INDEX idx_document_no (DocumentNo),
    INDEX idx_product_code (ProductCode),
    INDEX idx_created_date (CreatedDate),
    
    -- Foreign Key
    CONSTRAINT fk_order_items_orders 
        FOREIGN KEY (DocumentNo) REFERENCES orders(DocumentNo) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Order items detail - each product line in an order';

-- ===================================
-- ตัวอย่างข้อมูลที่ถูกต้อง
-- ===================================

-- Orders (Header)
-- DocumentNo: DOC123
-- CustomerCode: CUST001  
-- TotalItems: 2
-- Quantity: 5.00 (รวม: 2 + 3)
-- Price: 900.00 (รวมหลังหักส่วนลด: 1000 - 100)
-- SubtotalAmount: 1000.00 (รวมก่อนหักส่วนลด: 400 + 600)
-- DiscountAmount: 100.00 (ส่วนลดรวม)

-- Order Items (Detail)
-- 1. DocumentNo: DOC123, ProductName: "ปุ๋ย A", UnitPrice: 200, Quantity: 2, LineTotal: 400
-- 2. DocumentNo: DOC123, ProductName: "ปุ๋ย B", UnitPrice: 200, Quantity: 3, LineTotal: 600

-- ===================================
-- View สำหรับ Report
-- ===================================

CREATE OR REPLACE VIEW order_items_report AS
SELECT 
    o.DocumentNo,
    o.CustomerCode,
    o.DocumentDate,
    o.PaymentMethod,
    o.DiscountAmount as OrderDiscount,
    o.DiscountPercent as OrderDiscountPercent,
    o.SubtotalAmount as OrderSubtotal,
    o.Price as OrderTotal,
    o.CreatedBy as OrderBy,
    
    -- Item details
    oi.ProductCode,
    oi.ProductName,
    oi.UnitPrice,
    oi.Quantity as ItemQuantity,
    oi.LineTotal,
    oi.ItemDiscount,
    oi.ItemDiscountPercent,
    
    -- Calculated fields
    (oi.LineTotal - IFNULL(oi.ItemDiscount, 0)) as ItemNetTotal,
    ROUND((oi.LineTotal / o.SubtotalAmount) * 100, 2) as ItemPercentOfOrder
    
FROM orders o
LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo
ORDER BY o.DocumentDate DESC, oi.id;

-- ===================================
-- Benefits ของโครงสร้างใหม่:
-- ===================================
-- 1. Report แยกรายการได้ ✅
-- 2. Unit Price แท้จริง ✅  
-- 3. สามารถใส่ส่วนลดรายบรรทัดได้ ✅
-- 4. ข้อมูลถูกต้องตาม Database Normalization ✅
-- 5. รองรับการขยายระบบในอนาคต ✅

-- ===================================
-- Migration Plan:
-- ===================================
-- Phase 1: สร้างตาราง order_items
-- Phase 2: แปลงข้อมูลเก่าให้เข้าโครงสร้างใหม่
-- Phase 3: ปรับ API และ UI
-- Phase 4: Testing และ Deploy