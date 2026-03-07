# Changelog

## [2026-03-07] — Complete Cross-Platform Reliability (WSL & Server Readiness)

**Branch**: `fix/windows-dev-environment`

### What changed
- Fixed a silent fatal error causing Dashboard Quick Actions to disappear on fresh WSL and Server installs.
- Removed dependency on undefined custom capability in `block_sceh_dashboard` and replaced it with standard Moodle core capability checks.
- Made `chmod` configuration commands in `start-web.sh` non-fatal (`|| true`) to prevent Docker container crashes on restrictive host filesystems (like Windows/WSL).
- Replaced hardcoded localhost URLs in setup scripts with dynamic `$CFG->wwwroot` routes for accurate messaging across all deployments.
- Verified that all class filenames match Moodle's strict case-sensitive autoloading rules for Linux.
- Fixed non-portable `sed` regex in `scripts/moodlehq/validate-plugin-lock.sh` to work on both Mac (BSD) and Linux (GNU).
- Added a 5-minute wait loop in `scripts/moodlehq/restore-custom-state.sh` to prevent race conditions during fresh installs.
- Refactored `init_cli_admin` in `scripts/lib/config_helper.php` to bypass restrictive capability checks in CLI mode.
- **SSH/WSL Stability**: Prevented `provision.sh` from hanging over SSH remote sessions on Windows/WSL by explicitly disabling TTY allocation (`-T`) and closing `stdin` (`< /dev/null`) for long-running `docker exec` commands.
- Automated standard developer password (`Test@2026!`) across environments.
- **Login Stability**: Automated removal of "Force password change" and site policy nags for mock users in `restore-custom-state.sh`, preventing permission errors on WSL/Windows.
- Successfully provisioned the MoodleHQ MySQL stack on the `fix/windows-dev-environment` branch.

### Why
The Dashboard bug was caused by WSL encountering a fresh database build without historical cache, triggering a fatal capability missing error that macOS masked. The `chmod` operations previously crashed WSL containers because Windows maps volume permissions strictly, which wasn't an issue on Mac OrbStack. These changes ensure the provisioning process is fully resilient across macOS, WSL, Windows, and remote Linux servers. The admin password update was necessary to meet Moodle's default security policy. The `+` quantifier is not supported by standard BSD/Darwin `sed`, which caused the plugin validation to fail during provisioning on Mac. Reverting to `*` ensures the script is portable.

### Files touched
- `block_sceh_dashboard/block_sceh_dashboard.php` — Removed legacy custom capability checks to fix WSL crash.
- `scripts/moodlehq/start-web.sh` — Added `|| true` to `chmod` commands.
- `scripts/add_dashboard_block.php` — Dynamic URL output.
- `scripts/add_dashboard_for_all.php` — Dynamic URL output.
- `scripts/moodlehq/validate-plugin-lock.sh` — Portability fix for component/version extraction.

## [2026-03-05] — Fixed Moodle build issues on Windows (122 plugins missing)

**Branch**: `master`

### What changed
- Corrected `dirroot` logic in `scripts/moodlehq/start-web.sh` to point to `/public` subdirectory on container boot.
- Updated `docs/RELEASE_NOTES.md` with the automated fix details.
- Verified fix on Mac (OrbStack) and provided Windows verification steps.

### Why
Moodle on Windows was failing to resolve plugin paths because `dirroot` was incorrectly defaulting to the parent directory instead of the `/public` directory. Automating this in `start-web.sh` provides a robust, zero-config fix for all developers.

### Files touched
- `scripts/moodlehq/start-web.sh` — Automated `$CFG->dirroot` correction.
- `docs/RELEASE_NOTES.md` — Documented the fix.
- `CHANGELOG.md` — [NEW] Initialized changelog with this fix.
