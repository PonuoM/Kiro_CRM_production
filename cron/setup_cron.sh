#!/bin/bash
# setup_cron.sh
# ติดตั้ง Cron Jobs สำหรับระบบจัดการลูกค้าอัตโนมัติ

echo "🔧 Setting up Kiro CRM Auto System Cron Jobs..."

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
PHP_SCRIPT="$PROJECT_DIR/production_auto_system.php"

echo "📍 Project Directory: $PROJECT_DIR"
echo "🐘 PHP Script: $PHP_SCRIPT"

# Check if PHP script exists
if [ ! -f "$PHP_SCRIPT" ]; then
    echo "❌ Error: PHP script not found at $PHP_SCRIPT"
    exit 1
fi

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ Error: PHP not found. Please install PHP CLI"
    exit 1
fi

# Test PHP script
echo "🧪 Testing PHP script..."
if php -l "$PHP_SCRIPT" > /dev/null 2>&1; then
    echo "✅ PHP script syntax is valid"
else
    echo "❌ Error: PHP script has syntax errors"
    php -l "$PHP_SCRIPT"
    exit 1
fi

# Backup existing crontab
echo "💾 Backing up existing crontab..."
crontab -l > "$PROJECT_DIR/cron/crontab_backup_$(date +%Y%m%d_%H%M%S).txt" 2>/dev/null || echo "No existing crontab found"

# Create new cron entries
CRON_ENTRIES="
# Kiro CRM Auto System - Production
# Added on $(date)

# Daily Cleanup - ทุกวันเวลา 01:00 AM
0 1 * * * php $PHP_SCRIPT daily >> $PROJECT_DIR/logs/cron_daily.log 2>&1

# Smart Update - ทุกวันเวลา 02:00 AM  
0 2 * * * php $PHP_SCRIPT smart >> $PROJECT_DIR/logs/cron_smart.log 2>&1

# Auto-reassign - ทุก 6 ชั่วโมง
0 */6 * * * php $PHP_SCRIPT reassign >> $PROJECT_DIR/logs/cron_reassign.log 2>&1

# Full System Check - ทุกวันอาทิตย์เวลา 03:00 AM
0 3 * * 0 php $PHP_SCRIPT all >> $PROJECT_DIR/logs/cron_full.log 2>&1

# Health Check - ทุก 30 นาที (เฉพาะเวลาทำงาน 8:00-18:00)
*/30 8-18 * * 1-6 php $PROJECT_DIR/system_health_check.php >> $PROJECT_DIR/logs/health_check.log 2>&1
"

# Create logs directory if it doesn't exist
mkdir -p "$PROJECT_DIR/logs"

# Add cron entries
echo "⚙️ Adding cron entries..."
(crontab -l 2>/dev/null; echo "$CRON_ENTRIES") | crontab -

if [ $? -eq 0 ]; then
    echo "✅ Cron jobs installed successfully!"
else
    echo "❌ Error: Failed to install cron jobs"
    exit 1
fi

# Display current crontab
echo ""
echo "📋 Current crontab:"
echo "=================="
crontab -l | grep -A 20 "Kiro CRM Auto System"

# Create log rotation script
echo ""
echo "📝 Creating log rotation script..."

cat > "$PROJECT_DIR/cron/rotate_logs.sh" << 'EOF'
#!/bin/bash
# rotate_logs.sh
# Rotate and compress old log files

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOGS_DIR="$PROJECT_DIR/logs"
DATE=$(date +%Y%m%d)

echo "🔄 Rotating logs on $DATE..."

# Rotate logs older than 7 days
find "$LOGS_DIR" -name "*.log" -mtime +7 -exec gzip {} \;

# Delete compressed logs older than 30 days
find "$LOGS_DIR" -name "*.log.gz" -mtime +30 -delete

# Keep only last 10 rotated logs of each type
for log_type in cron_daily cron_smart cron_reassign cron_full health_check; do
    ls -t "$LOGS_DIR"/${log_type}.log.gz 2>/dev/null | tail -n +11 | xargs rm -f 2>/dev/null
done

echo "✅ Log rotation completed"
EOF

chmod +x "$PROJECT_DIR/cron/rotate_logs.sh"

# Add log rotation to cron (weekly)
LOG_ROTATION_CRON="0 4 * * 0 $PROJECT_DIR/cron/rotate_logs.sh >> $PROJECT_DIR/logs/log_rotation.log 2>&1"
(crontab -l 2>/dev/null; echo "$LOG_ROTATION_CRON") | crontab -

echo "✅ Log rotation script created and scheduled"

# Create monitoring script
echo ""
echo "📊 Creating monitoring script..."

cat > "$PROJECT_DIR/cron/monitor_system.sh" << 'EOF'
#!/bin/bash
# monitor_system.sh
# Monitor auto system performance and send alerts

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOGS_DIR="$PROJECT_DIR/logs"

# Check for recent errors in logs
RECENT_ERRORS=$(find "$LOGS_DIR" -name "*.log" -mmin -60 -exec grep -l "ERROR\|FATAL\|Exception" {} \;)

if [ -n "$RECENT_ERRORS" ]; then
    echo "⚠️ Recent errors found in:"
    echo "$RECENT_ERRORS"
    
    # Optional: Send email notification
    # echo "Kiro CRM Auto System Errors" | mail -s "CRM System Alert" admin@company.com
fi

# Check disk space
DISK_USAGE=$(df "$PROJECT_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 90 ]; then
    echo "⚠️ Disk space usage is ${DISK_USAGE}% - Consider cleanup"
fi

# Check database connection
if php -r "
require_once '$PROJECT_DIR/config/database.php';
try {
    \$pdo = new PDO(\$dsn, \$username, \$password, \$options);
    echo 'Database connection: OK';
} catch (Exception \$e) {
    echo 'Database connection: ERROR - ' . \$e->getMessage();
    exit(1);
}
"; then
    echo "✅ Database connection healthy"
else
    echo "❌ Database connection failed"
fi
EOF

chmod +x "$PROJECT_DIR/cron/monitor_system.sh"

echo "✅ Monitoring script created"

# Final instructions
echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📋 What's been installed:"
echo "========================"
echo "• Daily cleanup at 01:00 AM"
echo "• Smart update at 02:00 AM"  
echo "• Auto-reassign every 6 hours"
echo "• Full system check on Sundays at 03:00 AM"
echo "• Health check every 30 minutes (business hours)"
echo "• Log rotation every Sunday at 04:00 AM"
echo ""
echo "📁 Log files location: $PROJECT_DIR/logs/"
echo "🔧 Management scripts: $PROJECT_DIR/cron/"
echo ""
echo "📊 To monitor the system:"
echo "  ./cron/monitor_system.sh"
echo ""
echo "🔍 To view logs:"
echo "  tail -f logs/cron_*.log"
echo ""
echo "⚠️ Important: Make sure the web server user has write permissions to the logs directory"
echo "  sudo chown -R www-data:www-data $PROJECT_DIR/logs/"
echo "  sudo chmod -R 755 $PROJECT_DIR/logs/"