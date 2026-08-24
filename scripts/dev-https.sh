#!/usr/bin/env bash
# =============================================================================
# ARIKARTECH — Local HTTPS Development Stack
# =============================================================================
# Architecture:
#   Internal HTTP                    →  TLS Proxy  →  Exposed HTTPS
#   http://127.0.0.1:3001 (Next.js)  →  :3000      →  https://127.0.0.1:3000
#   http://127.0.0.1:8080 (Laravel)  →  :8000      →  https://127.0.0.1:8000
#   http://127.0.0.1:5174 (Vite)     →  :5173      →  https://127.0.0.1:5173
#
# Prerequisites:
#   Run once: export PATH="$HOME/.local/bin:$PATH" && mkcert -install
#
# Usage: npm run dev:https   OR   bash scripts/dev-https.sh
# =============================================================================

set -e
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CERT="$REPO_ROOT/.certs/local-cert.pem"
KEY="$REPO_ROOT/.certs/local-key.pem"

# ── Guard against re-entrant cleanup ──────────────────────────────────────────
_CLEANING=0

cleanup() {
  if [[ $_CLEANING -eq 1 ]]; then return; fi
  _CLEANING=1
  echo ""
  echo "Stopping ARIKARTECH HTTPS dev stack..."
  # Kill tracked child PIDs directly — avoid killing the parent shell
  for pid in "${CHILD_PIDS[@]}"; do
    kill "$pid" 2>/dev/null || true
  done
  wait 2>/dev/null || true
  exit 0
}
trap cleanup EXIT INT TERM

# ── Cert check ────────────────────────────────────────────────────────────────
if [[ ! -f "$CERT" || ! -f "$KEY" ]]; then
  echo ""
  echo "❌  Certificates not found at .certs/"
  echo "    Run: export PATH=\"\$HOME/.local/bin:\$PATH\""
  echo "         mkcert -key-file .certs/local-key.pem -cert-file .certs/local-cert.pem 127.0.0.1 localhost ::1"
  exit 1
fi

# ── Clear all required ports ───────────────────────────────────────────────────
echo "Clearing ports 3000 3001 5173 5174 8000 8080..."
fuser -k 3000/tcp 3001/tcp 5173/tcp 5174/tcp 8000/tcp 8080/tcp 2>/dev/null || true
sleep 1

echo ""
echo "  ┌──────────────────────────────────────────────────────────────────┐"
echo "  │          ARIKARTECH — Local HTTPS Development Stack             │"
echo "  │                                                                  │"
echo "  │   Public:   https://127.0.0.1:3000  (Next.js 15)               │"
echo "  │   API:      https://127.0.0.1:8000  (Laravel 11)               │"
echo "  │   Admin:    https://127.0.0.1:5173  (Vite/React)               │"
echo "  └──────────────────────────────────────────────────────────────────┘"
echo ""

CHILD_PIDS=()

# ── 1. Laravel artisan serve (internal :8080) ─────────────────────────────────
echo "[1/4] Laravel API    http://127.0.0.1:8080 (internal)"
(cd "$REPO_ROOT/backend" && php artisan serve --host=127.0.0.1 --port=8080 2>&1 | sed 's/^/[laravel] /') &
CHILD_PIDS+=($!)

sleep 2

# ── 2. TLS reverse proxy (:3000/:8000/:5173) ──────────────────────────────────
echo "[2/4] HTTPS Proxy    :3000/:8000/:5173  (TLS termination)"
(cd "$REPO_ROOT" && node scripts/local-https-proxy.mjs 2>&1 | sed 's/^/[proxy]  /') &
CHILD_PIDS+=($!)

sleep 1

# ── 3. Next.js (internal :3001) ───────────────────────────────────────────────
echo "[3/4] Next.js        http://127.0.0.1:3001 (internal)"
(cd "$REPO_ROOT/public" && npx next dev -p 3001 -H 127.0.0.1 2>&1 | sed 's/^/[next]   /') &
CHILD_PIDS+=($!)

# ── 4. Vite admin (internal :5174) ────────────────────────────────────────────
echo "[4/4] Vite Admin     http://127.0.0.1:5174 (internal)"
(cd "$REPO_ROOT/admin" && npx vite --port 5174 --host 127.0.0.1 2>&1 | sed 's/^/[vite]   /') &
CHILD_PIDS+=($!)

echo ""
echo "  Waiting for services to be ready..."
sleep 4
echo ""
echo "  ✅  All services started."
echo ""
echo "  Open in browser:"
echo "    https://127.0.0.1:3000   — Public storefront"
echo "    https://127.0.0.1:5173   — Admin dashboard"
echo "    https://127.0.0.1:8000   — API"
echo ""
echo "  Press Ctrl+C to stop all services."
echo ""

wait
