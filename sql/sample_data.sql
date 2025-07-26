-- ========================================
-- CRM System Sample Data
-- ========================================
-- This file contains sample data for testing the CRM system
-- Run this after setting up the main database schema
-- 
-- Usage:
-- USE your_database_name;
-- SOURCE sample_data.sql;
-- ========================================

-- Clear existing sample data (keep default users)
DELETE FROM sales_histories WHERE CreatedBy != 'system';
DELETE FROM orders WHERE CreatedBy != 'system';
DELETE FROM tasks WHERE CreatedBy != 'system';
DELETE FROM call_logs WHERE CreatedBy != 'system';
DELETE FROM customers WHERE CreatedBy != 'system';

-- Reset auto increment
ALTER TABLE customers AUTO_INCREMENT = 1;
ALTER TABLE call_logs AUTO_INCREMENT = 1;
ALTER TABLE tasks AUTO_INCREMENT = 1;
ALTER TABLE orders AUTO_INCREMENT = 1;
ALTER TABLE sales_histories AUTO_INCREMENT = 1;

-- ========================================
-- SAMPLE CUSTOMERS
-- ========================================

-- ลูกค้าใหม่ (กำลังดูแล)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy) VALUES
('CUST001', 'บริษัท เกษตรกรรมไทย จำกัด', '02-111-1111', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย', 'กรุงเทพมหานคร', '10110', 'ข้าว', 'ลูกค้าใหม่', 'กำลังดูแล', 'sale1', NOW(), 'admin'),
('CUST002', 'สวนผลไม้สมบูรณ์', '02-222-2222', '456 ถนนพหลโยธิน แขวงลาดยาว เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'ผลไม้', 'ลูกค้าใหม่', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 5 DAY), 'admin'),
('CUST003', 'เกษตรกรรมอินทรีย์', '02-333-3333', '789 ถนนรัชดาภิเษก แขวงดินแดง เขตดินแดง', 'กรุงเทพมหานคร', '10400', 'ผัก', 'ลูกค้าใหม่', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 10 DAY), 'admin'),

-- ลูกค้าติดตาม (กำลังดูแล)
('CUST004', 'ฟาร์มไก่อินทรีย์', '02-444-4444', '321 ถนนเพชรบุรี แขวงมักกะสัน เขตราชเทวี', 'กรุงเทพมหานคร', '10400', 'ปศุสัตว์', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 45 DAY), 'admin'),
('CUST005', 'สหกรณ์เกษตรกรรม', '02-555-5555', '654 ถนนลาดพร้าว แขวงจอมพล เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'ข้าว', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 60 DAY), 'admin'),
('CUST006', 'ฟาร์มผักปลอดสาร', '02-666-6666', '987 ถนนวิภาวดี แขวงจตุจักร เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'ผัก', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 30 DAY), 'admin'),

-- ลูกค้าเก่า (กำลังดูแล)
('CUST007', 'บริษัท เกษตรแปรรูป จำกัด', '02-777-7777', '147 ถนนพระราม 4 แขวงสุริยวงศ์ เขตบางรัก', 'กรุงเทพมหานคร', '10500', 'แปรรูป', 'ลูกค้าเก่า', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 90 DAY), 'admin'),
('CUST008', 'สวนดอกไม้งาม', '02-888-8888', '258 ถนนสีลม แขวงสีลม เขตบางรัก', 'กรุงเทพมหานคร', '10500', 'ดอกไม้', 'ลูกค้าเก่า', 'กำลังดูแล', 'sale1', DATE_SUB(NOW(), INTERVAL 120 DAY), 'admin'),

-- ลูกค้าใหม่ (ตะกร้าแจก) - ไม่มีการอัปเดตเกิน 30 วัน
('CUST009', 'เกษตรกรรมสมัยใหม่', '02-999-9999', '369 ถนนอโศก แขวงคลองเตยเหนือ เขตวัฒนา', 'กรุงเทพมหานคร', '10110', 'ผัก', 'ลูกค้าใหม่', 'ตะกร้าแจก', NULL, DATE_SUB(NOW(), INTERVAL 35 DAY), 'admin'),
('CUST010', 'ฟาร์มเห็ดออร์แกนิค', '02-101-0101', '741 ถนนเพชรบุรีตัดใหม่ แขวงบางกะปิ เขตห้วยขวาง', 'กรุงเทพมหานคร', '10310', 'เห็ด', 'ลูกค้าใหม่', 'ตะกร้าแจก', NULL, DATE_SUB(NOW(), INTERVAL 40 DAY), 'admin'),

-- ลูกค้าติดตาม (ตะกร้ารอ) - ไม่สั่งซื้อเกิน 3 เดือน
('CUST011', 'สวนผึ้งธรรมชาติ', '02-111-0111', '852 ถนนรามคำแหง แขวงหัวหมาก เขตบางกะปิ', 'กรุงเทพมหานคร', '10240', 'ผึ้ง', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, DATE_SUB(NOW(), INTERVAL 100 DAY), 'admin'),
('CUST012', 'เกษตรกรรมครบวงจร', '02-222-0222', '963 ถนนลาดมะยม แขวงวังทองหลาง เขตวังทองหลาง', 'กรุงเทพมหานคร', '10310', 'ข้าว', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, DATE_SUB(NOW(), INTERVAL 110 DAY), 'admin'),

-- ลูกค้าเก่า (ตะกร้ารอ) - ไม่ซื้อซ้ำเกิน 3 เดือน
('CUST013', 'ฟาร์มปลาออร์แกนิค', '02-333-0333', '159 ถนนนวมินทร์ แขวงนวลจันทร์ เขตบึงกุ่ม', 'กรุงเทพมหานคร', '10230', 'ปลา', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, DATE_SUB(NOW(), INTERVAL 150 DAY), 'admin');

-- ========================================
-- SAMPLE CALL LOGS
-- ========================================

INSERT INTO call_logs (CustomerCode, CallDate, CallTime, CallMinutes, CallStatus, CallReason, TalkStatus, TalkReason, Remarks, CreatedBy) VALUES
-- ลูกค้าใหม่
('CUST001', NOW(), '09:30', '15', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'ลูกค้าสนใจสินค้าใหม่ ต้องการข้อมูลเพิ่มเติม', 'sale1'),
('CUST001', DATE_SUB(NOW(), INTERVAL 2 DAY), '14:20', '8', 'ติดต่อได้', NULL, 'คุยไม่จบ', 'ลูกค้าไม่ว่าง', 'จะโทรกลับอีกครั้ง', 'sale1'),
('CUST002', DATE_SUB(NOW(), INTERVAL 1 DAY), '11:15', '12', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'ปรึกษาเรื่องราคาและการส่งมอบ', 'sale1'),
('CUST003', DATE_SUB(NOW(), INTERVAL 3 DAY), '16:45', '0', 'ติดต่อไม่ได้', 'ไม่รับสาย', NULL, NULL, NULL, 'sale1'),

-- ลูกค้าติดตาม
('CUST004', DATE_SUB(NOW(), INTERVAL 5 DAY), '10:30', '20', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'ลูกค้าพอใจกับสินค้า กำลังพิจารณาสั่งซื้อ', 'sale1'),
('CUST005', DATE_SUB(NOW(), INTERVAL 7 DAY), '13:45', '5', 'ติดต่อได้', NULL, 'คุยไม่จบ', 'ลูกค้าติดประชุม', 'นัดโทรใหม่พรุ่งนี้', 'sale1'),
('CUST006', DATE_SUB(NOW(), INTERVAL 4 DAY), '15:20', '18', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'ลูกค้าต้องการดูสินค้าก่อนตัดสินใจ', 'sale1'),

-- ลูกค้าเก่า
('CUST007', DATE_SUB(NOW(), INTERVAL 10 DAY), '09:15', '25', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'ลูกค้าสั่งซื้อเพิ่ม ขอใบเสนอราคา', 'sale1'),
('CUST008', DATE_SUB(NOW(), INTERVAL 8 DAY), '14:30', '0', 'ติดต่อไม่ได้', 'เบอร์ไม่ใช้แล้ว', NULL, NULL, 'ต้องหาเบอร์ติดต่อใหม่', 'sale1');

-- ========================================
-- SAMPLE TASKS
-- ========================================

INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy) VALUES
-- งานวันนี้
('CUST001', NOW(), 'ติดตามการตัดสินใจสั่งซื้อ ส่งใบเสนอราคา', 'รอดำเนินการ', 'sale1'),
('CUST004', NOW(), 'โทรกลับตามนัด เรื่องการสั่งซื้อ', 'รอดำเนินการ', 'sale1'),

