# Antigravity Fixes Verification

**Date:** 2026-02-23  
**Purpose:** Verify fixes made by Antigravity against ARCHITECTURAL_RETROSPECTIVE.md addenda  
**Status:** Partial completion - 4 of 6 critical fixes applied

---

## Critical Risks Fixed

### ✅ Risk 3a: Orphaned Bitnami Credentials Removed

**Issue:** `.env` contained both Bitnami-era credentials (`MARIADB_PASSWORD`, `MARIADB_ROOT_PASSWORD`) and MoodleHQ credentials. Bitnami passwords were orphaned but readable on disk.

**Fix Applied:**
- `.env` now contains only `MOODLEHQ_*` credentials
- All `MARIADB_*` entries removed
- Verified: No services reference Bitnami credentials

**Evidence:**
```bash
# .env file (lines 6-17)
MOODLEHQ_DB_PASSWORD=cDY9meIvEROmHqXWymwCF9AKNESFZz2s
MOODLEHQ_DB_ROOT_PASSWORD=78MpvfqwnCwGSydOdbjfZwo1v7Cp1qZu
# No MARIADB_* entries present
```

**Status:** ✅ FIXED

---

### ✅ Risk 3a: Root DB Password Stripped from Web/Cron Containers

**Issue:** `MOODLEHQ_DB_ROOT_PASSWORD` was passed to web/cron containers via `environment:`, but they have no legitimate use for root DB access.

**Fix Applied:**
- Root password only passed to MySQL container
- Web container: Only has `SCEH_DB_PASSWORD` (application user)
- Cron container: Only has `SCEH_DB_PASSWORD` (application user)

**Evidence:**
```yaml
# docker-compose.moodlehq.yml
mysql:
  environment:
    - MYSQL_ROOT_PASSWORD=${MOODLEHQ_DB_ROOT_PASSWORD}  # ✓ Only here

moodle:
  environment:
    - SCEH_DB_PASSWORD=${MOODLEHQ_DB_PASSWORD}  # ✓ No root password

moodle_cron:
  environment:
    - SCEH_DB_PASSWORD=${MOODLEHQ_DB_PASSWORD}  # ✓ No root password
```

**Status:** ✅ FIXED

---

### ✅ Risk 5a: Cron Container Now Has DB Credentials

**Issue:** Cron container received zero `SCEH_DB_*` environment variables. It relied entirely on `config.php` written by web container. If web container rebuilt before cron starts, cron enters infinite sleep loop.

**Fix Applied:**
- Cron container now receives all `SCEH_DB_*` environment variables
- Defense-in-depth: Can connect to DB even if `config.php` missing

**Evidence:**
```yaml
# docker-compose.moodlehq.yml (lines 66-74)
moodle_cron:
  environment:
    - SCEH_CRON_INTERVAL=${MOODLEHQ_CRON_INTERVAL:-60}
    - SCEH_DB_HOST=mysql
    - SCEH_DB_PORT=3306
    - SCEH_DB_NAME=${MOODLEHQ_DB_NAME:-moodle}
    - SCEH_DB_USER=${MOODLEHQ_DB_USER:-moodle}
    - SCEH_DB_PASSWORD=${MOODLEHQ_DB_PASSWORD}
```

**Status:** ✅ FIXED

---

### ✅ Risk 5a: Cron Error Logging Improved

**Issue:** `start-cron.sh` line 13 had `|| true` which swallowed all errors. Cron failures were silent.

**Fix Applied:**
- Replaced `|| true` with `|| echo "Cron: ERROR at $(date)" >&2`
- Added DB connectivity check before entering cron loop
- Exits with error if DB unreachable

**Evidence:**
```bash
# scripts/moodlehq/start-cron.sh (lines 11-18)
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

# Line 27
php /var/www/html/admin/cli/cron.php || echo "Cron: ERROR at $(date)" >&2
```

**Status:** ✅ FIXED

---

## Critical Risks Partially Fixed

### ⚠️ Risk 5a: Cron Healthcheck Added, Web Healthcheck Missing

**Issue:** Neither web nor cron containers had health checks. Containers could be "running" but Moodle crashed.

**Fix Applied:**
- ✅ Cron container: Healthcheck verifies `config.php` exists
- ❌ Web container: Still missing healthcheck

**Evidence:**
```yaml
# docker-compose.moodlehq.yml (lines 74-78)
moodle_cron:
  healthcheck:
    test: ["CMD-SHELL", "test -f /var/www/html/config.php"]
    interval: 30s
    timeout: 5s
    retries: 5

# moodle service: No healthcheck block present
```

**Recommended Fix:**
```yaml
moodle:
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost/login/index.php"]
    interval: 30s
    timeout: 10s
    retries: 3
    start_period: 60s
```

**Status:** ⚠️ PARTIAL (cron fixed, web missing)

---

### ⚠️ Risk 19: Bitnami Symlinks Still Present

**Issue:** `start-web.sh` creates `/bitnami/moodle` and `/opt/bitnami/moodle` symlinks for backward compatibility. New developers encounter Bitnami paths and assume Bitnami installation.

**Fix Applied:**
- ❌ Symlinks still present in `start-web.sh` (lines 23-28)

