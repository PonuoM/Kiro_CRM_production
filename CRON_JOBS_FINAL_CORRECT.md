# 🎯 Cron Jobs ที่ถูกต้องสำหรับ CRM System

## 📋 **Cron Jobs ปัจจุบันของคุณ:**

```bash
# 1. Auto Status Manager (Web-based)
0 2 * * * /usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1

# 2. Auto Rules Shell Script (เก่า)
0 1 * * * /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/run_auto_rules.sh

# 3. Daily Cleanup
0 1 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1

# 4. Smart Update
0 2 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php smart >> logs/cron_smart.log 2>&1

# 5. Auto Reassign
0 */6 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1

# 6. Full System Check (วันอาทิตย์)
0 3 * * 0 cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php all >> logs/cron_full.log 2>&1

# 7. Health Check
*/30 8-18 * * 1-6 cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php system_health_check.php >> logs/health_check.log 2>&1

# 8. Auto Rules Fixed (ตัวเก่าที่มีปัญหา)
0 2 * * * /usr/bin/php /path/to/cron/auto_rules_fixed.php
```

## ✅ **แก้ไขให้ถูกต้อง:**

### **เปลี่ยนรายการที่ 8:**
```bash
# ลบอันเก่า:
0 2 * * * /usr/bin/php /path/to/cron/auto_rules_fixed.php

# เปลี่ยนเป็น:
0 2 * * * /usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1
```

### **ลบรายการที่ 2 (ไม่จำเป็น):**
```bash
# ลบอันนี้ออก:
0 1 * * * /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/run_auto_rules.sh
```

## 🎯 **Cron Jobs สุดท้ายที่ถูกต้อง:**

```bash
# 1. Daily Cleanup (01:00)
0 1 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1

# 2. Auto Status Manager (02:00)
0 2 * * * /usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1

# 3. Auto Rules with Activity Logging (02:00) - หลัก
0 2 * * * /usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1

# 4. Smart Update (02:00)
0 2 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php smart >> logs/cron_smart.log 2>&1

# 5. Auto Reassign (ทุก 6 ชั่วโมง)
0 */6 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1

# 6. Full System Check (วันอาทิตย์ 03:00)
0 3 * * 0 cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php all >> logs/cron_full.log 2>&1

# 7. Health Check (เวลาทำงาน)
*/30 8-18 * * 1-6 cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php system_health_check.php >> logs/health_check.log 2>&1
```

## ⚠️ **สำคัญ:**

1. **ลบ** run_auto_rules.sh (รายการที่ 2)
2. **เปลี่ยน** auto_rules_fixed.php เป็น auto_rules_with_activity_log.php (รายการที่ 8)
3. **รัน 3 ตัวพร้อมกันที่ 02:00**: Auto Status Manager + Auto Rules + Smart Update

## 🔍 **การทำงานเวลา 02:00:**
- **Auto Status Manager**: จัดการสถานะพื้นฐาน
- **Auto Rules with Activity Log**: ประมวลผลลูกค้าตามกฎ + บันทึก Activity Log
- **Smart Update**: อัปเดต Temperature และ Grade

ระบบจะทำงานตามลำดับ และบันทึก Activity Log ทุกการเปลี่ยนแปลง!