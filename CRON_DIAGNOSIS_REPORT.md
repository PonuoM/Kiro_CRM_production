# 🚨 Cronjobs Diagnosis Report

## 📊 การวิเคราะห์ Cronjobs ที่ติดตั้งบน Server

### Cronjobs ที่พบ:
```bash
0    2    *    *    *    /usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1
0    1    *    *    *    cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1
0    2    *    *    *    cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php smart >> logs/cron_smart.log 2>&1
0    */6  *    *    *    cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1
0    3    *    *    0    cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php all >> logs/cron_full.log 2>&1
*/30 8-18 *    *    1-6  cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php system_health_check.php >> logs/health_check.log 2>&1
0    2    *    *    *    /usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1
```

## ❌ ปัญหาที่พบ

### 1. **ไฟล์ Log ไม่ถูกสร้าง**
ไฟล์ log ต่อไปนี้ไม่มีใน logs/ directory:
- `cron_daily.log`
- `cron_smart.log` 
- `cron_reassign.log`
- `cron_full.log`
- `health_check.log`
- `cron_auto_rules_activity.log`

### 2. **สาเหตุที่เป็นไปได้**

#### A) **PHP Path ไม่ถูกต้อง**
- Cronjobs ใช้ `php` แทน `/usr/bin/php`
- Server อาจไม่รู้จัค `php` command

#### B) **Working Directory ปัญหา**
- `cd` command อาจไม่ทำงาน
- Relative path `logs/` อาจไม่ถูกต้อง

#### C) **File Permissions**
- ไม่มีสิทธิ์สร้างไฟล์ในโฟลเดอร์ logs/
- PHP script ไม่สามารถเขียนไฟล์ได้

#### D) **PHP Script Error**
- Script มี error ทำให้ไม่ทำงาน
- Database connection failed

## 🔍 วิธีการ Debug

### ขั้นตอนที่ 1: ตรวจสอบ PHP Path
```bash
# SSH เข้า server แล้วทดสอบ
which php
/usr/bin/php --version

# ทดสอบรัน script manual
cd /home/primacom/public_html/crm_system/Kiro_CRM_production
php production_auto_system.php daily
```

### ขั้นตอนที่ 2: ตรวจสอบ Permissions
```bash
# เช็คสิทธิ์โฟลเดอร์ logs
ls -la logs/
chmod 755 logs/
chmod 644 logs/*.log (ถ้ามี)

# เช็คเจ้าของไฟล์
whoami
ls -la production_auto_system.php
```

### ขั้นตอนที่ 3: ทดสอบการสร้างไฟล์
```bash
# ทดสอบสร้างไฟล์ใน logs
touch logs/test.log
echo "test" >> logs/test.log
cat logs/test.log
```

### ขั้นตอนที่ 4: ดู System Cron Logs
```bash
# ดู cron logs ของระบบ (ถ้ามีสิทธิ์)
tail -f /var/log/cron
grep CRON /var/log/syslog
```

## 🛠️ วิธีแก้ไข

### แก้ไขที่ 1: ใช้ Full PHP Path
แก้ไข cronjobs จาก:
```bash
cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1
```

เป็น:
```bash
cd /home/primacom/public_html/crm_system/Kiro_CRM_production && /usr/bin/php production_auto_system.php daily >> logs/cron_daily.log 2>&1
```

### แก้ไขที่ 2: ใช้ Absolute Path สำหรับ Logs
```bash
/usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/production_auto_system.php daily >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_daily.log 2>&1
```

### แก้ไขที่ 3: เพิ่ม Error Handling ใน Script
เพิ่มใน `production_auto_system.php`:
```php
// เพิ่มที่ต้นไฟล์
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ทดสอบการเขียนไฟล์
$testLog = __DIR__ . '/logs/cron_test.log';
if (!file_put_contents($testLog, date('Y-m-d H:i:s') . " - Script started\n", FILE_APPEND)) {
    die("Cannot write to logs directory");
}
```

## 📝 สคริปต์ทดสอบ

### สร้างไฟล์ `test_cron_setup.php`:
```php
<?php
// test_cron_setup.php
echo "=== Cron Environment Test ===\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "Current directory: " . getcwd() . "\n";
echo "Script directory: " . __DIR__ . "\n";
echo "PHP version: " . phpversion() . "\n";
echo "User: " . get_current_user() . "\n";

// ทดสอบการเขียนไฟล์
$logDir = __DIR__ . '/logs';
$testFile = $logDir . '/cron_test.log';

echo "Log directory: $logDir\n";
echo "Log directory exists: " . (is_dir($logDir) ? 'YES' : 'NO') . "\n";
echo "Log directory writable: " . (is_writable($logDir) ? 'YES' : 'NO') . "\n";

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
    echo "Created logs directory\n";
}

$testMessage = date('Y-m-d H:i:s') . " - Cron test successful\n";
if (file_put_contents($testFile, $testMessage, FILE_APPEND)) {
    echo "Successfully wrote to: $testFile\n";
} else {
    echo "FAILED to write to: $testFile\n";
}

// ทดสอบ database connection
echo "\n=== Database Test ===\n";
try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        include __DIR__ . '/config/database.php';
        $pdo = new PDO($dsn, $username, $password, $options);
        echo "Database connection: SUCCESS\n";
    } else {
        echo "Database config not found\n";
    }
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
```

### เพิ่ม Cronjob ทดสอบ:
```bash
# รันทุกนาทีเพื่อทดสอบ
* * * * * /usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/test_cron_setup.php >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_debug.log 2>&1
```

## 🎯 ขั้นตอนการแก้ไขที่แนะนำ

### 1. ทดสอบ Manual ก่อน
```bash
ssh เข้า server
cd /home/primacom/public_html/crm_system/Kiro_CRM_production
/usr/bin/php test_cron_setup.php
```

### 2. ตรวจสอบผลลัพธ์
```bash
cat logs/cron_debug.log
ls -la logs/
```

### 3. แก้ไข Cronjobs
- ลบ cronjobs เก่าทั้งหมด
- เพิ่ม cronjobs ใหม่ด้วย full path
- ใช้ absolute path สำหรับ logs

### 4. Monitor ผลลัพธ์
```bash
tail -f logs/cron_*.log
```

## 🚨 Emergency Fixes

หาก cronjobs ยังไม่ทำงาน ให้ลองวิธีนี้:

### 1. สร้าง Wrapper Script
```bash
#!/bin/bash
# cron_wrapper.sh
cd /home/primacom/public_html/crm_system/Kiro_CRM_production
/usr/bin/php production_auto_system.php $1 >> logs/cron_$1.log 2>&1
```

### 2. ใช้ Wrapper ใน Cronjobs
```bash
0 1 * * * /home/primacom/public_html/crm_system/Kiro_CRM_production/cron_wrapper.sh daily
```

### 3. Fallback ด้วย Web Request
```bash
0 1 * * * /usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/production_auto_system.php?mode=daily" > /dev/null 2>&1
```