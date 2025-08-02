# เอกสารการออกแบบ - แก้ไขระบบ Customer Intelligence

## ภาพรวม

การออกแบบนี้มุ่งเน้นการแก้ไขปัญหาการคำนวณ Customer Grade และ Temperature ที่ไม่ถูกต้อง โดยจะปรับปรุงอัลกอริทึมการคำนวณ, แก้ไขข้อมูลที่มีอยู่, และเพิ่มเครื่องมือ debug เพื่อป้องกันปัญหาในอนาคต

## สถาปัตยกรรม

### การไหลของข้อมูล (Data Flow)

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Orders Table  │    │  Grade Calculator│    │  Customer Table │
│   (Price field) │───►│   (New Logic)   │───►│ (Grade/Temp)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   UI Display    │
                       │   (Updated)     │
                       └─────────────────┘
```

### โครงสร้างไฟล์ที่จะแก้ไข

```
/crm-system/
├── api/
│   ├── customers/
│   │   ├── calculate_grade.php (ใหม่)
│   │   ├── update_intelligence.php (ใหม่)
│   │   └── debug_intelligence.php (ใหม่)
│   └── maintenance/
│       └── fix_customer_intelligence.php (ใหม่)
├── includes/
│   └── customer_intelligence.php (แก้ไข)
├── pages/
│   ├── customer_detail.php (แก้ไข UI)
│   └── dashboard.php (แก้ไข UI)
└── sql/
    └── fix_customer_intelligence.sql (ใหม่)
```

## คอมโพเนนต์และอินเทอร์เฟซ

### 1. Grade Calculator Module

**วัตถุประสงค์:** คำนวณ Customer Grade จากยอดซื้อจริง

**อัลกอริทึมใหม่:**
```php
function calculateCustomerGrade($customerCode) {
    // ดึงยอดซื้อรวมจาก orders.Price
    $totalPurchase = "SELECT SUM(Price) as total 
                     FROM orders 
                     WHERE CustomerCode = ? 
                     AND Price IS NOT NULL";
    
    // กำหนด Grade ตามเกณฑ์ใหม่
    if ($total >= 810000) return 'A';      // VIP Customer
    if ($total >= 85000) return 'B';       // Premium Customer  
    if ($total >= 2000) return 'C';        // Regular Customer
    return 'D';                            // New Customer
}
```

**API Endpoints:**
- `POST /api/customers/calculate_grade.php` - คำนวณ Grade ลูกค้ารายเดียว
- `POST /api/customers/update_intelligence.php` - อัปเดต Grade/Temperature
- `GET /api/customers/debug_intelligence.php` - Debug การคำนวณ

### 2. Temperature Calculator Module

**วัตถุประสงค์:** คำนวณ Customer Temperature ตามสถานะปัจจุบัน

**อัลกอริทึมใหม่:**
```php
function calculateCustomerTemperature($customerCode) {
    $customer = getCustomerData($customerCode);
    $callHistory = getCallHistory($customerCode);
    $totalPurchase = getTotalPurchase($customerCode);
    
    // กฎพิเศษ: ลูกค้า Grade A,B ไม่ควรเป็น FROZEN
    if (in_array($customer['Grade'], ['A', 'B']) && $totalPurchase > 50000) {
        if ($customer['Temperature'] == 'FROZEN') {
            return 'WARM'; // เปลี่ยนจาก FROZEN เป็น WARM
        }
    }
    
    // ลูกค้าใหม่ที่ยังไม่เคยติดต่อ
    if ($customer['CustomerStatus'] == 'ลูกค้าใหม่' && empty($callHistory)) {
        return 'HOT';
    }
    
    // ตรวจสอบการโทรล่าสุด
    $lastCall = getLastCall($customerCode);
    if ($lastCall && $lastCall['TalkStatus'] == 'คุยจบ' && isPositiveResult($lastCall)) {
        return 'HOT';
    }
    
    // ตรวจสอบการปฏิเสธ
    $rejectionCount = countRejections($customerCode);
    if ($rejectionCount >= 2) {
        return 'COLD';
    }
    
    // ตรวจสอบการ assign มากเกินไป (แต่ไม่ใช่ Grade A,B)
    if ($customer['AssignmentCount'] >= 3 && !in_array($customer['Grade'], ['A', 'B'])) {
        return 'FROZEN';
    }
    
    return 'WARM'; // Default
}
```

### 3. Data Migration Module

**วัตถุประสงค์:** แก้ไขข้อมูลที่มีอยู่ทั้งหมด

**กระบวนการ:**
```sql
-- 1. สร้างตารางสำรอง
CREATE TABLE customers_backup AS SELECT * FROM customers;

