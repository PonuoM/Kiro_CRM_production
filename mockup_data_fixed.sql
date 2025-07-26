-- ======================================================================
-- Kiro CRM Production - Mock Data for Testing System (CORRECTED VERSION)
-- Created: 2024-01-25
-- Purpose: Comprehensive test data matching production_setup.sql schema
-- Team Structure: 1 Supervisor + 2 Sales (60 customers total)
-- ======================================================================

-- Clean existing test data (optional - uncomment if needed)
-- DELETE FROM sales_histories WHERE CustomerCode LIKE 'TEST%';
-- DELETE FROM orders WHERE CustomerCode LIKE 'TEST%';
-- DELETE FROM tasks WHERE CustomerCode LIKE 'TEST%';
-- DELETE FROM call_logs WHERE CustomerCode LIKE 'TEST%';
-- DELETE FROM customers WHERE CustomerCode LIKE 'TEST%';
-- DELETE FROM users WHERE Username IN ('supervisor01', 'sales01', 'sales02');

-- ======================================================================
-- 1. TEAM USERS DATA (1 Supervisor + 2 Sales)
-- Matching production_setup.sql users table schema
-- ======================================================================

-- Supervisor
INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, CompanyCode, Position, Role, CreatedBy, CreatedDate, Status) VALUES
('supervisor01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมชาย', 'หัวหน้าทีม', 'supervisor01@company.com', '081-111-1111', 'COMP01', 'Sales Supervisor', 'Supervisor', 'admin', NOW(), 1);

-- Sales Team Members
INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, CompanyCode, Position, Role, CreatedBy, CreatedDate, Status) VALUES
('sales01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมหญิง', 'เซลส์หนึ่ง', 'sales01@company.com', '081-222-2222', 'COMP01', 'Sales Representative', 'Sale', 'supervisor01', NOW(), 1),
('sales02', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'สมศักดิ์', 'เซลส์สอง', 'sales02@company.com', '081-333-3333', 'COMP01', 'Sales Representative', 'Sale', 'supervisor01', NOW(), 1);

-- ======================================================================
-- 2. CUSTOMER DATA - Sales01 (20 customers)
-- Matching production_setup.sql customers table schema
-- ======================================================================

-- Sales01 - New Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST001', 'บริษัท เทคโนโลยี ใหม่ จำกัด', '02-111-1001', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย', 'กรุงเทพมหานคร', '10110', 'เทคโนโลยี', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 2 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST002', 'ร้าน ABC การค้า', '081-111-1002', '456 ถนนรัชดาภิเษก แขวงลาดยาว เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'การค้าทั่วไป', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST003', 'นายใหม่ ใจดี', '081-111-1003', '789 ถนนพหลโยธิน แขวงสามเสนใน เขตพญาไท', 'กรุงเทพมหานคร', '10400', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', NOW(), 'supervisor01', NOW()),
('TEST004', 'ห้างหุ้นส่วน สดใส', '02-111-1004', '321 ถนนเพชรบุรี แขวงทุ่งพญาไท เขตราชเทวี', 'กรุงเทพมหานคร', '10400', 'ห้างหุ้นส่วน', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales01', DATE_SUB(NOW(), INTERVAL 3 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST005', 'นางสาวใหม่ รักการค้า', '081-111-1005', '654 ถนนวิภาวดี แขวงจตุจักร เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Sales01 - Follow Up Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST006', 'บริษัท ติดตาม ดีดี จำกัด', '02-111-1006', '987 ถนนศรีนครินทร์ แขวงสวนหลวง เขตสวนหลวง', 'กรุงเทพมหานคร', '10250', 'บริการ', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 10 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('TEST007', 'ร้าน Follow Up Store', '081-111-1007', '147 ถนนลาดพร้าว แขวงจอมพล เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'ค้าปลีก', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 8 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 8 DAY)),
('TEST008', 'นายติดตาม ใส่ใจ', '081-111-1008', '258 ถนนรามคำแหง แขวงหัวหมาก เขตบางกะปิ', 'กรุงเทพมหานคร', '10240', 'บุคคลธรรมดา', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 12 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 12 DAY)),
('TEST009', 'บริษัท ติดตาม สม่ำเสมอ จำกัด', '02-111-1009', '369 ถนนพระราม 4 แขวงคลองเตย เขตคลองเตย', 'กรุงเทพมหานคร', '10110', 'บริการ', 'ลูกค้าติดตาม', 'ตะกร้ารอ', 'sales01', DATE_SUB(NOW(), INTERVAL 15 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 15 DAY)),
('TEST010', 'นางสาวติดตาม ใกล้สนิท', '081-111-1010', '741 ถนนเจริญกรุง แขวงบางรัก เขตบางรัก', 'กรุงเทพมหานคร', '10500', 'บุคคลธรรมดา', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 7 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Sales01 - Old Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST011', 'บริษัท เก่าแก่ มั่นคง จำกัด', '02-111-1011', '852 ถนนสีลม แขวงสีลม เขตบางรัก', 'กรุงเทพมหานคร', '10500', 'การเงิน', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 90 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 90 DAY)),
('TEST012', 'ร้าน เก่าดี มีประวัติ', '081-111-1012', '963 ถนนสาทร แขวงยานนาวา เขตสาทร', 'กรุงเทพมหานคร', '10120', 'ค้าปลีก', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 120 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 120 DAY)),
('TEST013', 'นายเก่า ประจำลูก', '081-111-1013', '159 ถนนนราธิวาส แขวงช่องนนทรี เขตยานนาวา', 'กรุงเทพมหานคร', '10120', 'บุคคลธรรมดา', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 180 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 180 DAY)),
('TEST014', 'บริษัท เก่าแต่ดี จำกัด', '02-111-1014', '357 ถนนพลับพลา แขวงวังทองหลาง เขตวังทองหลาง', 'กรุงเทพมหานคร', '10310', 'อุตสาหกรรม', 'ลูกค้าเก่า', 'ตะกร้ารอ', 'sales01', DATE_SUB(NOW(), INTERVAL 200 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 200 DAY)),
('TEST015', 'นางเก่า วัยเก๋า', '081-111-1015', '468 ถนนอโศก แขวงคลองเตยเหนือ เขตวัฒนา', 'กรุงเทพมหานคร', '10110', 'บุคคลธรรมดา', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 150 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 150 DAY));

