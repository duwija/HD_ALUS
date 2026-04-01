#!/usr/bin/env bash
# =============================================================================
#  deploy-all.sh — Jalankan dari laptop/server master untuk deploy ke SEMUA server
#
#  Syarat:
#    - SSH key sudah ter-copy ke semua server (ssh-copy-id root@IP)
#    - Setiap server sudah diinit dengan server-init.sh
#
#  Gunakan:
#    ./scripts/deploy-all.sh              (deploy branch main ke semua server)
#    ./scripts/deploy-all.sh hotfix/v2    (branch tertentu)
#    ./scripts/deploy-all.sh main server1 server2  (server tertentu saja)
# =============================================================================
set -euo pipefail

BRANCH="${1:-main}"
APP_DIR="/var/www/kencana.alus.co.id"
SSH_USER="root"
SSH_OPTS="-o StrictHostKeyChecking=no -o ConnectTimeout=10"

# ── DAFTAR SERVER ─────────────────────────────────────────────────────────────
# Format: "alias|IP_atau_hostname"
# Edit sesuai server production Anda
SERVERS=(
    "server1|192.168.1.10"
    "server2|192.168.1.11"
    "server3|192.168.1.12"
    # "server4|192.168.1.13"
    # tambah server di sini
)

# Jika argumen ke-2 dst ada, deploy hanya ke server tersebut
if [ $# -gt 1 ]; then
    FILTER_SERVERS=("${@:2}")
    FILTERED=()
    for s in "${SERVERS[@]}"; do
        alias="${s%%|*}"
        for f in "${FILTER_SERVERS[@]}"; do
            if [ "$alias" = "$f" ]; then
                FILTERED+=("$s")
            fi
        done
    done
    SERVERS=("${FILTERED[@]}")
fi

# Warna
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${GREEN}[MASTER]${NC} $*"; }
ok()      { echo -e "${GREEN}[  OK  ]${NC} $*"; }
fail()    { echo -e "${RED}[ FAIL ]${NC} $*"; }
section() { echo -e "\n${BLUE}══════════════════════════════════════════${NC}"; echo -e "${BLUE}  $*${NC}"; echo -e "${BLUE}══════════════════════════════════════════${NC}"; }

FAILED_SERVERS=()
DEPLOY_LOG="/tmp/deploy-all-$(date +%Y%m%d_%H%M%S).log"

section "DEPLOY DIMULAI | branch: $BRANCH | $(date '+%Y-%m-%d %H:%M:%S')"
info "Target: ${#SERVERS[@]} server(s)"
info "Log: $DEPLOY_LOG"
echo ""

for server_entry in "${SERVERS[@]}"; do
    alias="${server_entry%%|*}"
    host="${server_entry##*|}"

    echo -e "${YELLOW}▶ Deploy ke $alias ($host)...${NC}"

    if ssh $SSH_OPTS "$SSH_USER@$host" \
        "bash $APP_DIR/scripts/deploy.sh $BRANCH" \
        2>&1 | tee -a "$DEPLOY_LOG" | grep -E "DEPLOY|ERROR|WARN|OK"; then
        ok "$alias ($host) — BERHASIL"
    else
        fail "$alias ($host) — GAGAL"
        FAILED_SERVERS+=("$alias ($host)")
    fi
    echo ""
done

# ── Ringkasan ─────────────────────────────────────────────────────────────────
section "RINGKASAN DEPLOY"
TOTAL=${#SERVERS[@]}
FAILED=${#FAILED_SERVERS[@]}
SUCCESS=$((TOTAL - FAILED))

echo -e "  Total server  : $TOTAL"
echo -e "  ${GREEN}Berhasil${NC}      : $SUCCESS"
echo -e "  ${RED}Gagal${NC}         : $FAILED"

if [ ${#FAILED_SERVERS[@]} -gt 0 ]; then
    echo ""
    echo -e "${RED}Server yang gagal:${NC}"
    for s in "${FAILED_SERVERS[@]}"; do
        echo -e "  - $s"
    done
    echo ""
    echo -e "Lihat log lengkap: $DEPLOY_LOG"
    exit 1
fi

echo ""
info "Semua server berhasil di-deploy!"
