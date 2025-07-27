Brownfield Architecture Document: Kiro CRM Enhancement
เวอร์ชัน: 1.0
วันที่จัดทำ: 26 กรกฎาคม 2568
ผู้จัดทำ: Architect (Winston)
เอกสารอ้างอิง: PRD: Kiro CRM - ฉบับปรับปรุง V2

1. บทนำและการวิเคราะห์ระบบปัจจุบัน (Introduction & Current State Analysis)
1.1. บทนำ (Introduction)
เอกสารนี้คือพิมพ์เขียวทางสถาปัตยกรรมสำหรับการปรับปรุง (Enhancement) ระบบ Kiro_CRM_production โดยมีเป้าหมายเพื่อนำข้อกำหนดจาก PRD v2.0 มา υλοποίηση ให้เกิดขึ้นจริง เอกสารนี้จะลงรายละเอียดทางเทคนิค, กำหนดโครงสร้าง, และระบุผลกระทบต่อโค้ดเบสเดิม เพื่อให้ทีมพัฒนาและ AI Agent สามารถทำงานได้อย่างสอดคล้องกัน

1.2. การวิเคราะห์ระบบปัจจุบัน (Current State Analysis)

สถาปัตยกรรม: เป็นเว็บแอปพลิเคชันแบบ Monolith ที่พัฒนาด้วยภาษา PHP

ฐานข้อมูล: MySQL/MariaDB โดยมีโครงสร้างตารางหลักตามที่ระบุในไฟล์ ตารางาน.pdf

Frontend: เป็นการ Render ฝั่ง Server (Server-Side Rendering) โดยใช้ HTML/CSS/JavaScript พื้นฐาน

ข้อจำกัด: การพัฒนาใหม่ทั้งหมดจะต้องทำบน Tech Stack เดิม เพื่อรักษาความเข้ากันได้ของระบบ (Compatibility)

2. สถาปัตยกรรมของส่วนปรับปรุง (Enhancement Architecture)
2.1. การออกแบบการเปลี่ยนแปลงฐานข้อมูล (Database Schema Design)
เพื่อรองรับ Logic ใหม่ จะต้องมีการเปลี่ยนแปลงโครงสร้างตารางดังนี้ โดยจะต้องทำผ่าน SQL Migration Script:

-- Story 1.1: Alter Database Schema
-- เพิ่มคอลัมน์สำหรับนับจำนวนครั้งที่ติดต่อและจำนวนครั้งที่ถูกแจกจ่าย
ALTER TABLE `customers`
ADD COLUMN `ContactAttempts` INT NOT NULL DEFAULT 0 AFTER `LastContactDate`,
ADD COLUMN `AssignmentCount` INT NOT NULL DEFAULT 0 AFTER `ContactAttempts`;

-- แก้ไข ENUM ของ CustomerTemperature ให้รองรับสถานะ FROZEN
ALTER TABLE `customers`
MODIFY COLUMN `CustomerTemperature` ENUM('HOT', 'WARM', 'COLD', 'FROZEN') DEFAULT 'WARM';