-- Sales01 - Basket Distribution (2 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST016', 'บริษัท ตะกร้าแจก ไม่เอา จำกัด', '02-111-1016', '579 ถนนวิทยุ แขวงปทุมวัน เขตปทุมวัน', 'กรุงเทพมหานคร', '10330', 'บริการ', 'ลูกค้าติดตาม', 'ตะกร้าแจก', 'sales01', DATE_SUB(NOW(), INTERVAL 35 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 35 DAY)),
('TEST017', 'นายตะกร้า แจกไม่รับ', '081-111-1017', '680 ถนนราชดำริ แขวงลุมพินี เขตปทุมวัน', 'กรุงเทพมหานคร', '10330', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'ตะกร้าแจก', 'sales01', DATE_SUB(NOW(), INTERVAL 32 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 32 DAY));

-- Sales01 - Waiting Basket / 30-day Rule (3 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST018', 'บริษัท ใกล้ครบ 30 วัน จำกัด', '02-111-1018', '791 ถนนเฉลิมพระเกียรติ แขวงหนองบอน เขตประเวศ', 'กรุงเทพมหานคร', '10250', 'บริการ', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 28 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 28 DAY)),
('TEST019', 'นายใกล้โดน ดึงคืน', '081-111-1019', '802 ถนนกรุงเทพกรีฑา แขวงหัวหมาก เขตบางกะปิ', 'กรุงเทพมหานคร', '10240', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', DATE_SUB(NOW(), INTERVAL 29 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 29 DAY)),
('TEST020', 'บริษัท พ้น 30 วันแล้ว จำกัด', '02-111-1020', '913 ถนนอ่อนนุช แขวงสวนหลวง เขตสวนหลวง', 'กรุงเทพมหานคร', '10250', 'บริการ', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'supervisor01', DATE_SUB(NOW(), INTERVAL 31 DAY));

-- ======================================================================
-- 3. CUSTOMER DATA - Sales02 (20 customers)
-- ======================================================================

-- Sales02 - New Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST021', 'บริษัท ใหม่หมาด จำกัด', '02-222-2001', '124 ถนนพระราม 1 แขวงปทุมวัน เขตปทุมวัน', 'กรุงเทพมหานคร', '10330', 'เทคโนโลยี', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 1 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST022', 'ร้าน สดใหม่ ค้าขาย', '081-222-2002', '235 ถนนพระราม 2 แขวงแสมดำ เขตบางขุนเทียน', 'กรุงเทพมหานคร', '10150', 'ค้าปลีก', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 2 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST023', 'นายใหม่หน่อย สนใจมาก', '081-222-2003', '346 ถนนพระราม 3 แขวงบางโคล่ เขตบางคอแหลม', 'กรุงเทพมหานคร', '10120', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', NOW(), 'supervisor01', NOW()),
('TEST024', 'บริษัท เพิ่งเริ่ม จำกัด', '02-222-2004', '457 ถนนพระราม 4 แขวงปทุมวัน เขตปทุมวัน', 'กรุงเทพมหานคร', '10330', 'สตาร์ทอัพ', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales02', DATE_SUB(NOW(), INTERVAL 4 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('TEST025', 'นางสาวใหม่ล่าสุด', '081-222-2005', '568 ถนนพระราม 9 แขวงห้วยขวาง เขตห้วยขวาง', 'กรุงเทพมหานคร', '10310', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales02', DATE_SUB(NOW(), INTERVAL 3 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Sales02 - Follow Up Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST026', 'บริษัท ติดตามเข้ม จำกัด', '02-222-2006', '679 ถนนบางนา แขวงบางนาใต้ เขตบางนา', 'กรุงเทพมหานคร', '10260', 'บริการ', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 11 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 11 DAY)),
('TEST027', 'ร้าน ติดตามสม่ำเสมอ', '081-222-2007', '780 ถนนนวมินทร์ แขวงคลองกุ่ม เขตบึงกุ่ม', 'กรุงเทพมหานคร', '10230', 'ค้าส่ง', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 9 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 9 DAY)),
('TEST028', 'นายติดตาม ใกล้ปิด', '081-222-2008', '891 ถนนเสรีไทย แขวงคันนายาว เขตคันนายาว', 'กรุงเทพมหานคร', '10230', 'บุคคลธรรมดา', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 13 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 13 DAY)),
('TEST029', 'บริษัท ติดตามดี จำกัด', '02-222-2009', '902 ถนนหทัยราษฎร์ แขวงคลองสามประเวศ เขตลาดกระบัง', 'กรุงเทพมหานคร', '10520', 'บริการ', 'ลูกค้าติดตาม', 'ตะกร้ารอ', 'sales02', DATE_SUB(NOW(), INTERVAL 16 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 16 DAY)),
('TEST030', 'นางสาวติดตาม เก่งมาก', '081-222-2010', '13 ถนนกิ่งแก้ว แขวงราชาเทวะ เขตบางพลี', 'สมุทรปราการ', '10540', 'บุคคลธรรมดา', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 6 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 6 DAY));

-- Sales02 - Old Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST031', 'บริษัท เก่าแก่มาก จำกัด', '02-222-2011', '124 ถนนเอกชัย แขวงบางบอน เขตบางบอน', 'กรุงเทพมหานคร', '10150', 'อุตสาหกรรม', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 100 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 100 DAY)),
('TEST032', 'ร้าน เก่าดั้งเดิม', '081-222-2012', '235 ถนนเพชรเกษม แขวงหนองแขม เขตหนองแขม', 'กรุงเทพมหานคร', '10160', 'ค้าปลีก', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 130 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 130 DAY)),
('TEST033', 'นายเก่าแก่ ซื้อประจำ', '081-222-2013', '346 ถนนเทพารักษ์ แขวงบางปู เขตเมืองสมุทรปราการ', 'สมุทรปราการ', '10280', 'บุคคลธรรมดา', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 190 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 190 DAY)),
('TEST034', 'บริษัท เก่าน่าเชื่อถือ จำกัด', '02-222-2014', '457 ถนนปิ่นเกล้า แขวงบางซื่อ เขตบางซื่อ', 'กรุงเทพมหานคร', '10800', 'การเงิน', 'ลูกค้าเก่า', 'ตะกร้ารอ', 'sales02', DATE_SUB(NOW(), INTERVAL 210 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 210 DAY)),
('TEST035', 'นางเก่าคุ้นเคย', '081-222-2015', '568 ถนนประชาชื่น แขวงทุ่งสองห้อง เขตหลักสี่', 'กรุงเทพมหานคร', '10210', 'บุคคลธรรมดา', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 160 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 160 DAY));

