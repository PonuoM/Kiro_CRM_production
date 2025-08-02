# CartStatus Monitoring Cron Jobs

## เพิ่ม Cron Jobs สำหรับ CartStatus Monitoring

ตาม pattern ของ cron jobs ที่มีอยู่แล้ว ให้เพิ่ม 2 cron jobs นี้:

### 1. CartStatus Monitoring (ทุกชั่วโมง)
```
Minute: 15
Hour: *
Day of Month: *
Month: *
Day of Week: *
Command: php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/monitor_cartstatus.php >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cartstatus_monitor.log 2>&1
```

### 2. CartStatus Auto-Fix (ทุกวันเวลา 03:30)
```
Minute: 30
Hour: 3
Day of Month: *
Month: *
Day of Week: *
Command: php /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/monitor_cartstatus.php --fix >> /home/primacom/domains/prima49.com/public_html/crm_system/Kiro_CRM_production/logs/cartstatus_autofix.log 2>&1
```

## สรุปเวลาที่ใช้แล้ว:
- 01:00 - production_auto_system.php daily
- 02:00 - auto_status_manager.php และ production_auto_system.php smart และ auto_rules_with_activity_log.php
- 03:00 - production_auto_system.php all (วันอาทิตย์)
- 03:30 - **CartStatus Auto-Fix (ใหม่)**
- 08:00-18:00 ทุก 30 นาที - health_check.php (วันจันทร์-เสาร์)
- ทุก 6 ชั่วโมง - production_auto_system.php reassign
- ทุกชั่วโมงนาทีที่ 15 - **CartStatus Monitor (ใหม่)**

## หมายเหตุ:
- เลือกเวลา 03:30 สำหรับ auto-fix เพื่อไม่ชนกับ cron jobs อื่นๆ
- เลือกนาทีที่ 15 ทุกชั่วโมงสำหรับ monitoring เพื่อไม่ชนกับงานอื่น
- ใช้ path เดียวกับ cron jobs ที่มีอยู่แล้ว
- Log ไปยัง logs directory เหมือนระบบเดิม