# Orders Table Schema Analysis

## 📋 Current Orders Table (ตามที่เคยเห็น)

```sql
Field                Type            Null    Key     Default             Extra
id                   int(11)         NO      PRI                         auto_increment
DocumentNo           varchar(50)     NO      UNI
CustomerCode         varchar(50)     NO      MUL
DocumentDate         datetime        NO      MUL
PaymentMethod        varchar(200)    YES
Products             varchar(500)    YES
Quantity             decimal(10,2)   YES
Price                decimal(10,2)   YES
OrderBy              varchar(50)     YES
CreatedDate          datetime        YES     MUL     current_timestamp()
CreatedBy            varchar(50)     YES     MUL
DiscountAmount       decimal(10,2)   YES     MUL     0.00
DiscountPercent      decimal(5,2)    YES     MUL     0.00
DiscountRemarks      varchar(500)    YES
ProductsDetail       longtext        YES
SubtotalAmount       decimal(10,2)   YES                0.00
```

## ✅ Columns ที่มีอยู่แล้ว
- ✅ `id` - Primary Key
- ✅ `DocumentNo` - เลขที่เอกสาร (UNIQUE)
- ✅ `CustomerCode` - รหัสลูกค้า
- ✅ `DocumentDate` - วันที่เอกสาร
- ✅ `PaymentMethod` - วิธีการชำระเงิน
- ✅ `Products` - ชื่อสินค้า (รวมกัน)
- ✅ `Quantity` - จำนวน (รวม)
- ✅ `Price` - ราคา (รวม)
- ✅ `OrderBy` - ผู้สั่ง
- ✅ `CreatedDate` - วันที่สร้าง
- ✅ `CreatedBy` - ผู้สร้าง
- ✅ `DiscountAmount` - จำนวนส่วนลด
- ✅ `DiscountPercent` - เปอร์เซ็นต์ส่วนลด
- ✅ `DiscountRemarks` - หมายเหตุส่วนลด
- ✅ `ProductsDetail` - รายละเอียดสินค้า JSON
- ✅ `SubtotalAmount` - ยอดรวมก่อนส่วนลด

## ❌ Columns ที่ต้องเพิ่ม
- ❌ `TotalItems` - จำนวนรายการสินค้าที่แตกต่างกัน

## 🆕 Order Items Table ที่ต้องสร้างใหม่

```sql
CREATE TABLE order_items (
    id                  int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    DocumentNo          varchar(50) NOT NULL,           -- เลขที่เอกสาร (FK)
    ProductCode         varchar(50) DEFAULT NULL,       -- รหัสสินค้า
    ProductName         varchar(200) NOT NULL,          -- ชื่อสินค้า
    UnitPrice           decimal(10,2) NOT NULL DEFAULT 0.00,    -- ราคาต่อหน่วย
    Quantity            decimal(10,2) NOT NULL DEFAULT 1.00,    -- จำนวน
    LineTotal           decimal(10,2) NOT NULL DEFAULT 0.00,    -- รวมรายการ (UnitPrice * Quantity)
    ItemDiscount        decimal(10,2) DEFAULT 0.00,     -- ส่วนลดรายการ
    ItemDiscountPercent decimal(5,2) DEFAULT 0.00,      -- เปอร์เซ็นต์ส่วนลดรายการ
    CreatedDate         datetime DEFAULT CURRENT_TIMESTAMP,
    CreatedBy           varchar(50) DEFAULT NULL,
    
    INDEX idx_document_no (DocumentNo),
    INDEX idx_product_code (ProductCode),
    
    CONSTRAINT fk_order_items_orders 
        FOREIGN KEY (DocumentNo) REFERENCES orders(DocumentNo) 
        ON DELETE CASCADE ON UPDATE CASCADE
);
```

## 🔤 Case Sensitivity Issues
- MySQL บน Windows (XAMPP) มักเป็น **case insensitive**
- ใช้ชื่อ field เป็น **PascalCase** ตามที่มีอยู่เดิม
- ชื่อตาราง: `orders`, `order_items` (lowercase)

## 📊 ตัวอย่างข้อมูลใหม่

### Orders (Header)
```
id: 38
DocumentNo: DOC202507311109361444
CustomerCode: TEST039
TotalItems: 2                    ← เพิ่มใหม่
Quantity: 4.00                   ← รวมจาก order_items
Price: 320.00                    ← ยอดสุทธิหลังส่วนลด
SubtotalAmount: 400.00           ← รวมจาก order_items
DiscountAmount: 80.00
```

### Order Items (Detail)  
```
DocumentNo: DOC202507311109361444, ProductName: "ปุ๋ย A", UnitPrice: 100, Quantity: 2, LineTotal: 200
DocumentNo: DOC202507311109361444, ProductName: "ปุ๋ย B", UnitPrice: 100, Quantity: 2, LineTotal: 200
```

## 🎯 Implementation Plan

### Phase 1: Database Changes
1. เพิ่ม `TotalItems` column ใน orders table
2. สร้าง `order_items` table
3. สร้าง indexes และ foreign keys

### Phase 2: Data Migration  
1. แปลงข้อมูลเก่าจาก `ProductsDetail` JSON
2. สร้าง order_items records
3. อัพเดต TotalItems ใน orders

### Phase 3: API Changes
1. ปรับ Order.php model
2. ปรับ API create/read/update
3. เพิ่ม OrderItem model

### Phase 4: Testing
1. ทดสอบการสร้าง order ใหม่
2. ทดสอบการ query ข้อมูล
3. ทดสอบ reports

## ⚠️ Backward Compatibility
- รักษา `Products` field ไว้สำหรับระบบเก่า
- รักษา `ProductsDetail` JSON ไว้
- API เก่ายังทำงานได้