-- Sales02 - Basket Distribution (2 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST036', 'บริษัท ตะกร้าแจกไม่ใช้ จำกัด', '02-222-2016', '679 ถนนพระราม 5 แขวงบางกรวย เขตบางกรวย', 'นนทบุรี', '11130', 'บริการ', 'ลูกค้าติดตาม', 'ตะกร้าแจก', 'sales02', DATE_SUB(NOW(), INTERVAL 33 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 33 DAY)),
('TEST037', 'นายตะกร้า แจกแล้วทิ้ง', '081-222-2017', '780 ถนนแจ้งวัฒนะ แขวงคลองเจ็ดเก้า เขตปากเกร็ด', 'นนทบุรี', '11120', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'ตะกร้าแจก', 'sales02', DATE_SUB(NOW(), INTERVAL 34 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 34 DAY));

-- Sales02 - Waiting Basket / 30-day Rule (3 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST038', 'บริษัท เกือบ 30 วัน จำกัด', '02-222-2018', '891 ถนนติวานนท์ แขวงตลาดขวัญ เขตเมืองนนทบุรี', 'นนทบุรี', '11000', 'บริการ', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 27 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 27 DAY)),
('TEST039', 'นายใกล้ถูก รีเซ็ต', '081-222-2019', '902 ถนนรัตนาธิเบศร์ แขวงมหาสวัสดิ์ เขตบางกรวย', 'นนทบุรี', '11130', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', DATE_SUB(NOW(), INTERVAL 29 DAY), 'supervisor01', DATE_SUB(NOW(), INTERVAL 29 DAY)),
('TEST040', 'บริษัท เกิน 30 วันแล้ว จำกัด', '02-222-2020', '13 ถนนบางกรวย-ไทรน้อย แขวงบางเสี้ยน เขตบางใหญ่', 'นนทบุรี', '11140', 'บริการ', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'supervisor01', DATE_SUB(NOW(), INTERVAL 32 DAY));

-- ======================================================================
-- 4. SUPERVISOR CUSTOMERS (20 customers) - No Sales Assigned
-- ======================================================================

-- Supervisor Direct - New Customers (5 customers) 
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST041', 'บริษัท ใหม่ใส่ Sup จำกัด', '02-333-3001', '124 ถนนพุทธมณฑลสาย 4 แขวงศาลายา เขตพุทธมณฑล', 'นครปฐม', '73170', 'เทคโนโลยี', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST042', 'ร้าน ใหม่ รอ Sup', '081-333-3002', '235 ถนนบรมราชชนนี แขวงบางบำหรุ เขตบางพลัด', 'กรุงเทพมหานคร', '10700', 'ค้าปลีก', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST043', 'นายใหม่ รอการมอบหมาย', '081-333-3003', '346 ถนนจรัญสนิทวงศ์ แขวงบางอ้อ เขตบางพลัด', 'กรุงเทพมหานคร', '10700', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, NULL, 'admin', NOW()),
('TEST044', 'บริษัท ใหม่พิเศษ จำกัด', '02-333-3004', '457 ถนนเจริญนคร แขวงคลองสาน เขตคลองสาน', 'กรุงเทพมหานคร', '10600', 'บริการ', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST045', 'นางสาวใหม่ รอคิว', '081-333-3005', '568 ถนนกรุงธนบุรี แขวงคลองต้นไทร เขตคลองสาน', 'กรุงเทพมหานคร', '10600', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Supervisor Direct - Follow Up Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST046', 'บริษัท ติดตาม Sup จำกัด', '02-333-3006', '679 ถนนตากสิน แขวงบุคคโล เขตธนบุรี', 'กรุงเทพมหานคร', '10600', 'บริการ', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 14 DAY)),
('TEST047', 'ร้าน ติดตาม รอมอบหมาย', '081-333-3007', '780 ถนนสมเด็จพระเจ้าตากสิน แขวงบางหว้า เขตภาษีเจริญ', 'กรุงเทพมหานคร', '10160', 'ค้าส่ง', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('TEST048', 'นายติดตาม พิเศษ Sup', '081-333-3008', '891 ถนนเพชรเกษม แขวงหนองแขม เขตหนองแขม', 'กรุงเทพมหานคร', '10160', 'บุคคลธรรมดา', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 12 DAY)),
('TEST049', 'บริษัท ติดตามใหญ่ จำกัด', '02-333-3009', '902 ถนนกาญจนาภิเษก แขวงบางแค เขตบางแค', 'กรุงเทพมหานคร', '10160', 'อุตสาหกรรม', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 17 DAY)),
('TEST050', 'นางสาวติดตาม VIP', '081-333-3010', '13 ถนนบางแค-รัตนาธิเบศร์ แขวงหลักสอง เขตหลักสี่', 'กรุงเทพมหานคร', '10210', 'บุคคลธรรมดา', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 8 DAY));

