Brownfield Architecture Document: Kiro CRM Enhancement
เวอร์ชัน: 2.0
วันที่ปรับปรุง: 27 กรกฎาคม 2568
ผู้จัดทำ: Architect (Winston)
อ้างอิง: PRD: Kiro CRM - V3

1. ภาพรวมสถาปัตยกรรม
ระบบเป็นเว็บแอปพลิเคชันแบบ Monolith พัฒนาด้วย PHP และ MySQL การปรับปรุงครั้งนี้จะยังคงอยู่บน Tech Stack เดิม และเน้นการแก้ไขและเพิ่มเติม Logic ในฝั่ง Backend เป็นหลัก ควบคู่ไปกับการปรับปรุง UI ในฝั่ง Frontend

2. การออกแบบการเปลี่ยนแปลงฐานข้อมูล (Database Schema Design)
จะต้องมีการรัน SQL Migration Script เพื่อปรับปรุงตาราง customers ให้รองรับ Logic ใหม่

-- Story 1.1: Alter Database Schema
ALTER TABLE `customers`
ADD COLUMN `ContactAttempts` INT NOT NULL DEFAULT 0 COMMENT 'จำนวนครั้งที่พยายามติดต่อ' AFTER `LastContactDate`,
ADD COLUMN `AssignmentCount` INT NOT NULL DEFAULT 0 COMMENT 'จำนวนครั้งที่ถูกแจกจ่าย' AFTER `ContactAttempts`;

ALTER TABLE `customers`
MODIFY COLUMN `CustomerTemperature` ENUM('HOT', 'WARM', 'COLD', 'FROZEN') DEFAULT 'WARM';

-- หมายเหตุ: ต้องตรวจสอบให้แน่ใจว่าตาราง users มีคอลัมน์ supervisor_id และ Foreign Key ถูกต้อง

3. สถาปัตยกรรม Backend และ Logic อัตโนมัติ
3.1. Cron Job (cron/auto_rules.php)

การแก้ไข (Fix):

ปรับปรุง SQL Query ให้สามารถค้นหาลูกค้าที่เลยกำหนดเวลา (Overdue) ได้อย่างถูกต้อง เช่น WHERE DATEDIFF(CURDATE(), LastContactDate) > 90 และทำการ UPDATE สถานะได้ทันที

การเพิ่มเติม (Enhancement):

Hybrid Logic: เพิ่มเงื่อนไขการตรวจสอบ ContactAttempts >= 3 สำหรับลูกค้าใหม่

Freezing Logic: เพิ่มเงื่อนไขการตรวจสอบ AssignmentCount >= 3 และทำการ UPDATE CustomerTemperature เป็น 'FROZEN'

3.2. Lead Assignment API (api/sales/assign.php)

การเปลี่ยนแปลง: เพิ่ม Logic การ UPDATE customers SET AssignmentCount = AssignmentCount + 1 เมื่อมีการแจกจ่ายงานสำเร็จ

3.3. User Management API (api/users/toggle_status.php)

การเปลี่ยนแปลง: เพิ่ม Logic ตรวจสอบ Role ของผู้ใช้ที่กำลังจะ Inactive หากเป็น 'Sales' ให้ Trigger Sales Departure Workflow เพื่อโอนย้ายรายชื่อตามกฎ 3 ข้อที่ระบุใน PRD

3.4. Dashboard API (api/dashboard/summary.php)

การเปลี่ยนแปลง: แก้ไข SQL Query เพื่อคำนวณและส่งค่า time_remaining_days กลับไปใน JSON response

4. สถาปัตยกรรม Frontend (UI/UX Revamp)
4.1. Dashboard View (pages/dashboard.php)

การเปลี่ยนแปลง: แก้ไขโค้ด JavaScript เพื่อ:

อ่านค่า time_remaining_days และ CustomerTemperature จาก API

สร้าง HTML ของ Progress Bar และกำหนด class CSS ตามเงื่อนไข (เขียว/เหลือง/แดง)

เพิ่ม class CSS สำหรับ ไฮไลท์แถว ให้กับลูกค้าที่เข้าเงื่อนไข

4.2. Dashboard CSS (assets/css/dashboard.css)

การเปลี่ยนแปลง: เพิ่ม Style Class ใหม่สำหรับ Progress Bar, การไฮไลท์แถว, และปรับปรุงสไตล์โดยรวมตาม Guideline