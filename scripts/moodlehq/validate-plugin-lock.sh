#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "${ROOT_DIR}"

LOCK_FILE="${LOCK_FILE:-scripts/moodlehq/plugins.lock}"

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

trim() {
  local s="$1"
  s="${s#"${s%%[![:space:]]*}"}"
  s="${s%"${s##*[![:space:]]}"}"
  printf "%s" "$s"
}

extract_component() {
  local file="$1"
  sed -n "s/^[[:space:]]*\\\$plugin->component[[:space:]]*=[[:space:]]*'\\([^']*\\)'.*/\\1/p" "$file" | head -n1
}

extract_version() {
  local file="$1"
  sed -n "s/^[[:space:]]*\\\$plugin->version[[:space:]]*=[[:space:]]*\\([^;]*\\);.*/\\1/p" "$file" | head -n1
}

[ -f "${LOCK_FILE}" ] || fail "Missing lock file: ${LOCK_FILE}"

while IFS='|' read -r raw_component raw_path raw_version raw_artifact || [ -n "${raw_component:-}" ]; do
  line="$(trim "${raw_component}")"
  [ -z "${line}" ] && continue
  [[ "${line}" == \#* ]] && continue

  component="$(trim "${raw_component}")"
  relpath="$(trim "${raw_path}")"
  expected_version="$(trim "${raw_version}")"
  artifact="$(trim "${raw_artifact:-}")"

  version_file="${relpath}/version.php"
  [ -f "${version_file}" ] || fail "Missing version.php for ${component} at ${version_file}"

  actual_component="$(extract_component "${version_file}")"
  [ -n "${actual_component}" ] || fail "Could not parse component from ${version_file}"
  [ "${actual_component}" = "${component}" ] || fail "Component mismatch at ${version_file}: expected ${component}, got ${actual_component}"

  actual_version="$(extract_version "${version_file}")"
  [ -n "${actual_version}" ] || fail "Could not parse version from ${version_file}"
  [ "${actual_version}" = "${expected_version}" ] || \
    fail "Version mismatch for ${component}: expected ${expected_version}, got ${actual_version}"

  if [ "${artifact}" != "-" ] && [ -n "${artifact}" ]; then
    [ -f "${artifact}" ] || fail "Pinned artifact missing for ${component}: ${artifact}"
  fi
done < "${LOCK_FILE}"

echo "Plugin lock validation passed."