-- Supervisor Direct - Old Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST051', 'บริษัท เก่า Sup ดูแล จำกัด', '02-333-3011', '124 ถนนนครอินทร์ แขวงลาดยาว เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'การเงิน', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 110 DAY)),
('TEST052', 'ร้าน เก่าแก่ รอ Sales', '081-333-3012', '235 ถนนวิภาวดีรังสิต แขวงดินแดง เขตดินแดง', 'กรุงเทพมหานคร', '10400', 'ค้าปลีก', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 140 DAY)),
('TEST053', 'นายเก่า สำคัญ Sup', '081-333-3013', '346 ถนนพหลโยธิน แขวงลาดยาว เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'บุคคลธรรมดา', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 170 DAY)),
('TEST054', 'บริษัท เก่าใหญ่ จำกัด', '02-333-3014', '457 ถนนรัชดาภิเษก แขวงห้วยขวาง เขตห้วยขวาง', 'กรุงเทพมหานคร', '10310', 'อุตสาหกรรม', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 220 DAY)),
('TEST055', 'นางเก่า VIP ระดับ Sup', '081-333-3015', '568 ถนนลาดพร้าว แขวงจอมพล เขตจตุจักร', 'กรุงเทพมหานคร', '10900', 'บุคคลธรรมดา', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 180 DAY));