-- 2. อัปเดต Grade ทั้งหมด
UPDATE customers c 
SET Grade = (
    CASE 
        WHEN (SELECT COALESCE(SUM(Price), 0) FROM orders WHERE CustomerCode = c.CustomerCode) >= 810000 THEN 'A'
        WHEN (SELECT COALESCE(SUM(Price), 0) FROM orders WHERE CustomerCode = c.CustomerCode) >= 85000 THEN 'B'
        WHEN (SELECT COALESCE(SUM(Price), 0) FROM orders WHERE CustomerCode = c.CustomerCode) >= 2000 THEN 'C'
        ELSE 'D'
    END
),
GradeUpdated = NOW();

-- 3. อัปเดต Temperature สำหรับ Grade A,B ที่เป็น FROZEN
UPDATE customers 
SET Temperature = 'WARM', 
    TemperatureUpdated = NOW()
WHERE Grade IN ('A', 'B') 
AND Temperature = 'FROZEN'
AND (SELECT COALESCE(SUM(Price), 0) FROM orders WHERE CustomerCode = customers.CustomerCode) > 50000;
```

### 4. UI Enhancement Module

**วัตถุประสงค์:** ปรับปรุงการแสดงผล Customer Intelligence

**การปรับปรุง Customer Detail Page:**
```php
// แสดง Grade พร้อมสี
function displayGrade($grade, $totalPurchase) {
    $colors = [
        'A' => '#28a745', // เขียว
        'B' => '#007bff', // น้ำเงิน  
        'C' => '#ffc107', // เหลือง
        'D' => '#6c757d'  // เทา
    ];
    
    $labels = [
        'A' => 'VIP Customer',
        'B' => 'Premium Customer',
        'C' => 'Regular Customer', 
        'D' => 'New Customer'
    ];
    
    return sprintf(
        '<div class="grade-display" style="background-color: %s">
            <span class="grade">Grade %s</span>
            <span class="label">%s</span>
            <span class="amount">฿%s</span>
        </div>',
        $colors[$grade],
        $grade,
        $labels[$grade],
        number_format($totalPurchase, 2)
    );
}

