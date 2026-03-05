# Changelog

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
