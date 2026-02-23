# Architectural Retrospective: SCEH Moodle Implementation

**Date:** 2026-02-23  
**Reviewer:** Senior Software Architect (Kiro initial draft, Antigravity audit addenda)  
**Scope:** Docker deployment, Rules Engine, plugin architecture, security, scalability

---

## Executive Summary

This retrospective identifies critical risks, optimization opportunities, and refactoring recommendations for the SCEH Moodle deployment. The system is functional but exhibits brittleness in event-driven automation, incomplete state separation, and insufficient observability. The next Moodle upgrade will expose weaknesses in plugin coupling and transaction safety.

**Priority:** Address Critical Risks (1-5) before next production deployment.

---

## CRITICAL RISKS

### 1. Rules Engine as Event-Driven Plugin vs. Core Integration
**Risk:** The `local_sceh_rules` plugin implements business logic through Moodle's event system (observers watching attendance/roster events). This creates a fragile coupling where:
- Event schema changes in Moodle core or third-party plugins break your automation
- No transactional guarantees between event trigger and rule execution
- Observer execution order is undefined—if multiple observers modify the same competency, race conditions emerge

**Second-order effect:** During Moodle upgrades (4.x → 5.x → 6.x), event signatures change. You'll discover broken automation in production when attendance marking silently fails to trigger competency locks. The audit log will show gaps, but users won't notice until compliance audits.

**Evidence:** `local_sceh_rules/classes/engine/rule_evaluator.php` has no retry logic, no idempotency checks, no dead-letter queue for failed evaluations.

**Recommendation:**
- Add idempotency keys to rule evaluations (check if already processed)
- Implement retry logic with exponential backoff
- Add circuit breaker pattern (disable rule after N consecutive failures)
- Create monitoring dashboard for rule execution success/failure rates

---

### 2. Docker State Separation Is Incomplete
**Risk:** While DB and moodledata are in named volumes, the application layer mounts local directories:
```yaml
volumes:
  - ./local_sceh_rules:/var/www/html/public/local/sceh_rules
  - ./block_sceh_dashboard:/var/www/html/public/blocks/sceh_dashboard
```
This means:
- Plugin code is NOT in the container image—it's bind-mounted from host
- Container is not self-contained; you cannot `docker pull` and run elsewhere
- Version drift between environments (dev has different plugin code than prod)

**Second-order effect:** Zero-touch recovery is impossible. Your disaster recovery procedure requires:
1. Restore volumes (DB + moodledata) ✓
2. Clone git repo with exact commit SHA
3. Manually mount correct directories
4. Hope file permissions align

A true immutable infrastructure would bake plugins into the image at build time.

**Recommendation:**
- Create custom Docker image that copies plugins into image:
```dockerfile
FROM moodlehq/moodle-php-apache:8.2
COPY ./local_sceh_rules /var/www/html/public/local/sceh_rules
COPY ./block_sceh_dashboard /var/www/html/public/blocks/sceh_dashboard
RUN chown -R www-data:www-data /var/www/html/public
```
- Tag images with git commit SHA for traceability
- Use bind mounts only in development, not production

---

### 3. Environment Variable Security Theater
**Risk:** You moved from Docker secrets to `.env` files, which is a lateral move, not an upgrade:
- `.env` files are plaintext on disk
- No encryption at rest
- No audit trail of who accessed secrets
- `generate-env.sh` creates secrets but doesn't rotate them

**Second-order effect:** When you deploy to Azure/AWS, you'll need to refactor again to use Key Vault/Secrets Manager. The current approach is dev-friendly but not prod-ready. If the host is compromised, all secrets are readable.

**Recommendation:**
- Development: Current `.env` approach is acceptable
- Production: Migrate to proper secrets management:
  - Azure: Azure Key Vault
  - AWS: AWS Secrets Manager
  - Self-hosted: HashiCorp Vault
- Implement secret rotation (90-day cycle)
- Add audit logging for secret access

> [!WARNING]
> **Addendum (code audit):** `.env` currently contains *both* Bitnami-era credentials (`MARIADB_PASSWORD`, `MARIADB_ROOT_PASSWORD`) and MoodleHQ credentials (`MOODLEHQ_DB_PASSWORD`, `MOODLEHQ_DB_ROOT_PASSWORD`). The Bitnami passwords are orphaned — no service references them — but they sit on disk as free credentials for anyone who reads the file. Remove the `MARIADB_*` entries immediately. Additionally, `MOODLEHQ_DB_ROOT_PASSWORD` is passed to the Moodle web container via `environment:`, but the web container has no legitimate use for root DB access. Strip it from the web/cron services.

