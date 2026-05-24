#!/usr/bin/env bash
# Smoke test for the staging environment

set -euo pipefail

STAGING_URL="${STAGING_URL:-https://staging.clickykeys.fun}"
ENDPOINT="${STAGING_URL}/api/healthz.php"
TIMEOUT="${TIMEOUT:-10}"

BODY_FILE="$(mktemp)"
trap 'rm -f "${BODY_FILE}"' EXIT

echo "→ GET ${ENDPOINT}"

# -s: silent, -o: write body to file, -w: print status code to stdout,
# --max-time: hard cap on the whole request.
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

# Payload contract: {"status":"ok","service":"php-fpm"}
if ! grep -q '"status":"ok"' "${BODY_FILE}"; then
  echo "✗ FAIL: response does not contain expected payload"
  echo "Response body:"
  cat "${BODY_FILE}"
  exit 1
fi

echo "✓ PASS: staging responds with healthy payload"
echo "Body: $(cat "${BODY_FILE}")"
