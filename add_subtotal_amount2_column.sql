-- เพิ่มคอลัมน์ Subtotal_amount2 ในตาราง orders
-- สำหรับเก็บข้อมูล subtotal ที่ถูกต้องจาก Frontend

USE crm_system;

-- เพิ่มคอลัมน์ใหม่
ALTER TABLE orders 
ADD COLUMN Subtotal_amount2 DECIMAL(10,2) DEFAULT 0 COMMENT 'Correct subtotal amount from frontend calculation';

-- เพิ่ม index สำหรับคอลัมน์ใหม่
ALTER TABLE orders 
ADD INDEX idx_subtotal_amount2 (Subtotal_amount2);

-- ตรวจสอบโครงสร้างตาราง
DESCRIBE orders;

-- แสดงข้อมูลเปรียบเทียบ (สำหรับทดสอบ)
SELECT 
    DocumentNo,
    SubtotalAmount as 'Old_Subtotal',
    Subtotal_amount2 as 'New_Subtotal',
    (SubtotalAmount - Subtotal_amount2) as 'Difference'
FROM orders 
ORDER BY CreatedDate DESC 
LIMIT 10;