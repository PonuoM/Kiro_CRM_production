# 🕐 Final CartStatus Monitoring Cron Jobs

## เพิ่ม 2 Cron Jobs ใหม่สำหรับ CartStatus Monitoring

ตาม pattern ของ cron jobs ที่มีอยู่แล้ว 7 ตัว ให้เพิ่ม 2 ตัวนี้:

---

## 🔍 **Cron Job #8: CartStatus Monitoring**
```
Minute: 15
Hour: *
Day of Month: *
Month: *
Day of Week: *
Command: php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/monitor_cartstatus.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cartstatus_monitor.log 2>&1
```

**หน้าที่:** ตรวจสอบความสอดคล้องของ CartStatus ทุกชั่วโมง (นาทีที่ 15)
**ผลลัพธ์:** บันทึกสถานะใน log ไฟล์

---

## 🔧 **Cron Job #9: CartStatus Auto-Fix** 
```
Minute: 30
Hour: 3
Day of Month: *
Month: *
Day of Week: *
Command: php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/monitor_cartstatus.php --fix >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cartstatus_autofix.log 2>&1
```

**หน้าที่:** แก้ไข CartStatus ที่ไม่สอดคล้องอัตโนมัติทุกวันเวลา 03:30
**ผลลัพธ์:** แก้ไขข้อมูลและบันทึกผลลัพธ์ใน log

---

## 📊 **สรุปตารางเวลา Cron Jobs ทั้งหมด (9 ตัว):**

| เวลา | งาน | ไฟล์ | ความถี่ |
|------|-----|------|---------|
| 01:00 | Production Daily | production_auto_system.php daily | ทุกวัน |
| 02:00 | Auto Status + Smart + Auto Rules | auto_status_manager.php, production_auto_system.php smart, auto_rules_with_activity_log.php | ทุกวัน |
| 03:00 | Production Full | production_auto_system.php all | วันอาทิตย์ |
| **03:30** | **CartStatus Auto-Fix** | **monitor_cartstatus.php --fix** | **ทุกวัน** |
| ทุก 6 ชม | Production Reassign | production_auto_system.php reassign | ทุก 6 ชม |
| 08:00-18:00/30น | Health Check | system_health_check.php | จ-ส |
| **ทุกชม:15** | **CartStatus Monitor** | **monitor_cartstatus.php** | **ทุกชั่วโมง** |

---

## 🎯 **ข้อดีของการจัดเวลานี้:**

✅ **ไม่ชนเวลา:** เลือกเวลา 03:30 และนาทีที่ 15 เพื่อหลีกเลี่ยงการทำงานพร้อมกัน
✅ **ตรวจสอบบ่อย:** ทุกชั่วโมงจะตรวจสอบปัญหา
✅ **แก้ไขเร็ว:** ทุกวันเวลา 03:30 จะแก้ไขปัญหาอัตโนมัติ
✅ **Log ครบถ้วน:** บันทึกผลลัพธ์ทุกครั้งใน logs directory
✅ **เสถียร:** ใช้ path เดียวกับระบบที่มีอยู่

---

## 📁 **Log Files ที่จะถูกสร้าง:**

- `/logs/cartstatus_monitor.log` - ผลการตรวจสอบทุกชั่วโมง
- `/logs/cartstatus_autofix.log` - ผลการแก้ไขทุกวัน

---

## 🔧 **การทดสอบ:**

```bash
# ทดสอบ monitoring
php monitor_cartstatus.php --verbose

# ทดสอบ auto-fix
php monitor_cartstatus.php --fix --verbose

# ทดสอบผ่าน web
https://www.prima49.com/crm_system/Kiro_CRM_production/monitor_cartstatus.php
```