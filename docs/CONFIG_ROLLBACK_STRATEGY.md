# Configuration Rollback Strategy

**Purpose:** Version control, rollback procedures, and change management for configuration scripts  
**Audience:** System Admins, DevOps, Developers  
**Last Updated:** 2026-02-23

---

## Executive Summary

The 28 configuration scripts in `scripts/config/` modify critical system state (roles, capabilities, badges, rules). Currently, these scripts lack:
- Rollback mechanisms
- Version control for rule definitions
- Ability to disable rules without deletion
- Idempotency guarantees

This document establishes patterns for safe configuration changes with rollback capability.

---

## Current State Analysis

### Configuration Scripts Inventory

**28 scripts in `scripts/config/`:**
- Role configuration (5 scripts)
- Capability assignments (8 scripts)
- Badge system (3 scripts)
- Competency framework (4 scripts)
- Rules engine (6 scripts)
- Miscellaneous (2 scripts)

**Current Patterns:**
```php
// Typical script structure
$transaction = $DB->start_delegated_transaction();
try {
    $DB->insert_record('table', $data);  // No existence check
    $transaction->allow_commit();
} catch (Throwable $e) {
    $transaction->rollback($e);
}
```

**Issues:**
1. No idempotency (running twice creates duplicates)
2. No rollback after commit (transaction only protects single run)
3. No version tracking (can't identify which config version is deployed)
4. No disable mechanism (must delete to deactivate)

---

## Rollback Strategy Framework

### Principle 1: Configuration as Code

**All configuration changes must be:**
- Version controlled in git
- Tagged with semantic versions
- Documented with change rationale
- Reversible via rollback scripts

---

### Principle 2: Idempotent Operations

**Every config script must:**
- Check for existing state before creating
- Update existing records instead of duplicating
- Report current state vs. desired state
- Support `--dry-run` mode

**Pattern:**
```php
// Check-then-create pattern
$existing = $DB->get_record('badge', ['name' => $badge_name]);
if (!$existing) {
    $badge_id = $DB->insert_record('badge', $badge);
    echo "✓ Created badge: {$badge_name}\n";
} else {
    // Update if needed
    if ($existing->description !== $badge_description) {
        $existing->description = $badge_description;
        $DB->update_record('badge', $existing);
        echo "⊙ Updated badge: {$badge_name}\n";
    } else {
        echo "⊙ Badge already exists: {$badge_name} (no changes)\n";
    }
    $badge_id = $existing->id;
}
```

---

### Principle 3: Soft Deletes Over Hard Deletes

**Never delete configuration records. Instead:**
- Add `enabled` flag (default: 1)
- Add `archived` flag (default: 0)
- Add `deleted_at` timestamp (default: NULL)

**Benefits:**
- Can re-enable without recreating
- Preserves audit history
- Allows rollback by toggling flags

**Example:**
```php
// Disable rule instead of deleting
$DB->set_field('local_sceh_rules', 'enabled', 0, ['id' => $rule_id]);
echo "✓ Disabled rule: {$rule_name}\n";

// Re-enable later
$DB->set_field('local_sceh_rules', 'enabled', 1, ['id' => $rule_id]);
echo "✓ Re-enabled rule: {$rule_name}\n";
```

---

### Principle 4: Version Tracking

**Add version metadata to configuration tables:**

```sql
-- Attendance rules
ALTER TABLE mdl_local_sceh_attendance_rules 
ADD COLUMN config_version VARCHAR(20) DEFAULT '1.0.0',
ADD COLUMN deployed_at INT(10) DEFAULT NULL,
ADD COLUMN deployed_by INT(10) DEFAULT NULL;

-- Roster rules
ALTER TABLE mdl_local_sceh_roster_rules 
ADD COLUMN config_version VARCHAR(20) DEFAULT '1.0.0',
ADD COLUMN deployed_at INT(10) DEFAULT NULL,
ADD COLUMN deployed_by INT(10) DEFAULT NULL;
```

**Track in config script:**
```php
$config_version = '1.2.0';  // Semantic version
$rule->config_version = $config_version;
$rule->deployed_at = time();
$rule->deployed_by = $USER->id;
$DB->insert_record('local_sceh_rules', $rule);
```

---

## Rollback Mechanisms

### Level 1: Disable Without Deletion

**Use Case:** Temporarily deactivate a rule/badge/capability

**Implementation:**
```php
// scripts/config/disable_rule.php
<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php'); // TODO: Create this file
require_moodle_config();

global $DB;
init_cli_admin('moodle/site:config');

$rule_id = $argv[1] ?? null;
if (!$rule_id) {
    fwrite(STDERR, "Usage: php disable_rule.php <rule_id>\n");
    exit(1);
}

$rule = $DB->get_record('local_sceh_rules', ['id' => $rule_id], '*', MUST_EXIST);

echo "Disabling rule: {$rule->name} (ID: {$rule_id})\n";
echo "Current state: " . ($rule->enabled ? 'ENABLED' : 'DISABLED') . "\n";

if ($rule->enabled) {
    $DB->set_field('local_sceh_rules', 'enabled', 0, ['id' => $rule_id]);
    echo "✓ Rule disabled\n";
} else {
    echo "⊙ Rule already disabled\n";
}
```

**Rollback:**
```bash
# Disable rule
php scripts/config/disable_rule.php 123

# Re-enable rule
php scripts/config/enable_rule.php 123
```

**Time:** Instant (no data loss)

---

### Level 2: Database Snapshot Before Changes

**Use Case:** Major configuration changes (role restructure, badge system overhaul)

**Implementation:**
```bash
#!/bin/bash
# scripts/config/snapshot_before_change.sh

CONFIG_NAME=$1
SNAPSHOT_DIR="/backup/moodle/config_snapshots"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

mkdir -p ${SNAPSHOT_DIR}

# Snapshot relevant tables
mysqldump -u ${DB_USER} -p${DB_PASS} ${DB_NAME} \
    mdl_role \
    mdl_role_capabilities \
    mdl_badge \
    mdl_badge_criteria \
    mdl_local_sceh_rules \
    > ${SNAPSHOT_DIR}/${CONFIG_NAME}_${TIMESTAMP}.sql

echo "Snapshot saved: ${SNAPSHOT_DIR}/${CONFIG_NAME}_${TIMESTAMP}.sql"
echo ${SNAPSHOT_DIR}/${CONFIG_NAME}_${TIMESTAMP}.sql > /tmp/last_snapshot.txt
```

**Usage:**
```bash
# Before running config script
./scripts/config/snapshot_before_change.sh "badge_system_v2"

# Run config script
php scripts/config/configure_badge_system.php

# If something goes wrong, rollback
./scripts/config/rollback_from_snapshot.sh
```

**Rollback Script:**
```bash
#!/bin/bash
# scripts/config/rollback_from_snapshot.sh

LAST_SNAPSHOT=$(cat /tmp/last_snapshot.txt)

if [ ! -f "$LAST_SNAPSHOT" ]; then
    echo "ERROR: No snapshot found"
    exit 1
fi

echo "Rolling back from: $LAST_SNAPSHOT"
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled"
    exit 0
fi

# Restore snapshot
mysql -u ${DB_USER} -p${DB_PASS} ${DB_NAME} < ${LAST_SNAPSHOT}

echo "✓ Rollback complete"
```

**Time:** 5-10 minutes (depends on table size)

---

### Level 3: Git-Tagged Configuration Versions

**Use Case:** Track which configuration version is deployed

**Implementation:**

**1. Tag configuration scripts in git:**
```bash
# After testing config changes
git add scripts/config/
git commit -m "feat: badge system v2.0 with competency criteria"
git tag -a config-v2.0.0 -m "Badge system v2.0"
git push origin config-v2.0.0
```

**2. Record deployed version in database:**
```php
// Add to config_helper.php
function record_config_deployment($component, $version, $description) {
    global $DB, $USER;
    
    $record = new stdClass();
    $record->component = $component;
    $record->version = $version;
    $record->description = $description;
    $record->deployed_by = $USER->id;
    $record->deployed_at = time();
    $record->git_tag = exec('git describe --tags --always');
    
    $DB->insert_record('local_sceh_config_deployments', $record);
}

// Usage in config script
record_config_deployment('badge_system', '2.0.0', 'Added competency-based criteria');
```

**3. Create deployment tracking table:**
```sql
CREATE TABLE mdl_local_sceh_config_deployments (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    component VARCHAR(100) NOT NULL,
    version VARCHAR(20) NOT NULL,
    description TEXT,
    deployed_by BIGINT(10) NOT NULL,
    deployed_at BIGINT(10) NOT NULL,
    git_tag VARCHAR(50),
    PRIMARY KEY (id),
    KEY component_version (component, version)
);
```

**Rollback:**
```bash
# Check current version
php scripts/config/show_deployed_versions.php

# Rollback to previous version
git checkout config-v1.5.0
php scripts/config/configure_badge_system.php --rollback
```

**Time:** 10-15 minutes (re-run config scripts from old version)

---

### Level 4: Full System Restore

**Use Case:** Catastrophic configuration failure

**Implementation:** Restore from daily/weekly backup (see OPERATIONS_GUIDE.md)

**Time:** 1-2 hours

---

## Configuration Change Workflow

### Standard Change Process

**1. Pre-Change Checklist:**
```
□ Configuration change documented in git commit message
□ Dry-run executed successfully
□ Database snapshot created
□ Rollback plan documented
□ Change window scheduled (low-traffic time)
□ Stakeholders notified
```

**2. Execute Change:**
```bash
# Create snapshot
./scripts/config/snapshot_before_change.sh "rule_engine_v3"

# Dry-run
php scripts/config/configure_rules_engine.php --dry-run

# Review dry-run output
# If acceptable, apply changes
php scripts/config/configure_rules_engine.php

# Verify changes
php scripts/config/verify_rules_engine.php
```

**3. Post-Change Verification:**
```
□ Configuration applied successfully
□ No errors in Moodle logs
□ Smoke test passed (login, view course, check rules)
□ Deployment recorded in tracking table
□ Git tag created
□ Stakeholders notified of completion
```

**4. Rollback (if needed):**
```bash
# Immediate rollback (within 1 hour)
./scripts/config/rollback_from_snapshot.sh

# Delayed rollback (after 1 hour)
git checkout <previous_tag>
php scripts/config/configure_rules_engine.php
```

---

## Idempotency Patterns

### Pattern 1: Check-Then-Create

**Use for:** Badges, roles, rules

```php
$existing = $DB->get_record('badge', ['name' => $badge_name]);
if (!$existing) {
    $badge_id = $DB->insert_record('badge', $badge);
    echo "✓ Created: {$badge_name}\n";
} else {
    $badge_id = $existing->id;
    echo "⊙ Already exists: {$badge_name}\n";
}
```

---

### Pattern 2: Upsert (Update or Insert)

**Use for:** Configuration values, settings

```php
function upsert_config($name, $value, $plugin = null) {
    global $DB;
    
    $existing = $DB->get_record('config_plugins', [
        'plugin' => $plugin,
        'name' => $name
    ]);
    
    if ($existing) {
        if ($existing->value !== $value) {
            $existing->value = $value;
            $DB->update_record('config_plugins', $existing);
            echo "⊙ Updated: {$name} = {$value}\n";
        } else {
            echo "⊙ No change: {$name} = {$value}\n";
        }
    } else {
        $record = new stdClass();
        $record->plugin = $plugin;
        $record->name = $name;
        $record->value = $value;
        $DB->insert_record('config_plugins', $record);
        echo "✓ Created: {$name} = {$value}\n";
    }
}
```

---

### Pattern 3: Declarative State

**Use for:** Capabilities, role assignments

```php
// Desired state
$desired_capabilities = [
    'moodle/course:view' => CAP_ALLOW,
    'moodle/course:update' => CAP_ALLOW,
    'moodle/course:delete' => CAP_PREVENT,
];

// Current state
$current_capabilities = $DB->get_records('role_capabilities', [
    'roleid' => $role_id,
    'contextid' => $context_id
]);

// Reconcile
foreach ($desired_capabilities as $capability => $permission) {
    $current = $current_capabilities[$capability] ?? null;
    
    if (!$current || $current->permission !== $permission) {
        assign_capability($capability, $permission, $role_id, $context_id, true);
        echo "✓ Set: {$capability} = " . ($permission == CAP_ALLOW ? 'ALLOW' : 'PREVENT') . "\n";
    } else {
        echo "⊙ No change: {$capability}\n";
    }
}
```

---

## Version Control for Rules

### Rule Definition Versioning

**Add version tracking to rules table:**
```sql
ALTER TABLE mdl_local_sceh_attendance_rules 
ADD COLUMN version INT(10) DEFAULT 1,
ADD COLUMN previous_version_id BIGINT(10) DEFAULT NULL,
ADD COLUMN change_description TEXT;

ALTER TABLE mdl_local_sceh_roster_rules 
ADD COLUMN version INT(10) DEFAULT 1,
ADD COLUMN previous_version_id BIGINT(10) DEFAULT NULL,
ADD COLUMN change_description TEXT;
```

**Create new version instead of updating:**
```php
function create_rule_version($rule_id, $changes, $change_description) {
    global $DB, $USER;
    
    // Get current rule
    $current = $DB->get_record('local_sceh_rules', ['id' => $rule_id], '*', MUST_EXIST);
    
    // Disable current version
    $DB->set_field('local_sceh_rules', 'enabled', 0, ['id' => $rule_id]);
    
    // Create new version
    $new_rule = clone $current;
    unset($new_rule->id);
    $new_rule->version = $current->version + 1;
    $new_rule->previous_version_id = $rule_id;
    $new_rule->change_description = $change_description;
    $new_rule->timemodified = time();
    $new_rule->usermodified = $USER->id;
    
    // Apply changes
    foreach ($changes as $field => $value) {
        $new_rule->$field = $value;
    }
    
    $new_id = $DB->insert_record('local_sceh_rules', $new_rule);
    
    echo "✓ Created rule version {$new_rule->version} (ID: {$new_id})\n";
    echo "  Previous version {$current->version} (ID: {$rule_id}) disabled\n";
    
    return $new_id;
}

// Usage
$changes = ['conditions' => json_encode(['attendance_pct' => 85])];
create_rule_version(123, $changes, 'Increased attendance threshold to 85%');
```

**Rollback to previous version:**
```php
function rollback_rule_version($rule_id) {
    global $DB;
    
    $current = $DB->get_record('local_sceh_rules', ['id' => $rule_id], '*', MUST_EXIST);
    
    if (!$current->previous_version_id) {
        echo "✗ No previous version to rollback to\n";
        return false;
    }
    
    // Disable current version
    $DB->set_field('local_sceh_rules', 'enabled', 0, ['id' => $rule_id]);
    
    // Re-enable previous version
    $DB->set_field('local_sceh_rules', 'enabled', 1, ['id' => $current->previous_version_id]);
    
    echo "✓ Rolled back to version {$current->version - 1} (ID: {$current->previous_version_id})\n";
    echo "  Current version {$current->version} (ID: {$rule_id}) disabled\n";
    
    return true;
}

// Usage
rollback_rule_version(456);
```

---

## Configuration Testing

### Dry-Run Mode

**All config scripts must support `--dry-run`:**

```php
$dryrun = in_array('--dry-run', $argv);

if ($dryrun) {
    echo "=== DRY-RUN MODE ===\n";
    echo "No changes will be applied.\n\n";
}

// ... configuration logic ...

if (!$dryrun) {
    $DB->insert_record('badge', $badge);
    echo "✓ Created badge\n";
} else {
    echo "Would create badge: {$badge->name}\n";
}
```

---

### Verification Scripts

**Create verification script for each config script:**

```php
// scripts/config/verify_badge_system.php
<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php'); // TODO: Create this file
require_moodle_config();

global $DB;

echo "=== Badge System Verification ===\n\n";

$expected_badges = [
    'Allied Assist Completion',
    'Medical Assistant Completion',
    'Competency Master',
];

$issues = [];

foreach ($expected_badges as $badge_name) {
    $badge = $DB->get_record('badge', ['name' => $badge_name]);
    if (!$badge) {
        $issues[] = "Missing badge: {$badge_name}";
        echo "✗ {$badge_name}: NOT FOUND\n";
    } else {
        echo "✓ {$badge_name}: EXISTS (ID: {$badge->id})\n";
        
        // Check criteria
        $criteria = $DB->get_records('badge_criteria', ['badgeid' => $badge->id]);
        if (empty($criteria)) {
            $issues[] = "Badge {$badge_name} has no criteria";
            echo "  ✗ No criteria defined\n";
        } else {
            echo "  ✓ " . count($criteria) . " criteria defined\n";
        }
    }
}

echo "\n";

if (empty($issues)) {
    echo "✓ All checks passed\n";
    exit(0);
} else {
    echo "✗ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
    exit(1);
}
```

**Usage:**
```bash
# After applying config
php scripts/config/configure_badge_system.php
php scripts/config/verify_badge_system.php

# Exit code 0 = success, 1 = issues found
```

---

## Audit Trail

### Configuration Change Log

**Create audit table:**
```sql
CREATE TABLE mdl_local_sceh_config_audit (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    component VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    userid BIGINT(10) NOT NULL,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY component_action (component, action),
    KEY timecreated (timecreated)
);
```

**Log all configuration changes:**
```php
function log_config_change($component, $action, $details) {
    global $DB, $USER;
    
    $record = new stdClass();
    $record->component = $component;
    $record->action = $action;
    $record->details = json_encode($details);
    $record->userid = $USER->id;
    $record->timecreated = time();
    
    $DB->insert_record('local_sceh_config_audit', $record);
}

// Usage
log_config_change('badge_system', 'create_badge', [
    'badge_name' => 'Allied Assist Completion',
    'badge_id' => $badge_id,
]);
```

**Query audit log:**
```php
// scripts/config/show_config_history.php
$changes = $DB->get_records('local_sceh_config_audit', 
    ['component' => 'badge_system'], 
    'timecreated DESC', 
    '*', 
    0, 
    50
);

foreach ($changes as $change) {
    $user = $DB->get_record('user', ['id' => $change->userid]);
    $date = date('Y-m-d H:i:s', $change->timecreated);
    echo "[{$date}] {$user->username}: {$change->action}\n";
    echo "  " . json_encode(json_decode($change->details), JSON_PRETTY_PRINT) . "\n\n";
}
```

---

## Emergency Rollback Procedures

### Scenario 1: Bad Rule Breaks Automation

**Symptoms:**
- Rules engine failing
- Competencies not locking/unlocking
- Errors in audit log

**Immediate Action:**
```bash
# Disable problematic rule
php scripts/config/disable_rule.php <rule_id>

# Verify rule disabled
php scripts/config/verify_rules_engine.php
```

**Time:** 2 minutes

---

### Scenario 2: Badge Configuration Breaks Issuance

**Symptoms:**
- Badges not being awarded
- Badge criteria errors
- Users complaining about missing badges

**Immediate Action:**
```bash
# Rollback from snapshot
./scripts/config/rollback_from_snapshot.sh

# Or disable badge
php scripts/config/disable_badge.php <badge_id>
```

**Time:** 5-10 minutes

---

### Scenario 3: Role/Capability Change Breaks Access

**Symptoms:**
- Users can't access courses
- Trainers can't grade
- Permission denied errors

**Immediate Action:**
```bash
# Rollback from snapshot (fastest)
./scripts/config/rollback_from_snapshot.sh

# Or restore from git tag
git checkout <previous_tag>
php scripts/config/configure_roles.php
```

**Time:** 10-15 minutes

---

## Best Practices

### Do's

✅ **Always create snapshot before major changes**
✅ **Test in development environment first**
✅ **Use dry-run mode to preview changes**
✅ **Document rollback plan before applying**
✅ **Schedule changes during low-traffic windows**
✅ **Verify changes after applying**
✅ **Keep audit trail of all changes**
✅ **Use semantic versioning for config versions**

---

### Don'ts

❌ **Never run config scripts in production without testing**
❌ **Never delete configuration records (use soft delete)**
❌ **Never skip snapshot creation for "small" changes**
❌ **Never apply changes without rollback plan**
❌ **Never ignore verification script failures**
❌ **Never run multiple config scripts simultaneously**
❌ **Never modify production database directly (use scripts)**

---

## Recommended Improvements

### Immediate (Week 1)

1. ☐ Add `enabled` flag to all configuration tables
2. ☐ Implement snapshot scripts
3. ☐ Add dry-run mode to all config scripts
4. ☐ Create verification scripts for critical configs

### Short-Term (Month 1)

5. ☐ Implement version tracking for rules
6. ☐ Create audit log table and logging functions
7. ☐ Add idempotency to all config scripts
8. ☐ Document rollback procedures for each config type

### Long-Term (Quarter 1)

9. ☐ Build configuration management UI (view/rollback)
10. ☐ Implement automated testing for config changes
11. ☐ Add configuration drift detection
12. ☐ Create configuration documentation generator

---

## Summary

**Configuration changes are code changes.** They require:
- Version control (git tags)
- Testing (dry-run, verification)
- Rollback capability (snapshots, soft deletes)
- Audit trail (change log)
- Documentation (commit messages, release notes)

**Rollback Levels:**
1. Disable (instant, no data loss)
2. Snapshot restore (5-10 minutes)
3. Git tag rollback (10-15 minutes)
4. Full backup restore (1-2 hours)

**Key Principle:** Every configuration change must be reversible without data loss.

---

**Document Version:** 1.0  
**Last Updated:** 2026-02-23  
**Next Review:** 2026-05-23 (Quarterly)
