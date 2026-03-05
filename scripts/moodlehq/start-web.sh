#!/usr/bin/env bash
set -euo pipefail

if [ ! -f /var/www/html/version.php ] && [ ! -f /var/www/html/public/version.php ]; then
  echo "Moodle core missing at /var/www/html. Run scripts/moodlehq/bootstrap-core.sh first." >&2
  exit 1
fi

mkdir -p /var/www/moodledata
chown -R www-data:www-data /var/www/moodledata
chmod 0770 /var/www/moodledata

# Avoid 400 "Size of a request header field exceeds server limit"
# when localhost cookies from multiple dev stacks become large.
cat > /etc/apache2/conf-available/sceh-header-limits.conf <<'EOF'
LimitRequestFieldSize 65536
LimitRequestLine 65536
ServerName localhost
EOF
a2enconf sceh-header-limits >/dev/null

# Keep compatibility with existing project scripts that use Bitnami-style paths.
mkdir -p /bitnami /opt/bitnami
if [ ! -f /var/www/html/public/config.php ] && [ -f /var/www/html/config.php ]; then
  ln -snf /var/www/html/config.php /var/www/html/public/config.php
fi
ln -snf /var/www/html/public /bitnami/moodle
ln -snf /var/www/html/public /opt/bitnami/moodle

wait_for_db() {
  local host="$1" port="$2" user="$3" pass="$4" db="$5"
  for i in $(seq 1 60); do
    if DBH="$host" DBP="$port" DBU="$user" DBPW="$pass" DBN="$db" php -r '$h=getenv("DBH");$P=(int)getenv("DBP");$u=getenv("DBU");$p=getenv("DBPW");$d=getenv("DBN");$m=@new mysqli($h,$u,$p,$d,$P); if(!$m->connect_errno){$m->close(); exit(0);} exit(1);'; then
      return 0
    fi
    sleep 2
  done
  return 1
}

if ! wait_for_db "${SCEH_DB_HOST}" "${SCEH_DB_PORT}" "${SCEH_DB_USER}" "${SCEH_DB_PASSWORD}" "${SCEH_DB_NAME}"; then
  echo "Database did not become ready in time." >&2
  exit 1
fi

if [ ! -f /var/www/html/config.php ]; then
  echo "No config.php found; running initial Moodle install..."
  php /var/www/html/admin/cli/install.php \
    --non-interactive \
    --agree-license \
    --lang=en \
    --wwwroot="${SCEH_WWWROOT}" \
    --dataroot="${SCEH_DATAROOT}" \
    --dbtype=mysqli \
    --dbhost="${SCEH_DB_HOST}" \
    --dbname="${SCEH_DB_NAME}" \
    --dbuser="${SCEH_DB_USER}" \
    --dbpass="${SCEH_DB_PASSWORD}" \
    --dbport="${SCEH_DB_PORT}" \
    --fullname="${SCEH_FULLNAME}" \
    --shortname="${SCEH_SHORTNAME}" \
    --adminuser="${SCEH_ADMIN_USER}" \
    --adminpass="${SCEH_ADMIN_PASS}" \
    --adminemail="${SCEH_ADMIN_EMAIL}"
else
  echo "config.php found; skipping automatic upgrade on boot."
  echo "Run manual upgrade when needed: php /var/www/html/admin/cli/upgrade.php --non-interactive"
fi

# Fix potential permission and path issues on config.php (common on Windows/WSL2 host mounts)
if [ -f /var/www/html/config.php ]; then
  chmod 644 /var/www/html/config.php
  
  # Ensure dirroot points to/public subdirectory to resolve missing plugins issue
  if ! grep -q "dirroot.*=.*__DIR__" /var/www/html/config.php; then
    echo "Correcting dirroot in config.php to include /public..."
    # Replace existing dirroot or append it if for some reason it's missing (unlikely after install)
    if grep -q "\$CFG->dirroot" /var/www/html/config.php; then
      sed -i "s|^\$CFG->dirroot.*=.*|\$CFG->dirroot = __DIR__ . '/public';|" /var/www/html/config.php
    else
      # Append before the last line (usually require_once)
      sed -i "/require_once/i \$CFG->dirroot = __DIR__ . '/public';" /var/www/html/config.php
    fi
  fi
fi

exec apache2-foreground
