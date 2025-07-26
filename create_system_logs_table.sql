-- System Logs Table for Auto Status Management
-- ตารางสำหรับเก็บ log การเปลี่ยนแปลงสถานะอัตโนมัติ

CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `LogType` varchar(50) NOT NULL COMMENT 'ประเภท log เช่น AUTO_STATUS, WORKFLOW_FIX',
  `Action` varchar(100) NOT NULL COMMENT 'การกระทำที่ทำ เช่น UPDATE_CART_STATUS, BATCH_UPDATE',
  `Details` text COMMENT 'รายละเอียดของการกระทำ',
  `AffectedCount` int(11) DEFAULT 0 COMMENT 'จำนวนลูกค้าที่ได้รับผลกระทำ',
  `CreatedBy` varchar(50) NOT NULL COMMENT 'ผู้ทำการ เช่น auto_system, admin, sales01',
  `CreatedDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ExecutionTime` decimal(10,3) DEFAULT NULL COMMENT 'เวลาที่ใช้ในการประมวลผล (วินาที)',
  `Status` enum('SUCCESS','ERROR','WARNING') DEFAULT 'SUCCESS',
  `ErrorMessage` text COMMENT 'ข้อความ error ถ้ามี',
  PRIMARY KEY (`id`),
  KEY `idx_log_type` (`LogType`),
  KEY `idx_created_date` (`CreatedDate`),
  KEY `idx_created_by` (`CreatedBy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='ตารางเก็บ log การทำงานของระบบอัตโนมัติ';

-- Insert sample log entries for testing
INSERT INTO `system_logs` (`LogType`, `Action`, `Details`, `AffectedCount`, `CreatedBy`, `CreatedDate`, `Status`) VALUES
('SYSTEM_INIT', 'CREATE_TABLE', 'Created system_logs table for auto status management', 0, 'system', NOW(), 'SUCCESS'),
('WORKFLOW_FIX', 'TABLE_CREATION', 'System logs table created successfully', 0, 'admin', NOW(), 'SUCCESS');

-- View to check recent logs
CREATE OR REPLACE VIEW `recent_system_logs` AS
SELECT 
    id,
    LogType,
    Action,
    Details,
    AffectedCount,
    CreatedBy,
    CreatedDate,
    Status,
    CASE 
        WHEN CreatedDate >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'Today'
        WHEN CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'This Week'
        WHEN CreatedDate >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'This Month'
        ELSE 'Older'
    END as TimeCategory
FROM system_logs 
WHERE CreatedDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY CreatedDate DESC;

-- Index for performance
ALTER TABLE `system_logs` 
ADD INDEX `idx_composite` (`LogType`, `CreatedDate`, `Status`);

SELECT 'System logs table created successfully!' as Result;