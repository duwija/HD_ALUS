#!/bin/bash
# Restore Script - Backup ID: 20251109_000034
# Created: 2025-11-09 00:00:34
# Purpose: Restore files to state before laporan keuangan fixes

echo "=================================================="
echo "RESTORE SCRIPT - Laporan Keuangan"
echo "Backup ID: 20251109_000034"
echo "Backup Date: 2025-11-09 00:00:34"
echo "=================================================="
echo ""

# Set variables
PROJECT_ROOT="/var/www/html/adiyasa.alus.co.id"
BACKUP_DIR="$PROJECT_ROOT/.backups/20251109_000034"

# Check if backup exists
if [ ! -d "$BACKUP_DIR" ]; then
    echo "ERROR: Backup directory not found: $BACKUP_DIR"
    exit 1
fi

echo "Backup directory found: $BACKUP_DIR"
echo ""

# Confirm restore
read -p "Are you sure you want to restore? This will overwrite current files (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Restore cancelled."
    exit 0
fi

echo ""
echo "Starting restore process..."
echo ""

# Restore Controller
if [ -f "$BACKUP_DIR/JurnalController.php.backup" ]; then
    echo "[1/4] Restoring JurnalController..."
    cp -p "$BACKUP_DIR/JurnalController.php.backup" "$PROJECT_ROOT/app/Http/Controllers/JurnalController.php"
    echo "      ✓ JurnalController.php restored"
else
    echo "      ✗ JurnalController.php.backup not found"
fi

# Restore Routes
if [ -f "$BACKUP_DIR/web.php.backup" ]; then
    echo "[2/4] Restoring routes/web.php..."
    cp -p "$BACKUP_DIR/web.php.backup" "$PROJECT_ROOT/routes/web.php"
    echo "      ✓ web.php restored"
else
    echo "      ✗ web.php.backup not found"
fi

# Restore Views
echo "[3/4] Restoring view files..."
VIEW_COUNT=0
if [ -f "$BACKUP_DIR/views/neraca.blade.php.backup" ]; then
    cp -p "$BACKUP_DIR/views/neraca.blade.php.backup" "$PROJECT_ROOT/resources/views/jurnal/neraca.blade.php"
    echo "      ✓ neraca.blade.php restored"
    ((VIEW_COUNT++))
fi

if [ -f "$BACKUP_DIR/views/arus_kas.blade.php.backup" ]; then
    cp -p "$BACKUP_DIR/views/arus_kas.blade.php.backup" "$PROJECT_ROOT/resources/views/jurnal/arus_kas.blade.php"
    echo "      ✓ arus_kas.blade.php restored"
    ((VIEW_COUNT++))
fi

if [ -f "$BACKUP_DIR/views/neraca_saldo.blade.php.backup" ]; then
    cp -p "$BACKUP_DIR/views/neraca_saldo.blade.php.backup" "$PROJECT_ROOT/resources/views/jurnal/neraca_saldo.blade.php"
    echo "      ✓ neraca_saldo.blade.php restored"
    ((VIEW_COUNT++))
fi

echo "      ✓ $VIEW_COUNT view file(s) restored"

# Remove new files created
echo "[4/4] Removing newly created files..."
REMOVED_COUNT=0
if [ -f "$PROJECT_ROOT/resources/views/jurnal/laba_rugi.blade.php" ]; then
    rm -f "$PROJECT_ROOT/resources/views/jurnal/laba_rugi.blade.php"
    echo "      ✓ laba_rugi.blade.php removed"
    ((REMOVED_COUNT++))
fi

if [ -f "$PROJECT_ROOT/resources/views/jurnal/laba_rugi_pdf.blade.php" ]; then
    rm -f "$PROJECT_ROOT/resources/views/jurnal/laba_rugi_pdf.blade.php"
    echo "      ✓ laba_rugi_pdf.blade.php removed"
    ((REMOVED_COUNT++))
fi

echo "      ✓ $REMOVED_COUNT new file(s) removed"

echo ""
echo "=================================================="
echo "RESTORE COMPLETED SUCCESSFULLY"
echo "=================================================="
echo ""
echo "Files have been restored to state: 2025-11-09 00:00:34"
echo "All changes made after this backup have been reverted."
echo ""
echo "Next steps:"
echo "1. Clear cache: php artisan cache:clear"
echo "2. Restart web server if needed"
echo "3. Test the application"
echo ""
