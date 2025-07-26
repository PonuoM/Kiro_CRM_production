#!/bin/bash
# CRM Auto Rules Cron Job Script
# This script runs the auto rules engine

# Configuration - UPDATE THESE PATHS
SCRIPT_DIR="/home/username/public_html/crm-system/cron"
LOG_DIR="/home/username/public_html/logs"
PHP_PATH="/usr/local/bin/php"

# Create log directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Change to script directory
cd "$SCRIPT_DIR"

# Log start
echo "$(date): Starting auto rules execution" >> "$LOG_DIR/cron_success.log"

# Run auto rules with logging
if $PHP_PATH auto_rules.php >> "$LOG_DIR/cron_success.log" 2>> "$LOG_DIR/cron_errors.log"; then
    echo "$(date): Auto rules completed successfully" >> "$LOG_DIR/cron_success.log"
    exit_code=0
else
    exit_code=$?
    echo "$(date): Auto rules failed with exit code $exit_code" >> "$LOG_DIR/cron_errors.log"
fi

# Clean up old log files (keep 30 days)
find "$LOG_DIR" -name "*.log" -mtime +30 -delete

echo "$(date): Cron job completed with exit code $exit_code" >> "$LOG_DIR/cron_success.log"

exit $exit_code