-- Supervisor Direct - Mixed Status (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerAddress, CustomerProvince, CustomerPostalCode, Agriculture, CustomerStatus, CartStatus, Sales, AssignDate, CreatedBy, CreatedDate) VALUES
('TEST056', 'บริษัท พิเศษคดี จำกัด', '02-333-3016', '679 ถนนสุขาภิบาล 5 แขวงคลองเจ้าคุณสิงห์ เขตวัฒนา', 'กรุงเทพมหานคร', '10110', 'บริการ', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 20 DAY)),
('TEST057', 'นายใหญ่ รอการพิจารณา', '081-333-3017', '780 ถนนเอกมัย แขวงคลองตันเหนือ เขตวัฒนา', 'กรุงเทพมหานคร', '10110', 'บุคคลธรรมดา', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('TEST058', 'บริษัท ระหว่างตรวจสอบ จำกัด', '02-333-3018', '891 ถนนสุขุมวิท แขวงพระโขนง เขตวัฒนา', 'กรุงเทพมหานคร', '10110', 'การเงิน', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 95 DAY)),
('TEST059', 'นางสาวปัญหาพิเศษ', '081-333-3019', '902 ถนนเพลินจิต แขวงลุมพินี เขตปทุมวัน', 'กรุงเทพมหานคร', '10330', 'บุคคลธรรมดา', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 25 DAY)),
('TEST060', 'บริษัท รอจัดสรร จำกัด', '02-333-3020', '13 ถนนสาทร แขวงสีลม เขตบางรัก', 'กรุงเทพมหานคร', '10500', 'บริการ', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, NULL, 'admin', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ======================================================================
-- 5. TASKS DATA (ติดตาม Follow Up)
-- Matching production_setup.sql tasks table schema
-- ======================================================================

-- Today's Tasks (5 tasks)
INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy, CreatedDate) VALUES
('TEST006', CURDATE(), 'TEST: นัดหมายพบลูกค้า - นำเสนอสินค้าใหม่', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST008', CURDATE(), 'TEST: นัดหมายติดตามผล - เสนอโปรโมชั่น', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST026', CURDATE(), 'TEST: นัดหมายปิดการขาย - เซ็นสัญญา', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST028', CURDATE(), 'TEST: นัดหมายทำการตลาด - แนะนำบริการเพิ่ม', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST046', CURDATE(), 'TEST: นัดหมาย VIP - หารือแผนขายปี 2024', 'รอดำเนินการ', 'admin', NOW());

