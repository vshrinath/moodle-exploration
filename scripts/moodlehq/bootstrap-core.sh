#!/usr/bin/env bash
set -euo pipefail

MOODLE_CORE_DIR="${MOODLE_CORE_DIR:-moodle-core}"
MOODLE_GIT_BRANCH="${MOODLE_GIT_BRANCH:-MOODLE_501_STABLE}"
MOODLE_REPO_URL="${MOODLE_REPO_URL:-https://github.com/moodle/moodle.git}"

if [ -f "${MOODLE_CORE_DIR}/version.php" ]; then
  echo "Moodle core already present at ${MOODLE_CORE_DIR}; skipping clone."
  exit 0
fi

if [ -e "${MOODLE_CORE_DIR}" ] && [ ! -d "${MOODLE_CORE_DIR}" ]; then
  echo "${MOODLE_CORE_DIR} exists and is not a directory. Remove it and retry." >&2
  exit 1
fi

mkdir -p "${MOODLE_CORE_DIR}"

echo "Cloning Moodle core (${MOODLE_GIT_BRANCH}) into ${MOODLE_CORE_DIR}..."
rm -rf "${MOODLE_CORE_DIR}"

if git clone --depth 1 --branch "${MOODLE_GIT_BRANCH}" "${MOODLE_REPO_URL}" "${MOODLE_CORE_DIR}"; then
  echo "Moodle core cloned with host git."
else
  echo "Host git clone failed. Retrying with containerized git..."
  rm -rf "${MOODLE_CORE_DIR}"
  docker run --rm \
    -v "$(pwd):/work" \
    -w /work \
    alpine/git:2.47.2 \
    clone --depth 1 --branch "${MOODLE_GIT_BRANCH}" "${MOODLE_REPO_URL}" "${MOODLE_CORE_DIR}"
  echo "Moodle core cloned with containerized git."
fi

echo "Moodle core bootstrap complete."
