#!/usr/bin/env bash
# =============================================================================
# ARIKARTECH — Local HTTPS Development Stack
# =============================================================================
# Starts all three services over HTTPS using shared mkcert certificates.
#
# Architecture:
#   Internal HTTP                   →  TLS Proxy  →  Exposed HTTPS
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

# Check certs exist
if [[ ! -f "$CERT" || ! -f "$KEY" ]]; then
  echo ""
  echo "❌  Certificates not found at .certs/"
  echo "    Run: export PATH=\"\$HOME/.local/bin:\$PATH\""
  echo "         mkcert -key-file .certs/local-key.pem -cert-file .certs/local-cert.pem 127.0.0.1 localhost ::1"
  exit 1
fi

# Cleanup child processes on exit
cleanup() {
  echo ""
  echo "Stopping ARIKARTECH HTTPS dev stack..."
  kill 0 2>/dev/null || true
}
trap cleanup EXIT INT TERM

echo ""
echo "  ┌──────────────────────────────────────────────────────────────────┐"
echo "  │          ARIKARTECH — Local HTTPS Development Stack             │"
echo "  │                                                                  │"
echo "  │   Public:   https://127.0.0.1:3000  (Next.js 15)               │"
echo "  │   API:      https://127.0.0.1:8000  (Laravel 11)               │"
echo "  │   Admin:    https://127.0.0.1:5173  (Vite/React)               │"
echo "  └──────────────────────────────────────────────────────────────────┘"
echo ""

# 1. Laravel artisan serve on internal HTTP :8080
echo "[1/4] Laravel API    http://127.0.0.1:8080 (internal)"
(cd "$REPO_ROOT/backend" && php artisan serve --host=127.0.0.1 --port=8080 2>&1 | sed 's/^/[laravel] /') &

sleep 2

# 2. TLS reverse proxy (terminates TLS for all three services)
echo "[2/4] HTTPS Proxy    :3000/:8000/:5173  (TLS termination)"
(cd "$REPO_ROOT" && node scripts/local-https-proxy.mjs 2>&1 | sed 's/^/[proxy]  /') &

sleep 1

# 3. Next.js on internal HTTP :3001
echo "[3/4] Next.js        http://127.0.0.1:3001 (internal)"
(cd "$REPO_ROOT/public" && npx next dev -p 3001 -H 127.0.0.1 2>&1 | sed 's/^/[next]   /') &

# 4. Vite admin on internal HTTP :5174
echo "[4/4] Vite Admin     http://127.0.0.1:5174 (internal)"
(cd "$REPO_ROOT/admin" && npx vite --port 5174 --host 127.0.0.1 2>&1 | sed 's/^/[vite]   /') &

echo ""
echo "  Waiting for services to be ready..."
sleep 4
echo ""
echo "  ✅  All services started."
echo ""
echo "  Open in browser (after running mkcert -install):"
echo "    https://127.0.0.1:3000   — Public storefront"
echo "    https://127.0.0.1:5173   — Admin dashboard"
echo "    https://127.0.0.1:8000   — API"
echo ""
echo "  Press Ctrl+C to stop all services."
echo ""

# Wait for all background jobs
wait
