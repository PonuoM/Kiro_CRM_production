-- Create products table for CRM system
CREATE TABLE IF NOT EXISTS `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_code` varchar(20) NOT NULL UNIQUE,
    `product_name` varchar(255) NOT NULL,
    `category` varchar(100) DEFAULT NULL,
    `unit` varchar(50) DEFAULT 'ชิ้น',
    `standard_price` decimal(10,2) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_date` timestamp DEFAULT CURRENT_TIMESTAMP,
    `created_by` varchar(100) DEFAULT NULL,
    `modified_date` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modified_by` varchar(100) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_product_code` (`product_code`),
    KEY `idx_category` (`category`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert product data
INSERT INTO `products` (`product_code`, `product_name`, `category`, `unit`, `standard_price`, `is_active`) VALUES
-- ปุ๋ย Large (50 กก.)
('FER-L01', 'ปุ๋ยสิงห์ชมพู สูตร 6-3-3 (50 กก.)', 'ปุ๋ยเคมี', 'ถุง', 450.00, 1),
('FER-L02', 'ปุ๋ยสิงห์ส้ม สูตร 12-4-4 (50 กก.)', 'ปุ๋ยเคมี', 'ถุง', 480.00, 1),
('FER-L03', 'ปุ๋ยอินทรีย์สิงห์ทอง (50 กก.)', 'ปุ๋ยอินทรีย์', 'ถุง', 380.00, 1),
('FER-L04', 'ปุ๋ยสิงห์เขียว สูตร 4-4-12 (50 กก.)', 'ปุ๋ยเคมี', 'ถุง', 420.00, 1),

-- ปุ๋ย Small (25 กก.)
('FER-S01', 'ปุ๋ยอินทรีย์สิงห์ทอง (25 กก.)', 'ปุ๋ยอินทรีย์', 'ถุง', 200.00, 1),
('FER-S02', 'ปุ๋ยสารปรับปรุงดิน (25 กก.)', 'ปุ๋ยปรับปรุงดิน', 'ถุง', 180.00, 1),

-- ปุ๋ยน้ำ
('FER-W01', 'ปุ๋ยน้ำ สูตร 4-24-24', 'ปุ๋ยน้ำ', 'ลิตร', 85.00, 1),
('FER-W02', 'ปุ๋ยน้ำ สูตร 21-3-3', 'ปุ๋ยน้ำ', 'ลิตร', 90.00, 1),

-- ผลิตภัณฑ์ชีวภาพ
('BIO-001', 'อะมิโน-มิค', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 120.00, 1),
('BIO-002', 'ไคโตซานมิค', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 150.00, 1),
('BIO-003', 'ซุปเปอร์ไตรโค', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 180.00, 1),
('BIO-004', 'แคลโบมิคพลัส', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 160.00, 1),
('BIO-005', 'บิว-เมธามิค', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 140.00, 1),
('BIO-006', 'Beta Oil', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 200.00, 1),
('BIO-007', 'บีที (BT)', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 130.00, 1),
('BIO-008', 'จุลินทรีย์ย่อยสลาย', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 110.00, 1),
('BIO-009', 'ไฮมิค (สารจับใบ)', 'ผลิตภัณฑ์ชีวภาพ', 'ลิตร', 95.00, 1),

-- ของแถม/ของขวัญ
('GFT-001', 'เสื้อเทพมงคล', 'ของแถม', 'ตัว', 0.00, 1),
('GFT-002', 'เสื้อแสนราชสีห์ (พรีออนิค)', 'ของแถม', 'ตัว', 0.00, 1),
('GFT-003', 'แถม: ปุ๋ยสิงห์เขียว 4-4-12 (50 กก.)', 'ของแถม', 'ถุง', 0.00, 1);