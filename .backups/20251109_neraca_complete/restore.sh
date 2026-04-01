#!/bin/bash

# RESTORE SCRIPT - Neraca Complete
# Restore Point: 2025-11-09 00:45
# Description: Restore all Neraca-related files to working state

echo "=========================================="
echo "RESTORE NERACA COMPLETE - 2025-11-09"
echo "=========================================="
echo ""

# Set backup directory
BACKUP_DIR="/var/www/html/adiyasa.alus.co.id/.backups/20251109_neraca_complete"
PROJECT_DIR="/var/www/html/adiyasa.alus.co.id"

# Check if backup exists
if [ ! -d "$BACKUP_DIR" ]; then
    echo "❌ Error: Backup directory not found!"
    echo "   Expected: $BACKUP_DIR"
    exit 1
fi

echo "📁 Backup directory found: $BACKUP_DIR"
echo ""
echo "Files to restore:"
ls -1 $BACKUP_DIR/*.php $BACKUP_DIR/*.blade.php 2>/dev/null
echo ""

# Confirm restoration
read -p "⚠️  This will overwrite current files. Continue? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Restoration cancelled."
    exit 0
fi

echo ""
echo "🔄 Starting restoration..."
echo ""

# Restore files
echo "📄 Restoring JurnalController.php..."
cp "$BACKUP_DIR/JurnalController.php" "$PROJECT_DIR/app/Http/Controllers/" && echo "   ✅ Done"

echo "📄 Restoring neraca.blade.php..."
cp "$BACKUP_DIR/neraca.blade.php" "$PROJECT_DIR/resources/views/jurnal/" && echo "   ✅ Done"

echo "📄 Restoring neraca_pdf.blade.php..."
cp "$BACKUP_DIR/neraca_pdf.blade.php" "$PROJECT_DIR/resources/views/jurnal/" && echo "   ✅ Done"

echo "📄 Restoring NeracaExport.php..."
cp "$BACKUP_DIR/NeracaExport.php" "$PROJECT_DIR/app/Exports/" && echo "   ✅ Done"

echo "📄 Restoring web.php..."
cp "$BACKUP_DIR/web.php" "$PROJECT_DIR/routes/" && echo "   ✅ Done"

echo ""
echo "🧹 Clearing Laravel cache..."
cd "$PROJECT_DIR"
php artisan view:clear > /dev/null 2>&1 && echo "   ✅ View cache cleared"
php artisan cache:clear > /dev/null 2>&1 && echo "   ✅ Application cache cleared"
php artisan route:clear > /dev/null 2>&1 && echo "   ✅ Route cache cleared"

echo ""
echo "=========================================="
echo "✅ RESTORATION COMPLETED SUCCESSFULLY!"
echo "=========================================="
echo ""
echo "Test the restored Neraca:"
echo "  🌐 https://adiyasa.alus.co.id/jurnal/neraca"
echo ""
echo "Verify functionality:"
echo "  ✓ View displays with 2-column layout"
echo "  ✓ PDF export works"
echo "  ✓ Excel export works"
echo "  ✓ Balance calculation is correct"
echo ""