-- งานพรุ่งนี้
('CUST002', DATE_ADD(NOW(), INTERVAL 1 DAY), 'นำเสนอสินค้าใหม่ ส่งแคตตาล็อก', 'รอดำเนินการ', 'sale1'),
('CUST005', DATE_ADD(NOW(), INTERVAL 1 DAY), 'โทรกลับตามนัด หลังเสร็จประชุม', 'รอดำเนินการ', 'sale1'),

-- งานสัปดาห์หน้า
('CUST003', DATE_ADD(NOW(), INTERVAL 3 DAY), 'ติดตามหลังส่งข้อมูลสินค้า', 'รอดำเนินการ', 'sale1'),
('CUST006', DATE_ADD(NOW(), INTERVAL 5 DAY), 'นัดหมายไปดูสินค้าที่โรงงาน', 'รอดำเนินการ', 'sale1'),
('CUST007', DATE_ADD(NOW(), INTERVAL 7 DAY), 'ส่งใบเสนอราคาสินค้าใหม่', 'รอดำเนินการ', 'sale1'),

-- งานที่เสร็จแล้ว
('CUST008', DATE_SUB(NOW(), INTERVAL 1 DAY), 'ติดตามการชำระเงิน', 'เสร็จสิ้น', 'sale1'),
('CUST001', DATE_SUB(NOW(), INTERVAL 3 DAY), 'ส่งข้อมูลสินค้าเบื้องต้น', 'เสร็จสิ้น', 'sale1');

