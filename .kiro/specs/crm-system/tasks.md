# แผนการดำเนินงาน

- [x] 1. ตั้งค่าโครงสร้างโปรเจกต์และฐานข้อมูล





  - สร้างโครงสร้างไดเรกทอรีตามการออกแบบ
  - สร้างไฟล์ config สำหรับการเชื่อมต่อฐานข้อมูล
  - สร้าง SQL schema สำหรับตารางทั้งหมด
  - _Requirements: 1.3, 2.2, 2.6_

- [x] 2. พัฒนาระบบ Authentication และ User Management




  - [x] 2.1 สร้าง database connection และ base classes


    - เขียนไฟล์ config/database.php สำหรับการเชื่อมต่อฐานข้อมูล
    - สร้าง base class สำหรับ database operations
    - เขียน utility functions ใน includes/functions.php
    - _Requirements: 1.1, 1.2_

  - [x] 2.2 พัฒนาระบบ login และ session management


    - สร้าง API endpoint สำหรับ login (api/auth/login.php)
    - พัฒนา session management และ role checking
    - สร้างหน้า login.php พร้อม form validation
    - เขียน unit tests สำหรับ authentication functions
    - _Requirements: 1.1, 1.2, 1.4, 1.5, 1.6_



  - [x] 2.3 สร้างระบบจัดการผู้ใช้งาน

    - พัฒนา API สำหรับ CRUD operations ของผู้ใช้
    - สร้างหน้า user management สำหรับ Admin
    - เขียน validation สำหรับข้อมูลผู้ใช้
    - _Requirements: 1.3_

- [x] 3. พัฒนาระบบจัดการลูกค้า




  - [x] 3.1 สร้าง Customer Model และ basic CRUD operations


    - เขียน Customer class พร้อม validation methods
    - สร้าง API endpoints สำหรับ customer CRUD
    - พัฒนาระบบสร้าง CustomerCode อัตโนมัติ
    - เขียน unit tests สำหรับ Customer model
    - _Requirements: 2.2, 2.3, 2.5_



  - [x] 3.2 พัฒนาระบบนำเข้าข้อมูล CSV

    - สร้าง import_customers.php สำหรับประมวลผล CSV
    - เขียน validation สำหรับข้อมูล CSV
    - พัฒนาระบบตรวจสอบเบอร์โทรซ้ำและอัปเดตข้อมูล
    - สร้าง error reporting สำหรับการนำเข้าข้อมูล
    - เขียน integration tests สำหรับ CSV import
    - _Requirements: 2.1, 2.4, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_

  - [x] 3.3 สร้างหน้าแสดงรายชื่อลูกค้าและการค้นหา


    - พัฒนา API สำหรับ list customers พร้อม filtering
    - สร้างหน้า customer list พร้อมระบบค้นหา
    - เพิ่มฟังก์ชัน pagination สำหรับข้อมูลจำนวนมาก
    - _Requirements: 7.3, 7.4_

- [x] 4. พัฒนาระบบบันทึกการโทรและการสื่อสาร




  - [x] 4.1 สร้าง Call Log Model และ API


    - เขียน CallLog class พร้อม validation
    - สร้าง API endpoint สำหรับบันทึกการโทร (api/calls/log.php)
    - พัฒนาระบบจัดการสถานะการโทรและการคุย
    - เขียน unit tests สำหรับ call logging functions
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 4.2 สร้างหน้าแสดงประวัติการโทร


    - พัฒนา API สำหรับดึงประวัติการโทร
    - สร้าง UI สำหรับแสดงประวัติการโทรในหน้า customer detail
    - เพิ่มฟังก์ชันกรองประวัติตามวันที่
    - _Requirements: 3.6_

- [x] 5. พัฒนาระบบจัดการคำสั่งซื้อ




  - [x] 5.1 สร้าง Order Model และ API


    - เขียน Order class พร้อม validation methods
    - สร้าง API endpoint สำหรับสร้างคำสั่งซื้อ (api/orders/create.php)
    - พัฒนาระบบสร้าง DocumentNo อัตโนมัติ
    - เขียน logic สำหรับอัปเดต CustomerStatus เป็น "ลูกค้าเก่า"
    - เขียน unit tests สำหรับ order management
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 5.2 สร้างระบบติดตามประวัติการขาย


    - พัฒนา SalesHistory management
    - สร้าง API สำหรับดึงประวัติคำสั่งซื้อ
    - เพิ่มฟังก์ชันแสดงประวัติในหน้า customer detail
    - _Requirements: 4.5, 4.6, 9.1, 9.3_

- [x] 6. พัฒนาระบบจัดการงานและการติดตาม




  - [x] 6.1 สร้าง Task Model และ API


    - เขียน Task class พร้อม validation
    - สร้าง API endpoints สำหรับ task CRUD operations
    - พัฒนาระบบกรองงานตามวันที่
    - เขียน unit tests สำหรับ task management
    - _Requirements: 5.1, 5.3, 5.6_



  - [x] 6.2 สร้างระบบแสดงงานประจำวัน





    - พัฒนา API สำหรับดึงงานวันนี้
    - สร้าง UI สำหรับแสดงงานในแท็บ "DO"
    - เพิ่มฟังก์ชันอัปเดตสถานะงาน
    - _Requirements: 5.2, 5.4, 5.5_

