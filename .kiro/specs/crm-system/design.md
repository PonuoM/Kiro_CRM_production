# เอกสารการออกแบบระบบ CRM

## ภาพรวม

ระบบ CRM นี้ถูกออกแบบเป็นเว็บแอปพลิเคชันที่ใช้ PHP, HTML, CSS, JavaScript สำหรับ Frontend และ MariaDB (MySQL) สำหรับฐานข้อมูล ระบบจะทำงานบน XAMPP สำหรับการพัฒนาในเครื่อง และ DirectAdmin สำหรับการใช้งานจริง

ระบบมีจุดเด่นหลักคือการจัดการวงจรชีวิตลูกค้าอัตโนมัติผ่าน "ตะกร้าแจก" และ "ตะกร้ารอ" ตามเงื่อนไขเวลา 30 วัน และ 3 เดือน

## สถาปัตยกรรม

### สถาปัตยกรรมระดับสูง

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend API   │    │   Database      │
│   (HTML/CSS/JS) │◄──►│   (PHP)         │◄──►│   (MariaDB)     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   Cron Jobs     │
                       │   (auto_rules)  │
                       └─────────────────┘
```

### โครงสร้างไดเรกทอรี

```
/crm-system/
├── config/
│   ├── database.php
│   └── config.php
├── api/
│   ├── auth/
│   │   ├── login.php
│   │   └── logout.php
│   ├── customers/
│   │   ├── list.php
│   │   ├── detail.php
│   │   ├── create.php
│   │   ├── update.php
│   │   └── import.php
│   ├── calls/
│   │   ├── log.php
│   │   └── history.php
│   ├── orders/
│   │   ├── create.php
│   │   └── history.php
│   └── tasks/
│       ├── create.php
│       ├── list.php
│       └── update.php
├── pages/
│   ├── login.php
│   ├── dashboard.php
│   ├── customer_detail.php
│   └── admin/
│       └── user_management.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── cron/
│   └── auto_rules.php
└── sql/
    └── database_schema.sql
```

## คอมโพเนนต์และอินเทอร์เฟซ

### 1. Authentication Module

**วัตถุประสงค์:** จัดการการเข้าสู่ระบบและการยืนยันตัวตน

**คอมโพเนนต์:**
- `AuthController`: จัดการ login/logout
- `SessionManager`: จัดการ session และสิทธิ์การเข้าถึง
- `RoleManager`: ตรวจสอบบทบาทผู้ใช้ (Admin, Supervisor, Sale)

**API Endpoints:**
- `POST /api/auth/login.php` - เข้าสู่ระบบ
- `POST /api/auth/logout.php` - ออกจากระบบ
- `GET /api/auth/check.php` - ตรวจสอบสถานะการเข้าสู่ระบบ

### 2. Customer Management Module

**วัตถุประสงค์:** จัดการข้อมูลลูกค้าและการนำเข้าข้อมูล

**คอมโพเนนต์:**
- `CustomerController`: CRUD operations สำหรับลูกค้า
- `ImportController`: จัดการการนำเข้าข้อมูลจาก CSV
- `CustomerValidator`: ตรวจสอบความถูกต้องของข้อมูล

**API Endpoints:**
- `GET /api/customers/list.php` - รายชื่อลูกค้า (รองรับการกรอง)
- `GET /api/customers/detail.php?id={CustomerCode}` - รายละเอียดลูกค้า
- `POST /api/customers/create.php` - สร้างลูกค้าใหม่
- `PUT /api/customers/update.php` - อัปเดตข้อมูลลูกค้า
- `POST /api/customers/import.php` - นำเข้าข้อมูลจาก CSV

### 3. Call Logging Module

**วัตถุประสงค์:** บันทึกและติดตามการสื่อสารกับลูกค้า

**คอมโพเนนต์:**
- `CallLogController`: จัดการบันทึกการโทร
- `CallStatusManager`: จัดการสถานะการโทรและการคุย

**API Endpoints:**
- `POST /api/calls/log.php` - บันทึกการโทร
- `GET /api/calls/history.php?customer={CustomerCode}` - ประวัติการโทร

### 4. Order Management Module

**วัตถุประสงค์:** จัดการคำสั่งซื้อและประวัติการขาย

**คอมโพเนนต์:**
- `OrderController`: จัดการคำสั่งซื้อ
- `SalesHistoryManager`: ติดตามประวัติการขาย

**API Endpoints:**
- `POST /api/orders/create.php` - สร้างคำสั่งซื้อ
- `GET /api/orders/history.php?customer={CustomerCode}` - ประวัติคำสั่งซื้อ

### 5. Task Management Module

**วัตถุประสงค์:** จัดการงานและการติดตาม

**คอมโพเนนต์:**
- `TaskController`: CRUD operations สำหรับงาน
- `TaskScheduler`: จัดการตารางงาน

**API Endpoints:**
- `POST /api/tasks/create.php` - สร้างงานใหม่
- `GET /api/tasks/list.php` - รายการงาน (รองรับการกรองตามวันที่)
- `PUT /api/tasks/update.php` - อัปเดตงาน

### 6. Dashboard Module

**วัตถุประสงค์:** แสดงข้อมูลสรุปและการนำทาง

**คอมโพเนนต์:**
- `DashboardController`: จัดการข้อมูลแดชบอร์ด
- `TabManager`: จัดการแท็บต่างๆ

**API Endpoints:**
- `GET /api/dashboard/summary.php` - ข้อมูลสรุป
- `GET /api/dashboard/tasks_today.php` - งานวันนี้
- `GET /api/dashboard/customers_by_status.php` - ลูกค้าตามสถานะ

### 7. Automation Module

**วัตถุประสงค์:** จัดการกฎอัตโนมัติสำหรับวงจรชีวิตลูกค้า

**คอมโพเนนต์:**
- `AutoRulesEngine`: ประมวลผลกฎอัตโนมัติ
- `CustomerLifecycleManager`: จัดการการเปลี่ยนสถานะลูกค้า

**Cron Job:**
- `auto_rules.php` - รันทุกวันเพื่อตรวจสอบและอัปเดตสถานะลูกค้า

## โมเดลข้อมูล

### ตาราง users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Username NVARCHAR(50) UNIQUE NOT NULL,
    Password NVARCHAR(255) NOT NULL,
    FirstName NVARCHAR(200) NOT NULL,
    LastName NVARCHAR(200) NOT NULL,
    Email NVARCHAR(200),
    Phone NVARCHAR(200),
    CompanyCode NVARCHAR(10),
    Position NVARCHAR(200),
    Role ENUM('Admin', 'Supervisor', 'Sale') NOT NULL,
    LastLoginDate DATETIME,
    Status INT DEFAULT 1,
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
    ModifiedBy NVARCHAR(50)
) COLLATE=utf8mb4_unicode_ci;
```

