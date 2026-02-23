#!/usr/bin/env bash
set -euo pipefail

# Wait for config.php to exist (written by start-web.sh on first boot).
echo "Cron: waiting for Moodle config.php..."
while [ ! -f /var/www/html/config.php ]; do
  sleep 5
done

# Verify DB connectivity before entering the loop.
echo "Cron: verifying database connectivity..."
if ! php -r "
  define('CLI_SCRIPT', true);
  require('/var/www/html/config.php');
  global \$DB;
  \$DB->get_record_sql('SELECT 1');
  echo 'DB OK';
" 2>/dev/null; then
  echo "Cron: ERROR - cannot connect to database. Check SCEH_DB_* env vars." >&2
  exit 1
fi

interval="${SCEH_CRON_INTERVAL:-60}"

echo "Cron: starting loop (every ${interval}s)..."
while true; do
  php /var/www/html/admin/cli/cron.php || echo "Cron: ERROR at $(date)" >&2
  sleep "${interval}"
done
