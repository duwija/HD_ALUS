#!/usr/bin/env bash
set -euo pipefail

REMOTE_HOST="${REMOTE_HOST:-103.156.74.19}"
REMOTE_USER="${REMOTE_USER:-lubax}"
REMOTE_PORT="${REMOTE_PORT:-22}"
REMOTE_DIR="${REMOTE_DIR:-/home/lubax/wa-gateway-baileys}"
MANAGER_PORT="${MANAGER_PORT:-30120}"
MANAGER_HOST="${MANAGER_HOST:-0.0.0.0}"
MANAGER_TOKEN="${MANAGER_TOKEN:-}"

SSH_TARGET="${REMOTE_USER}@${REMOTE_HOST}"

sync_files() {
  if ssh -p "${REMOTE_PORT}" "${SSH_TARGET}" "command -v rsync >/dev/null 2>&1"; then
    rsync -avz --delete \
      --exclude ".git" \
      --exclude "node_modules" \
      --exclude "auth_*" \
      --exclude "sessions_*.json" \
      --exclude "cache_*.json" \
      --exclude "*.log" \
      -e "ssh -p ${REMOTE_PORT}" \
      ./ "${SSH_TARGET}:${REMOTE_DIR}/"
    return
  fi

  echo "Remote rsync not found, using tar-over-ssh fallback"
  ssh -p "${REMOTE_PORT}" "${SSH_TARGET}" "mkdir -p '${REMOTE_DIR}'"
  tar \
    --exclude=".git" \
    --exclude="node_modules" \
    --exclude="auth_*" \
    --exclude="sessions_*.json" \
    --exclude="cache_*.json" \
    --exclude="*.log" \
    -czf - . | ssh -p "${REMOTE_PORT}" "${SSH_TARGET}" "tar -xzf - -C '${REMOTE_DIR}'"
}

echo "[1/4] Sync files to ${SSH_TARGET}:${REMOTE_DIR}"
sync_files

echo "[2/4] Install dependencies on remote"
ssh -p "${REMOTE_PORT}" "${SSH_TARGET}" \
  "cd '${REMOTE_DIR}' && npm install --omit=dev"

echo "[3/4] Start/restart PM2 process wa-manager"
ssh -p "${REMOTE_PORT}" "${SSH_TARGET}" bash <<EOF
set -euo pipefail
cd "${REMOTE_DIR}"
export MANAGER_PORT="${MANAGER_PORT}"
export MANAGER_HOST="${MANAGER_HOST}"
export MANAGER_TOKEN="${MANAGER_TOKEN}"

if pm2 describe wa-manager >/dev/null 2>&1; then
  pm2 restart wa-manager --update-env
else
  pm2 start manager-server.js --name wa-manager --time --update-env
fi

pm2 save
EOF

echo "[4/4] Health check"
ssh -p "${REMOTE_PORT}" "${SSH_TARGET}" \
  "curl -fsS 'http://127.0.0.1:${MANAGER_PORT}/health'"

echo "Done. Manager should be reachable at: http://${REMOTE_HOST}:${MANAGER_PORT}"
