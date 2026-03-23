#!/bin/bash
#
# Automatic Database Backup Script for Multi-Tenant ISP System
# Usage: ./backup_all_tenants.sh [--compress]
#
# This script will:
# 1. Backup all active tenant databases
# 2. Store backups in tenant-specific directories
# 3. Clean up old backups (older than 7 days)
# 4. Send notification on failure (optional)
#

# Configuration
APP_DIR="/var/www/kencana.alus.co.id"
LOG_FILE="${APP_DIR}/storage/logs/backup.log"
COMPRESS=false

# Check for compress flag
if [[ "$1" == "--compress" ]]; then
    COMPRESS=true
fi

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Start backup process
log_message "=========================================="
log_message "Starting automatic database backup..."
log_message "=========================================="

cd "$APP_DIR" || exit 1

# Run Laravel backup command
if [ "$COMPRESS" = true ]; then
    php artisan tenant:backup-database --compress 2>&1 | tee -a "$LOG_FILE"
else
    php artisan tenant:backup-database 2>&1 | tee -a "$LOG_FILE"
fi

BACKUP_STATUS=$?

if [ $BACKUP_STATUS -eq 0 ]; then
    log_message "✓ All backups completed successfully"
else
    log_message "✗ Backup process encountered errors (exit code: $BACKUP_STATUS)"
    
    # Optional: Send email notification on failure
    # echo "Database backup failed. Check logs at ${LOG_FILE}" | mail -s "Backup Failed" admin@alus.co.id
fi

log_message "=========================================="
log_message "Backup process finished"
log_message "=========================================="
echo ""

exit $BACKUP_STATUS