-- ========================================
-- SAMPLE ORDERS
-- ========================================

INSERT INTO orders (DocumentNo, CustomerCode, DocumentDate, PaymentMethod, Products, Quantity, Price, OrderBy, CreatedBy) VALUES
-- คำสั่งซื้อของลูกค้าเก่า
('ORD2024001', 'CUST007', DATE_SUB(NOW(), INTERVAL 15 DAY), 'เงินสด', 'ปุ๋ยอินทรีย์ชนิดเม็ด', 100.00, 25000.00, 'sale1', 'sale1'),
('ORD2024002', 'CUST007', DATE_SUB(NOW(), INTERVAL 45 DAY), 'เครดิต 30 วัน', 'เมล็ดพันธุ์ข้าวหอมมะลิ', 50.00, 15000.00, 'sale1', 'sale1'),
('ORD2024003', 'CUST008', DATE_SUB(NOW(), INTERVAL 30 DAY), 'เงินสด', 'ปุ๋ยเคมี NPK', 75.00, 18000.00, 'sale1', 'sale1'),
('ORD2024004', 'CUST008', DATE_SUB(NOW(), INTERVAL 90 DAY), 'เครดิต 15 วัน', 'ยาฆ่าแมลงออร์แกนิค', 25.00, 8000.00, 'sale1', 'sale1'),

-- คำสั่งซื้อของลูกค้าติดตาม (เก่า)
('ORD2024005', 'CUST004', DATE_SUB(NOW(), INTERVAL 60 DAY), 'เงินสด', 'อาหารไก่ออร์แกนิค', 200.00, 35000.00, 'sale1', 'sale1'),
('ORD2024006', 'CUST005', DATE_SUB(NOW(), INTERVAL 75 DAY), 'เครดิต 30 วัน', 'ปุ๋ยหมัก', 150.00, 22000.00, 'sale1', 'sale1'),
('ORD2024007', 'CUST006', DATE_SUB(NOW(), INTERVAL 40 DAY), 'เงินสด', 'เมล็ดพันธุ์ผัก', 30.00, 5000.00, 'sale1', 'sale1');

-- ========================================
-- SAMPLE SALES HISTORIES
-- ========================================

