#!/usr/bin/env bash
set -euo pipefail

while [ ! -f /var/www/html/config.php ]; do
  echo "Waiting for Moodle config.php before starting cron..."
  sleep 5
done

interval="${SCEH_CRON_INTERVAL:-60}"

echo "Starting Moodle cron loop (every ${interval}s)..."
while true; do
  php /var/www/html/admin/cli/cron.php || true
  sleep "${interval}"
done
