Product Requirements Document (PRD): Kiro CRM
เวอร์ชัน: 2.0
วันที่ปรับปรุงล่าสุด: 26 กรกฎาคม 2568
ผู้จัดทำ: นักวิเคราะห์ (Mary) & Product Manager (John)

1. ภาพรวมและเป้าหมายโครงการ (Overview & Goals)
1.1. ปัญหาหลัก (Problem Statement)
ในปัจจุบัน องค์กรประสบปัญหาในการบริหารจัดการรายชื่อลูกค้า (Leads) สำหรับทีม Telesales ซึ่งนำไปสู่ปัญหาต่างๆ ได้แก่:

รายชื่อซ้ำซ้อน: Telesales แต่ละคนได้รับรายชื่อที่อาจซ้ำกับคนอื่น ทำให้ลูกค้าถูกติดต่อหลายครั้งและเกิดประสบการณ์ที่ไม่ดี

ข้อมูลกระจัดกระจาย: ข้อมูลการติดต่อและสถานะของลูกค้าถูกเก็บไว้ที่ Sales แต่ละคน (บน Google Sheet) ทำให้ไม่มีข้อมูลกลางในการวิเคราะห์และติดตามผล

การสูญเสียโอกาส: รายชื่อที่มีศักยภาพอาจไม่ได้รับการติดตามอย่างต่อเนื่อง (ถูกดอง) หรือไม่ถูกนำกลับมา Follow-up ในเวลาที่เหมาะสม ทำให้องค์กรสูญเสียโอกาสในการขาย

1.2. เป้าหมายหลักของโครงการ (Project Goals)
พัฒนาระบบ Kiro CRM ซึ่งเป็น Web Application เพื่อเป็นศูนย์กลาง (Centralization) ในการบริหารจัดการรายชื่อลูกค้า โดยมีเป้าหมายหลักดังนี้:

รวบรวมข้อมูล: จัดเก็บข้อมูลลูกค้า, ประวัติการติดต่อ, และสถานะทั้งหมดไว้ในฐานข้อมูลกลางที่เดียว

เพิ่มประสิทธิภาพ: สร้าง Workflow อัตโนมัติในการแจกจ่าย, ดึงคืน, และติดตามรายชื่อ เพื่อให้ Sales ทำงานกับ Lead ที่มีคุณภาพสูงสุดในเวลาที่เหมาะสม

การวัดผลและวิเคราะห์: สามารถวัดผลการทำงานของ Telesales และวิเคราะห์ข้อมูลลูกค้าเพื่อปรับปรุงกลยุทธ์การขายได้

2. ผู้ใช้งานและบทบาท (Users & Roles)
ระบบจะมีการกำหนดสิทธิ์การใช้งาน 3 ระดับ ดังนี้:

Admin:

มีสิทธิ์สูงสุดในการตั้งค่าระบบ

หน้าที่หลัก: นำเข้าข้อมูลลูกค้าใหม่ (Import Data) และ แจกจ่ายรายชื่อ (Assign Leads) ให้กับ Sales ในทีมต่างๆ

สามารถจัดการข้อมูลผู้ใช้งานทั้งหมด (เพิ่ม/ลบ/แก้ไข)

Supervisor:

ทำหน้าที่เป็น หัวหน้าทีม (Team Lead) ของ Sales

สามารถดูภาพรวมการทำงาน, รายงาน, และสถานะของลูกทีมตัวเองได้

ไม่มีสิทธิ์ในการแจกจ่ายรายชื่อข้ามทีม

มีหน้าที่ดูแลลูกค้าต่อในกรณีพิเศษ (เช่น Sales ลาออก)

Sales:

เป็นผู้ใช้งานหลักของระบบ

มีหน้าที่ติดต่อลูกค้า, บันทึกผลการโทร, อัปเดตสถานะ, และสร้างรายการสั่งซื้อ

สามารถดูได้เฉพาะรายชื่อลูกค้าที่อยู่ในความดูแลของตนเอง

3. Workflow การจัดการรายชื่อลูกค้า (Lead Management Workflow)
นี่คือ Logic การทำงานหลักของระบบ ที่ออกแบบมาเพื่อเพิ่มประสิทธิภาพการขายสูงสุด