// แสดง Temperature พร้อมไอคอน
function displayTemperature($temperature) {
    $icons = [
        'HOT' => '🔥',
        'WARM' => '☀️', 
        'COLD' => '❄️',
        'FROZEN' => '🧊'
    ];
    
    $colors = [
        'HOT' => '#dc3545',    // แดง
        'WARM' => '#fd7e14',   // ส้ม
        'COLD' => '#17a2b8',   // ฟ้า
        'FROZEN' => '#6f42c1'  // ม่วง
    ];
    
    return sprintf(
        '<span class="temperature" style="color: %s" title="%s">
            %s %s
        </span>',
        $colors[$temperature],
        getTemperatureDescription($temperature),
        $icons[$temperature],
        $temperature
    );
}
```

### 5. Debug และ Monitoring Module

**วัตถุประสงค์:** เครื่องมือตรวจสอบและแก้ไขปัญหา

**Debug API:**
```php
// GET /api/customers/debug_intelligence.php?customer_code=CUST001
function debugCustomerIntelligence($customerCode) {
    $debug = [];
    
    // ข้อมูลพื้นฐาน
    $customer = getCustomer($customerCode);
    $debug['customer_info'] = $customer;
    
    // การคำนวณยอดซื้อ
    $orders = getOrders($customerCode);
    $totalPurchase = array_sum(array_column($orders, 'Price'));
    $debug['purchase_calculation'] = [
        'orders' => $orders,
        'total' => $totalPurchase,
        'expected_grade' => calculateExpectedGrade($totalPurchase)
    ];
    
    // การคำนวณ Temperature
    $callHistory = getCallHistory($customerCode);
    $debug['temperature_calculation'] = [
        'call_history' => $callHistory,
        'rejection_count' => countRejections($customerCode),
        'assignment_count' => $customer['AssignmentCount'],
        'expected_temperature' => calculateExpectedTemperature($customerCode)
    ];
    
    // เปรียบเทียบผลลัพธ์
    $debug['comparison'] = [
        'current_grade' => $customer['Grade'],
        'expected_grade' => calculateExpectedGrade($totalPurchase),
        'grade_correct' => $customer['Grade'] == calculateExpectedGrade($totalPurchase),
        'current_temperature' => $customer['Temperature'],
        'expected_temperature' => calculateExpectedTemperature($customerCode),
        'temperature_correct' => $customer['Temperature'] == calculateExpectedTemperature($customerCode)
    ];
    
    return $debug;
}
```

## การจัดการข้อผิดพลาด

### ระดับ Calculation
- **Division by Zero**: ตรวจสอบก่อนหาร
- **Null Values**: ใช้ COALESCE ในการ query
- **Invalid Data**: Validate ข้อมูลก่อนคำนวณ

### ระดับ Database  
- **Transaction Safety**: ใช้ transactions สำหรับการอัปเดตจำนวนมาก
- **Backup Strategy**: สร้าง backup ก่อนแก้ไข
- **Rollback Plan**: เตรียมสคริปต์ rollback

### Error Logging
```php
function logIntelligenceError($customerCode, $error, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'customer_code' => $customerCode,
        'error' => $error,
        'context' => $context,
        'stack_trace' => debug_backtrace()
    ];
    
    file_put_contents(
        LOG_PATH . 'customer_intelligence_errors.log',
        json_encode($logEntry) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
```

## กลยุทธ์การทดสอบ

### Unit Testing
```php
class CustomerIntelligenceTest {
    public function testGradeCalculation() {
        // Test Grade A (≥ 810,000)
        $this->assertEquals('A', calculateGrade(904891.17));
        $this->assertEquals('A', calculateGrade(810000));
        
        // Test Grade B (85,000 - 809,999)
        $this->assertEquals('B', calculateGrade(100000));
        $this->assertEquals('B', calculateGrade(85000));
        
        // Test Grade C (2,000 - 84,999)
        $this->assertEquals('C', calculateGrade(50000));
        $this->assertEquals('C', calculateGrade(2000));
        
        // Test Grade D (< 2,000)
        $this->assertEquals('D', calculateGrade(1999));
        $this->assertEquals('D', calculateGrade(0));
    }
    
    public function testTemperatureLogic() {
        // Test HOT conditions
        $this->assertEquals('HOT', calculateTemperature('NEW_CUSTOMER_NO_CALLS'));
        $this->assertEquals('HOT', calculateTemperature('POSITIVE_LAST_CALL'));
        
        // Test COLD conditions  
        $this->assertEquals('COLD', calculateTemperature('MULTIPLE_REJECTIONS'));
        
        // Test Grade A/B override
        $this->assertNotEquals('FROZEN', calculateTemperature('GRADE_A_HIGH_PURCHASE'));
    }
}
```

### Integration Testing
- ทดสอบการทำงานร่วมกันของ Grade และ Temperature calculation
- ทดสอบการอัปเดตข้อมูลจำนวนมาก
- ทดสอบ UI แสดงผลหลังการแก้ไข

### Performance Testing
- ทดสอบความเร็วในการคำนวณลูกค้าจำนวนมาก
- ทดสอบ memory usage ในการประมวลผลข้อมูลทั้งหมด
- ทดสอบ database performance หลังการอัปเดต

## การปรับใช้งาน (Deployment)

### ขั้นตอนการแก้ไข
1. **Backup ข้อมูล** - สร้าง backup ตาราง customers และ orders
2. **Deploy โค้ดใหม่** - อัปโหลดไฟล์ที่แก้ไขแล้ว
3. **รันสคริปต์แก้ไข** - ประมวลผลข้อมูลทั้งหมด
4. **ทดสอบผลลัพธ์** - ตรวจสอบความถูกต้อง
5. **Monitor ระบบ** - ติดตามการทำงานหลังแก้ไข

### Rollback Plan
```sql
-- หากเกิดปัญหา สามารถ rollback ได้
UPDATE customers c 
SET Grade = (SELECT Grade FROM customers_backup b WHERE b.CustomerCode = c.CustomerCode),
    Temperature = (SELECT Temperature FROM customers_backup b WHERE b.CustomerCode = c.CustomerCode),
    GradeUpdated = (SELECT GradeUpdated FROM customers_backup b WHERE b.CustomerCode = c.CustomerCode);
```

## การติดตามและบำรุงรักษา

### Monitoring Dashboard
- แสดงจำนวนลูกค้าในแต่ละ Grade
- แสดงการกระจายตัวของ Temperature
- แสดงข้อผิดพลาดที่เกิดขึ้น

### Automated Checks
- ตรวจสอบความถูกต้องของการคำนวณทุกวัน
- เตือนเมื่อพบข้อมูลผิดปกติ
- รายงานประสิทธิภาพการทำงาน

### Maintenance Schedule
- ทุกสัปดาห์: ตรวจสอบ Grade/Temperature ที่อาจผิดพลาด
- ทุกเดือน: วิเคราะห์ประสิทธิภาพและปรับปรุง
- ทุกไตรมาส: ทบทวนเกณฑ์การจัด Grade และ Temperature