INSERT INTO sales_histories (CustomerCode, SaleName, StartDate, EndDate, AssignBy, CreatedBy) VALUES
-- การมอบหมายปัจจุบัน (ยังไม่จบ)
('CUST001', 'sale1', DATE_SUB(NOW(), INTERVAL 10 DAY), NULL, 'supervisor', 'supervisor'),
('CUST002', 'sale1', DATE_SUB(NOW(), INTERVAL 15 DAY), NULL, 'supervisor', 'supervisor'),
('CUST003', 'sale1', DATE_SUB(NOW(), INTERVAL 20 DAY), NULL, 'supervisor', 'supervisor'),
('CUST004', 'sale1', DATE_SUB(NOW(), INTERVAL 60 DAY), NULL, 'supervisor', 'supervisor'),
('CUST005', 'sale1', DATE_SUB(NOW(), INTERVAL 75 DAY), NULL, 'supervisor', 'supervisor'),
('CUST006', 'sale1', DATE_SUB(NOW(), INTERVAL 45 DAY), NULL, 'supervisor', 'supervisor'),
('CUST007', 'sale1', DATE_SUB(NOW(), INTERVAL 120 DAY), NULL, 'supervisor', 'supervisor'),
('CUST008', 'sale1', DATE_SUB(NOW(), INTERVAL 150 DAY), NULL, 'supervisor', 'supervisor'),

-- การมอบหมายที่จบแล้ว (สำหรับลูกค้าในตะกร้า)
('CUST009', 'sale1', DATE_SUB(NOW(), INTERVAL 50 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY), 'supervisor', 'supervisor'),
('CUST010', 'sale1', DATE_SUB(NOW(), INTERVAL 55 DAY), DATE_SUB(NOW(), INTERVAL 40 DAY), 'supervisor', 'supervisor'),
('CUST011', 'sale1', DATE_SUB(NOW(), INTERVAL 120 DAY), DATE_SUB(NOW(), INTERVAL 100 DAY), 'supervisor', 'supervisor'),
('CUST012', 'sale1', DATE_SUB(NOW(), INTERVAL 130 DAY), DATE_SUB(NOW(), INTERVAL 110 DAY), 'supervisor', 'supervisor'),
('CUST013', 'sale1', DATE_SUB(NOW(), INTERVAL 180 DAY), DATE_SUB(NOW(), INTERVAL 150 DAY), 'supervisor', 'supervisor');

-- ========================================
-- UPDATE CUSTOMER ORDER DATES
-- ========================================

-- อัปเดต OrderDate ของลูกค้าตามคำสั่งซื้อล่าสุด
UPDATE customers c 
SET OrderDate = (
    SELECT MAX(DocumentDate) 
    FROM orders o 
    WHERE o.CustomerCode = c.CustomerCode
) 
WHERE CustomerCode IN (SELECT DISTINCT CustomerCode FROM orders);

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- แสดงสรุปข้อมูลที่สร้าง
SELECT 'Sample data created successfully!' as Status;

SELECT 'Customer Summary:' as Info;
SELECT 
    CustomerStatus,
    CartStatus,
    COUNT(*) as Count
FROM customers 
GROUP BY CustomerStatus, CartStatus
ORDER BY CustomerStatus, CartStatus;

SELECT 'Data Counts:' as Info;
SELECT 
    (SELECT COUNT(*) FROM customers) as Customers,
    (SELECT COUNT(*) FROM call_logs) as CallLogs,
    (SELECT COUNT(*) FROM tasks) as Tasks,
    (SELECT COUNT(*) FROM orders) as Orders,
    (SELECT COUNT(*) FROM sales_histories) as SalesHistories;

SELECT 'Tasks Today:' as Info;
SELECT COUNT(*) as TasksToday
FROM tasks 
WHERE DATE(FollowupDate) = CURDATE() 
AND Status = 'รอดำเนินการ';

SELECT 'Recent Orders:' as Info;
SELECT COUNT(*) as RecentOrders
FROM orders 
WHERE DocumentDate >= DATE_SUB(NOW(), INTERVAL 30 DAY);