#!/usr/bin/env bash
# =============================================================================
#  server-init.sh — Setup server BARU (jalankan sekali saja di server baru)
#  Jalankan sebagai root: bash server-init.sh
#
#  Yang dilakukan:
#  1. Install dependency (git, composer, php ext)
#  2. Clone repo dari GitHub
#  3. Copy .env (dari template atau dari server master)
#  4. Fix permission
#  5. Composer install + artisan setup
# =============================================================================
set -euo pipefail

APP_DIR="/var/www/kencana.alus.co.id"
GITHUB_REPO="https://github.com/duwija/HD_ALUS"   # ← sesuaikan jika perlu
WEB_USER="apache"
WEB_GROUP="apache"
BRANCH="main"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
info()    { echo -e "${GREEN}[INIT]${NC} $*"; }
warning() { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; exit 1; }
ask()     { echo -e "${YELLOW}[INPUT]${NC} $*"; }

# ── Cek root ──────────────────────────────────────────────────────────────────
[ "$(id -u)" -eq 0 ] || error "Jalankan sebagai root"

info "======================================================"
info "  Server Init: $(hostname)"
info "  Tanggal: $(date '+%Y-%m-%d %H:%M:%S')"
info "======================================================"

# ── 1. Pastikan prerequisite tersedia ────────────────────────────────────────
info "Mengecek prerequisite..."
for cmd in git php composer mysql; do
    if ! command -v "$cmd" &>/dev/null; then
        warning "$cmd belum terinstall. Install dengan: yum install $cmd / apt install $cmd"
    else
        info "  ✓ $cmd: $(${cmd} --version 2>&1 | head -1)"
    fi
done

# ── 2. Clone atau update repo ─────────────────────────────────────────────────
if [ -d "$APP_DIR/.git" ]; then
    info "Repo sudah ada di $APP_DIR, skip clone."
else
    info "Clone repo dari GitHub..."
    mkdir -p "$(dirname $APP_DIR)"
    git clone "$GITHUB_REPO" "$APP_DIR" --branch "$BRANCH"
fi
cd "$APP_DIR"

# ── 3. Setup .env ─────────────────────────────────────────────────────────────
if [ ! -f "$APP_DIR/.env" ]; then
    warning ".env belum ada!"
    if [ -f "$APP_DIR/.env.example" ]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
        warning ".env disalin dari .env.example — EDIT dulu sebelum lanjut!"
    else
        error ".env.example tidak ditemukan. Copy .env dari server lain secara manual."
    fi
    ask "Setelah edit .env, jalankan ulang script ini atau lanjutkan manual."
    exit 0
else
    info ".env sudah ada — OK"
fi

# ── 4. Composer install ───────────────────────────────────────────────────────
info "Composer install (no-dev)..."
composer install \
    --no-interaction \
    --no-dev \
    --optimize-autoloader \
    --no-scripts 2>&1 | tail -5

# ── 5. Generate app key (hanya jika belum ada) ────────────────────────────────
if grep -q "APP_KEY=$" "$APP_DIR/.env" || grep -q "^APP_KEY=base64:$" "$APP_DIR/.env" 2>/dev/null; then
    info "Generate app key..."
    php artisan key:generate --force
else
    info "APP_KEY sudah ada — OK"
fi

# ── 6. Storage link ───────────────────────────────────────────────────────────
info "Storage link..."
php artisan storage:link 2>/dev/null || warning "Storage link gagal (mungkin sudah ada)"

# ── 7. Migrate ────────────────────────────────────────────────────────────────
info "Database migrate..."
php artisan migrate --force --no-interaction

# ── 8. Cache ─────────────────────────────────────────────────────────────────
info "Cache config, route, view..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 9. Fix permissions ────────────────────────────────────────────────────────
info "Setting permissions..."

# Code: root:apache, 644/755
find "$APP_DIR" \
    -not -path "$APP_DIR/.git/*" \
    -not -path "$APP_DIR/storage/*" \
    -not -path "$APP_DIR/bootstrap/cache/*" \
    -not -name ".env" \
    -type f \
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

# Writable dirs: apache:apache, 775
chown -R "$WEB_USER":"$WEB_GROUP" "$APP_DIR/storage"
chown -R "$WEB_USER":"$WEB_GROUP" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage"
chmod -R 775 "$APP_DIR/bootstrap/cache"

# .env: hanya root bisa baca
chown root:root "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"
setfacl -m u:"$WEB_USER":r "$APP_DIR/.env" 2>/dev/null || chmod 644 "$APP_DIR/.env"

# Scripts executable
chmod +x "$APP_DIR/scripts/"*.sh 2>/dev/null || true

# ── 10. Selesai ───────────────────────────────────────────────────────────────
info "======================================================"
info "  Server Init SELESAI: $(hostname)"
info ""
info "  Checklist:"
info "  ✓ Repo di-clone/update"
info "  ✓ .env ada"
info "  ✓ Composer install"
info "  ✓ Migrate"
info "  ✓ Permission fixed"
info "======================================================"
info "Untuk deploy update di masa depan: ./scripts/deploy.sh"
