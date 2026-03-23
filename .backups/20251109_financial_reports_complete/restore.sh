#!/bin/bash
################################################################################
# QUICK RESTORE SCRIPT - Financial Reports Complete
# Backup ID: 20251109_financial_reports_complete
# Date: 9 November 2025
################################################################################

echo "=========================================="
echo "  RESTORE FINANCIAL REPORTS"
echo "  Backup: 20251109_financial_reports_complete"
echo "=========================================="
echo ""

# Set colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get base directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BASE_DIR="/var/www/html/adiyasa.alus.co.id"
BACKUP_DIR="$BASE_DIR/.backups/20251109_financial_reports_complete"

# Check if backup exists
if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}❌ Backup directory not found!${NC}"
    echo "Expected: $BACKUP_DIR"
    exit 1
fi

echo "📁 Backup location: $BACKUP_DIR"
echo ""

# Function to backup current file before restore
backup_current() {
    local file=$1
    if [ -f "$file" ]; then
        cp "$file" "$file.pre_restore_$(date +%Y%m%d_%H%M%S)"
        echo -e "${YELLOW}  ↳ Backed up current version${NC}"
    fi
}

# Ask user what to restore
echo "Select restore option:"
echo "  1) Restore ALL (recommended)"
echo "  2) Restore Neraca only"
echo "  3) Restore Laba Rugi only"
echo "  4) Restore Arus Kas only"
echo "  5) Restore Buku Besar only"
echo "  6) Restore Controller only"
echo "  0) Cancel"
echo ""
read -p "Enter option [1-6, 0 to cancel]: " choice

case $choice in
    1)
        echo ""
        echo -e "${GREEN}Restoring ALL files...${NC}"
        echo ""
        
        # Controller
        echo "📄 JurnalController.php"
        backup_current "$BASE_DIR/app/Http/Controllers/JurnalController.php"
        cp "$BACKUP_DIR/JurnalController.php" "$BASE_DIR/app/Http/Controllers/"
        echo -e "${GREEN}  ✅ Restored${NC}"
        
        # Neraca
        echo "📄 Neraca files"
        backup_current "$BASE_DIR/resources/views/jurnal/neraca.blade.php"
        cp "$BACKUP_DIR/neraca.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/neraca_pdf.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/NeracaExport.php" "$BASE_DIR/app/Exports/"
        echo -e "${GREEN}  ✅ Restored${NC}"
        
        # Laba Rugi
        echo "📄 Laba Rugi files"
        backup_current "$BASE_DIR/resources/views/jurnal/laba_rugi.blade.php"
        cp "$BACKUP_DIR/laba_rugi.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/laba_rugi_pdf.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/LabaRugiExport.php" "$BASE_DIR/app/Exports/"
        echo -e "${GREEN}  ✅ Restored${NC}"
        
        # Arus Kas
        echo "📄 Arus Kas files"
        backup_current "$BASE_DIR/resources/views/jurnal/arus_kas.blade.php"
        cp "$BACKUP_DIR/arus_kas.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/arus_kas_pdf.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/ArusKasExport.php" "$BASE_DIR/app/Exports/"
        echo -e "${GREEN}  ✅ Restored${NC}"
        
        # Buku Besar
        echo "📄 Buku Besar files"
        backup_current "$BASE_DIR/resources/views/jurnal/bukubesar.blade.php"
        cp "$BACKUP_DIR/bukubesar.blade.php" "$BASE_DIR/resources/views/jurnal/"
        echo -e "${GREEN}  ✅ Restored${NC}"
        ;;
        
    2)
        echo ""
        echo -e "${GREEN}Restoring Neraca only...${NC}"
        backup_current "$BASE_DIR/resources/views/jurnal/neraca.blade.php"
        cp "$BACKUP_DIR/neraca.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/neraca_pdf.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/NeracaExport.php" "$BASE_DIR/app/Exports/"
        echo -e "${GREEN}✅ Neraca restored${NC}"
        ;;
        
    3)
        echo ""
        echo -e "${GREEN}Restoring Laba Rugi only...${NC}"
        backup_current "$BASE_DIR/resources/views/jurnal/laba_rugi.blade.php"
        cp "$BACKUP_DIR/laba_rugi.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/laba_rugi_pdf.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/LabaRugiExport.php" "$BASE_DIR/app/Exports/"
        echo -e "${GREEN}✅ Laba Rugi restored${NC}"
        ;;
        
    4)
        echo ""
        echo -e "${GREEN}Restoring Arus Kas only...${NC}"
        backup_current "$BASE_DIR/resources/views/jurnal/arus_kas.blade.php"
        cp "$BACKUP_DIR/arus_kas.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/arus_kas_pdf.blade.php" "$BASE_DIR/resources/views/jurnal/"
        cp "$BACKUP_DIR/ArusKasExport.php" "$BASE_DIR/app/Exports/"
        echo -e "${GREEN}✅ Arus Kas restored${NC}"
        ;;
        
    5)
        echo ""
        echo -e "${GREEN}Restoring Buku Besar only...${NC}"
        backup_current "$BASE_DIR/resources/views/jurnal/bukubesar.blade.php"
        cp "$BACKUP_DIR/bukubesar.blade.php" "$BASE_DIR/resources/views/jurnal/"
        echo -e "${GREEN}✅ Buku Besar restored${NC}"
        ;;
        
    6)
        echo ""
        echo -e "${GREEN}Restoring Controller only...${NC}"
        backup_current "$BASE_DIR/app/Http/Controllers/JurnalController.php"
        cp "$BACKUP_DIR/JurnalController.php" "$BASE_DIR/app/Http/Controllers/"
        echo -e "${GREEN}✅ Controller restored${NC}"
        ;;
        
    0)
        echo ""
        echo -e "${YELLOW}Restore cancelled${NC}"
        exit 0
        ;;
        
    *)
        echo ""
        echo -e "${RED}Invalid option${NC}"
        exit 1
        ;;
esac

echo ""
echo "🔄 Clearing Laravel cache..."
cd "$BASE_DIR"
php artisan view:clear > /dev/null 2>&1
php artisan route:cache > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
echo -e "${GREEN}✅ Cache cleared${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN}✅ RESTORE COMPLETE!${NC}"
echo "=========================================="
echo ""
echo "📝 Pre-restore backups saved with .pre_restore_* extension"
echo "📖 Read RESTORE_NOTES.md for testing checklist"
echo ""