- [x] 7. พัฒนา Dashboard และ User Interface





  - [x] 7.1 สร้างโครงสร้าง Dashboard หลัก


    - สร้างหน้า dashboard.php พร้อมระบบ tab navigation
    - พัฒนา JavaScript สำหรับการเปลี่ยนแท็บ
    - สร้าง CSS สำหรับ responsive design
    - _Requirements: 7.1, 7.6_

  - [x] 7.2 พัฒนาแท็บต่างๆ ใน Dashboard


    - สร้างแท็บ "DO (นัดหมายวันนี้)" พร้อม API
    - สร้างแท็บ "ลูกค้าใหม่", "ลูกค้าติดตาม", "ลูกค้าเก่า"
    - สร้างแท็บ "Follow (ดูนัดหมายทั้งหมด)"
    - เขียน integration tests สำหรับ dashboard functionality
    - _Requirements: 7.2, 7.3_

  - [x] 7.3 สร้างหน้า Customer Detail


    - พัฒนาหน้า customer_detail.php แบบครอบคลุม
    - เพิ่มส่วนแสดงประวัติคำสั่งซื้อ
    - เพิ่มส่วนแสดงประวัติ Sales ที่เคยดูแล
    - สร้างฟอร์มสำหรับบันทึกการโทรใหม่
    - สร้างฟอร์มสำหรับสร้างนัดหมายใหม่
    - _Requirements: 7.5_

- [x] 8. พัฒนาระบบ Sales Assignment และ History




  - [x] 8.1 สร้างระบบมอบหมายลูกค้า


    - พัฒนา API สำหรับมอบหมายลูกค้าให้ Sales
    - สร้างระบบบันทึกใน sales_histories table
    - เขียน logic สำหรับจบการมอบหมายเก่าและสร้างใหม่
    - _Requirements: 9.1, 9.2_

  - [x] 8.2 สร้างระบบติดตามประสิทธิภาพ Sales


    - พัฒนา API สำหรับ sales performance metrics
    - สร้างหน้ารายงานสำหรับ Supervisor
    - เพิ่มฟังก์ชันแสดงประวัติ Sales ในหน้า customer detail
    - _Requirements: 9.4, 9.5, 9.6_

- [x] 9. พัฒนาระบบกฎอัตโนมัติ (Auto Rules)




  - [x] 9.1 สร้าง Auto Rules Engine


    - เขียนสคริปต์ cron/auto_rules.php
    - พัฒนา logic สำหรับตรวจสอบลูกค้าใหม่ที่ไม่มีการอัปเดต 30 วัน
    - พัฒนา logic สำหรับตรวจสอบลูกค้าติดตามที่ไม่สั่งซื้อ 3 เดือน
    - พัฒนา logic สำหรับตรวจสอบลูกค้าเก่าที่ไม่ซื้อซ้ำ 3 เดือน
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 9.2 สร้างระบบอัปเดตสถานะอัตโนมัติ


    - เขียน functions สำหรับอัปเดต CartStatus
    - พัฒนาระบบ audit trail สำหรับการเปลี่ยนแปลงอัตโนมัติ
    - เพิ่ม error handling และ logging สำหรับ cron job
    - เขียน unit tests สำหรับ auto rules logic
    - _Requirements: 6.4, 6.5_

- [x] 10. การทดสอบและ Quality Assurance




  - [x] 10.1 ทดสอบ Integration ทั้งระบบ


    - ทดสอบ user workflows ทั้งหมด
    - ทดสอบการทำงานร่วมกันของ API endpoints
    - ทดสอบการนำเข้าข้อมูล CSV ขนาดใหญ่
    - ทดสอบ cron job และ auto rules

  - [x] 10.2 ทดสอบ Performance และ Security


    - ทดสอบความเร็วของการค้นหาลูกค้า
    - ทดสอบ SQL injection และ XSS protection
    - ทดสอบ authentication และ authorization
    - ทดสอบการทำงานบนข้อมูลจำนวนมาก

- [x] 11. การเตรียมสำหรับ Production




  - [x] 11.1 สร้างไฟล์ SQL สำหรับ Production


    - รวบรวม SQL schema ทั้งหมดในไฟล์เดียว
    - สร้าง sample data สำหรับการทดสอบ
    - เขียนคำแนะนำการติดตั้งใน README

  - [x] 11.2 เตรียมการ Deploy บน DirectAdmin


    - ปรับแต่งไฟล์ config สำหรับ production environment
    - สร้างคำแนะนำการตั้งค่า cron job
    - ทดสอบการ deploy บน staging environment
    - สร้าง backup และ restore procedures