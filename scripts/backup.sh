#!/bin/bash
# CRM System Backup Script
# This script creates automated backups of database and files

# Configuration - UPDATE THESE VALUES
BACKUP_DIR="/home/username/public_html/backups"
DB_NAME="primacom_CRM"
DB_USER="primacom_bloguser"
DB_PASS="pJnL53Wkhju2LaGPytw8"
APP_DIR="/home/username/public_html/crm-system"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "$BACKUP_DIR"

echo "$(date): Starting backup process" >> "$BACKUP_DIR/backup.log"

# Database backup
echo "$(date): Backing up database..." >> "$BACKUP_DIR/backup.log"
if mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database_$DATE.sql"; then
    echo "$(date): Database backup completed successfully" >> "$BACKUP_DIR/backup.log"
else
    echo "$(date): Database backup failed" >> "$BACKUP_DIR/backup.log"
    exit 1
fi

# Files backup
echo "$(date): Backing up files..." >> "$BACKUP_DIR/backup.log"
if tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" \
    --exclude="$BACKUP_DIR" \
    --exclude="$APP_DIR/logs/*.log" \
    --exclude="$APP_DIR/uploads/temp/*" \
    "$APP_DIR/"; then
    echo "$(date): Files backup completed successfully" >> "$BACKUP_DIR/backup.log"
else
    echo "$(date): Files backup failed" >> "$BACKUP_DIR/backup.log"
    exit 1
fi

# Clean up old backups (keep 30 days)
echo "$(date): Cleaning up old backups..." >> "$BACKUP_DIR/backup.log"
find "$BACKUP_DIR" -name "database_*.sql" -mtime +30 -delete
find "$BACKUP_DIR" -name "files_*.tar.gz" -mtime +30 -delete

# Calculate backup sizes
DB_SIZE=$(ls -lh "$BACKUP_DIR/database_$DATE.sql" | awk '{print $5}')
FILES_SIZE=$(ls -lh "$BACKUP_DIR/files_$DATE.tar.gz" | awk '{print $5}')

echo "$(date): Backup completed successfully - DB: $DB_SIZE, Files: $FILES_SIZE" >> "$BACKUP_DIR/backup.log"

# Optional: Send email notification (uncomment if needed)
# echo "Backup completed at $(date)" | mail -s "CRM Backup Success" admin@yourdomain.com

exit 0