### ตาราง customers
```sql
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) UNIQUE NOT NULL,
    CustomerName NVARCHAR(500) NOT NULL,
    CustomerTel NVARCHAR(200) UNIQUE NOT NULL,
    CustomerAddress NVARCHAR(500),
    CustomerProvince NVARCHAR(200),
    CustomerPostalCode NVARCHAR(50),
    Agriculture NVARCHAR(200),
    CustomerStatus ENUM('ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า') DEFAULT 'ลูกค้าใหม่',
    CartStatus ENUM('ตะกร้าแจก', 'ตะกร้ารอ', 'กำลังดูแล') DEFAULT 'กำลังดูแล',
    Sales NVARCHAR(50),
    AssignDate DATETIME,
    OrderDate DATETIME,
    CallStatus NVARCHAR(50),
    TalkStatus NVARCHAR(50),
    Tags NVARCHAR(500),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
    ModifiedBy NVARCHAR(50),
    INDEX idx_customer_tel (CustomerTel),
    INDEX idx_customer_status (CustomerStatus),
    INDEX idx_cart_status (CartStatus),
    INDEX idx_sales (Sales)
) COLLATE=utf8mb4_unicode_ci;
```

### ตาราง call_logs
```sql
CREATE TABLE call_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) NOT NULL,
    CallDate DATETIME NOT NULL,
    CallTime NVARCHAR(50),
    CallMinutes NVARCHAR(50),
    CallStatus ENUM('ติดต่อได้', 'ติดต่อไม่ได้') NOT NULL,
    CallReason NVARCHAR(500),
    TalkStatus ENUM('คุยจบ', 'คุยไม่จบ'),
    TalkReason NVARCHAR(500),
    Remarks NVARCHAR(500),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE,
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_call_date (CallDate)
) COLLATE=utf8mb4_unicode_ci;
```

### ตาราง tasks
```sql
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) NOT NULL,
    FollowupDate DATETIME NOT NULL,
    Remarks NVARCHAR(500),
    Status ENUM('รอดำเนินการ', 'เสร็จสิ้น') DEFAULT 'รอดำเนินการ',
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE,
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_followup_date (FollowupDate),
    INDEX idx_status (Status)
) COLLATE=utf8mb4_unicode_ci;
```