---

### 4. Badge System Transaction Handling Is Inconsistent
**Risk:** `scripts/config/configure_badge_system.php` wraps badge creation in a transaction, but the actual badge awarding logic (triggered by competency completion) has no such protection:
- Badge criteria evaluation happens outside transactions
- If badge award fails mid-process, you get partial state (competency marked, badge not awarded)
- No compensating transactions

**Second-order effect:** Learners will report "I completed the competency but didn't get the badge." You'll need manual SQL fixes to reconcile state, which violates audit requirements.

**Evidence:**
```php
// configure_badge_system.php has transaction
$transaction = $DB->start_delegated_transaction();
try {
    // ... badge creation
    $transaction->allow_commit();
} catch (Throwable $e) {
    $transaction->rollback($e);
}

// But actual badge awarding (in observers) has no transaction wrapper
```

**Recommendation:**
- Wrap all badge awarding logic in transactions
- Add reconciliation script to detect and fix orphaned states:
  - Competencies marked complete but no badge awarded
  - Badges awarded but competency not complete
- Run reconciliation weekly as scheduled task

---

### 5. No Circuit Breaker for Rules Engine
**Risk:** If the rules engine encounters a bad rule (e.g., malformed competency ID), it will:
- Fail silently (only logs to audit table)
- Continue processing other rules
- No alerting to admins

**Second-order effect:** A single typo in a rule configuration can silently break automation for weeks. You'll only discover it when a learner complains they can't access content despite meeting attendance requirements.

**Evidence:** `local_sceh_rules/classes/engine/rule_evaluator.php` has no failure threshold logic.

**Recommendation:**
- Implement circuit breaker pattern:
  - Track failure count per rule
  - After 5 consecutive failures, disable rule and alert admin
  - Require manual re-enable after fix
- Add admin dashboard showing rule health:
  - Success rate (last 24h, 7d, 30d)
  - Last execution time
  - Failure count
  - Status (active/disabled/circuit-open)

---

### 5a. Cron Container Has No DB Credentials and Fails Silently
**Risk:** The `moodle_cron` service in `docker-compose.moodlehq.yml` receives **zero `SCEH_DB_*` environment variables**. It relies entirely on `config.php` being present (written by `start-web.sh` on first boot). If the web container is rebuilt before cron starts, or `config.php` is regenerated/deleted, cron enters an infinite sleep loop at `start-cron.sh` line 4 and **never processes tasks**.

**Second-order effect:** Moodle's cron handles badge issuance, completion checks, ad-hoc tasks, cache purging, and messaging. All of these fail silently — `start-cron.sh` swallows errors with `|| true` on line 13. You will not discover this until a user reports missing badges or a compliance check reveals unprocessed competency evaluations.

**Evidence:** `start-cron.sh` line 13: `php /var/www/html/admin/cli/cron.php || true`

**Recommendation:**
- Pass `SCEH_DB_*` variables to the cron container as defense-in-depth
- Add a healthcheck to the cron container that verifies `config.php` exists AND a DB query succeeds
- Replace `|| true` with proper error logging: `|| echo "Cron failed at $(date)" >&2`

---

### 5b. `reevaluate_course_attendance()` Is O(rules × enrolled_users) and Synchronous
**Risk:** In `attendance_observer.php` (lines 98-129), every `attendance_taken` event triggers a nested loop: fetch all enabled attendance rules for the course, then iterate **every enrolled user** against **every rule**. This runs synchronously inside the web request — the trainer who submitted attendance waits for it to complete.

**Second-order effect:** A course with 200 fellows and 5 attendance rules triggers 1,000 rule evaluations (each with DB reads + potential writes) on a single attendance submission. This causes request timeouts, DB connection pool exhaustion, and potential deadlocks with `transaction_helper`. The problem scales quadratically with enrollment.

**Evidence:**
```php
// attendance_observer.php lines 117-126
foreach ($rules as $rule) {
    foreach ($enrolledusers as $user) {
        $evaluator->evaluate($user->id, $rule);
    }
}
```

**Recommendation:** This is the strongest argument for item #6 (async task pattern). The observer should queue a single adhoc task with `{courseid}` payload; the task runs via cron and evaluates at its own pace.

