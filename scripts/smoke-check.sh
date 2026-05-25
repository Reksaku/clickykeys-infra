#!/usr/bin/env bash
# Usage:
#   BASE_URL=https://staging.example.com ./scripts/smoke-check.sh
#   BASE_URL=https://example.com TIMEOUT=15 ./scripts/smoke-check.sh

set -euo pipefail

BASE_URL="${BASE_URL:?BASE_URL is required}"
ENDPOINT="${BASE_URL%/}/api/healthz.php"
TIMEOUT="${TIMEOUT:-10}"
BODY_FILE="$(mktemp)"
trap 'rm -f "${BODY_FILE}"' EXIT

echo "→ GET ${ENDPOINT}"

STATUS=$(curl -s -o "${BODY_FILE}" \
  -w "%{http_code}" \
  --max-time "${TIMEOUT}" \
  "${ENDPOINT}" || true)

if [[ "${STATUS}" != "200" ]]; then
  echo "✗ FAIL: expected HTTP 200, got ${STATUS}"
  echo "Response body:"
  cat "${BODY_FILE}" || true
  exit 1
fi

# Payload contract from /api/healthz.php: {"status":"ok","service":"php-fpm"}
if ! grep -q '"status":"ok"' "${BODY_FILE}"; then
  echo "✗ FAIL: response does not contain expected payload"
  cat "${BODY_FILE}"
  exit 1
fi

echo "✓ PASS: ${BASE_URL} responds with healthy payload"
echo "Body: $(cat "${BODY_FILE}")"
