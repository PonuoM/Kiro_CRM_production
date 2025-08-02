# 🔧 Cronjobs ที่แก้ไขแล้ว - ใช้ Path ที่ถูกต้อง

## ✅ **Cronjobs ที่แก้ไขแล้ว**

ตาม sample commands ของ hosting ให้ใช้ path และคำสั่งดังนี้:

### 1. **Daily Cleanup - เวลา 01:00 AM**
```bash
0 1 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php daily >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_daily.log 2>&1
```

### 2. **Smart Update - เวลา 02:00 AM**
```bash
0 2 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php smart >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_smart.log 2>&1
```

### 3. **Auto-reassign - ทุก 6 ชั่วโมง**
```bash
0 */6 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php reassign >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_reassign.log 2>&1
```

### 4. **Full System Check - อาทิตย์ เวลา 03:00 AM**
```bash
0 3 * * 0 php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php all >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_full.log 2>&1
```

### 5. **Health Check - ทุก 30 นาที (เวลาทำงาน 8:00-18:00)**
```bash
*/30 8-18 * * 1-6 php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/system_health_check.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/health_check.log 2>&1
```

### 6. **Auto Rules with Activity Log - เวลา 02:00 AM**
```bash
0 2 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1
```

### 7. **Auto Status Manager - เวลา 02:00 AM (Web Request Method)**
```bash
0 2 * * * /usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1
```

## 🔍 **ความแตกต่างจาก Cronjobs เดิม**

### ❌ **Cronjobs เดิม (ปัญหา)**:
```bash
# ใช้ cd && php (อาจมีปัญหา working directory)
cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1
```

### ✅ **Cronjobs ใหม่ (แก้ไขแล้ว)**:
```bash
# ใช้ full path ทั้งหมด ไม่ต้องพึ่ง cd
php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php daily >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_daily.log 2>&1
```

### 🎯 **ข้อดีของ Path ใหม่**:
1. **ไม่ต้องใช้ `cd`** - หลีกเลี่ยงปัญหา working directory
2. **Full absolute path** - ชัดเจนไม่มีความกำกวม
3. **ตาม hosting standard** - ใช้ path ที่ hosting แนะนำ
4. **ลด race condition** - ไม่มีปัญหาการทำงานพร้อมกัน

## 🚀 **วิธีการนำไปใช้**

### ขั้นตอนที่ 1: ลบ Cronjobs เดิม
```bash
# SSH เข้า server แล้วรัน
crontab -e
# ลบ cronjobs เดิมทั้งหมดที่เกี่ยวข้อง
```

### ขั้นตอนที่ 2: เพิ่ม Cronjobs ใหม่
```bash
# Copy cronjobs ข้างบนไปใส่ใน crontab
# หรือใช้ cPanel → Cron Jobs
```

### ขั้นตอนที่ 3: ทดสอบทันที
```bash
# SSH เข้า server แล้วทดสอบ manual
php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php daily

# ตรวจสอบว่าสร้างไฟล์ log ได้หรือไม่
ls -la /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/
```

## 📋 **All-in-One Cronjobs (Copy & Paste)**

```bash
# Kiro CRM Auto System - Production (Updated 29/07/2025)
# Using correct hosting path structure

# Daily cleanup - 01:00 AM
0 1 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php daily >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_daily.log 2>&1

# Smart update - 02:00 AM  
0 2 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php smart >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_smart.log 2>&1

# Auto-reassign - Every 6 hours
0 */6 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php reassign >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_reassign.log 2>&1

# Full system check - Sunday 03:00 AM
0 3 * * 0 php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/production_auto_system.php all >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_full.log 2>&1

# Health check - Every 30 minutes (business hours)
*/30 8-18 * * 1-6 php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/system_health_check.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/health_check.log 2>&1

# Auto rules with activity log - 02:00 AM
0 2 * * * php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1

# Auto status manager (web request) - 02:00 AM
0 2 * * * /usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1
```

## ⚡ **Quick Test Commands**

```bash
# ทดสอบทันทีหลังติดตั้ง cronjobs
php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/test_cron_setup.php

# ตรวจสอบผลลัพธ์
cat /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cron_test.log

# ดู cronjobs ปัจจุบัน
crontab -l
```

**หมายเหตุ**: Path `/home/primacom/domains/prima49.com/public_html/` เป็น path มาตรฐานของ hosting และสามารถเข้าถึงได้ปกติแน่นอนครับ!