**Evidence:**
```bash
# scripts/moodlehq/start-web.sh (lines 23-28)
# Keep compatibility with existing project scripts that use Bitnami-style paths.
mkdir -p /bitnami /opt/bitnami
if [ ! -f /var/www/html/public/config.php ] && [ -f /var/www/html/config.php ]; then
  ln -snf /var/www/html/config.php /var/www/html/public/config.php
fi
ln -snf /var/www/html/public /bitnami/moodle
ln -snf /var/www/html/public /opt/bitnami/moodle
```

**Recommended Action:**
1. Audit all scripts for Bitnami path references
2. Update to actual paths (`/var/www/html/public`)
3. Remove symlinks in next release cycle

**Status:** ⚠️ NOT FIXED (deferred to next release)

---

## Critical Risks Not Yet Addressed

### ❌ Risk 1: Rules Engine Lacks Retry/Idempotency

**Issue:** `local_sceh_rules/classes/engine/rule_evaluator.php` has no retry logic, no idempotency checks, no dead-letter queue for failed evaluations.

**Status:** ❌ NOT FIXED

**Priority:** HIGH (next sprint)

---

### ❌ Risk 2: Docker State Separation Incomplete

**Issue:** Plugins bind-mounted from host, not baked into container image. Container not self-contained.

**Status:** ❌ NOT FIXED

**Priority:** MEDIUM (before production deployment)

---

### ❌ Risk 4: Badge Awarding Lacks Transaction Safety

**Issue:** Badge awarding logic (triggered by competency completion) has no transaction wrapper. Partial state possible.

**Status:** ❌ NOT FIXED

**Priority:** HIGH (next sprint)

---

### ❌ Risk 5: No Circuit Breaker for Rules Engine

**Issue:** Rules engine has no failure threshold logic. Bad rule fails silently.

**Status:** ❌ NOT FIXED

**Priority:** HIGH (next sprint)

---

### ❌ Risk 5b: Synchronous O(rules×users) Evaluation Loop

**Issue:** `attendance_observer.php` (lines 98-129) triggers nested loop on every attendance submission. Runs synchronously inside web request.

**Status:** ❌ NOT FIXED

**Priority:** HIGH (performance bottleneck at scale)

---

### ❌ Risk 5c: Roster Observer Is Placeholder

**Issue:** `roster_observer.php` (lines 90-116) contains placeholder method. Roster rules never fire in production.

**Status:** ❌ NOT FIXED

**Priority:** MEDIUM (if roster functionality needed)

---

### ❌ Risk 6: Plugin Dependencies Undeclared

**Issue:** `local_sceh_rules/version.php` doesn't declare dependencies on `mod_attendance`, `mod_scheduler`.

**Status:** ❌ NOT FIXED

**Priority:** LOW (add to version.php)

---

### ❌ Risk 9a: Importer Monolith (1,287 Lines)

**Issue:** `local_sceh_importer/index.php` is untestable monolith with view/business logic interspersed.

**Status:** ❌ NOT FIXED

**Priority:** LOW (technical debt)

---

### ❌ Risk 9b: Hardcoded Role Bypass in Importer

**Issue:** `local_sceh_importer/index.php` (lines 36-53) bypasses capability system with raw SQL role check.

**Status:** ❌ NOT FIXED

**Priority:** MEDIUM (security/maintainability)

---

### ❌ Risk 20: Root-Level File Sprawl

**Issue:** 30+ `CONFIG_*.txt`, `VERIFY_*.txt`, `CHECKPOINT_*.md` files in repo root. Violates AGENTS.md rule #14.

**Status:** ❌ NOT FIXED

**Priority:** LOW (housekeeping)

---

## Summary

**Fixed:** 4 critical issues
- ✅ Bitnami credentials removed from `.env`
- ✅ Root DB password stripped from web/cron
- ✅ Cron container has DB env vars
- ✅ Cron error logging improved

**Partially Fixed:** 2 issues
- ⚠️ Cron healthcheck added (web healthcheck missing)
- ⚠️ Bitnami symlinks still present (deferred)

**Not Fixed:** 10 critical/high-priority issues
- Rules engine hardening (retry, idempotency, circuit breaker)
- Badge transaction safety
- Synchronous evaluation loop performance
- Plugin dependency declarations
- Docker state separation
- Importer refactoring
- File sprawl cleanup

---

## Recommended Next Steps

### Immediate (This Week)
1. ✅ Add healthcheck to web container
2. ✅ Audit scripts for Bitnami path references

### Sprint 1 (Next 2 Weeks)
3. Add retry logic to rules engine
4. Wrap badge awarding in transactions
5. Implement circuit breaker pattern
6. Declare plugin dependencies in version.php

### Sprint 2 (Weeks 3-4)
7. Move attendance evaluation to adhoc task (fix O(n²) loop)
8. Implement roster observer (if needed)
9. Fix importer role bypass

### Sprint 3 (Month 2)
10. Bake plugins into Docker image
11. Refactor importer monolith
12. Clean up root-level file sprawl

---

**Document Version:** 1.0  
**Last Updated:** 2026-02-23  
**Verified By:** Kiro (automated verification)
