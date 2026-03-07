#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${ROOT_DIR}"

COMPOSE_FILE="docker-compose.moodlehq.yml"

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Missing required command: $1"
}

check_prereqs() {
  need_cmd docker
  need_cmd git
  [ -f "${COMPOSE_FILE}" ] || fail "Missing ${COMPOSE_FILE}"
  docker compose version >/dev/null 2>&1 || fail "docker compose is not available."
}

ensure_env() {
  if [ -f ".env" ]; then
    echo ".env already exists."
    ensure_standard_pass
    return 0
  fi
  echo "Generating .env..."
  ./scripts/generate-env.sh
}

ensure_standard_pass() {
  local new_pass="Test@2026!"
  if grep -Fq "MOODLEHQ_ADMIN_PASS=${new_pass}" .env; then
    return 0
  fi
  echo "Updating .env to use standard developer password: ${new_pass}"
  # Use a portable sed approach for macOS vs Linux
  if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s/^MOODLEHQ_ADMIN_PASS=.*/MOODLEHQ_ADMIN_PASS=${new_pass}/" .env
  else
    sed -i "s/^MOODLEHQ_ADMIN_PASS=.*/MOODLEHQ_ADMIN_PASS=${new_pass}/" .env
  fi
}

main() {
  check_prereqs

  ensure_env

  echo "Stopping Docker stack before bootstrap..."
  docker compose -f "${COMPOSE_FILE}" down

  echo "Bootstrapping Moodle core..."
  ./scripts/moodlehq/bootstrap-core.sh

  echo "Starting Docker stack..."
  docker compose -f "${COMPOSE_FILE}" up -d

  echo "Applying custom state restore..."
  ./scripts/moodlehq/restore-custom-state.sh

  echo "Validating plugin lock..."
  ./scripts/moodlehq/validate-plugin-lock.sh

  echo "Provisioning complete."
}

main "$@"
