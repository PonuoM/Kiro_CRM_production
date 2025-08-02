Product Requirements Document (PRD): Kiro CRM
เวอร์ชัน: 3.0 (ฉบับสมบูรณ์)
วันที่ปรับปรุง: 27 กรกฎาคม 2568
ผู้จัดทำ: นักวิเคราะห์ (Mary) & Product Manager (John)

1. ภาพรวมและเป้าหมายโครงการ
1.1. ปัญหาหลัก
องค์กรประสบปัญหาในการบริหารจัดการรายชื่อลูกค้า (Leads) สำหรับทีม Telesales ซึ่งนำไปสู่การทำงานที่ซ้ำซ้อน, การสูญเสียโอกาสในการขาย, และขาดข้อมูลกลางในการวิเคราะห์และวัดผล

1.2. เป้าหมายหลักของโครงการ
พัฒนาระบบ Kiro CRM ให้เป็นศูนย์กลางในการบริหารจัดการรายชื่อลูกค้า เพื่อเพิ่มประสิทธิภาพของทีม Telesales, สร้าง Workflow การทำงานที่เป็นอัตโนมัติ, และสามารถนำข้อมูลมาวิเคราะห์เพื่อปรับปรุงกลยุทธ์การขายได้

2. ผู้ใช้งานและบทบาท (Users & Roles)
Admin:

หน้าที่หลัก: นำเข้าข้อมูลลูกค้าใหม่ (Import Data) และ แจกจ่ายรายชื่อ (Assign Leads) ให้กับ Sales ในทีมต่างๆ

สิทธิ์: จัดการข้อมูลผู้ใช้งานทั้งหมด (เพิ่ม/ลบ/แก้ไขสถานะ) และตั้งค่าระบบ

Supervisor:

หน้าที่หลัก: เป็น หัวหน้าทีม (Team Lead) ของ Sales ดูแลภาพรวมการทำงานและรายงานของลูกทีม

สิทธิ์: รับผิดชอบดูแลรายชื่อต่อจาก Sales ที่ลาออกในกรณีที่มีการนัดหมายไว้

Sales:

หน้าที่หลัก: ติดต่อลูกค้า, บันทึกผล, อัปเดตสถานะ, และสร้างรายการสั่งซื้อ

สิทธิ์: ดูและจัดการได้เฉพาะรายชื่อลูกค้าที่อยู่ในความรับผิดชอบของตนเอง

3. Workflow การจัดการรายชื่อลูกค้า
3.1. กฎการดึงรายชื่อคืนแบบผสมผสาน (Hybrid Logic Rule)
ระบบจะดึงรายชื่อจาก Sales กลับสู่กระบวนการกลางโดยอัตโนมัติ เมื่อเข้าเงื่อนไข ข้อใดข้อหนึ่ง ดังนี้:

เงื่อนไขตามเวลา:

ลูกค้าใหม่: หากไม่มีการอัปเดตสถานะใดๆ ภายใน 30 วัน นับจาก customers.AssignDate ระบบจะเปลี่ยน customers.CartStatus เป็น 'ตะกร้าแจก'

ลูกค้าติดตาม/ลูกค้าเก่า: หากไม่มีการสั่งซื้อใหม่ภายใน 3 เดือน (90 วัน) นับจาก customers.LastContactDate ระบบจะเปลี่ยน customers.CartStatus เป็น 'ตะกร้ารอ'

เงื่อนไขตามการปฏิสัมพันธ์:

ลูกค้าใหม่: หาก customers.ContactAttempts มีค่าถึง 3 ครั้ง แต่ customers.CustomerStatus ยังคงเป็น 'ลูกค้าใหม่' ระบบจะเปลี่ยน customers.CartStatus เป็น 'ตะกร้าแจก'

3.2. กฎการแช่แข็งรายชื่อ (Freezing a Lead Rule)

ทุกครั้งที่ Admin แจกรายชื่อให้ Sales ระบบจะบวกค่า customers.AssignmentCount ขึ้น 1

หาก customers.AssignmentCount มีค่าถึง 3 และลูกค้ารายนั้นถูกดึงคืนอีกครั้ง ระบบจะเปลี่ยน customers.CustomerTemperature เป็น 'FROZEN' และจะไม่แสดงรายชื่อนี้ใน "ตะกร้าแจก" อีกเป็นเวลา 6 เดือน

3.3. Workflow กรณี Sales ลาออก (Sales Departure Workflow)
เมื่อ Admin เปลี่ยนสถานะของ Sales เป็น Inactive:

1.รายชื่อที่มีการนัดหมาย: โอนย้ายให้ Supervisor ของทีมดูแลต่อ (customers.Sales = supervisor_id)

2.รายชื่อที่ติดต่อแล้ว: ย้ายเข้า 'ตะกร้ารอ' (customers.CartStatus = 'ตะกร้ารอ')

3.รายชื่อใหม่ (ยังไม่เคยติดต่อ): ย้ายกลับเข้า 'ตะกร้าแจก' (customers.CartStatus = 'ตะกร้าแจก')

4. ข้อกำหนดด้าน UX/UI
แนวคิดหลัก: "เรียบหรู ดูแพง แต่ใช้งานง่าย"

ชุดสี: ขาว-ดำ-เขียวเข้ม

Dashboard (Sales):

ต้องเป็น Data Table อัจฉริยะ

ต้องมีคอลัมน์ "เวลาที่เหลือ" ที่แสดงผลเป็น Progress Bar เปลี่ยนสีตามความเร่งด่วน (เขียว > เหลือง > แดง)