---

### 5c. Roster Observer `determine_roster_type_from_appointment()` Is a Placeholder — Roster Rules Never Fire
**Risk:** `roster_observer.php` (lines 90-116) contains a method explicitly documented as "This is a placeholder." It queries `{data_content}` / `{data_fields}` — tables belonging to `mod_data`, not `mod_scheduler`. The event `\mod_scheduler\event\appointment_added` does **not** populate `other['rostertype']` by default.

**Second-order effect:** The entire roster→competency progression pipeline is **inert in production**. No roster event will successfully resolve a roster type, so no roster rule will ever fire, and no competency evidence will ever be generated from roster completions. The audit log will show zero roster rule evaluations — but only if someone thinks to check.

**Evidence:** `roster_observer.php` line 105 comment: `// This is a placeholder - actual implementation would depend on`

**Recommendation:**
- Implement the actual query against `mod_scheduler` tables (`{scheduler_appointment}`, `{scheduler_slots}`)
- Add an integration test that fires a real `appointment_added` event and asserts the roster rule executed
- Until implemented, add a `debugging()` warning so the gap is visible during development

---

## OPTIMIZATION OPPORTUNITIES

### 6. Rules Engine Should Be a Scheduled Task, Not Event-Driven
**Current:** Observers fire on every attendance/roster event (real-time)

**Better:** Scheduled task runs every 15 minutes, processes pending evaluations in batch

