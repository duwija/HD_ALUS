#!/usr/bin/env bash
# =============================================================================
#  deploy.sh — Jalankan di SETIAP server (biasanya dipanggil dari deploy-all.sh)
#  Lokasi: /var/www/kencana.alus.co.id/scripts/deploy.sh
#
#  Gunakan:
#    ./scripts/deploy.sh              (deploy branch main)
#    ./scripts/deploy.sh hotfix/v2    (deploy branch tertentu)
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/kencana.alus.co.id"
WEB_USER="apache"
WEB_GROUP="apache"
BRANCH="${1:-main}"

# Warna output
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()    { echo -e "${GREEN}[DEPLOY]${NC} $*"; }
warning() { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; exit 1; }

# ── 0. Pastikan di direktori yang benar ──────────────────────────────────────
cd "$APP_DIR" || error "Direktori $APP_DIR tidak ditemukan"
info "Deploy dimulai: $(hostname) | branch: $BRANCH | $(date '+%Y-%m-%d %H:%M:%S')"

# ── 1. Backup .env (jaga-jaga tidak tertimpa) ─────────────────────────────────
if [ -f .env ]; then
    cp .env /tmp/.env.backup.$(date +%s)
    info ".env sudah di-backup ke /tmp/"
fi

# ── 2. Git Pull ────────────────────────────────────────────────────────────────
info "Pulling dari GitHub (branch: $BRANCH)..."
git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true
git fetch origin
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

# Kembalikan .env jika tertimpa (harusnya tidak karena .gitignore)
if [ ! -f .env ]; then
    warning ".env tidak ada! Pastikan .env sudah ada di server ini."
    if [ -f /tmp/.env.backup.* ]; then
        cp /tmp/.env.backup.* .env
        warning ".env dipulihkan dari backup."
    fi
fi

# ── 3. Composer ───────────────────────────────────────────────────────────────
info "Composer install..."
composer install \
    --no-interaction \
    --no-dev \
    --optimize-autoloader \
    --no-scripts 2>&1 | tail -5

# ── 4. Artisan cache & migrate ────────────────────────────────────────────────
info "Config cache..."
php artisan config:cache

info "Route cache..."
php artisan route:cache

info "View cache..."
php artisan view:cache

info "Database migrate..."
php artisan migrate --force --no-interaction

# ── 5. Fix file permissions (KUNCI agar tidak 500 error) ─────────────────────
info "Fixing file permissions..."

# 5a. Semua file code: owner root (atau deploy user), group apache, readable
find "$APP_DIR" \
    -not -path "$APP_DIR/.git/*" \
    -not -path "$APP_DIR/vendor/*" \
    -not -path "$APP_DIR/storage/*" \
    -not -path "$APP_DIR/bootstrap/cache/*" \
    -not -name ".env" \
    | xargs chown root:"$WEB_GROUP" 2>/dev/null || true

find "$APP_DIR" \
    -not -path "$APP_DIR/.git/*" \
    -not -path "$APP_DIR/storage/*" \
    -not -path "$APP_DIR/bootstrap/cache/*" \
    -type f \
    | xargs chmod 644 2>/dev/null || true

find "$APP_DIR" \
    -not -path "$APP_DIR/.git/*" \
    -not -path "$APP_DIR/storage/*" \
    -not -path "$APP_DIR/bootstrap/cache/*" \
    -type d \
    | xargs chmod 755 2>/dev/null || true

# 5b. storage/ dan bootstrap/cache/ milik apache supaya bisa tulis
chown -R "$WEB_USER":"$WEB_GROUP" "$APP_DIR/storage"
chown -R "$WEB_USER":"$WEB_GROUP" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

# 5c. .env hanya root yang bisa baca (keamanan)
chown root:root "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"
# Beri apache akses baca .env
setfacl -m u:"$WEB_USER":r "$APP_DIR/.env" 2>/dev/null || chmod 644 "$APP_DIR/.env"

# 5d. Script deploy sendiri harus executable
chmod +x "$APP_DIR/scripts/"*.sh 2>/dev/null || true

# ── 6. Queue worker restart (jika pakai supervisor) ───────────────────────────
info "Restart queue workers..."
php artisan queue:restart 2>/dev/null || warning "Queue restart gagal (mungkin tidak pakai queue)"

# ── 7. Opcache / PHP-FPM reload ───────────────────────────────────────────────
info "Reload PHP-FPM..."
if systemctl is-active --quiet php8.1-fpm 2>/dev/null; then
    systemctl reload php8.1-fpm
elif systemctl is-active --quiet php8.0-fpm 2>/dev/null; then
    systemctl reload php8.0-fpm
elif systemctl is-active --quiet php-fpm 2>/dev/null; then
    systemctl reload php-fpm
else
    warning "PHP-FPM tidak ditemukan, skip reload"
fi

# ── 8. Selesai ─────────────────────────────────────────────────────────────────
info "======================================================="
info "  DEPLOY SELESAI: $(hostname)"
info "  Waktu: $(date '+%Y-%m-%d %H:%M:%S')"
info "======================================================="
