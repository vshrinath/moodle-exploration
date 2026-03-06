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
    return 0
  fi
  echo "Generating .env..."
  ./scripts/generate-env.sh
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

  echo "Provisioning complete."
}

main "$@"