3.1. กฎการดึงรายชื่อคืนแบบผสมผสาน (Hybrid Logic Rule)
ระบบจะดึงรายชื่อจาก Sales กลับสู่กระบวนการกลางโดยอัตโนมัติ เมื่อเข้าเงื่อนไข ข้อใดข้อหนึ่ง ดังนี้:

เงื่อนไขตามเวลา (Time-Based):

ลูกค้าใหม่: หากไม่มีการอัปเดตใดๆ (ไม่มี call_logs ใหม่) ภายใน 30 วัน นับจาก customers.AssignDate ระบบจะเปลี่ยน customers.CartStatus เป็น 'ตะกร้าแจก'

ลูกค้าติดตาม/ลูกค้าเก่า: หากไม่มีการสั่งซื้อใหม่ (ไม่มี orders ใหม่) ภายใน 3 เดือน นับจาก customers.LastContactDate ระบบจะเปลี่ยน customers.CartStatus เป็น 'ตะกร้ารอ'

เงื่อนไขตามการปฏิสัมพันธ์ (Interaction-Based):

ลูกค้าใหม่: หาก customers.ContactAttempts มีค่าถึง 3 ครั้ง แต่ customers.CustomerStatus ยังคงเป็น 'ลูกค้าใหม่' ระบบจะเปลี่ยน customers.CartStatus เป็น 'ตะกร้าแจก'

3.2. กฎการแช่แข็งรายชื่อ (Freezing a Lead Rule)
เพื่อคัดกรองรายชื่อที่ไม่มีแนวโน้มในการซื้อ:

ทุกครั้งที่ Admin แจกรายชื่อให้ Sales ระบบจะบวกค่า customers.AssignmentCount ขึ้น 1

หาก customers.AssignmentCount มีค่าถึง 3 และลูกค้ารายนั้นถูกดึงคืนอีกครั้ง ระบบจะเปลี่ยน customers.CustomerTemperature เป็น 'FROZEN' และจะไม่แสดงรายชื่อนี้ใน "ตะกร้าแจก" อีกเป็นเวลา 6 เดือน

3.3. Workflow กรณี Sales ลาออก (Sales Departure Workflow) - สำคัญ
เมื่อ Admin ทำการเปลี่ยนสถานะของ User ที่เป็น Sales เป็น Inactive:

ระบบจะทำการตรวจสอบรายชื่อลูกค้าทั้งหมดที่ Sales คนนั้นดูแลอยู่ (customers.Sales = username ของผู้ที่ลาออก) และแบ่งการจัดการออกเป็น 3 ส่วนโดยอัตโนมัติ:

รายชื่อที่มีการนัดหมาย (Active Tasks):

เงื่อนไข: ลูกค้าที่มี tasks.Status = 'รอดำเนินการ'

การดำเนินการ: เปลี่ยนเจ้าของรายชื่อ (customers.Sales) ให้เป็น Supervisor ของทีมนั้นๆ (หาได้จาก users.supervisor_id) โดยอัตโนมัติ

รายชื่อที่ติดต่อแล้วแต่ยังไม่ปิดการขาย/นัดหมาย:

เงื่อนไข: ลูกค้าที่ customers.CustomerStatus = 'ลูกค้าติดตาม' แต่ไม่มี Task ที่รอดำเนินการ

การดำเนินการ: เปลี่ยน customers.CartStatus เป็น 'ตะกร้ารอ'

รายชื่อใหม่ที่ยังไม่เคยติดต่อ:

เงื่อนไข: ลูกค้าที่ customers.CustomerStatus = 'ลูกค้าใหม่' และ customers.ContactAttempts = 0

การดำเนินการ: เปลี่ยน customers.CartStatus เป็น 'ตะกร้าแจก'

4. ข้อกำหนดด้าน UX/UI (UX/UI Requirements)
4.1. แนวคิดหลัก (Core Concept): "เรียบหรู ดูแพง แต่ใช้งานง่าย"

4.2. ชุดสี (Color Palette):