ต้องมีการ ไฮไลท์ทั้งแถว สำหรับลูกค้าที่ CustomerTemperature = 'HOT' หรือมีเวลาเหลือน้อย

Customer Detail:

ต้องปรับ Layout เป็น 3 คอลัมน์ (ข้อมูลหลัก, Timeline, Action)

5. แผนการดำเนินงาน (Action Plan - Epics & Stories)
Epic 1: Core Logic Enhancement & Bug Fix
Goal: แก้ไขข้อผิดพลาดของ Cron Job เดิม และ υλοποίηση Logic การจัดการ Lead อัตโนมัติที่สมบูรณ์

Story 1.1: Alter Database Schema

As a System Admin, I want to update the customers table structure, so that it can support the new lead management logic.

Acceptance Criteria:

1.ตาราง customers ต้องมีคอลัมน์ ContactAttempts (INT, DEFAULT 0)

2.ตาราง customers ต้องมีคอลัมน์ AssignmentCount (INT, DEFAULT 0)

3.คอลัมน์ CustomerTemperature ในตาราง customers ต้องถูกแก้ไขให้รองรับค่า 'FROZEN'

4.ยืนยันว่าตาราง users มีคอลัมน์ supervisor_id ที่เชื่อมโยงไปยัง users.id ได้

Story 1.2: Fix & Enhance Lead Management Cron Job

As a System, I want a daily automated script to run correctly, so that I can enforce all business rules accurately.

Acceptance Criteria:

1.แก้ไขไฟล์ cron/auto_rules.php

2.Script ต้องแก้ไข Query ให้สามารถค้นหาลูกค้าที่ เลยกำหนดเวลา (Overdue) ได้อย่างถูกต้อง และทำการดึงคืนได้ทันที

3.Script ต้อง υλοποίηση Hybrid Logic Rule (เงื่อนไขเวลา + การปฏิสัมพันธ์)

4.Script ต้อง υλοποίηση Freezing a Lead Rule

Story 1.3: Update Lead Assignment Logic

As an Admin, I want the system to track how many times a lead has been assigned, so that we can apply the Freezing rule correctly.

Acceptance Criteria:

1.แก้ไข Logic ในไฟล์ api/sales/assign.php (หรือไฟล์ที่เกี่ยวข้อง)

2.ทุกครั้งที่มีการ Assign งานให้ Sales, ค่าในคอลัมน์ customers.AssignmentCount ของลูกค้ารายนั้นๆ จะต้องถูก +1

Epic 2: Sales Departure Workflow Automation
Goal: สร้างกระบวนการอัตโนมัติเพื่อจัดการรายชื่อของ Sales ที่ลาออก เพื่อให้ธุรกิจดำเนินต่อไปได้อย่างไม่สะดุด

Story 2.1: Implement Lead Re-assignment Logic on User Deactivation

As a System, I want to automatically re-assign leads when a sales user is deactivated, so that no lead is left unattended.

Acceptance Criteria:

1.แก้ไข Logic ใน api/users/toggle_status.php (หรือไฟล์ที่เกี่ยวข้อง)

2.เมื่อสถานะ users.Status ของ Sales ถูกเปลี่ยนเป็น Inactive, ระบบจะต้อง Trigger Logic การโอนย้ายรายชื่อ

3.รายชื่อที่มี tasks.Status = 'รอดำเนินการ' จะต้องถูกเปลี่ยน customers.Sales ให้เป็น supervisor_id ของ Sales คนนั้น

4.รายชื่อที่เป็น 'ลูกค้าติดตาม' จะต้องถูกเปลี่ยน customers.CartStatus เป็น 'ตะกร้ารอ'

5.รายชื่อที่เป็น 'ลูกค้าใหม่' และยังไม่เคยติดต่อ จะต้องถูกเปลี่ยน customers.CartStatus เป็น 'ตะกร้าแจก'

Epic 3: Dashboard UI/UX Revamp
Goal: ปรับปรุงหน้า Dashboard ของ Sales ให้ทันสมัย ใช้งานง่าย และสามารถชี้นำการทำงานได้อย่างมีประสิทธิภาพ

Story 3.1: Enhance Dashboard API

As a Frontend Developer, I want the dashboard API to provide "time remaining" data, so that I can build the intelligent data table.

Acceptance Criteria:

1.แก้ไข API ในไฟล์ api/dashboard/summary.php

2.API response สำหรับลูกค้าแต่ละราย จะต้องมี field ใหม่ชื่อ time_remaining_days

3.API response จะต้องส่งค่า CustomerTemperature มาด้วย

Story 3.2: Implement Intelligent Data Table UI

As a Salesperson, I want to see a smart dashboard that helps me prioritize my work, so that I can improve my sales performance.

Acceptance Criteria:

1.แก้ไขไฟล์ pages/dashboard.php และ assets/css/dashboard.css

2.UI ต้องแสดงผลเป็น Data Table ที่มีดีไซน์ "เรียบหรู ดูแพง"

3.ต้องมีคอลัมน์ "เวลาที่เหลือ" ที่แสดงผลเป็น Progress Bar ที่เปลี่ยนสีได้ (เขียว/เหลือง/แดง)

4.แถวของลูกค้าที่มี CustomerTemperature = 'HOT' หรือมี time_remaining_days < 5 จะต้องถูกไฮไลท์ให้โดดเด่น