-- ยืนยันว่ามีคอลัมน์ supervisor_id ในตาราง users
-- (หากยังไม่มี ให้ใช้ ALTER TABLE users ADD COLUMN supervisor_id INT NULL AFTER Role;)
-- ALTER TABLE `users` ADD CONSTRAINT `fk_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `users`(`id`);

2.2. สถาปัตยกรรม Backend และ Logic อัตโนมัติ

ส่วนประกอบใหม่: Lead Management Cron Job

ไฟล์: cron/auto_rules.php (สร้างใหม่)

หน้าที่: เป็น Script ที่จะถูกเรียกใช้งานวันละ 1 ครั้ง (ผ่าน Server Cron Job) เพื่อบังคับใช้กฎทางธุรกิจใหม่ทั้งหมด

Logic ภายใน:

Hybrid Logic Rule: เขียน SQL Query เพื่อค้นหาลูกค้าที่เข้าเงื่อนไขการดึงคืน (ทั้งตามเวลาและการปฏิสัมพันธ์) และทำการ UPDATE ค่า customers.CartStatus

Freezing a Lead Rule: เขียน SQL Query เพื่อค้นหาลูกค้าที่เข้าเงื่อนไขการแช่แข็ง (AssignmentCount >= 3) และทำการ UPDATE ค่า customers.CustomerTemperature เป็น 'FROZEN'

ความปลอดภัย: ไฟล์นี้จะต้องถูกป้องกันไม่ให้สามารถเรียกใช้งานผ่าน URL โดยตรงได้

ส่วนประกอบที่ต้องแก้ไข:

Lead Assignment API (api/sales/assign.php):

การเปลี่ยนแปลง: เพิ่ม Logic การ UPDATE customers SET AssignmentCount = AssignmentCount + 1 WHERE CustomerCode = ? ในส่วนที่มีการแจกจ่ายงานสำเร็จ

User Management API (api/users/toggle_status.php):

การเปลี่ยนแปลง: เพิ่ม Logic ตรวจสอบว่า Role ของผู้ใช้ที่กำลังจะ Inactive คือ 'Sales' หรือไม่ หากใช่ ให้ Trigger Sales Departure Workflow เพื่อทำการโอนย้ายรายชื่อตามกฎ 3 ข้อที่ระบุใน PRD

Dashboard API (api/dashboard/summary.php):

การเปลี่ยนแปลง: แก้ไข SQL Query เพื่อ SELECT ข้อมูลลูกค้า พร้อมทั้งคำนวณและส่งค่า time_remaining_days กลับไปใน JSON response ด้วย

2.3. สถาปัตยกรรม Frontend (UI/UX Revamp)

ส่วนประกอบที่ต้องแก้ไข:

Dashboard View (pages/dashboard.php):

การเปลี่ยนแปลง: แก้ไขโค้ดฝั่ง Client-side (JavaScript) เพื่อดึงข้อมูลจาก Dashboard API ที่แก้ไขแล้ว

Logic:

อ่านค่า time_remaining_days และ CustomerTemperature จาก API response

ใช้ JavaScript ในการสร้าง HTML ของ Progress Bar และกำหนดสี (class) ตามเงื่อนไข (เขียว/เหลือง/แดง)

ใช้ JavaScript ในการเพิ่ม class CSS สำหรับ ไฮไลท์แถว ให้กับลูกค้าที่เข้าเงื่อนไข

Dashboard CSS (assets/css/dashboard.css):

การเปลี่ยนแปลง: เพิ่ม Style Class ใหม่สำหรับ Progress Bar และการไฮไลท์แถว รวมถึงปรับแก้ Style เดิมให้สอดคล้องกับ Guideline "เรียบหรู ดูแพง" (Font, Whitespace, Colors)

3. แผนการทดสอบ (Test Strategy)
เพื่อให้มั่นใจว่าการปรับปรุงครั้งนี้มีคุณภาพและไม่ส่งผลกระทบต่อระบบเดิม:

Unit Testing: ควรมีการเขียน Test Case สำหรับฟังก์ชันที่ซับซ้อนใน cron/auto_rules.php

Integration Testing: ต้องมีการทดสอบ Cron Job กับฐานข้อมูลจำลอง เพื่อดูว่าข้อมูลถูกอัปเดตอย่างถูกต้องตามเงื่อนไข

End-to-End Testing (Manual):

ทดสอบ Workflow การดึงคืนทั้งหมด (เวลา, จำนวนครั้ง, การแช่แข็ง)

ทดสอบ Workflow กรณี Sales ลาออกอย่างละเอียดทุกกรณี

ทดสอบหน้า Dashboard ว่าแสดงผล Progress Bar และการไฮไลท์ได้ถูกต้อง

4. ข้อควรระวังในการ υλοποίηση (Implementation Notes)
Cron Job Scheduling: การตั้งเวลา Cron Job บน Server เป็นขั้นตอนทาง Infrastructure ที่ต้องทำหลังจากพัฒนา Script เสร็จสิ้น

Performance: Query ที่ใช้ใน Cron Job จะต้องมีประสิทธิภาพสูงและใช้ Index ของฐานข้อมูลอย่างเหมาะสม เพื่อป้องกันปัญหาระบบช้า

Backward Compatibility: การเปลี่ยนแปลงฐานข้อมูลต้องทำอย่างระมัดระวัง และต้องแน่ใจว่าไม่ทำให้ฟังก์ชันเดิมของระบบที่ยังไม่ได้แก้ไขเกิดข้อผิดพลาด

เอกสารฉบับนี้คือพิมพ์เขียวทางเทคนิคทั้งหมดครับ หากคุณเห็นด้วยกับแนวทางนี้ ผมจะส่งมอบเอกสารนี้ให้กับทีมพัฒนา (AI Agent) เพื่อเริ่มกระบวนการเขียนโค้ดตามแผนงานใน PRD ต่อไปครับ