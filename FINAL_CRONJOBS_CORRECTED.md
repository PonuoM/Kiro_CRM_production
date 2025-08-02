# 🎯 Cronjobs ฉบับสุดท้าย - ใช้ /usr/bin/php

## ✅ **Cronjobs ที่ถูกต้อง 100%**

ตามคำสั่งที่คุณแสดง ให้ใช้ `/usr/bin/php` กับ full path ทั้งหมด:

### **All Cronjobs - Ready to Use:**

```bash
# Daily Cleanup - 01:00 AM
0 1 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php daily >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_daily.log 2>&1

# Smart Update - 02:00 AM  
0 2 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php smart >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_smart.log 2>&1

# Auto-reassign - Every 6 hours
0 */6 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php reassign >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_reassign.log 2>&1

# Full System Check - Sunday 03:00 AM
0 3 * * 0 /usr/bin/php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php all >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_full.log 2>&1

# Health Check - Every 30 minutes (business hours)
*/30 8-18 * * 1-6 /usr/bin/php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/system_health_check.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/health_check.log 2>&1

# Auto Rules with Activity Log - 02:00 AM
0 2 * * * /usr/bin/php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1

# Auto Status Manager (Web Request Method) - 02:00 AM
0 2 * * * /usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1
```

## 🔍 **ทดสอบคำสั่งทันที:**

```bash
# SSH เข้า server แล้วทดสอบ
/usr/bin/php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php all

# ตรวจสอบว่าสร้างไฟล์ log ได้หรือไม่
ls -la /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/

# ดูเนื้อหาไฟล์ log ที่สร้างขึ้น
tail -f /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_full.log
```

## 📊 **ตารางเวลาการ Cronjobs:**

| เวลา | Command | ไฟล์ Log | วัตถุประสงค์ |
|------|---------|----------|-------------|
| 01:00 | daily | cron_daily.log | ทำความสะอาดข้อมูลประจำวัน |
| 02:00 | smart | cron_smart.log | อัพเดต Temperature/Grade |
| 02:00 | auto_rules | cron_auto_rules_activity.log | จัดสรรลูกค้าอัตโนมัติ |
| 02:00 | curl status | /dev/null | Auto Status Manager |
| 03:00 (วันอาทิตย์) | all | cron_full.log | ตรวจสอบระบบครบถ้วน |
| ทุก 6 ชม. | reassign | cron_reassign.log | จัดสรรลูกค้าใหม่ |
| ทุก 30 นาที (8-18 น.) | health_check | health_check.log | ตรวจสุขภาพระบบ |

## 🎯 **สิ่งที่ควรเกิดขึ้นหลังติดตั้ง:**

1. **ไฟล์ Log ต่อไปนี้จะถูกสร้างขึ้น:**
   - `cron_daily.log`
   - `cron_smart.log`
   - `cron_reassign.log`
   - `cron_full.log` (วันอาทิตย์)
   - `health_check.log`
   - `cron_auto_rules_activity.log`

2. **ระบบจะทำงานอัตโนมัติ:**
   - ลูกค้าใหม่เลย 30 วัน → กลับตะกร้า
   - ลูกค้าติดตาม 15-30 วัน → กลับตะกร้า
   - ลูกค้าติดตาม/เก่า ไม่มี Orders 90+ วัน → ตะกร้ารอ
   - อัพเดต Temperature และ Grade อัตโนมัติ

## 🚨 **ถ้ายังไม่ทำงาน ให้ตรวจสอบ:**

```bash
# 1. ตรวจสอบ PHP CLI
/usr/bin/php -v

# 2. ตรวจสอบสิทธิ์ไฟล์
ls -la /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php

# 3. ตรวจสอบสิทธิ์โฟลเดอร์ logs
ls -la /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/

# 4. ตรวจสอบ syntax PHP
/usr/bin/php -l /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php

# 5. ดู system cron logs (ถ้ามีสิทธิ์)
tail -f /var/log/cron
```

## 📝 **หมายเหตุสำคัญ:**

- **เวลาทั้งหมดเป็น Server Time** (GMT+7 สำหรับไทย)
- **ไฟล์ log จะถูกสร้างอัตโนมัติ** เมื่อ cronjobs ทำงาน
- **การทดสอบควรรอ 1-2 วัน** เพื่อดูผลลัพธ์จริง
- **ข้อมูลทดสอบใน CRON_TEST_DATA.sql** พร้อมใช้สำหรับวันพรุ่งนี้

คำสั่งที่คุณแสดงถูกต้องแล้วครับ! ลองใช้ cronjobs ชุดนี้ดู น่าจะแก้ปัญหาได้แล้ว 🎉