### ตาราง orders
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    DocumentNo NVARCHAR(50) UNIQUE NOT NULL,
    CustomerCode NVARCHAR(50) NOT NULL,
    DocumentDate DATETIME NOT NULL,
    PaymentMethod NVARCHAR(200),
    Products NVARCHAR(500),
    Quantity DECIMAL(10,2),
    Price DECIMAL(10,2),
    OrderBy NVARCHAR(50),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE,
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_document_date (DocumentDate)
) COLLATE=utf8mb4_unicode_ci;
```

### ตาราง sales_histories
```sql
CREATE TABLE sales_histories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    CustomerCode NVARCHAR(50) NOT NULL,
    SaleName NVARCHAR(50) NOT NULL,
    StartDate DATETIME NOT NULL,
    EndDate DATETIME,
    AssignBy NVARCHAR(50),
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    CreatedBy NVARCHAR(50),
    FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE,
    INDEX idx_customer_code (CustomerCode),
    INDEX idx_sale_name (SaleName),
    INDEX idx_date_range (StartDate, EndDate)
) COLLATE=utf8mb4_unicode_ci;
```

## การจัดการข้อผิดพลาด

### ระดับ Application
- **Input Validation**: ตรวจสอบข้อมูลนำเข้าทุกครั้งก่อนประมวลผล
- **SQL Injection Prevention**: ใช้ Prepared Statements
- **XSS Protection**: Sanitize output ทุกครั้ง
- **CSRF Protection**: ใช้ tokens สำหรับฟอร์มที่สำคัญ

### ระดับ Database
- **Transaction Management**: ใช้ transactions สำหรับการดำเนินการที่ซับซ้อน
- **Constraint Handling**: จัดการ foreign key constraints และ unique constraints
- **Connection Pooling**: จัดการการเชื่อมต่อฐานข้อมูลอย่างมีประสิทธิภาพ

### Error Logging
```php
// ตัวอย่างการจัดการ error
try {
    // Database operations
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ'];
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    return ['success' => false, 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];
}
```

## กลยุทธ์การทดสอบ

### Unit Testing
- ทดสอบฟังก์ชันแต่ละตัวแยกเป็นอิสระ
- ทดสอบ validation functions
- ทดสอบ business logic

### Integration Testing
- ทดสอบการทำงานร่วมกันของ API endpoints
- ทดสอบการเชื่อมต่อฐานข้อมูล
- ทดสอบ authentication flow

### System Testing
- ทดสอบ user workflows ทั้งหมด
- ทดสอบ cron jobs
- ทดสอบการนำเข้าข้อมูล CSV

### Performance Testing
- ทดสอบการโหลดข้อมูลลูกค้าจำนวนมาก
- ทดสอบความเร็วของการค้นหา
- ทดสอบการทำงานของ auto rules

## การปรับใช้งาน (Deployment)

### XAMPP (Development)
1. วางไฟล์ในโฟลเดอร์ `htdocs/crm-system/`
2. สร้างฐานข้อมูล `prima49_crm` ใน phpMyAdmin
3. รัน SQL schema
4. ตั้งค่าไฟล์ `config/database.php`

### DirectAdmin (Production)
1. อัปโหลดไฟล์ผ่าน File Manager
2. สร้างฐานข้อมูลผ่าน MySQL Management
3. Import SQL schema
4. แก้ไขไฟล์ config สำหรับ production
5. ตั้งค่า Cron Job สำหรับ `auto_rules.php`

### Security Considerations
- ใช้ HTTPS ในการใช้งานจริง
- ตั้งค่า file permissions อย่างเหมาะสม
- ซ่อนไฟล์ config จาก web access
- ใช้ strong passwords สำหรับฐานข้อมูล
- Regular backup ฐานข้อมูล

## การปรับขนาด (Scalability)

### Database Optimization
- สร้าง indexes ที่เหมาะสม
- ใช้ query optimization
- พิจารณา database partitioning สำหรับข้อมูลจำนวนมาก

### Caching Strategy
- ใช้ session caching สำหรับข้อมูลผู้ใช้
- Cache ข้อมูลที่ไม่เปลี่ยนแปลงบ่อย
- ใช้ browser caching สำหรับ static assets

### Monitoring
- ติดตาม database performance
- ติดตาม server resources
- Log การใช้งานสำหรับการวิเคราะห์