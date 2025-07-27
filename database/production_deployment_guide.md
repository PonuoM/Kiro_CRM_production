# Production Deployment Guide - Migration v2.0

**Story 1.1: Alter Database Schema for Lead Management Logic**

## 🚀 Pre-Deployment Checklist

### ✅ Testing Verification
- [ ] All tests in `test_migration_complete.php` passed
- [ ] Migration script `migration_v2.0.sql` tested successfully
- [ ] Rollback script `rollback_v2.0.sql` tested and ready
- [ ] Performance tests completed with acceptable results
- [ ] Business scenario tests validated

### ✅ Backup Preparation
- [ ] Database backup created using `scripts/backup.php`
- [ ] Backup file verified and accessible
- [ ] Backup restoration tested in staging environment
- [ ] File system backup of application code completed

### ✅ Production Environment
- [ ] Production database credentials verified
- [ ] Database connection tested
- [ ] Sufficient disk space available
- [ ] MySQL version compatibility confirmed
- [ ] Current production traffic low (recommended)

## 📋 Deployment Steps

### Step 1: Create Production Backup
```bash
# Run the backup script
php scripts/backup.php

# Verify backup file was created
ls -la backups/

# Note the backup filename for rollback reference
```

### Step 2: Database Migration Execution
```sql
-- Connect to production database
mysql -u [username] -p [database_name]

-- Execute migration script
source database/migration_v2.0.sql;

-- Verify execution completed successfully
-- Look for "Migration v2.0 Completed Successfully!" message
```

### Step 3: Post-Deployment Verification
```bash
# Run the complete test suite
php test_migration_complete.php

# Should show "MIGRATION v2.0 SUCCESS!" message
# All Acceptance Criteria should show "PASS"
```

### Step 4: Smoke Testing
- [ ] Login to CRM system works
- [ ] Customer list page loads correctly
- [ ] User management functions normally
- [ ] No error messages in application logs
- [ ] Database queries respond within normal time

## 🔍 Verification Checklist

### Database Schema Verification
```sql
-- Verify ContactAttempts column
DESCRIBE customers;
-- Should show ContactAttempts INT NOT NULL DEFAULT 0

-- Verify AssignmentCount column  
-- Should show AssignmentCount INT NOT NULL DEFAULT 0

-- Verify CustomerTemperature ENUM
SHOW COLUMNS FROM customers LIKE 'CustomerTemperature';
-- Should include 'FROZEN' in ENUM values

-- Verify supervisor_id column
DESCRIBE users;
-- Should show supervisor_id INT NULL

-- Verify foreign key constraint
SHOW CREATE TABLE users;
-- Should show CONSTRAINT fk_users_supervisor
```

### Application Functionality Testing
1. **Create New Customer**
   - ContactAttempts should default to 0
   - AssignmentCount should default to 0
   - CustomerTemperature should have valid default

2. **Update Customer Temperature**
   - Should be able to set to 'FROZEN'
   - No errors when saving

3. **User Management**
   - supervisor_id field should be available
   - Can assign supervisors to sales users

## 🚨 Rollback Procedures

### When to Rollback
- Migration script fails during execution
- Post-deployment tests fail
- Application errors occur
- Performance degradation detected
- Business functionality broken

### Rollback Execution
```bash
# 1. Stop application traffic (if possible)

# 2. Execute rollback script
mysql -u [username] -p [database_name] < database/rollback_v2.0.sql

# 3. Restore from backup if needed
mysql -u [username] -p [database_name] < backups/[backup_file.sql]

# 4. Verify rollback completed
php database/test_migration_setup.php
# Should show migration is needed again

# 5. Restart application services
```

## 📊 Post-Deployment Monitoring

### First 24 Hours
- [ ] Monitor application error logs
- [ ] Check database performance metrics
- [ ] Verify user-reported issues
- [ ] Monitor system resource usage

### Performance Benchmarks
- [ ] Customer list page load time < 2 seconds
- [ ] User login response time < 1 second
- [ ] Database query response time normal
- [ ] No increase in error rates

## 🎯 Success Criteria

### Technical Success
- ✅ All 4 Acceptance Criteria implemented
- ✅ No application errors
- ✅ Performance within acceptable ranges
- ✅ All tests passing

### Business Success
- ✅ Lead management logic now supported
- ✅ Contact attempt tracking available
- ✅ Assignment count tracking enabled
- ✅ FROZEN temperature status usable
- ✅ Team hierarchy management possible

## 📝 Deployment Log Template

```
=== MIGRATION v2.0 DEPLOYMENT LOG ===
Date: [YYYY-MM-DD]
Time: [HH:MM:SS]
Environment: Production
Deployer: [Name]

Pre-Deployment:
[ ] Backup created: [backup_filename]
[ ] Tests passed: [test_results]
[ ] Traffic status: [low/medium/high]

Deployment:
[ ] Migration started: [HH:MM:SS]
[ ] Migration completed: [HH:MM:SS]
[ ] Duration: [X minutes]

Post-Deployment:
[ ] Verification passed: [Yes/No]
[ ] Smoke tests passed: [Yes/No]
[ ] Performance normal: [Yes/No]

Issues Encountered:
[List any issues and resolutions]

Final Status: [SUCCESS/ROLLBACK/PENDING]

Next Steps:
[Any follow-up actions needed]
```

## 🔗 Related Files

- **Migration Script**: `database/migration_v2.0.sql`
- **Test Suite**: `test_migration_complete.php`
- **Rollback Script**: `database/rollback_v2.0.sql`
- **Backup Script**: `scripts/backup.php`
- **Story Documentation**: `docs/stories/1.1.story.md`

## 📞 Emergency Contacts

In case of deployment issues:
- Database Administrator: [Contact Info]
- System Administrator: [Contact Info]
- Development Team Lead: [Contact Info]
- Project Manager: [Contact Info]

---

**Remember**: Always test in staging environment before production deployment!