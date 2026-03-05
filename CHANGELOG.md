# Changelog

## [2026-03-05] — Fixed Moodle build issues on Windows (122 plugins missing)

**Branch**: `master`

### What changed
- Corrected `dirroot` in `moodle-core/config.php` to point to `/public` subdirectory.
- Updated `docs/RELEASE_NOTES.md` with the fix details.
- Verified fix on Mac (OrbStack) and provided Windows verification steps.

### Why
Moodle on Windows was failing to resolve plugin paths because `dirroot` was incorrectly set to the parent directory instead of the `/public` directory where the core and plugins actually reside. This caused "122 plugins missing" and "misplaced plugin" errors.

### Files touched
- `moodle-core/config.php` — Corrected `$CFG->dirroot` path.
- `docs/RELEASE_NOTES.md` — Documented the fix.
- `CHANGELOG.md` — [NEW] Initialized changelog with this fix.
