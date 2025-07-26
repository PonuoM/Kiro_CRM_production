-- ======================================================================
-- Kiro CRM Production - Mock Data for Testing System
-- Created: 2024-01-25
-- Purpose: Comprehensive test data for all customer types and statuses
-- Team Structure: 1 Supervisor + 2 Sales (60 customers total)
-- ======================================================================

-- Clean existing test data (optional - uncomment if needed)
-- DELETE FROM customers WHERE CustomerCode LIKE 'TEST%';
-- DELETE FROM daily_tasks WHERE task_description LIKE 'TEST:%';
-- DELETE FROM call_history WHERE notes LIKE 'TEST:%';

-- ======================================================================
-- 1. TEAM USERS DATA (1 Supervisor + 2 Sales)
-- ======================================================================

-- Supervisor
INSERT INTO users (Username, Password, Role, , email, phone, team_id, supervisor_id, is_active, created_date) VALUES
('supervisor01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supervisor', 'นายสมชาย หัวหน้าทีม', 'supervisor01@company.com', '081-111-1111', 1, NULL, 1, NOW());

-- Sales Team Members
INSERT INTO users (username, password_hash, user_role, full_name, email, phone, team_id, supervisor_id, is_active, created_date) VALUES
('sales01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', 'นางสมหญิง เซลส์หนึ่ง', 'sales01@company.com', '081-222-2222', 1, 1, 1, NOW()),
('sales02', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', 'นายสมศักดิ์ เซลส์สอง', 'sales02@company.com', '081-333-3333', 1, 1, 1, NOW());

-- ======================================================================
-- 2. CUSTOMER DATA - Sales01 (20 customers)
-- Distribution: Mix of all customer types and statuses
-- ======================================================================

-- Sales01 - New Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST001', 'บริษัท เทคโนโลยี ใหม่ จำกัด', '02-111-1001', 'contact@newtech.com', '123 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กทม. 10110', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
('TEST002', 'ร้าน ABC การค้า', '081-111-1002', 'abc@trade.com', '456 ถนนรัชดาภิเษก แขวงลาดยาว เขตจตุจักร กทม. 10900', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('TEST003', 'บุคคลธรรมดา นายใหม่ ใจดี', '081-111-1003', 'newguy@email.com', '789 ถนนพหลโยธิน แขวงสามเสนใน เขตพญาไท กทม. 10400', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', 1, 1, NOW(), NOW()),
('TEST004', 'ห้างหุ้นส่วน สดใส', '02-111-1004', 'sadsai@partner.com', '321 ถนนเพชรบุรี แขวงทุ่งพญาไท เขตราชเทวี กทม. 10400', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
('TEST005', 'นางสาวใหม่ รักการค้า', '081-111-1005', 'newlove@trade.com', '654 ถนนวิภาวดี แขวงจตุจักร เขตจตุจักร กทม. 10900', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW());

-- Sales01 - Follow Up Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST006', 'บริษัท ติดตาม ดีดี จำกัด', '02-111-1006', 'follow@goodcompany.com', '987 ถนนศรีนครินทร์ แขวงสวนหลวง เขตสวนหลวง กทม. 10250', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST007', 'ร้าน Follow Up Store', '081-111-1007', 'followup@store.com', '147 ถนนลาดพร้าว แขวงจอมพล เขตจตุจักร กทม. 10900', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST008', 'นายติดตาม ใส่ใจ', '081-111-1008', 'care@follow.com', '258 ถนนรามคำแหง แขวงหัวหมาก เขตบางกะปิ กทม. 10240', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 12 DAY), NOW()),
('TEST009', 'บริษัท ติดตาม สม่ำเสมอ จำกัด', '02-111-1009', 'regular@follow.com', '369 ถนนพระราม 4 แขวงคลองเตย เขตคลองเตย กทม. 10110', 'ลูกค้าติดตาม', 'ตะกร้ารอ', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST010', 'นางสาวติดตาม ใกล้สนิป', '081-111-1010', 'close@deal.com', '741 ถนนเจริญกรุง แขวงบางรัก เขตบางรัก กทม. 10500', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), NOW());

-- Sales01 - Old Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST011', 'บริษัท เก่าแก่ มั่นคง จำกัด', '02-111-1011', 'stable@oldcompany.com', '852 ถนนสีลม แขวงสีลม เขตบางรัก กทม. 10500', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
('TEST012', 'ร้าน เก่าดี มีประวัติ', '081-111-1012', 'history@oldstore.com', '963 ถนนสาทร แขวงยานนาวา เขตสาทร กทม. 10120', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 120 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST013', 'นายเก่า ประจำลูก', '081-111-1013', 'regular@customer.com', '159 ถนนนราธิวาส แขวงช่องนนทรี เขตยานนาวา กทม. 10120', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 180 DAY), NOW()),
('TEST014', 'บริษัท เก่าแต่ดี จำกัด', '02-111-1014', 'oldbut@good.com', '357 ถนนพลับพลา แขวงวังทองหลาง เขตวังทองหลาง กทม. 10310', 'ลูกค้าเก่า', 'ตะกร้ารอ', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 200 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
('TEST015', 'นางเก่า วัยเก๋า', '081-111-1015', 'senior@customer.com', '468 ถนนอโศก แขวงคลองเตยเหนือ เขตวัฒนา กทม. 10110', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 150 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Sales01 - Basket Distribution (2 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST016', 'บริษัท ตะกร้าแจก ไม่เอา จำกัด', '02-111-1016', 'notake@distribution.com', '579 ถนนวิทยุ แขวงปทุมวัน เขตปทุมวัน กทม. 10330', 'ลูกค้าติดตาม', 'ตะกร้าแจก', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 35 DAY), DATE_SUB(NOW(), INTERVAL 35 DAY)),
('TEST017', 'นายตะกร้า แจกไม่รับ', '081-111-1017', 'nogive@basket.com', '680 ถนนราชดำริ แขวงลุมพินี เขตปทุมวัน กทม. 10330', 'ลูกค้าใหม่', 'ตะกร้าแจก', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 32 DAY), DATE_SUB(NOW(), INTERVAL 32 DAY));

-- Sales01 - Waiting Basket / 30-day Rule (3 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST018', 'บริษัท ใกล้ครบ 30 วัน จำกัด', '02-111-1018', 'almost30@days.com', '791 ถนนเฉลิมพระเกียรติ แขวงหนองบอน เขตประเวศ กทม. 10250', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 28 DAY)),
('TEST019', 'นายใกล้โดน ดึงคืน', '081-111-1019', 'pullback@30days.com', '802 ถนนกรุงเทพกรีฑา แขวงหัวหมาก เขตบางกะปิ กทม. 10240', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales01', 1, 1, DATE_SUB(NOW(), INTERVAL 29 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY)),
('TEST020', 'บริษัท พ้น 30 วันแล้ว จำกัด', '02-111-1020', 'over30@days.com', '913 ถนนอ่อนนุช แขวงสวนหลวง เขตสวนหลวง กทม. 10250', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 31 DAY));

-- ======================================================================
-- 3. CUSTOMER DATA - Sales02 (20 customers)
-- Distribution: Mix of all customer types and statuses
-- ======================================================================

-- Sales02 - New Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST021', 'บริษัท ใหม่หมาด จำกัด', '02-222-2001', 'fresh@newcompany.com', '124 ถนนพระราม 1 แขวงปทุมวัน เขตปทุมวัน กทม. 10330', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('TEST022', 'ร้าน สดใหม่ ค้าขาย', '081-222-2002', 'fresh@trade.com', '235 ถนนพระราม 2 แขวงแสมดำ เขตบางขุนเทียน กทม. 10150', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
('TEST023', 'นายใหม่หน่อย สนใจมาก', '081-222-2003', 'interested@new.com', '346 ถนนพระราม 3 แขวงบางโคล่ เขตบางคอแหลม กทม. 10120', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', 1, 1, NOW(), NOW()),
('TEST024', 'บริษัท เพิ่งเริ่ม จำกัด', '02-222-2004', 'juststart@company.com', '457 ถนนพระราม 4 แขวงปทุมวัน เขตปทุมวัน กทม. 10330', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY), NOW()),
('TEST025', 'นางสาวใหม่ล่าสุด', '081-222-2005', 'latest@new.com', '568 ถนนพระราม 9 แขวงห้วยขวาง เขตห้วยขวาง กทม. 10310', 'ลูกค้าใหม่', 'ตะกร้ารอ', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW());

-- Sales02 - Follow Up Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST026', 'บริษัท ติดตามเข้ม จำกัด', '02-222-2006', 'intensive@follow.com', '679 ถนนบางนา แขวงบางนาใต้ เขตบางนา กทม. 10260', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST027', 'ร้าน ติดตามสม่ำเสมอ', '081-222-2007', 'consistent@follow.com', '780 ถนนนวมินทร์ แขวงคลองกุ่ม เขตบึงกุ่ม กทม. 10230', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 9 DAY), NOW()),
('TEST028', 'นายติดตาม ใกล้ปิด', '081-222-2008', 'nearclose@follow.com', '891 ถนนเสรีไทย แขวงคันนายาว เขตคันนายาว กทม. 10230', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST029', 'บริษัท ติดตามดี จำกัด', '02-222-2009', 'goodfollow@company.com', '902 ถนนหทัยราษฎร์ แขวงคลองสามประเวศ เขตลาดกระบัง กทม. 10520', 'ลูกค้าติดตาม', 'ตะกร้ารอ', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
'TEST030', 'นางสาวติดตาม เก่งมาก', '081-222-2010', 'excellent@follow.com', '13 ถนนกิ่งแก้ว แขวงราชาเทวะ เขตบางพลี สมุทรปราการ 10540', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 6 DAY), NOW());

-- Sales02 - Old Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST031', 'บริษัท เก้าแก่มาก จำกัด', '02-222-2011', 'veryold@company.com', '124 ถนนเอกชัย แขวงบางบอน เขตบางบอน กทม. 10150', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 100 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
('TEST032', 'ร้าน เก่าดั้งเดิม', '081-222-2012', 'traditional@old.com', '235 ถนนเพชรเกษม แขวงหนองแขม เขตหนองแขม กทม. 10160', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 130 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST033', 'นายเก่าแก่ ซื้อประจำ', '081-222-2013', 'regular@old.com', '346 ถนนเทพารักษ์ แขวงบางปู เขตเมืองสมุทรปราการ สมุทรปราการ 10280', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 190 DAY), NOW()),
('TEST034', 'บริษัท เก่าน่าเชื่อถือ จำกัด', '02-222-2014', 'trustworthy@old.com', '457 ถนนปิ่นเกล้า แขวงบางซื่อ เขตบางซื่อ กทม. 10800', 'ลูกค้าเก่า', 'ตะกร้ารอ', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 210 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),
('TEST035', 'นางเก่าคุ้นเคย', '081-222-2015', 'familiar@old.com', '568 ถนนประชาชื่น แขวงทุ่งสองห้อง เขตหลักสี่ กทม. 10210', 'ลูกค้าเก่า', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 160 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY));

-- Sales02 - Basket Distribution (2 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST036', 'บริษัท ตะกร้าแจกไม่ใช้ จำกัด', '02-222-2016', 'notuse@distribution.com', '679 ถนนพระราม 5 แขวงบางกรวย เขตบางกรวย นนทบุรี 11130', 'ลูกค้าติดตาม', 'ตะกร้าแจก', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 33 DAY), DATE_SUB(NOW(), INTERVAL 33 DAY)),
('TEST037', 'นายตะกร้า แจกแล้วทิ้ง', '081-222-2017', 'abandon@basket.com', '780 ถนนแจ้งวัฒนะ แขวงคลองเจ็ดเก้า เขตปากเกร็ด นนทบุรี 11120', 'ลูกค้าใหม่', 'ตะกร้าแจก', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 34 DAY), DATE_SUB(NOW(), INTERVAL 34 DAY));

-- Sales02 - Waiting Basket / 30-day Rule (3 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST038', 'บริษัท เกือบ 30 วัน จำกัด', '02-222-2018', 'near30@days.com', '891 ถนนติวานนท์ แขวงตลาดขวัญ เขตเมืองนนทบุรี นนทบุรี 11000', 'ลูกค้าติดตาม', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 27 DAY), DATE_SUB(NOW(), INTERVAL 27 DAY)),
('TEST039', 'นายใกล้ถูก รีเซ็ต', '081-222-2019', 'nearneset@30days.com', '902 ถนนรัตนาธิเบศร์ แขวงมหาสวัสดิ์ เขตบางกรวย นนทบุรี 11130', 'ลูกค้าใหม่', 'กำลังดูแล', 'sales02', 1, 1, DATE_SUB(NOW(), INTERVAL 29 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY)),
('TEST040', 'บริษัท เกิน 30 วันแล้ว จำกัด', '02-222-2020', 'exceeded30@days.com', '13 ถนนบางกรวย-ไทรน้อย แขวงบางเสี้ยน เขตบางใหญ่ นนทบุรี 11140', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 32 DAY), DATE_SUB(NOW(), INTERVAL 32 DAY));

-- ======================================================================
-- 4. SUPERVISOR CUSTOMERS (20 customers) - No Sales Assigned
-- Mixed status for supervisor direct management
-- ======================================================================

-- Supervisor Direct - New Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST041', 'บริษัท ใหม่ใส่ Sup จำกัด', '02-333-3001', 'newsup@company.com', '124 ถนนพุทธมณฑลสาย 4 แขวงศาลายา เขตพุทธมณฑล นครปฐม 73170', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
('TEST042', 'ร้าน ใหม่ รอ Sup', '081-333-3002', 'waitsup@new.com', '235 ถนนบรมราชชนนี แขวงบางบำหรุ เขตบางพลัด กทม. 10700', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
('TEST043', 'นายใหม่ รอการมอบหมาย', '081-333-3003', 'waitassign@new.com', '346 ถนนจรัญสนิทวงศ์ แขวงบางอ้อ เขตบางพลัด กทม. 10700', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, 1, 1, NOW(), NOW()),
('TEST044', 'บริษัท ใหม่พิเศษ จำกัด', '02-333-3004', 'specialnew@company.com', '457 ถนนเจริญนคร แขวงคลองสาน เขตคลองสาน กทม. 10600', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
('TEST045', 'นางสาวใหม่ รอคิว', '081-333-3005', 'queuenew@wait.com', '568 ถนนกรุงธนบุรี แขวงคลองต้นไทร เขตคลองสาน กทม. 10600', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW());

-- Supervisor Direct - Follow Up Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST046', 'บริษัท ติดตาม Sup จำกัด', '02-333-3006', 'followsup@company.com', '679 ถนนตากสิน แขวงบุคคโล เขตธนบุรี กทม. 10600', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST047', 'ร้าน ติดตาม รอมอบหมาย', '081-333-3007', 'followwait@assign.com', '780 ถนนสมเด็จพระเจ้าตากสิน แขวงบางหว้า เขตภาษีเจริญ กทม. 10160', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW()),
('TEST048', 'นายติดตาม พิเศษ Sup', '081-333-3008', 'specialfollow@sup.com', '891 ถนนเพชรเกษม แขวงหนองแขม เขตหนองแขม กทม. 10160', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST049', 'บริษัท ติดตามใหญ่ จำกัด', '02-333-3009', 'bigfollow@company.com', '902 ถนนกาญจนาภิเษก แขวงบางแค เขตบางแค กทม. 10160', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 17 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST050', 'นางสาวติดตาม VIP', '081-333-3010', 'vipfollow@customer.com', '13 ถนนบางแค-รัตนาธิเบศร์ แขวงหลักสอง เขตหลักสี่ กทม. 10210', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 8 DAY), NOW());

-- Supervisor Direct - Old Customers (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST051', 'บริษัท เก่า Sup ดูแล จำกัด', '02-333-3011', 'oldsup@company.com', '124 ถนนนครอินทร์ แขวงลาดยาว เขตจตุจักร กทม. 10900', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 110 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
('TEST052', 'ร้าน เก่าแก่ รอ Sales', '081-333-3012', 'oldwait@sales.com', '235 ถนนวิภาวดีรังสิต แขวงดินแดง เขตดินแดง กทม. 10400', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 140 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
('TEST053', 'นายเก่า สำคัญ Sup', '081-333-3013', 'importantold@sup.com', '346 ถนนพหลโยธิน แขวงลาดยาว เขตจตุจักร กทม. 10900', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 170 DAY), NOW()),
('TEST054', 'บริษัท เก่าใหญ่ จำกัด', '02-333-3014', 'bigold@company.com', '457 ถนนรัชดาภิเษก แขวงห้วยขวาง เขตห้วยขวาง กทม. 10310', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 220 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
('TEST055', 'นางเก่า VIP ระดับ Sup', '081-333-3015', 'vipold@supervisor.com', '568 ถนนลาดพร้าว แขวงจอมพล เขตจตุจักร กทม. 10900', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 180 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY));

-- Supervisor Direct - Mixed Status (5 customers)
INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CartStatus, Sales, team_id, supervisor_id, CreatedDate, ModifiedDate) VALUES
('TEST056', 'บริษัท พิเศษคดี จำกัด', '02-333-3016', 'specialcase@company.com', '679 ถนนสุขาภิบาล 5 แขวงคลองเจ้าคุณสิงห์ เขตวัฒนา กทม. 10110', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST057', 'นายใหญ่ รอการพิจารณา', '081-333-3017', 'bigwait@consider.com', '780 ถนนเอกมัย แขวงคลองตันเหนือ เขตวัฒนา กทม. 10110', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
('TEST058', 'บริษัท ระหว่างตรวจสอบ จำกัด', '02-333-3018', 'underreview@company.com', '891 ถนนสุขุมวิท แขวงพระโขนง เขตวัฒนา กทม. 10110', 'ลูกค้าเก่า', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 95 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
('TEST059', 'นางสาวปัญหาพิเศษ', '081-333-3019', 'specialproblem@case.com', '902 ถนนเพลินจิต แขวงลุมพินี เขตปทุมวัน กทม. 10330', 'ลูกค้าติดตาม', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST060', 'บริษัท รอจัดสรร จำกัด', '02-333-3020', 'waitallocation@company.com', '13 ถนนสาทร แขวงสีลม เขตบางรัก กทม. 10500', 'ลูกค้าใหม่', 'ตะกร้ารอ', NULL, 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW());

-- ======================================================================
-- 5. DAILY TASKS / FOLLOW UP DATA
-- ======================================================================

-- Today's Appointments (5 tasks)
INSERT INTO daily_tasks (assigned_to, customer_code, task_type, task_description, task_date, task_time, priority, status, created_by, created_date) VALUES
('sales01', 'TEST006', 'นัดหมาย', 'TEST: นัดหมายพบลูกค้า - นำเสนอสินค้าใหม่', CURDATE(), '09:00:00', 'สูง', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales01', 'TEST008', 'นัดหมาย', 'TEST: นัดหมายติดตามผล - เสนอโปรโมชั่น', CURDATE(), '14:00:00', 'ปานกลาง', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales02', 'TEST026', 'นัดหมาย', 'TEST: นัดหมายปิดการขาย - เซ็นสัญญา', CURDATE(), '10:30:00', 'สูง', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales02', 'TEST028', 'นัดหมาย', 'TEST: นัดหมายทำการตลาด - แนะนำบริการเพิ่ม', CURDATE(), '16:00:00', 'ปานกลาง', 'รอดำเนินการ', 'supervisor01', NOW()),
('supervisor01', 'TEST046', 'นัดหมาย', 'TEST: นัดหมาย VIP - หารือแผนขายปี 2024', CURDATE(), '11:00:00', 'สูง', 'รอดำเนินการ', 'admin', NOW());

-- Follow Up Tasks (10 tasks)
INSERT INTO daily_tasks (assigned_to, customer_code, task_type, task_description, task_date, task_time, priority, status, created_by, created_date) VALUES
('sales01', 'TEST007', 'ติดตามลูกค้า', 'TEST: โทรติดตามความต้องการ - เสนอแพ็กเกจใหม่', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:30:00', 'ปานกลาง', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales01', 'TEST009', 'ติดตามลูกค้า', 'TEST: ส่งใบเสนอราคา - รอการพิจารณา', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'ปานกลาง', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales01', 'TEST018', 'ติดตามลูกค้า', 'TEST: ติดตามก่อนครบ 30 วัน - เร่งตัดสินใจ', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00', 'สูง', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales02', 'TEST027', 'ติดตามลูกค้า', 'TEST: แจ้งข่าวสารสินค้า - กิจกรรมพิเศษ', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', 'ต่ำ', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales02', 'TEST029', 'ติดตามลูกค้า', 'TEST: นำส่งเอกสารเพิ่มเติม - ข้อมูลทางเทคนิค', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:30:00', 'ปานกลาง', 'รอดำเนินการ', 'supervisor01', NOW()),
('sales02', 'TEST038', 'ติดตามลูกค้า', 'TEST: ติดตามก่อนเวลาหมด - เร่งการตัดสินใจ', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:30:00', 'สูง', 'รอดำเนินการ', 'supervisor01', NOW()),
('supervisor01', 'TEST048', 'ติดตามลูกค้า', 'TEST: ติดตามลูกค้าใหญ่ - จัดทำแผนขาย', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'สูง', 'รอดำเนินการ', 'admin', NOW()),
('supervisor01', 'TEST051', 'ติดตามลูกค้า', 'TEST: ติดตามลูกค้าเก่า - หาโอกาสขายเพิ่ม', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:30:00', 'ปานกลาง', 'รอดำเนินการ', 'admin', NOW()),
('supervisor01', 'TEST056', 'ติดตามลูกค้า', 'TEST: ตรวจสอบปัญหาพิเศษ - หาแนวทางแก้ไข', CURDATE(), '13:30:00', 'สูง', 'รอดำเนินการ', 'admin', NOW()),
('supervisor01', 'TEST058', 'ติดตามลูกค้า', 'TEST: ทบทวนลูกค้าเก่า - ประเมินศักยภาพ', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:30:00', 'ปานกลาง', 'รอดำเนินการ', 'admin', NOW());

-- ======================================================================
-- 6. CALL HISTORY DATA
-- ======================================================================

-- Recent Call History (15 records)
INSERT INTO call_history (customer_code, caller_name, call_type, call_duration, call_result, notes, created_by, call_date) VALUES
('TEST001', 'sales01', 'โทรออก', '00:15:30', 'สนใจ', 'TEST: ลูกค้าสนใจสินค้า ขอข้อมูลเพิ่มเติม นัดส่งใบเสนอราคา', 'sales01', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('TEST006', 'sales01', 'โทรออก', '00:25:45', 'ติดตาม', 'TEST: ติดตามใบเสนอราคา ลูกค้าขอเวลาพิจารณา 3 วัน', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST008', 'sales01', 'โทรเข้า', '00:08:20', 'สอบถาม', 'TEST: ลูกค้าโทรสอบถามรายละเอียด การรับประกันสินค้า', 'sales01', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('TEST011', 'sales01', 'โทรออก', '00:18:15', 'ปิดการขาย', 'TEST: ลูกค้าตัดสินใจซื้อ จะโอนเงินพรุ่งนี้ ยอด 50,000 บาท', 'sales01', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
('TEST018', 'sales01', 'โทรออก', '00:12:30', 'เร่งด่วน', 'TEST: แจ้งลูกค้าเหลือเวลา 2 วัน ก่อนสิ้นสุดโปรโมชั่น', 'sales01', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('TEST021', 'sales02', 'โทรออก', '00:20:10', 'สนใจ', 'TEST: ลูกค้าใหม่สนใจมาก ขอนัดหมายพบหน้า วันศุกร์นี้', 'sales02', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('TEST026', 'sales02', 'โทรเข้า', '00:06:45', 'ยืนยัน', 'TEST: ลูกค้าโทรมายืนยันการนัดหมาย เวลา 10:30 น. วันนี้', 'sales02', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('TEST028', 'sales02', 'โทรออก', '00:22:18', 'ติดตาม', 'TEST: ติดตามหลังการนำเสนอ ลูกค้าขออีก 1 สัปดาห์', 'sales02', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
('TEST031', 'sales02', 'โทรออก', '00:14:25', 'บำรุงความสัมพันธ์', 'TEST: โทรทักทายลูกค้าเก่า แจ้งสินค้าใหม่ที่น่าสนใจ', 'sales02', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST038', 'sales02', 'โทรออก', '00:16:50', 'เร่งด่วน', 'TEST: แจ้งเวลาเหลือน้อย ลูกค้าขอปรึกษาผู้บริหาร', 'sales02', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('TEST046', 'supervisor01', 'โทรออก', '00:35:20', 'ปรึกษา', 'TEST: ปรึกษาแผนการขาย ลูกค้าใหญ่พิเศษ วงเงิน 500,000', 'supervisor01', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
('TEST051', 'supervisor01', 'โทรเข้า', '00:28:45', 'ขอข้อมูล', 'TEST: ลูกค้าเก่าโทรขอข้อมูลบริการใหม่ ส่วนลดพิเศษ', 'supervisor01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST056', 'supervisor01', 'โทรออก', '00:45:30', 'แก้ปัญหา', 'TEST: ประสานแก้ปัญหาการส่งมอบ หารือวิธีแก้ไข', 'supervisor01', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('TEST002', 'sales01', 'โทรออก', '00:11:15', 'ไม่สนใจ', 'TEST: ลูกค้าไม่สนใจในขณะนี้ ขอติดต่อใหม่เดือนหน้า', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST024', 'sales02', 'โทรไม่ติด', '00:00:00', 'ไม่รับสาย', 'TEST: โทรไม่ติด 3 ครั้ง ลองส่ง LINE แทน', 'sales02', DATE_SUB(NOW(), INTERVAL 6 HOUR));

-- ======================================================================
-- 7. ORDER HISTORY DATA (Sample Orders)
-- ======================================================================

-- Sample Orders for Testing
INSERT INTO orders (customer_code, order_no, order_date, product_name, quantity, unit_price, total_amount, payment_method, order_status, sales_person, created_by, created_date) VALUES
('TEST011', 'ORD2024001', DATE_SUB(NOW(), INTERVAL 1 DAY), 'TEST: ชุดสินค้า Premium Package A', 2, 25000.00, 50000.00, 'โอนธนาคาร', 'ชำระแล้ว', 'sales01', 'sales01', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('TEST031', 'ORD2024002', DATE_SUB(NOW(), INTERVAL 3 DAY), 'TEST: บริการดูแลระบบ รายเดือน', 1, 15000.00, 15000.00, 'เงินสด', 'ชำระแล้ว', 'sales02', 'sales02', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('TEST006', 'ORD2024003', DATE_SUB(NOW(), INTERVAL 5 DAY), 'TEST: อุปกรณ์เสริม Set B', 3, 8500.00, 25500.00, 'โอนธนาคาร', 'รอชำระ', 'sales01', 'sales01', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('TEST026', 'ORD2024004', DATE_SUB(NOW(), INTERVAL 2 DAY), 'TEST: Software License ปี 2024', 1, 35000.00, 35000.00, 'เช็ค', 'ชำระแล้ว', 'sales02', 'sales02', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('TEST051', 'ORD2024005', DATE_SUB(NOW(), INTERVAL 7 DAY), 'TEST: บริการติดตั้งและอบรม', 1, 45000.00, 45000.00, 'โอนธนาคาร', 'กำลังดำเนินการ', 'supervisor01', 'supervisor01', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- ======================================================================
-- 8. SUMMARY STATISTICS UPDATE
-- ======================================================================

-- Update user statistics (if table exists)
-- This would typically be done by triggers or scheduled procedures in production

-- ======================================================================
-- END OF MOCK DATA
-- ======================================================================

-- Final verification queries (you can run these to check the data)
/*
SELECT 'Total Customers' as Description, COUNT(*) as Count FROM customers WHERE CustomerCode LIKE 'TEST%'
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
SELECT 'Daily Tasks', COUNT(*) FROM daily_tasks WHERE task_description LIKE 'TEST:%'
UNION ALL
SELECT 'Call History', COUNT(*) FROM call_history WHERE notes LIKE 'TEST:%'
UNION ALL
SELECT 'Orders', COUNT(*) FROM orders WHERE product_name LIKE 'TEST:%';
*/