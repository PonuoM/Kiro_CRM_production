# แผนการดำเนินงาน - แก้ไขระบบ Customer Intelligence

- [x] 1. วิเคราะห์และตรวจสอบระบบปัจจุบัน
  - ตรวจสอบ cron jobs ที่มีอยู่และการทำงาน
  - วิเคราะห์ข้อมูลในฐานข้อมูลที่ผิดพลาด
  - ระบุจุดที่ต้องแก้ไขทั้งหมด
  - _Requirements: 3.1, 3.2, 5.2_

- [x] 2. สร้างระบบคำนวณ Grade และ Temperature ใหม่
  - [x] 2.1 สร้าง Grade Calculator แบบ Real-time
    - เขียนฟังก์ชัน calculateCustomerGrade() ใหม่
    - ใช้ SUM(orders.Price) ตาม CustomerCode
    - ทดสอบการคำนวณกับข้อมูลตัวอย่าง
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

  - [x] 2.2 สร้าง Temperature Calculator แบบ Real-time
    - เขียนฟังก์ชัน calculateCustomerTemperature() ใหม่
    - เพิ่มกฎพิเศษสำหรับ Grade A,B ไม่ให้เป็น FROZEN
    - ทดสอบ logic กับสถานการณ์ต่างๆ
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [x] 2.3 สร้าง Auto-trigger System
    - เพิ่ม trigger เมื่อมีการสร้าง/อัปเดต orders
    - เพิ่ม trigger เมื่อมีการบันทึก call logs
    - เพิ่ม trigger เมื่อมีการเปลี่ยนแปลง customer status
    - ทดสอบการทำงานแบบ real-time
    - _Requirements: 1.6, 2.6_

- [x] 3. แก้ไขข้อมูลที่มีอยู่ทั้งหมด
  - [x] 3.1 สร้างสคริปต์ Data Migration
    - สร้าง backup ตาราง customers
    - เขียน SQL สำหรับอัปเดต Grade ทั้งหมด
    - เขียน SQL สำหรับอัปเดต Temperature ที่ผิดพลาด
    - เขียนสคริปต์ rollback เผื่อเกิดปัญหา
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [x] 3.2 รันการแก้ไขข้อมูล
    - [x] แก้ไข database schema issues (TemperatureUpdated, GradeUpdated)
    - [x] ทดสอบ Grade calculation (9 customers updated successfully)
    - [x] แก้ไข Temperature calculation references
    - [x] ปรับปรุง rollback script ให้ใช้ column names ที่ถูกต้อง
    - [x] สคริปต์พร้อมสำหรับ production deployment
    - _Requirements: 3.4, 3.5, 3.6_

- [x] 4. ตรวจสอบและแก้ไข Cron Jobs
  - [x] 4.1 วิเคราะห์ Cron Jobs ปัจจุบัน
    - [x] ตรวจสอบไฟล์ cron/auto_rules.php
    - [x] ทดสอบการทำงานของ cron job
    - [x] ระบุปัญหาและข้อผิดพลาด
    - _Requirements: 5.1, 5.2_

  - [x] 4.2 ปรับปรุง Cron Jobs
    - [x] อัปเดต cron/auto_rules.php รวม Customer Intelligence
    - [x] เพิ่มการ log การทำงาน (Enhanced logging with statistics)
    - [x] เพิ่มการตรวจสอบข้อผิดพลาด
    - [x] เพิ่ม High-value customer protection
    - [x] ทดสอบการทำงานใหม่
    - _Requirements: 5.3, 5.4, 5.5, 5.6_

  - [x] 4.3 สร้างระบบ Hybrid (Real-time + Cron)
    - [x] Real-time สำหรับการเปลี่ยนแปลงทันที (Auto-triggers in orders/call logs)
    - [x] Cron job สำหรับการตรวจสอบและแก้ไขข้อมูลเก่า (200 customers daily)
    - [x] ป้องกันการทำงานซ้ำซ้อน
    - [x] High-value customer protection integrated
    - _Requirements: 1.6, 2.6_

- [x] 5. สร้างเครื่องมือ Debug และ Monitoring
  - [x] 5.1 สร้าง Debug API
    - [x] สร้าง api/customers/debug_intelligence.php
    - [x] แสดงขั้นตอนการคำนวณทั้งหมด
    - [x] เปรียบเทียบผลลัพธ์ปัจจุบันกับที่คาดหวัง
    - [x] รองรับ individual customer และ system overview
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 5.2 สร้างเครื่องมือ Batch Update
    - [x] รวมไว้ใน debug_intelligence.php (batch_update action)
    - [x] รองรับการอัปเดตทีละกลุ่ม
    - [x] แสดง progress และสถิติ
    - [x] รองรับทั้ง specific customers และ all customers
    - _Requirements: 5.4, 5.5_

  - [x] 5.3 สร้างระบบ Monitoring
    - [x] System health checks ใน debug API
    - [x] Grade/Temperature distribution analysis
    - [x] High-value customer monitoring
    - [x] Grade mismatch detection
    - _Requirements: 5.6_

