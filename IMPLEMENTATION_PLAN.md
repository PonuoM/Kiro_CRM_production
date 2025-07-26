# 🎯 Implementation Plan - 3-Role CRM System

## 📊 Current Status

### ✅ Completed
1. **Database Schema Analysis** - ตรวจสอบ users table structure
2. **SQL Script Created** - `database/add_supervisor_id_field.sql` 
3. **New Permissions System** - `includes/permissions_new.php` (3 roles only)
4. **Dashboard Reverted** - กลับไปใช้ permissions system
5. **Test Scripts Created** - ไฟล์ทดสอบระบบ

### 🔄 Role Structure (Designed)
- **Admin**: ดูได้ทุกอย่าง, จัดการระบบ, user management
- **Supervisor**: ดูได้เฉพาะทีมตนเอง + supervisor tools  
- **Sales**: ดูได้เฉพาะลูกค้าที่ได้รับมอบหมาย

---

## 🚀 Execution Steps

### Step 1: Database Update (คุณทำเอง)
```bash
# เข้า MySQL และรันไฟล์นี้
mysql -u primacom_bloguser -p primacom_CRM < database/add_supervisor_id_field.sql
```

### Step 2: Activate New Permissions System
```bash
# เปิดไฟล์นี้ใน browser
http://your-domain/test_permissions_system.php
```
ไฟล์นี้จะ:
- Backup permissions.php เก่า → permissions_backup.php
- Copy permissions_new.php → permissions.php
- ทดสอบการทำงานของระบบใหม่

### Step 3: Revert Modified Files
ต้อง revert ไฟล์เหล่านี้กลับไปใช้ permissions system:

**Files to revert:**
1. `pages/customer_list_demo.php` ✅ (dashboard แก้ไขแล้ว)
2. `pages/order_history_demo.php`
3. `pages/call_history_demo.php`
4. `pages/daily_tasks_demo.php`

### Step 4: Test Each Role

**Admin Testing:**
```
Username: admin
Expected Menu: Dashboard, Customer Management (4), Admin Tools (4), Reports (1) = 10 items
Expected Data: See all customers, all users, all reports
```

**Supervisor Testing:**
```
Username: supervisor  
Expected Menu: Dashboard, Customer Management (4), Supervisor Tools (2), Reports (1) = 8 items
Expected Data: See own team's customers only
```

**Sales Testing:**
```
Username: sale1
Expected Menu: Dashboard, Customer Management (2), Reports (0) = 3 items  
Expected Data: See only assigned customers
```

---

## 📋 Detailed Action Checklist

### Phase 1: System Preparation
- [ ] 1.1 Run `add_supervisor_id_field.sql` in MySQL
- [ ] 1.2 Run `test_current_schema.php` to verify database
- [ ] 1.3 Run `test_permissions_system.php` to activate new system

### Phase 2: File Reversion (ผมทำ)
- [ ] 2.1 Revert `customer_list_demo.php` to use Permissions
- [ ] 2.2 Revert `order_history_demo.php` to use Permissions  
- [ ] 2.3 Revert `call_history_demo.php` to use Permissions
- [ ] 2.4 Revert `daily_tasks_demo.php` to use Permissions

### Phase 3: Role Testing (คุณทดสอบ)
- [ ] 3.1 Test Admin login → should see 10 menu items
- [ ] 3.2 Test Supervisor login → should see 8 menu items  
- [ ] 3.3 Test Sales login → should see 3 menu items
- [ ] 3.4 Verify data visibility per role

### Phase 4: Data Access Testing
- [ ] 4.1 Admin sees all customers
- [ ] 4.2 Supervisor sees only team customers
- [ ] 4.3 Sales sees only assigned customers
- [ ] 4.4 Admin can access user management
- [ ] 4.5 Sales cannot access admin tools

---

## 🎯 Key Differences from Before

### Old System Problems:
- ❌ 5 roles (too complex)
- ❌ Complex permissions matrix
- ❌ No team hierarchy
- ❌ All roles saw same data

### New System Benefits:
- ✅ 3 simple roles
- ✅ Clear hierarchy: Admin > Supervisor > Sales
- ✅ Team-based data access
- ✅ supervisor_id relationship
- ✅ Role-appropriate menus

---

## 🔍 Testing Scenarios

### Scenario 1: Admin Login
**Expected Results:**
- Menu: 10 items (all sections visible)
- Data: All customers, all users, all reports
- Access: All admin tools, user management

### Scenario 2: Supervisor Login  
**Expected Results:**
- Menu: 8 items (no admin tools, has supervisor tools)
- Data: Only team members' customers
- Access: Team management, supervisor reports

### Scenario 3: Sales Login
**Expected Results:**
- Menu: 3 items (basic functionality only)
- Data: Only personally assigned customers
- Access: Customer interaction, task management

---

## 🚨 Rollback Plan

If issues occur:
```php
// Restore old permissions
copy('includes/permissions_backup.php', 'includes/permissions.php');
```

---

## 📞 Support

**Files Created:**
- `database/add_supervisor_id_field.sql` - Database update
- `includes/permissions_new.php` - New 3-role system
- `test_permissions_system.php` - System activation
- `test_current_schema.php` - Database verification

**Next Action:** คุณรัน SQL ใน database, แล้วเปิด `test_permissions_system.php`