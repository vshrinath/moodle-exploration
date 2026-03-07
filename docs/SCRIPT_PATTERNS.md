# Script Patterns — Configuration, Verification, and Testing

**Authoritative guide for writing maintainable automation scripts**

This document defines the mandatory patterns for all scripts in `scripts/config/`, `scripts/verify/`, and `scripts/test/`. Following these patterns ensures scripts are idempotent, debuggable, and safe for both human and agentic execution.

---

## Table of Contents

1. [Script Categories](#script-categories)
2. [Mandatory Patterns](#mandatory-patterns)
3. [Config Helper Usage](#config-helper-usage)
4. [Idempotency Requirements](#idempotency-requirements)
5. [Error Handling](#error-handling)
6. [Output Format](#output-format)
7. [Anti-Patterns](#anti-patterns)
8. [Examples](#examples)

---

## Script Categories

### 1. Configuration Scripts (`scripts/config/`)

**Purpose**: Apply system configuration changes (roles, capabilities, settings)

**Requirements**:
- MUST be idempotent (safe to run multiple times)
- MUST support `--dry-run` flag
- MUST verify changes after applying
- MUST use `config_helper.php` for config loading
- MUST fail loudly if prerequisites missing

**Naming**: `configure_<feature_name>.php`

**Example**: `configure_trainer_visibility_permissions.php`

### 2. Verification Scripts (`scripts/verify/`)

**Purpose**: Check system state without making changes

**Requirements**:
- MUST be read-only (no database writes)
- MUST exit 0 on success, 1 on failure
- MUST output clear pass/fail for each check
- MUST use `config_helper.php` for config loading
- MUST skip gracefully if prerequisites missing

**Naming**: `verify_<feature_name>.php`

**Example**: `verify_trainer_visibility_permissions.php`

### 3. Test Scripts (`scripts/test/`)

**Purpose**: Validate business logic and workflows

**Requirements**:
- MUST clean up test data in `finally` blocks
- MUST use unique test identifiers (e.g., `CIRC_TEST_` prefix)
- MUST be runnable in CI environment
- MUST use `config_helper.php` for config loading
- MUST output test results in parseable format

**Naming**: 
- Property tests: `property_test_<property_name>.php`
- Workflow tests: `test_<workflow_name>.php`

**Example**: `property_test_circular_dependency_prevention.php`

---

## Mandatory Patterns

### 1. Script Header Template

```php
<?php
/**
 * <Script Purpose - One Line>
 *
 * <Detailed description of what this script does>
 *
 * Capabilities granted/checked:
 * - <capability1> - <description>
 * - <capability2> - <description>
 *
 * Usage:
 *   php scripts/<category>/<script_name>.php [--dry-run] [--mode=<mode>]
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->libdir . '/accesslib.php');
// Add other required libraries here

global $DB;

init_cli_admin('moodle/site:config');
```

**Critical Rules**:
- ALWAYS use `config_helper.php` - never hardcode config paths
- ALWAYS call `init_cli_admin()` to set admin context
- ALWAYS declare `global $DB` before using database

### 2. Argument Parsing Pattern

```php
$dryrun = in_array('--dry-run', $argv);
$mode = 'local';

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $dryrun = true;
    }
    if (strpos($arg, '--mode=') === 0) {
        $mode = substr($arg, 7);
    }
}

echo "=== <Script Name> ===\n";
echo "MODE\t" . ($dryrun ? 'DRY-RUN' : 'APPLY') . "\n\n";
```

**Critical Rules**:
- ALWAYS support `--dry-run` for config scripts
- ALWAYS echo mode at start for debugging
- NEVER use complex argument parsing libraries

### 3. Database Query Pattern

```php
// CORRECT: Use IGNORE_MISSING for optional records
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', IGNORE_MISSING);
if (!$role) {
    fwrite(STDERR, "ERROR: sceh_trainer role not found. Run role setup script first.\n");
    exit(1);
}

// CORRECT: Use MUST_EXIST for required records
$sysctx = context_system::instance();

// WRONG: Don't use get_record without error handling
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer']);
```

**Critical Rules**:
- ALWAYS use `IGNORE_MISSING` or `MUST_EXIST` explicitly
- ALWAYS check for null before using optional records
- ALWAYS write to STDERR for errors: `fwrite(STDERR, "ERROR: ...\n")`

### 4. Idempotent Change Pattern

```php
// Check current state
$existing = $DB->get_record('role_capabilities', [
    'roleid' => $role->id,
    'contextid' => $sysctx->id,
    'capability' => $cap,
]);

if ($existing && $existing->permission == CAP_ALLOW) {
    echo "SKIP\t{$cap} already configured\n";
    continue;
}

// Apply change only if needed
if (!$dryrun) {
    assign_capability($cap, CAP_ALLOW, $role->id, $sysctx->id, true);
    echo "APPLY\t{$cap} = ALLOW\n";
}
```

**Critical Rules**:
- ALWAYS check current state before applying changes
- ALWAYS skip if already in desired state
- ALWAYS respect `$dryrun` flag

### 5. Cleanup Pattern (Tests Only)

```php
$test_competencies = [];

try {
    // Test setup
    $comp = create_test_competency();
    $test_competencies[] = $comp->id;
    
    // Test execution
    run_test($comp);
    
} finally {
    // ALWAYS cleanup in finally block
    foreach ($test_competencies as $compid) {
        try {
            $DB->delete_records('competency', ['id' => $compid]);
        } catch (Exception $e) {
            // Log but don't fail cleanup
            echo "CLEANUP_WARNING\tFailed to delete competency {$compid}\n";
        }
    }
}
```

**Critical Rules**:
- ALWAYS use `finally` blocks for cleanup
- ALWAYS track created resources in arrays
- NEVER let cleanup failures break the test

---

## Config Helper Usage

### Required Import

```php
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();
```

### Available Functions

#### `require_moodle_config()`

Finds and loads Moodle config.php across environments.

**Supported paths**:
- `__DIR__ . '/../../config.php'` (MoodleHQ Docker)
- `/bitnami/moodle/config.php` (Azure/Bitnami)
- `/opt/bitnami/moodle/config.php` (Alternative Bitnami)

**Usage**:
```php
require_moodle_config(); // Throws exception if not found
```

#### `init_cli_admin($capability = 'moodle/site:config')`

Sets admin user context and checks capability.

**Usage**:
```php
// Default: requires moodle/site:config
init_cli_admin();

// Custom capability check
init_cli_admin('moodle/course:create');

// No capability check
init_cli_admin(null);
```

**Returns**: Admin user object

---

## Idempotency Requirements

### Definition

A script is idempotent if running it multiple times produces the same result as running it once, without errors or duplicate data.

### Checklist

- [ ] Check if resource already exists before creating
- [ ] Use `INSERT IGNORE` or check-then-insert pattern
- [ ] Use `assign_capability()` with overwrite flag
- [ ] Skip operations that are already complete
- [ ] Never assume clean state

### Example: Idempotent Role Assignment

```php
// CORRECT: Check before assigning
$existing = $DB->get_record('role_assignments', [
    'roleid' => $roleid,
    'contextid' => $contextid,
    'userid' => $userid,
]);

if ($existing) {
    echo "SKIP\tRole already assigned\n";
} else {
    role_assign($roleid, $userid, $contextid);
    echo "APPLY\tRole assigned\n";
}

// WRONG: Assign without checking
role_assign($roleid, $userid, $contextid); // May fail or create duplicates
```

---

## Error Handling

### Exit Codes

- `0` = Success
- `1` = Failure (missing prerequisites, validation failed)
- `2` = Partial success (some checks passed, some failed)

### Error Output

```php
// CORRECT: Write errors to STDERR
fwrite(STDERR, "ERROR: sceh_trainer role not found\n");
exit(1);

// WRONG: Write errors to STDOUT
echo "ERROR: sceh_trainer role not found\n";
exit(1);
```

### Graceful Skipping

```php
// CORRECT: Skip gracefully if optional dependency missing
$trainer = $DB->get_record('user', ['username' => 'mock.trainer'], '*', IGNORE_MISSING);
if (!$trainer) {
    echo "SKIP\tmock.trainer user not found\n";
    exit(0); // Exit 0 for optional checks
}

// WRONG: Fail hard for optional dependency
$trainer = $DB->get_record('user', ['username' => 'mock.trainer'], '*', MUST_EXIST);
```

---

## Output Format

### Standard Output Format

Use tab-separated key-value pairs for parseable output:

```php
echo "ROLE\t{$role->name} (ID={$role->id})\n";
echo "CAPABILITY\t{$cap}\n";
echo "STATUS\tALLOW\n";
echo "RESULT\t✓ All tests passed\n";
```

### Status Prefixes

- `SKIP` - Operation skipped (already done or not applicable)
- `APPLY` - Change applied
- `VERIFY` - Verification check
- `ERROR` - Fatal error
- `WARNING` - Non-fatal issue
- `RESULT` - Final outcome
- `NOTE` - Additional information

### Progress Indicators

```php
echo "  ✓ {$cap} = ALLOW\n";  // Success
echo "  ✗ {$cap} = DENY\n";   // Failure
echo "  - {$cap} checking...\n"; // In progress
```

---

## Anti-Patterns

### ❌ NEVER: Hardcode Config Paths

```php
// WRONG
require_once('/bitnami/moodle/config.php');

// CORRECT
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();
```

### ❌ NEVER: Skip Error Checking

```php
// WRONG
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer']);
$roleid = $role->id; // May be null!

// CORRECT
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', IGNORE_MISSING);
if (!$role) {
    fwrite(STDERR, "ERROR: Role not found\n");
    exit(1);
}
$roleid = $role->id;
```

### ❌ NEVER: Cleanup in try Block

```php
// WRONG
try {
    $comp = create_competency();
    run_test($comp);
    delete_competency($comp); // Won't run if test fails!
} catch (Exception $e) {
    echo "Test failed\n";
}

// CORRECT
$comp = null;
try {
    $comp = create_competency();
    run_test($comp);
} finally {
    if ($comp) {
        delete_competency($comp);
    }
}
```

### ❌ NEVER: Silent Failures

```php
// WRONG
try {
    assign_capability($cap, CAP_ALLOW, $roleid, $contextid);
} catch (Exception $e) {
    // Silent failure
}

// CORRECT
try {
    assign_capability($cap, CAP_ALLOW, $roleid, $contextid);
    echo "APPLY\t{$cap} = ALLOW\n";
} catch (Exception $e) {
    fwrite(STDERR, "ERROR\tFailed to assign {$cap}: {$e->getMessage()}\n");
    exit(1);
}
```

### ❌ NEVER: Assume Clean State

```php
// WRONG
$comp = create_competency(['idnumber' => 'TEST_COMP']);
// Fails if TEST_COMP already exists

// CORRECT
$existing = $DB->get_record('competency', ['idnumber' => 'TEST_COMP']);
if ($existing) {
    $DB->delete_records('competency', ['id' => $existing->id]);
}
$comp = create_competency(['idnumber' => 'TEST_COMP']);
```

---

## Examples

### Example 1: Configuration Script

```php
<?php
/**
 * Configure Trainer Visibility Permissions
 *
 * Grants sceh_trainer role minimum capabilities for show/hide activities.
 *
 * Usage:
 *   php scripts/config/configure_trainer_visibility_permissions.php [--dry-run]
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->libdir . '/accesslib.php');

global $DB;

init_cli_admin('moodle/site:config');

$dryrun = in_array('--dry-run', $argv);

echo "=== Configure Trainer Visibility Permissions ===\n";
echo "MODE\t" . ($dryrun ? 'DRY-RUN' : 'APPLY') . "\n\n";

// Get role
$role = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', IGNORE_MISSING);
if (!$role) {
    fwrite(STDERR, "ERROR: sceh_trainer role not found\n");
    exit(1);
}

$sysctx = context_system::instance();

$capabilities = [
    'moodle/course:activityvisibility' => 'Show/hide activities',
    'moodle/course:manageactivities' => 'Required for visibility toggle',
];

// Check current state
echo "CURRENT STATE:\n";
foreach ($capabilities as $cap => $desc) {
    $existing = $DB->get_record('role_capabilities', [
        'roleid' => $role->id,
        'contextid' => $sysctx->id,
        'capability' => $cap,
    ]);
    
    if ($existing && $existing->permission == CAP_ALLOW) {
        echo "  ✓ {$cap} = ALLOW\n";
    } else {
        echo "  - {$cap} = NOT_SET\n";
    }
}
echo "\n";

// Apply changes
if (!$dryrun) {
    echo "APPLYING CHANGES:\n";
    foreach ($capabilities as $cap => $desc) {
        $existing = $DB->get_record('role_capabilities', [
            'roleid' => $role->id,
            'contextid' => $sysctx->id,
            'capability' => $cap,
        ]);
        
        if ($existing && $existing->permission == CAP_ALLOW) {
            echo "  SKIP\t{$cap} already configured\n";
            continue;
        }
        
        try {
            assign_capability($cap, CAP_ALLOW, $role->id, $sysctx->id, true);
            echo "  APPLY\t{$cap} = ALLOW\n";
        } catch (Exception $e) {
            fwrite(STDERR, "  ERROR\t{$cap} failed: {$e->getMessage()}\n");
            exit(1);
        }
    }
    echo "\nDONE\tTrainer visibility permissions configured\n";
} else {
    echo "DRY-RUN\tNo changes applied\n";
}
```

### Example 2: Verification Script

```php
<?php
/**
 * Verify Trainer Visibility Permissions
 *
 * Tests that trainers can show/hide but not edit structure.
 *
 * Usage:
 *   php scripts/verify/verify_trainer_visibility_permissions.php
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->libdir . '/accesslib.php');

global $DB;

init_cli_admin('moodle/site:config');

echo "=== Verify Trainer Visibility Permissions ===\n\n";

// Get trainer user
$trainer = $DB->get_record('user', ['username' => 'mock.trainer'], '*', IGNORE_MISSING);
if (!$trainer) {
    echo "SKIP\tmock.trainer user not found\n";
    exit(0);
}

$sysctx = context_system::instance();

$tests = [
    'moodle/course:activityvisibility' => true,
    'moodle/course:manageactivities' => true,
    'moodle/course:update' => false,
];

$passed = 0;
$failed = 0;

foreach ($tests as $cap => $expected) {
    $has = has_capability($cap, $sysctx, $trainer->id);
    $result = ($has === $expected);
    
    if ($result) {
        echo "  ✓ {$cap}: " . ($expected ? 'ALLOW' : 'DENY') . "\n";
        $passed++;
    } else {
        echo "  ✗ {$cap}: expected " . ($expected ? 'ALLOW' : 'DENY') . "\n";
        $failed++;
    }
}

echo "\nSUMMARY: {$passed} passed, {$failed} failed\n";

if ($failed === 0) {
    echo "RESULT\t✓ All tests passed\n";
    exit(0);
} else {
    echo "RESULT\t✗ Some tests failed\n";
    exit(1);
}
```

### Example 3: Property Test

```php
<?php
/**
 * Property-Based Test: Circular Dependency Prevention
 *
 * Validates that competency prerequisite chains cannot form cycles.
 *
 * Usage:
 *   php scripts/test/property_test_circular_dependency_prevention.php
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->dirroot . '/competency/classes/api.php');

global $DB;

init_cli_admin();

echo "=== Property Test: Circular Dependency Prevention ===\n\n";

$test_competencies = [];

try {
    // Create test framework
    $framework = get_or_create_test_framework();
    
    // Create test competencies
    $comp_a = create_test_competency($framework, 'CIRC_TEST_A');
    $comp_b = create_test_competency($framework, 'CIRC_TEST_B');
    $comp_c = create_test_competency($framework, 'CIRC_TEST_C');
    
    $test_competencies = [$comp_a->id, $comp_b->id, $comp_c->id];
    
    // Test: A -> B -> C -> A should fail
    add_prerequisite($comp_a, $comp_b); // OK
    add_prerequisite($comp_b, $comp_c); // OK
    
    $circular_prevented = false;
    try {
        add_prerequisite($comp_c, $comp_a); // Should fail
    } catch (Exception $e) {
        $circular_prevented = true;
    }
    
    if ($circular_prevented) {
        echo "RESULT\t✓ Circular dependency prevented\n";
        exit(0);
    } else {
        echo "RESULT\t✗ Circular dependency NOT prevented\n";
        exit(1);
    }
    
} finally {
    // Cleanup
    foreach ($test_competencies as $compid) {
        try {
            $DB->delete_records('competency', ['id' => $compid]);
        } catch (Exception $e) {
            echo "CLEANUP_WARNING\tFailed to delete competency {$compid}\n";
        }
    }
}
```

---

## Verification Checklist

Before committing a new script, verify:

- [ ] Uses `config_helper.php` for config loading
- [ ] Calls `init_cli_admin()` with appropriate capability
- [ ] Supports `--dry-run` flag (config scripts only)
- [ ] Checks current state before applying changes
- [ ] Writes errors to STDERR with `fwrite(STDERR, ...)`
- [ ] Uses correct exit codes (0 = success, 1 = failure)
- [ ] Cleans up test data in `finally` blocks (test scripts only)
- [ ] Uses tab-separated output format
- [ ] Includes usage documentation in header comment
- [ ] Handles missing prerequisites gracefully

---

## Related Documentation

- [PLUGIN_DEVELOPMENT_GUIDE.md](./PLUGIN_DEVELOPMENT_GUIDE.md) - Custom plugin patterns
- [TROUBLESHOOTING_PLAYBOOK.md](./TROUBLESHOOTING_PLAYBOOK.md) - Common script issues
- [CONFIG_ROLLBACK_STRATEGY.md](./CONFIG_ROLLBACK_STRATEGY.md) - Rollback procedures