Primary: สีขาว (#FFFFFF)

Text: สีดำ/เทาเข้ม (#212529)

Accent: สีเขียวเข้มของบริษัท (Prima Passion Green)

4.3. หน้าจอหลัก (Key Screens Guideline):

Dashboard (Sales):

ต้องเป็นรูปแบบ Data Table ที่ผู้ใช้คุ้นเคย

ต้องมีคอลัมน์ "เวลาที่เหลือ" ที่แสดงผลเป็น Progress Bar เปลี่ยนสีตามความเร่งด่วน (เขียว > เหลือง > แดง)

ต้องมีการ ไฮไลท์ทั้งแถว สำหรับลูกค้าที่ CustomerTemperature = 'HOT' หรือมีเวลาเหลือน้อย เพื่อชี้นำการทำงาน

Customer Detail:

ต้องแบ่ง Layout เป็น 3 คอลัมน์ (ข้อมูลหลัก, Timeline, Action) เพื่อความเป็นระเบียบ

Sales History:

ต้องมีส่วนสรุปยอดขายรวมและจำนวนออเดอร์ของเดือนที่เลือกไว้ด้านบนสุดของตาราง

5. ข้อกำหนดเชิงฟังก์ชัน (Functional Requirements)
FR-1: ระบบต้องสามารถ Import รายชื่อลูกค้าจากไฟล์ได้

FR-2: ระบบต้องสามารถแจกจ่ายรายชื่อให้ Sales ได้โดย Admin

FR-3: ระบบต้องมี Cron Job ที่ทำงานทุกวันเพื่อตรวจสอบและบังคับใช้ Hybrid Logic Rule

FR-4: ระบบต้องมี Logic การ Freezing a Lead ตามเงื่อนไขที่กำหนด

FR-5: ระบบต้องมี Workflow อัตโนมัติสำหรับจัดการรายชื่อของ Sales ที่ลาออก ตามที่ระบุในข้อ 3.3

FR-6: Dashboard ของ Sales ต้องแสดงผลรายชื่อในรูปแบบ Data Table ที่มี Progress Bar และการไฮไลท์

6. ผลกระทบต่อฐานข้อมูล (Data Model Impact)
ตาราง customers:

เพิ่ม: ContactAttempts (INT, DEFAULT 0)

เพิ่ม: AssignmentCount (INT, DEFAULT 0)

แก้ไข: CustomerTemperature (ENUM) เพิ่มค่า 'FROZEN'

ตาราง users:

ยืนยัน: ต้องมีคอลัมน์ supervisor_id (INT, FK to users.id) เพื่อระบุหัวหน้าทีมของ Sales แต่ละคน

7. แผนการดำเนินงาน (Action Plan - Epics & Stories)
นี่คือแผนการพัฒนาทั้งหมด แบ่งเป็น Epic และ Story เพื่อให้ AI Agent สามารถทำงานต่อได้

Epic 1: Core Logic & Database Enhancement
Goal: ปรับปรุงฐานข้อมูลและพัฒนากลไกอัตโนมัติหลักของระบบ เพื่อรองรับ Workflow การจัดการ Lead แบบใหม่

Story 1.1: Alter Database Schema

As a System Admin,

I want to update the customers table structure,

so that it can support the new lead management logic.

Acceptance Criteria:

ตาราง customers ต้องมีคอลัมน์ ContactAttempts (INT, DEFAULT 0) เพิ่มเข้ามา

ตาราง customers ต้องมีคอลัมน์ AssignmentCount (INT, DEFAULT 0) เพิ่มเข้ามา

คอลัมน์ CustomerTemperature ในตาราง customers ต้องถูกแก้ไขให้รองรับค่า 'FROZEN'

ตาราง users ต้องมีคอลัมน์ supervisor_id ที่สามารถเชื่อมโยงไปยัง users.id ได้

Story 1.2: Develop Lead Management Cron Job

As a System,

I want a daily automated script to run,

so that I can enforce the Hybrid Logic and Freezing rules.

Acceptance Criteria:

สร้างไฟล์ใหม่ที่ cron/auto_rules.php

Script ต้องดึงข้อมูลลูกค้าที่เข้าเงื่อนไข Hybrid Logic Rule (ทั้งตามเวลาและการปฏิสัมพันธ์) และอัปเดต CartStatus ได้อย่างถูกต้อง

Script ต้องดึงข้อมูลลูกค้าที่เข้าเงื่อนไข Freezing a Lead Rule และอัปเดต CustomerTemperature เป็น 'FROZEN' ได้อย่างถูกต้อง

Script จะต้องทำงานได้อย่างมีประสิทธิภาพและไม่ทำให้ฐานข้อมูลทำงานหนักเกินไป

Story 1.3: Update Lead Assignment Logic

As an Admin,

I want the system to track how many times a lead has been assigned,

so that we can apply the Freezing rule correctly.

Acceptance Criteria:

แก้ไข Logic ในไฟล์ api/sales/assign.php (หรือไฟล์ที่เกี่ยวข้องกับการแจกจ่ายงาน)

ทุกครั้งที่มีการ Assign งานให้ Sales, ค่าในคอลัมน์ customers.AssignmentCount ของลูกค้ารายนั้นๆ จะต้องถูก +1

Epic 2: Sales Departure Workflow Automation
Goal: สร้างกระบวนการอัตโนมัติเพื่อจัดการรายชื่อของ Sales ที่ลาออก เพื่อให้ธุรกิจดำเนินต่อไปได้อย่างไม่สะดุด

Story 2.1: Implement Lead Re-assignment Logic

As a System,

I want to automatically re-assign leads when a sales user is deactivated,

so that no lead is left unattended.

Acceptance Criteria:

แก้ไข Logic ในหน้า pages/admin/user_management.php (หรือ API ที่เกี่ยวข้อง api/users/toggle_status.php)

เมื่อสถานะ users.Status ของ Sales ถูกเปลี่ยนเป็น Inactive (หรือ 0), ระบบจะต้อง Trigger Logic การโอนย้ายรายชื่อ

รายชื่อที่มี tasks.Status = 'รอดำเนินการ' จะต้องถูกเปลี่ยน customers.Sales ให้เป็น supervisor_id ของ Sales คนนั้น

รายชื่อที่เป็น 'ลูกค้าติดตาม' จะต้องถูกเปลี่ยน customers.CartStatus เป็น 'ตะกร้ารอ'

รายชื่อที่เป็น 'ลูกค้าใหม่' และยังไม่เคยติดต่อ จะต้องถูกเปลี่ยน customers.CartStatus เป็น 'ตะกร้าแจก'

Epic 3: Dashboard UI/UX Revamp
Goal: ปรับปรุงหน้า Dashboard ของ Sales ให้ทันสมัย ใช้งานง่าย และสามารถชี้นำการทำงานได้อย่างมีประสิทธิภาพ

Story 3.1: Enhance Dashboard API

As a Frontend Developer,

I want the dashboard API to provide "time remaining" data,

so that I can build the intelligent data table.

Acceptance Criteria:

แก้ไข API ในไฟล์ api/dashboard/summary.php (หรือไฟล์ที่เกี่ยวข้อง)

API response สำหรับลูกค้าแต่ละราย จะต้องมี field ใหม่ชื่อ time_remaining_days ซึ่งคำนวณจาก AssignDate

API response จะต้องส่งค่า CustomerTemperature มาด้วย

Story 3.2: Implement Intelligent Data Table UI

As a Salesperson,

I want to see a smart dashboard that helps me prioritize my work,

so that I can improve my sales performance.

Acceptance Criteria:

แก้ไขไฟล์ pages/dashboard.php และ assets/css/dashboard.css

UI ต้องแสดงผลเป็น Data Table ที่มีดีไซน์ "เรียบหรู ดูแพง" (ใช้ Font, Whitespace, และสีตาม Guideline)

ต้องมีคอลัมน์ "เวลาที่เหลือ" ที่แสดงผลเป็น Progress Bar ที่เปลี่ยนสีได้ (เขียว/เหลือง/แดง) ตามข้อมูล time_remaining_days จาก API

แถวของลูกค้าที่มี CustomerTemperature = 'HOT' หรือมี time_remaining_days < 5 จะต้องถูกไฮไลท์ให้โดดเด่น