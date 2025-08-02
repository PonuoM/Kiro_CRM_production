# 🔍 วิธีตรวจสอบ Cronjobs บน Hosting Server

## สาเหตุที่ Cronjobs อาจไม่ทำงาน

### 1. **ตรวจสอบ Cronjobs ที่ติดตั้งแล้ว**
```bash
# SSH เข้า hosting server แล้วรัน
crontab -l

# หรือผ่าน cPanel → Cron Jobs
# ดูว่ามี cronjobs ติดตั้งหรือไม่
```

### 2. **ตรวจสอบ PHP Path บน Hosting**
```bash
# ตรวจสอบ PHP CLI path
which php
php --version

# บาง hosting อาจใช้
/usr/bin/php
/usr/local/bin/php
/opt/php/bin/php
```

### 3. **ตรวจสอบสิทธิ์ไฟล์**
```bash
# ตรวจสอบสิทธิ์ไฟล์ PHP
ls -la production_auto_system.php

# ตรวจสอบสิทธิ์โฟลเดอร์ logs
ls -la logs/
chmod 755 logs/
```

## การ Debug Cronjobs บน Production

### 1. **ตรวจสอบ Cron Logs**
```bash
# ดู system cron logs (ถ้ามีสิทธิ์)
tail -f /var/log/cron
tail -f /var/log/syslog | grep CRON

# ดู error logs ของ hosting
tail -f ~/public_html/error_log
```

### 2. **ทดสอบรัน Manual**
```bash
# ทดสอบรันไฟล์ PHP แบบ manual
cd ~/public_html/your-project
php production_auto_system.php daily

# หรือแบบเต็ม path
/usr/bin/php /home/username/public_html/project/production_auto_system.php daily
```

### 3. **ตรวจสอบ Log Files**
```bash
# ดู log files ล่าสุด
ls -lt logs/
tail -f logs/cron_*.log
cat logs/cron_errors.log
```

## วิธีแก้ไขปัญหาที่พบบ่อย

### 1. **PHP Path ไม่ถูกต้อง**
- แก้ไข cronjob command:
```bash
# แทนที่
php /path/to/script.php

# เป็น
/usr/bin/php /full/path/to/script.php
```

### 2. **Database Connection Error**
- ตรวจสอบ config/database.php
- ตรวจสอบว่า MySQL service ทำงานหรือไม่
- ตรวจสอบ database credentials

### 3. **Permission Issues**
```bash
# กำหนดสิทธิ์
chmod 644 *.php
chmod 755 cron/
chmod 755 logs/
chown username:username logs/
```

### 4. **Memory/Time Limits**
- เพิ่มใน PHP script:
```php
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
```

## การตรวจสอบสถานะ Cronjobs

### 1. **สร้างไฟล์ทดสอบ**
```php
<?php
// test_cron.php
$log = date('Y-m-d H:i:s') . " - Cron is working!\n";
file_put_contents('logs/cron_test.log', $log, FILE_APPEND);
?>
```

### 2. **เพิ่ม Cronjob ทดสอบ**
```bash
# รันทุกนาที
* * * * * /usr/bin/php /full/path/to/test_cron.php
```

### 3. **ตรวจสอบผลลัพธ์**
```bash
tail -f logs/cron_test.log
```

## ขั้นตอนการ Debug แบบละเอียด

### 1. **เช็ค Hosting Environment**
```bash
# ดู PHP version และ modules
php -v
php -m

# ดู environment variables
env | grep -i path
```

### 2. **ตรวจสอบ Current Working Directory**
```php
<?php
// debug_cron.php
echo "Current directory: " . getcwd() . "\n";
echo "Script location: " . __DIR__ . "\n";
echo "PHP version: " . phpversion() . "\n";
?>
```

### 3. **ตรวจสอบ Database Connection**
```php
<?php
// test_db_connection.php
try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Database connected successfully\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
```

## วิธีติดตั้ง Cronjobs บน Hosting

### 1. **ผ่าน cPanel**
1. เข้า cPanel → Cron Jobs
2. เลือก timing ที่ต้องการ
3. ใส่ command: `/usr/bin/php /home/username/public_html/project/production_auto_system.php daily`

### 2. **ผ่าน SSH**
```bash
# เปิด crontab editor
crontab -e

# เพิ่ม cronjobs
0 1 * * * /usr/bin/php /full/path/to/production_auto_system.php daily >> /full/path/to/logs/cron_daily.log 2>&1
0 2 * * * /usr/bin/php /full/path/to/production_auto_system.php smart >> /full/path/to/logs/cron_smart.log 2>&1
```

## การ Monitor Cronjobs

### 1. **สร้าง Health Check**
```php
<?php
// cron_health_check.php
$logFile = 'logs/cron_success.log';
$lastRun = 0;

if (file_exists($logFile)) {
    $lastRun = filemtime($logFile);
}

$hoursSinceLastRun = (time() - $lastRun) / 3600;

if ($hoursSinceLastRun > 25) { // ไม่เกิน 1 วัน
    // ส่งแจ้งเตือน
    echo "WARNING: Cron hasn't run for " . round($hoursSinceLastRun) . " hours\n";
}
?>
```

### 2. **Email Notifications**
```bash
# เพิ่มใน crontab
MAILTO=admin@yourdomain.com
0 1 * * * /usr/bin/php /path/to/script.php
```

## Tips สำหรับ Production

1. **ใช้ Full Path เสมอ** - `/usr/bin/php` แทน `php`
2. **Log ทุก Output** - เพิ่ม `>> logfile.log 2>&1`
3. **Test Manual ก่อน** - รัน manual ให้ทำงานก่อน
4. **Set Timeout** - เพิ่ม timeout ในสคริปต์
5. **Monitor Regularly** - ตรวจสอบ logs เป็นประจำ

## คำสั่งที่ใช้บ่อย

```bash
# ดู cronjobs ปัจจุบัน
crontab -l

# แก้ไข cronjobs
crontab -e

# ลบ cronjobs ทั้งหมด
crontab -r

# ทดสอบ PHP script
php -l script.php

# ดู log แบบ real-time
tail -f logs/cron_daily.log

# ค้นหา errors ใน logs
grep -i error logs/*.log
```