**Why:**
- Decouples from event system fragility
- Allows retry logic and error handling
- Enables rate limiting (don't hammer DB on bulk attendance import)
- Easier to test (trigger task manually vs. simulating events)

**Tradeoff:** 15-minute delay in rule application. But real-time isn't required—trainers don't mark attendance and immediately expect competency locks.

**Implementation:**
```php
// Create scheduled task
class evaluate_rules extends \core\task\scheduled_task {
    public function execute() {
        // Get pending evaluations from queue table
        // Process in batch with transaction per rule
        // Update audit log
    }
}

// Queue evaluations from observers instead of executing
class attendance_observer {
    public static function attendance_taken($event) {
        // Insert into queue table instead of evaluating immediately
        $DB->insert_record('local_sceh_rules_queue', [
            'ruletype' => 'attendance',
            'userid' => $event->userid,
            'timecreated' => time()
        ]);
    }
}
```

---

### 7. Configuration Scripts Should Be Idempotent
**Current:** Scripts like `configure_badge_system.php` create records without checking existence:
```php
$badge_id = $DB->insert_record('badge', $badge);
```
Running twice creates duplicates.

**Better:** Check-then-create pattern:
```php
$existing = $DB->get_record('badge', ['name' => $template['name']]);
if (!$existing) {
    $badge_id = $DB->insert_record('badge', $badge);
} else {
    echo "  ⊙ Badge already exists: {$template['name']} (ID: {$existing->id})\n";
    $badge_id = $existing->id;
}
```

**Why:** Enables safe re-runs after partial failures. Current approach requires manual cleanup.

**Scope:** Apply to all 28 scripts in `scripts/config/`

---

### 8. Backup Strategy Excludes Plugin Code
**Risk:** Your backup scripts only capture DB + moodledata. Custom plugins (`local_sceh_rules`, `block_sceh_dashboard`) are in git but not in backups.

**Better:** Include `/var/www/html/public/local/` and `/var/www/html/public/blocks/` in weekly backups, or commit to "infrastructure as code" and version everything in git with tagged releases.

**Why:** If you lose the git repo (or forget which commit was deployed), you cannot restore the system even with perfect DB/file backups.

**Recommendation:**
- Add to weekly backup script:
```bash
# Backup custom plugins
tar -czf ${BACKUP_DIR}/plugins_${DATE}.tar.gz \
    /var/www/html/public/local/sceh_* \
    /var/www/html/public/blocks/sceh_* \
    /var/www/html/public/theme/sceh
```
- OR: Tag git releases and document deployed version in config:
```php
// config.php
$CFG->sceh_version = 'v1.2.3'; // Git tag
```

---

### 9. No Health Checks for Rules Engine
**Current:** Cron runs rules engine, but no monitoring of success/failure rates

**Better:** Add Prometheus metrics or simple log parsing:
- Rules evaluated per hour
- Rules failed per hour
- Average evaluation time
- Alert if failure rate >5%

**Why:** Silent failures are the enemy. You need observability before you need debugging.

**Implementation:**
```php
// Add to rule_evaluator.php
protected function record_metric($metric_name, $value) {
    set_config($metric_name, $value, 'local_sceh_rules');
}

// In evaluate()
$start = microtime(true);
try {
    $result = $this->evaluate($userid, $rule);
    $this->record_metric('rules_success_count', 
        get_config('local_sceh_rules', 'rules_success_count') + 1);
} catch (Exception $e) {
    $this->record_metric('rules_failure_count',
        get_config('local_sceh_rules', 'rules_failure_count') + 1);
}
$duration = microtime(true) - $start;
$this->record_metric('rules_avg_duration', $duration);
```

---

### 9a. `local_sceh_importer/index.php` Is a 1,287-Line Monolith
**Risk:** `index.php` handles upload, ZIP extraction, manifest building, validation UI rendering, activity selection with versioning logic, modal dialogs, and import execution — all in a single procedural file. The view layer (800+ lines of `html_writer` calls) is interspersed with business logic.

**Second-order effect:** Every change to the import UI risks breaking the upload/execute flow and vice versa. The file is untestable without a full HTTP request. The versioning logic (lines 179-204) and activity conflict detection (lines 206-230) are buried in procedural code with no unit tests.

**Recommendation:** Extract into: (a) a controller/handler class, (b) Mustache templates for the UI, (c) move versioning/conflict-detection into testable service classes.

---

### 9b. Hardcoded Role Short Names Bypass Moodle Capabilities
**Risk:** `local_sceh_importer/index.php` (lines 36-53) falls back to a raw SQL query checking `r.shortname IN ('sceh_program_owner', 'programowner')` when the standard capability check fails. This creates a second, parallel authorization path outside Moodle's capability system.

**Second-order effect:** Renaming or archiving either role silently denies importer access with no error. The pattern invites other developers to also bypass capabilities, eroding the authorization surface.

**Evidence:**
```php
$sql = "SELECT DISTINCT ra.id
          FROM {role_assignments} ra
          JOIN {role} r ON r.id = ra.roleid
         WHERE ra.userid = :userid
           AND r.shortname IN (:short1, :short2)";
```

**Recommendation:** Grant `local/sceh_importer:manage` to the `sceh_program_owner` role via `db/access.php` defaults. Remove the fallback SQL.

---

## REFACTORING RECOMMENDATIONS

### 10. Consolidate Duplicate Code in Configuration Scripts
**Evidence:** 28 scripts in `scripts/config/` follow identical patterns:
- Path detection (`/bitnami/moodle/config.php` vs. `./config.php`)
- Admin user initialization
- Error handling

**Refactor:** You already created `scripts/lib/config_helper.php`—good. Now:
1. Extract common patterns into `config_helper.php`:
   - `run_with_transaction($callback)`
   - `ensure_capability($capability)`
   - `log_config_change($component, $action)`
2. Reduce each config script to 20 lines of business logic

**Why:** Current duplication means bug fixes require 28 file edits. Centralized helpers mean one fix propagates everywhere.

**Example:**
```php
// config_helper.php
function run_with_transaction($callback, $description) {
    global $DB;
    $transaction = $DB->start_delegated_transaction();
    try {
        $result = $callback();
        $transaction->allow_commit();
        echo "✓ {$description}\n";
        return $result;
    } catch (Throwable $e) {
        $transaction->rollback($e);
        echo "✗ {$description} failed: {$e->getMessage()}\n";
        exit(1);
    }
}

// configure_badge_system.php (simplified)
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();
$admin = init_cli_admin('moodle/site:config');

run_with_transaction(function() {
    // Badge creation logic only
}, 'Badge system configuration');
```

---

### 11. Rules Engine Should Use Moodle's Task API, Not Custom Tables
**Current:** `local_sceh_rules_audit` table stores rule execution history

**Better:** Use Moodle's `task_adhoc` and `task_log` tables:
- Adhoc tasks for rule evaluations (queued, retryable)
- Task logs for audit trail (built-in, indexed, searchable)

**Why:** You're reinventing Moodle's task system. Leverage existing infrastructure instead of maintaining parallel systems.

**Implementation:**
```php
// Queue adhoc task instead of direct evaluation
$task = new \local_sceh_rules\task\evaluate_attendance_rule();
$task->set_custom_data([
    'ruleid' => $rule->id,
    'userid' => $userid,
]);
\core\task\manager::queue_adhoc_task($task);

// Task execution is logged automatically by Moodle
// View in: Site administration → Server → Tasks → Adhoc tasks
```

**Benefits:**
- Built-in retry mechanism
- Failure tracking
- Performance monitoring
- Admin UI for viewing/managing tasks

---

### 12. Plugin Dependencies Are Implicit, Not Explicit
**Risk:** `local_sceh_rules` depends on:
- Attendance plugin (for attendance rules)
- Scheduler plugin (for roster rules)
- Competency framework (core)

But `version.php` doesn't declare these dependencies:
```php
$plugin->dependencies = [
    'mod_attendance' => 2024051300,
    'mod_scheduler' => 2024051300,
];
```

**Second-order effect:** Installing `local_sceh_rules` on a fresh Moodle without attendance plugin will silently fail. No error, just broken automation.

**Recommendation:**
- Add to `local_sceh_rules/version.php`:
```php
$plugin->dependencies = [
    'mod_attendance' => ANY_VERSION, // Or specific version
];
```
- Add runtime checks in observers:
```php
public static function attendance_taken($event) {
    if (!$DB->get_manager()->table_exists('attendance_sessions')) {
        debugging('Attendance plugin not installed', DEBUG_DEVELOPER);
        return;
    }
    // ... rest of logic
}
```

---

### 13. Block XP/Stash vs. Native Gamification
**Analysis:** You're using third-party plugins (`block_xp`, `block_stash`) for gamification. Tradeoffs:

**Pros:**
- Rich feature set (leaderboards, items, drops)
- Active maintenance (2025 releases)
- Proven in production environments

**Cons:**
- Upgrade risk (plugins lag behind Moodle core by 6-12 months)
- Vendor lock-in (data schema is plugin-specific)
- Performance overhead (additional DB queries per page load)
- Migration complexity if plugin abandoned

**Native alternative:** Moodle's core badges + competencies can achieve 80% of gamification needs:
- Badges for achievements
- Competency levels for progression
- Course completion for milestones

**Recommendation:** 
- **If <6 months into deployment:** Consider migrating to native features. Cost: 2-3 weeks development + testing.
- **If >6 months in with active users:** Stick with Block XP/Stash. Migration cost outweighs benefit. Budget for upgrade delays (expect 3-6 month lag after Moodle major releases).

**Decision criteria:**
- User count with XP data: If <100, migration is feasible
- User count with XP data: If >500, migration is prohibitively expensive

---

### 14. Kirkpatrick ROI Plugin Has No Data Validation
**Risk:** `local_kirkpatrick_level4` scheduled tasks sync external data:
```php
public function sync_external_data() {
    // No validation of external data format
    // No schema versioning
    // No rollback on bad data
}
```

**Second-order effect:** If external system changes data format (e.g., CSV column order), sync silently imports garbage. ROI calculations become meaningless, but no alerts fire.

**Refactor:** Add:
1. **Schema validation:**
```php
private function validate_external_data($data) {
    $required_fields = ['learner_id', 'outcome_metric', 'date', 'value'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new moodle_exception('missing_field', 'local_kirkpatrick_level4', '', $field);
        }
    }
    // Validate data types
    if (!is_numeric($data['value'])) {
        throw new moodle_exception('invalid_value', 'local_kirkpatrick_level4');
    }
}
```

2. **Dry-run mode:**
```php
public function sync_external_data($dry_run = false) {
    $data = $this->fetch_external_data();
    $this->validate_external_data($data);
    
    if ($dry_run) {
        echo "Validation passed. Would import " . count($data) . " records.\n";
        return;
    }
    
    // Actual import
}
```

3. **Rollback mechanism:**
```php
// Keep previous import until new one validates
$transaction = $DB->start_delegated_transaction();
try {
    $this->archive_previous_import();
    $this->import_new_data($data);
    $transaction->allow_commit();
} catch (Exception $e) {
    $transaction->rollback($e);
    $this->restore_previous_import();
}
```

---

### 15. Docker Compose Uses `restart: unless-stopped` Without Health Checks
**Risk:** Containers restart automatically, but no health checks verify they're actually working:
```yaml
moodle:
  restart: unless-stopped
  # Missing: healthcheck
```

**Better:**
```yaml
moodle:
  restart: unless-stopped
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost/login/index.php"]
    interval: 30s
    timeout: 10s
    retries: 3
    start_period: 60s

mysql:
  restart: unless-stopped
  healthcheck:
    test: ["CMD-SHELL", "mysqladmin ping -h 127.0.0.1 -u$$MYSQL_USER -p$$MYSQL_PASSWORD --silent"]
    interval: 10s
    timeout: 5s
    retries: 20
```

**Why:** A container can be "running" but Moodle is crashed. Health checks prevent false positives and enable proper orchestration (moodle waits for mysql to be healthy, not just running).

**Note:** MySQL health check already exists in `docker-compose.moodlehq.yml` (lines 17-21). The Moodle web container also already uses `depends_on: mysql: condition: service_healthy` — so the MySQL→Moodle startup ordering is correct. What's missing is a health check on the **Moodle** container itself and on the **cron** container.

---

## ARCHITECTURAL DECISIONS REVIEW

### 16. Rules Engine Pattern: Plugin vs. Core Modification
**Decision:** Implemented as local plugin using event observers

**Tradeoff Analysis:**
- **Pro:** Version-controlled, upgradeable, doesn't modify core
- **Pro:** Can be disabled/enabled without code changes
- **Pro:** Follows Moodle plugin architecture best practices
- **Con:** Fragile coupling to event system
- **Con:** No transactional guarantees
- **Con:** Observer execution order undefined

**When this becomes wrong:** When Moodle changes event signatures (every major version) or when you need guaranteed execution order.

**Verdict:** Correct choice for maintainability, but needs hardening (retry logic, idempotency, monitoring). The alternative (core modification) would be unmaintainable across upgrades.

---

### 17. MoodleHQ Docker vs. Bitnami
**Decision:** Switched from Bitnami to MoodleHQ images

**Tradeoff Analysis:**
- **Pro:** Official images, better Moodle version alignment
- **Pro:** Closer to production Moodle deployments
- **Pro:** Simpler image (less abstraction)
- **Con:** Less opinionated (more manual config)
- **Con:** Smaller community than Bitnami
- **Con:** Fewer convenience scripts

**Verdict:** Correct for long-term supportability. Bitnami is convenient but adds abstraction layers that complicate debugging. MoodleHQ images are closer to "vanilla" Moodle, making troubleshooting easier.

---

### 18. Competency Framework Depth
**Decision:** Hierarchical competency tree (3+ levels deep)

**Tradeoff Analysis:**
- **Pro:** Mirrors real-world skill taxonomy
- **Pro:** Enables granular tracking
- **Pro:** Supports progressive disclosure in UI
- **Con:** Complex UI for trainers
- **Con:** Slow queries on deep trees (recursive CTEs)
- **Con:** Difficult to refactor once populated

**Performance impact:**
- Depth 3: Acceptable (<100ms queries)
- Depth 5: Marginal (100-500ms queries)
- Depth 7+: Problematic (>500ms queries, consider denormalization)

**Verdict:** Acceptable if tree depth <5 levels. Beyond that, consider:
- Flattening hierarchy
- Using tags instead of parent-child relationships
- Denormalizing common queries (materialized paths)

---

### 19. Bitnami Symlink Legacy in `start-web.sh`
**Decision:** `start-web.sh` (lines 23-28) creates `/bitnami/moodle` and `/opt/bitnami/moodle` symlinks pointing to `/var/www/html/public`, maintaining backward compatibility with the former Bitnami-based setup.

**Tradeoff Analysis:**
- **Pro:** Prevents breakage of any scripts still referencing Bitnami paths
- **Con:** New developers will encounter Bitnami paths and assume a Bitnami installation
- **Con:** Symlinks mask the real filesystem, complicating debugging
- **Con:** Writing to `/bitnami/moodle/` expecting Bitnami's writable structure may silently succeed but produce unexpected results

**Verdict:** Audit all scripts for Bitnami path references, update them to actual paths (`/var/www/html/public`), and remove the symlinks in the next release cycle.

---

### 20. Root-Level File Sprawl
**Finding:** The repo root contains 30+ `CONFIG_*.txt`, `VERIFY_*.txt`, `TASK_*_COMPLETION_REPORT.md`, and `CHECKPOINT_*` files alongside actual source code. These are operational artifacts from past configuration runs.

**Second-order effect:** Developers waste time distinguishing instructions-to-run from records-of-past-runs. This directly violates `AGENTS.md` rule #14 ("No file sprawl — documentation only in `/docs/`").

**Recommendation:** Move all operational/verification records into `docs/operational-logs/` or `artifacts/`. Keep the root clean: `README.md`, `AGENTS.md`, `CONVENTIONS.md`, `docker-compose.*.yml`, `.env.example`.

---

## SUMMARY

### Critical Risks (Fix Before Next Production Deployment)
1. **Rules engine lacks retry/idempotency** → Add task queue with retry logic
2. **Docker state separation incomplete** → Bake plugins into custom image
3. **Plaintext secrets with orphaned Bitnami creds** → Remove dead creds, strip root DB password from web/cron containers *(addendum 3a)*
4. **No circuit breaker for rules engine** → Add failure rate monitoring and auto-disable
5. **Badge awarding lacks transaction safety** → Wrap in DB transactions
5a. **Cron container missing DB env vars** → Add `SCEH_DB_*` and proper error logging *(addendum)*
5b. **Synchronous O(rules×users) evaluation loop** → Move to adhoc task *(addendum)*
5c. **Roster observer is a placeholder — rules never fire** → Implement actual roster-type resolution *(addendum)*
6. **Plugin dependencies undeclared** → Add to `version.php`

### Optimization Opportunities (Fix Next Quarter)
7. **Rules engine should be scheduled task** → Decouple from event system
8. **Configuration scripts need idempotency** → Check-then-create pattern
9. **Backup strategy excludes plugin code** → Include in weekly backups
9a. **Importer monolith (1,287 lines)** → Extract controller/renderer/service classes *(addendum)*
9b. **Hardcoded role bypass in importer** → Use Moodle capabilities properly *(addendum)*
10. **No health checks for rules engine** → Add metrics and monitoring

### Refactoring Recommendations (Technical Debt)
11. **Consolidate duplicate code** → Expand `config_helper.php` usage
12. **Use Moodle's task API** → Replace custom audit tables
13. **Add schema validation** → Kirkpatrick external data sync
14. **Add Docker health checks** → Verify container health, not just running
15. **Evaluate Block XP/Stash** → If early deployment, consider native features

### Architectural Decisions (Acceptable with Caveats)
16. **Rules engine as plugin:** Correct, but needs hardening
17. **MoodleHQ Docker:** Correct for long-term supportability
18. **Competency hierarchy:** Acceptable if depth <5 levels
19. **Bitnami symlink legacy:** Audit and remove in next release cycle *(addendum)*
20. **Root-level file sprawl:** Violates AGENTS.md rule #14, move to `docs/` *(addendum)*

---

## NEXT STEPS

### Immediate (This Sprint)
1. Add idempotency to rules engine evaluations
2. Declare plugin dependencies in `version.php`
3. Add Docker health check for Moodle container
4. Document deployed git commit SHA in production

### Short-term (Next Month)
5. Implement circuit breaker for rules engine
6. Wrap badge awarding in transactions
7. Add rules engine monitoring dashboard
8. Make configuration scripts idempotent

### Medium-term (Next Quarter)
9. Refactor rules engine to scheduled task pattern
10. Migrate to custom Docker image (bake plugins in)
11. Add schema validation to Kirkpatrick sync
12. Implement backup verification automation

### Long-term (Next 6 Months)
13. Evaluate Block XP/Stash migration (if <6 months deployed)
14. Plan for Moodle 6.x upgrade (test event compatibility)
15. Implement proper secrets management for production
16. Add comprehensive integration test suite
17. Clean up root-level file sprawl per AGENTS.md rule #14
18. Remove Bitnami symlinks after verifying no scripts reference them

---

## CONCLUSION

The system is functional but exhibits brittleness in three key areas:

1. **Event-driven automation** is fragile and will break during Moodle upgrades
2. **State separation** is incomplete, preventing true immutable infrastructure
3. **Observability** is insufficient, leading to silent failures

The architecture is sound for a v1.0 deployment, but production hardening requires:
- Transactional safety
- Retry mechanisms
- Health monitoring
- Immutable infrastructure

Prioritize Critical Risks 1-5 before the next Moodle major version upgrade. The current implementation will survive Moodle 5.x minor updates but may break on 6.x without hardening.

**Estimated effort to address all critical risks:** 3-4 weeks (1 developer)

**Risk if unaddressed:** Silent automation failures, data inconsistency, difficult disaster recovery

---

**Document Version:** 1.0  
**Last Updated:** 2026-02-23  
**Next Review:** Before Moodle 6.x upgrade
