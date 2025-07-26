# รายการตรวจสอบก่อนติดตั้ง Production

## ✅ ไฟล์ที่เตรียมแล้ว

- [x] สร้างโฟลเดอร์ production แล้ว
- [x] ลบไฟล์พัฒนาออกแล้ว (tests/, *.md)
- [x] คัดลอกไฟล์ config production แล้ว
- [x] สร้างไฟล์ .htaccess แล้ว
- [x] สร้างไฟล์ index.php แล้ว
- [x] สร้างไฟล์ health_check.php แล้ว
- [x] เตรียมสคริปต์ cron และ backup แล้ว
- [x] สร้างไฟล์บีบอัด production แล้ว

## 📋 สิ่งที่ต้องทำหลังอัปโหลด

### 1. การตั้งค่าฐานข้อมูล
```
ชื่อฐานข้อมูล: primacom_CRM
ชื่อผู้ใช้: primacom_bloguser
รหัสผ่าน: pJnL53Wkhju2LaGPytw8
```

### 2. ✅ แก้ไขไฟล์ config/database.php (เรียบร้อยแล้ว)
```php
'dbname' => 'primacom_CRM',
'username' => 'primacom_bloguser', 
'password' => 'pJnL53Wkhju2LaGPytw8',
```

### 3. แก้ไขไฟล์ config/config.php
```php
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('LOG_PATH', '/home/username/public_html/logs/');
define('UPLOAD_PATH', '/home/username/public_html/uploads/');
define('BACKUP_PATH', '/home/username/public_html/backups/');
```

### 4. สร้างโฟลเดอร์จำเป็น
```bash
mkdir -p logs uploads backups
chmod 755 logs uploads backups
```

### 5. นำเข้าฐานข้อมูล
- อัปโหลดไฟล์: sql/production_setup.sql
- ใช้ phpMyAdmin นำเข้าข้อมูล

### 6. ตั้งค่า Cron Jobs
```
# Auto rules (ทุกวันเวลา 02:00)
0 2 * * * /home/username/public_html/crm-system/cron/run_auto_rules.sh

# Backup (ทุกวันเวลา 01:00)  
0 1 * * * /home/username/public_html/crm-system/scripts/backup.sh
```

### 7. แก้ไข paths ในสคริปต์
- cron/run_auto_rules.sh
- scripts/backup.sh
อัปเดต username และ paths ให้ถูกต้อง

### 8. เปิดใช้ SSL และ Force HTTPS
- ใน DirectAdmin ติดตั้ง SSL Certificate
- เปิด comment ใน .htaccess สำหรับ force HTTPS

## 🔐 การตั้งค่าความปลอดภัย

### ข้อมูลเข้าสู่ระบบเริ่มต้น
```
Username: admin
Password: admin123
```
**⚠️ เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบครั้งแรก**

### ตรวจสอบสิทธิ์ไฟล์
```bash
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 config/*.php
```

## 🔍 การทดสอบหลังติดตั้ง

1. เข้าถึง: https://yourdomain.com/crm-system/
2. ทดสอบการเข้าสู่ระบบ
3. ตรวจสอบ health check: https://yourdomain.com/crm-system/health_check.php
4. ทดสอบการนำเข้าข้อมูล CSV
5. ตรวจสอบ logs ใน /logs/

## 📊 การติดตาม

### ไฟล์ Log ที่ต้องติดตาม
- /logs/application.log
- /logs/php_errors.log  
- /logs/cron_success.log
- /logs/cron_errors.log

### การบำรุงรักษา
- รายวัน: ตรวจสอบ error logs
- รายสัปดาห์: ตรวจสอบการสำรองข้อมูล
- รายเดือน: ล้างไฟล์ log เก่า

## 📞 การสนับสนุน

หากมีปัญหา:
1. ตรวจสอบไฟล์ log
2. ทดสอบ health check endpoint
3. ตรวจสอบการตั้งค่าฐานข้อมูล
4. ติดต่อผู้ให้บริการโฮสติ้งสำหรับปัญหาเซิร์ฟเวอร์