-- Follow Up Tasks (10 tasks)
INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy, CreatedDate) VALUES
('TEST007', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'TEST: โทรติดตามความต้องการ - เสนอแพ็กเกจใหม่', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST009', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'TEST: ส่งใบเสนอราคา - รอการพิจารณา', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST018', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'TEST: ติดตามก่อนครบ 30 วัน - เร่งตัดสินใจ', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST027', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'TEST: แจ้งข่าวสารสินค้า - กิจกรรมพิเศษ', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST029', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'TEST: นำส่งเอกสารเพิ่มเติม - ข้อมูลทางเทคนิค', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST038', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'TEST: ติดตามก่อนเวลาหมด - เร่งการตัดสินใจ', 'รอดำเนินการ', 'supervisor01', NOW()),
('TEST048', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'TEST: ติดตามลูกค้าใหญ่ - จัดทำแผนขาย', 'รอดำเนินการ', 'admin', NOW()),
('TEST051', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'TEST: ติดตามลูกค้าเก่า - หาโอกาสขายเพิ่ม', 'รอดำเนินการ', 'admin', NOW()),
('TEST056', CURDATE(), 'TEST: ตรวจสอบปัญหาพิเศษ - หาแนวทางแก้ไข', 'รอดำเนินการ', 'admin', NOW()),
('TEST058', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'TEST: ทบทวนลูกค้าเก่า - ประเมินศักยภาพ', 'รอดำเนินการ', 'admin', NOW());

-- ======================================================================
-- 6. CALL LOGS DATA
-- Matching production_setup.sql call_logs table schema
-- ======================================================================

-- Recent Call History (15 records)
INSERT INTO call_logs (CustomerCode, CallDate, CallTime, CallMinutes, CallStatus, CallReason, TalkStatus, TalkReason, Remarks, CreatedBy, CreatedDate) VALUES
('TEST001', DATE_SUB(NOW(), INTERVAL 2 HOUR), '09:00', '15', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ลูกค้าสนใจสินค้า ขอข้อมูลเพิ่มเติม นัดส่งใบเสนอราคา', 'sales01', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('TEST006', DATE_SUB(NOW(), INTERVAL 1 DAY), '14:30', '25', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ติดตามใบเสนอราคา ลูกค้าขอเวลาพิจารณา 3 วัน', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST008', DATE_SUB(NOW(), INTERVAL 3 HOUR), '16:15', '8', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ลูกค้าโทรสอบถามรายละเอียด การรับประกันสินค้า', 'sales01', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('TEST011', DATE_SUB(NOW(), INTERVAL 5 HOUR), '11:20', '18', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ลูกค้าตัดสินใจซื้อ จะโอนเงินพรุ่งนี้ ยอด 50,000 บาท', 'sales01', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
('TEST018', DATE_SUB(NOW(), INTERVAL 1 HOUR), '15:45', '12', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: แจ้งลูกค้าเหลือเวลา 2 วัน ก่อนสิ้นสุดโปรโมชั่น', 'sales01', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('TEST021', DATE_SUB(NOW(), INTERVAL 4 HOUR), '10:10', '20', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ลูกค้าใหม่สนใจมาก ขอนัดหมายพบหน้า วันศุกร์นี้', 'sales02', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('TEST026', DATE_SUB(NOW(), INTERVAL 30 MINUTE), '16:30', '6', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ลูกค้าโทรมายืนยันการนัดหมาย เวลา 10:30 น. วันนี้', 'sales02', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('TEST028', DATE_SUB(NOW(), INTERVAL 6 HOUR), '13:15', '22', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ติดตามหลังการนำเสนอ ลูกค้าขออีก 1 สัปดาห์', 'sales02', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
('TEST031', DATE_SUB(NOW(), INTERVAL 2 DAY), '09:45', '14', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: โทรทักทายลูกค้าเก่า แจ้งสินค้าใหม่ที่น่าสนใจ', 'sales02', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST038', DATE_SUB(NOW(), INTERVAL 2 HOUR), '14:50', '16', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: แจ้งเวลาเหลือน้อย ลูกค้าขอปรึกษาผู้บริหาร', 'sales02', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('TEST046', DATE_SUB(NOW(), INTERVAL 3 HOUR), '11:20', '35', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ปรึกษาแผนการขาย ลูกค้าใหญ่พิเศษ วงเงิน 500,000', 'supervisor01', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('TEST051', DATE_SUB(NOW(), INTERVAL 1 DAY), '15:45', '28', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ลูกค้าเก่าโทรขอข้อมูลบริการใหม่ ส่วนลดพิเศษ', 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST056', DATE_SUB(NOW(), INTERVAL 4 HOUR), '12:30', '45', 'ติดต่อได้', NULL, 'คุยจบ', NULL, 'TEST: ประสานแก้ปัญหาการส่งมอบ หารือวิธีแก้ไข', 'supervisor01', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('TEST002', DATE_SUB(NOW(), INTERVAL 1 DAY), '10:15', '11', 'ติดต่อได้', NULL, 'คุยไม่จบ', 'ลูกค้าไม่ว่าง', 'TEST: ลูกค้าไม่สนใจในขณะนี้ ขอติดต่อใหม่เดือนหน้า', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST024', DATE_SUB(NOW(), INTERVAL 6 HOUR), '14:00', '0', 'ติดต่อไม่ได้', 'ไม่รับสาย', NULL, NULL, 'TEST: โทรไม่ติด 3 ครั้ง ลองส่ง LINE แทน', 'sales02', DATE_SUB(NOW(), INTERVAL 6 HOUR));

-- ======================================================================
-- 7. ORDERS DATA (Sample Orders)
-- Matching production_setup.sql orders table schema
-- ======================================================================

-- Sample Orders for Testing
INSERT INTO orders (DocumentNo, CustomerCode, DocumentDate, PaymentMethod, Products, Quantity, Price, OrderBy, CreatedBy, CreatedDate) VALUES
('TEST-ORD-001', 'TEST011', DATE_SUB(NOW(), INTERVAL 1 DAY), 'โอนธนาคาร', 'TEST: ชุดสินค้า Premium Package A', 2.00, 50000.00, 'sales01', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST-ORD-002', 'TEST031', DATE_SUB(NOW(), INTERVAL 3 DAY), 'เงินสด', 'TEST: บริการดูแลระบบ รายเดือน', 1.00, 15000.00, 'sales02', 'sales02', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST-ORD-003', 'TEST006', DATE_SUB(NOW(), INTERVAL 5 DAY), 'โอนธนาคาร', 'TEST: อุปกรณ์เสริม Set B', 3.00, 25500.00, 'sales01', 'sales01', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('TEST-ORD-004', 'TEST026', DATE_SUB(NOW(), INTERVAL 2 DAY), 'เครดิต 30 วัน', 'TEST: Software License ปี 2024', 1.00, 35000.00, 'sales02', 'sales02', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST-ORD-005', 'TEST051', DATE_SUB(NOW(), INTERVAL 7 DAY), 'โอนธนาคาร', 'TEST: บริการติดตั้งและอบรม', 1.00, 45000.00, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- ======================================================================
-- 8. SALES HISTORIES DATA
-- Matching production_setup.sql sales_histories table schema
-- ======================================================================

-- Sales Assignment History
INSERT INTO sales_histories (CustomerCode, SaleName, StartDate, EndDate, AssignBy, CreatedBy, CreatedDate) VALUES
-- Sales01 current assignments
('TEST001', 'sales01', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST002', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST003', 'sales01', NOW(), NULL, 'supervisor01', 'supervisor01', NOW()),
('TEST004', 'sales01', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST005', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST006', 'sales01', DATE_SUB(NOW(), INTERVAL 10 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('TEST007', 'sales01', DATE_SUB(NOW(), INTERVAL 8 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 8 DAY)),
('TEST008', 'sales01', DATE_SUB(NOW(), INTERVAL 12 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 12 DAY)),
('TEST009', 'sales01', DATE_SUB(NOW(), INTERVAL 15 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 15 DAY)),
('TEST010', 'sales01', DATE_SUB(NOW(), INTERVAL 7 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('TEST011', 'sales01', DATE_SUB(NOW(), INTERVAL 90 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 90 DAY)),
('TEST012', 'sales01', DATE_SUB(NOW(), INTERVAL 120 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 120 DAY)),
('TEST013', 'sales01', DATE_SUB(NOW(), INTERVAL 180 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 180 DAY)),
('TEST014', 'sales01', DATE_SUB(NOW(), INTERVAL 200 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 200 DAY)),
('TEST015', 'sales01', DATE_SUB(NOW(), INTERVAL 150 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 150 DAY)),
('TEST016', 'sales01', DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY), 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 35 DAY)),
('TEST017', 'sales01', DATE_SUB(NOW(), INTERVAL 32 DAY), DATE_SUB(NOW(), INTERVAL 32 DAY), 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 32 DAY)),
('TEST018', 'sales01', DATE_SUB(NOW(), INTERVAL 28 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 28 DAY)),
('TEST019', 'sales01', DATE_SUB(NOW(), INTERVAL 29 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 29 DAY)),
('TEST020', 'sales01', DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 31 DAY), 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 31 DAY)),

-- Sales02 current assignments
('TEST021', 'sales02', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST022', 'sales02', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST023', 'sales02', NOW(), NULL, 'supervisor01', 'supervisor01', NOW()),
('TEST024', 'sales02', DATE_SUB(NOW(), INTERVAL 4 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('TEST025', 'sales02', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST026', 'sales02', DATE_SUB(NOW(), INTERVAL 11 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 11 DAY)),
('TEST027', 'sales02', DATE_SUB(NOW(), INTERVAL 9 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 9 DAY)),
('TEST028', 'sales02', DATE_SUB(NOW(), INTERVAL 13 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 13 DAY)),
('TEST029', 'sales02', DATE_SUB(NOW(), INTERVAL 16 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 16 DAY)),
('TEST030', 'sales02', DATE_SUB(NOW(), INTERVAL 6 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 6 DAY)),
('TEST031', 'sales02', DATE_SUB(NOW(), INTERVAL 100 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 100 DAY)),
('TEST032', 'sales02', DATE_SUB(NOW(), INTERVAL 130 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 130 DAY)),
('TEST033', 'sales02', DATE_SUB(NOW(), INTERVAL 190 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 190 DAY)),
('TEST034', 'sales02', DATE_SUB(NOW(), INTERVAL 210 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 210 DAY)),
('TEST035', 'sales02', DATE_SUB(NOW(), INTERVAL 160 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 160 DAY)),
('TEST036', 'sales02', DATE_SUB(NOW(), INTERVAL 33 DAY), DATE_SUB(NOW(), INTERVAL 33 DAY), 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 33 DAY)),
('TEST037', 'sales02', DATE_SUB(NOW(), INTERVAL 34 DAY), DATE_SUB(NOW(), INTERVAL 34 DAY), 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 34 DAY)),
('TEST038', 'sales02', DATE_SUB(NOW(), INTERVAL 27 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 27 DAY)),
('TEST039', 'sales02', DATE_SUB(NOW(), INTERVAL 29 DAY), NULL, 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 29 DAY)),
('TEST040', 'sales02', DATE_SUB(NOW(), INTERVAL 32 DAY), DATE_SUB(NOW(), INTERVAL 32 DAY), 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 32 DAY));

-- Update customer order dates based on orders
UPDATE customers c 
SET OrderDate = (
    SELECT MAX(DocumentDate) 
    FROM orders o 
    WHERE o.CustomerCode = c.CustomerCode
) 
WHERE CustomerCode IN (SELECT DISTINCT CustomerCode FROM orders);

-- ======================================================================
-- 9. VERIFICATION QUERIES
-- ======================================================================

-- Final verification queries (you can run these to check the data)
/*
SELECT 'Total Test Customers' as Description, COUNT(*) as Count FROM customers WHERE CustomerCode LIKE 'TEST%'
UNION ALL
SELECT 'Sales01 Customers', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND Sales = 'sales01'
UNION ALL  
SELECT 'Sales02 Customers', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND Sales = 'sales02'
UNION ALL
SELECT 'Supervisor Customers', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND Sales IS NULL
UNION ALL
SELECT 'New Customers', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND CustomerStatus = 'ลูกค้าใหม่'
UNION ALL
SELECT 'Follow Up Customers', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND CustomerStatus = 'ลูกค้าติดตาม'  
UNION ALL
SELECT 'Old Customers', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND CustomerStatus = 'ลูกค้าเก่า'
UNION ALL
SELECT 'Basket Distribution', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND CartStatus = 'ตะกร้าแจก'
UNION ALL
SELECT 'Waiting Basket', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND CartStatus = 'ตะกร้ารอ'
UNION ALL
SELECT 'Taking Care', COUNT(*) FROM customers WHERE CustomerCode LIKE 'TEST%' AND CartStatus = 'กำลังดูแล'
UNION ALL
SELECT 'Tasks', COUNT(*) FROM tasks WHERE Remarks LIKE 'TEST:%'
UNION ALL
SELECT 'Call Logs', COUNT(*) FROM call_logs WHERE Remarks LIKE 'TEST:%'
UNION ALL
SELECT 'Orders', COUNT(*) FROM orders WHERE Products LIKE 'TEST:%'
UNION ALL
SELECT 'Sales Histories', COUNT(*) FROM sales_histories WHERE CustomerCode LIKE 'TEST%';
*/

-- ======================================================================
-- END OF CORRECTED MOCK DATA
-- ======================================================================