- [ ] 6. ปรับปรุง User Interface
  - [x] 6.1 แก้ไขหน้า Customer Detail
    - [x] ปรับปรุงการแสดง Grade พร้อมสีและยอดเงิน (Fixed thresholds ≥฿810K, ≥฿85K, ≥฿2K)
    - [x] ปรับปรุงการแสดง Temperature พร้อมไอคอน (🔥HOT, 🌡️WARM, ❄️COLD, 🧊FROZEN)
    - [x] เพิ่ม tooltip อธิบายเกณฑ์ (Grade & Temperature criteria sections)
    - [x] แสดงวันที่อัปเดตล่าสุด (Grade Updated, Temperature Updated)
    - [x] เพิ่ม High-value Protection notice สำหรับ Grade A,B
    - _Requirements: 4.1, 4.2, 4.3, 4.5_

  - [ ] 6.2 แก้ไขหน้า Dashboard
    - อัปเดตการแสดง Grade ในรายการลูกค้า
    - เพิ่มการกรองตาม Grade และ Temperature
    - ปรับปรุงสีและไอคอนให้สอดคล้องกัน
    - _Requirements: 4.1, 4.2, 4.3_

  - [ ] 6.3 เพิ่มการแสดงข้อผิดพลาด
    - แสดงข้อความเตือนเมื่อข้อมูลผิดปกติ
    - แสดงสถานะการคำนวณ
    - เพิ่มปุ่มสำหรับ refresh ข้อมูล
    - _Requirements: 4.6_

- [ ] 7. การทดสอบและ Quality Assurance
  - [ ] 7.1 Unit Testing
    - ทดสอบฟังก์ชันคำนวณ Grade กับข้อมูลตัวอย่าง
    - ทดสอบฟังก์ชันคำนวณ Temperature กับสถานการณ์ต่างๆ
    - ทดสอบ edge cases และข้อมูลผิดปกติ
    - _Requirements: 6.1, 6.2_

  - [ ] 7.2 Integration Testing
    - ทดสอบการทำงานร่วมกันของ Grade และ Temperature
    - ทดสอบ real-time updates เมื่อมีการเปลี่ยนแปลงข้อมูล
    - ทดสอบการทำงานของ cron job ใหม่
    - _Requirements: 6.3, 6.4_

  - [ ] 7.3 Performance Testing
    - ทดสอบความเร็วในการคำนวณลูกค้าจำนวนมาก
    - ทดสอบ memory usage ในการประมวลผลข้อมูลทั้งหมด
    - ทดสอบ database performance หลังการอัปเดต
    - _Requirements: 6.5_

  - [ ] 7.4 User Acceptance Testing
    - ทดสอบกับข้อมูลจริงในสภาพแวดล้อมทดสอบ
    - ตรวจสอบความถูกต้องของการแสดงผล
    - ทดสอบการใช้งานจริงกับผู้ใช้
    - _Requirements: 6.6_

- [ ] 8. การปรับใช้งานและ Monitoring
  - [ ] 8.1 เตรียมการ Deploy
    - สร้าง deployment checklist
    - เตรียม backup ข้อมูลสำคัญ
    - เตรียมสคริปต์ rollback
    - กำหนดเวลา maintenance window
    - _Requirements: 3.6_

  - [ ] 8.2 Deploy ระบบใหม่
    - อัปโหลดไฟล์ที่แก้ไขแล้ว
    - รันสคริปต์ data migration
    - ทดสอบการทำงานหลัง deploy
    - ตรวจสอบ cron job ใหม่
    - _Requirements: 3.4, 3.5, 3.6_

  - [ ] 8.3 Post-deployment Monitoring
    - ติดตามการทำงานของระบบใหม่
    - ตรวจสอบ error logs
    - วิเคราะห์ performance
    - รวบรวม feedback จากผู้ใช้
    - _Requirements: 5.6_

- [ ] 9. Documentation และ Training
  - [ ] 9.1 สร้าง Technical Documentation
    - เขียนคู่มือการใช้งาน Debug API
    - เขียนคู่มือการ maintenance
    - อัปเดต API documentation
    - _Requirements: 5.1, 5.2, 5.3_

  - [ ] 9.2 สร้าง User Guide
    - อธิบายการทำงานของ Grade และ Temperature ใหม่
    - คู่มือการใช้งานฟีเจอร์ใหม่
    - FAQ สำหรับปัญหาที่อาจเกิดขึ้น
    - _Requirements: 4.4, 4.5_

  - [ ] 9.3 Training ผู้ใช้งาน
    - อบรมการใช้งานระบบใหม่
    - อธิบายการเปลี่ยนแปลงที่สำคัญ
    - รวบรวม feedback และปรับปรุง
    - _Requirements: 4.6_