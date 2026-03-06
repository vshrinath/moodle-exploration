#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${ROOT_DIR}"

COMPOSE_FILE="docker-compose.moodlehq.yml"
MOODLE_CONTAINER="moodlehq-dev-moodle-1"
QUESTIONNAIRE_REPO="${QUESTIONNAIRE_REPO:-https://github.com/PoetOS/moodle-mod_questionnaire.git}"
CONFIG_REPORTS_ZIP="${CONFIG_REPORTS_ZIP:-plugin-source/block_configurable_reports_moodle45_2024051300.zip}"

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

require_file() {
  local path="$1"
  [ -f "$path" ] || fail "Missing required file: $path"
}

run_moodle_php() {
  local script="$1"
  shift
  docker exec -w / "${MOODLE_CONTAINER}" php "${script}" "$@"
}

load_env_file() {
  local env_file="$1"
  [ -f "$env_file" ] || fail "Missing .env file at $env_file"

  while IFS= read -r line || [ -n "$line" ]; do
    case "$line" in
      ""|\#*) continue ;;
    esac
    local key="${line%%=*}"
    local value="${line#*=}"
    export "${key}=${value}"
  done < "$env_file"
}

ensure_special_char_password() {
  if [[ "${MOODLEHQ_ADMIN_PASS:-}" =~ [[:punct:]] ]]; then
    return 0
  fi
  fail "MOODLEHQ_ADMIN_PASS must include at least one special character."
}

ensure_configurable_reports() {
  if [ -f "blocks/configurable_reports/version.php" ]; then
    echo "Configurable Reports already present."
    return 0
  fi

  require_file "${CONFIG_REPORTS_ZIP}"
  echo "Extracting Configurable Reports plugin..."
  docker run --rm \
    --user "$(id -u):$(id -g)" \
    -v "${ROOT_DIR}:/work" \
    -w /work \
    --entrypoint sh \
    docker.io/moodlehq/moodle-php-apache:8.2 \
    -lc "rm -rf blocks/configurable_reports && unzip -q \"${CONFIG_REPORTS_ZIP}\" -d blocks"

  [ -f "blocks/configurable_reports/version.php" ] || fail "Configurable Reports extraction failed."
}

ensure_questionnaire() {
  if [ -f "mod/questionnaire/version.php" ]; then
    echo "Questionnaire already present."
    return 0
  fi

  if [ -d "mod/questionnaire" ] && [ -n "$(find mod/questionnaire -mindepth 1 -maxdepth 1 -print -quit)" ]; then
    fail "mod/questionnaire exists but is incomplete. Resolve manually, then rerun."
  fi

  echo "Cloning Questionnaire plugin..."
  rm -rf mod/questionnaire
  git clone --depth 1 "${QUESTIONNAIRE_REPO}" mod/questionnaire
  [ -f "mod/questionnaire/version.php" ] || fail "Questionnaire clone failed."
}

main() {
  require_file "${COMPOSE_FILE}"
  load_env_file "${ROOT_DIR}/.env"
  ensure_special_char_password

  ensure_configurable_reports
  ensure_questionnaire

  echo "Starting Docker stack..."
  docker compose -f "${COMPOSE_FILE}" up -d

  echo "Running Moodle upgrade..."
  run_moodle_php /var/www/html/admin/cli/upgrade.php --non-interactive

  echo "Finalizing admin setup..."
  run_moodle_php /var/www/html/admin/cli/reset_password.php \
    --username="${MOODLEHQ_ADMIN_USER}" \
    --password="${MOODLEHQ_ADMIN_PASS}"
  run_moodle_php /var/www/html/admin/cli/cfg.php --name=adminsetuppending --unset

  echo "Applying baseline and homepage/dashboard customizations..."
  run_moodle_php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=local
  run_moodle_php /var/www/html/public/scripts/add_dashboard_for_all.php
  run_moodle_php /var/www/html/admin/cli/cfg.php --name=theme --set=sceh
  run_moodle_php /var/www/html/admin/cli/purge_caches.php

  echo "Restore complete."
  echo "Check: ${MOODLEHQ_WWWROOT}"
  echo "User: ${MOODLEHQ_ADMIN_USER}"
}

main "$@"
