# CI Test Failure Fixes

**Date:** 2026-02-23  
**Failures:** 2 of 7 tests failing in CI/CD workflow

---

## Failure 1: Allied Health Quiz Workflow (AHW-AT-99)

**Error:** `Sorry, but you do not currently have permissions to do that (Hide/show activities).`

**Root Cause:** Test assumes trainer has `moodle/course:activityvisibility` capability, but config script hasn't run in CI environment.

**Location:** `scripts/test/test_allied_health_quiz_workflow.php` line 851-852

### Fix Option A: Update CI Workflow (Recommended)

Add config script execution before tests:

```yaml
# .github/workflows/test.yml (or similar)
- name: Configure System
  run: |
    docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_roles.php
    docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_trainer_visibility_permissions.php
    docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_mock_users.php
```

### Fix Option B: Make Test Defensive

```php
// scripts/test/test_allied_health_quiz_workflow.php
// Replace lines 851-852 with:

set_script_user($trainer);

// Check if trainer has required capability
$has_visibility_cap = has_capability(
    'moodle/course:activityvisibility',
    context_module::instance($samplequizcmid),
    $trainer
);

if (!$has_visibility_cap) {
    log_check(
        $results,
        'AHW-AT-14A',
        false,
        'Trainer lacks moodle/course:activityvisibility - run configure_trainer_visibility_permissions.php'
    );
    fail_exit($results, 'Missing trainer permissions');
}

require_capability('moodle/course:activityvisibility', context_module::instance($samplequizcmid));
require_capability('moodle/course:activityvisibility', context_module::instance($samplecontentcmid));
```

---

## Failure 2: Circular Dependency Prevention

**Error:** `Coding error detected, it must be fixed by a programmer: Invalid context id specified context::instance_by_id()`

**Root Cause:** Test looks for competency framework `OPHTHAL_FELLOW_2025` which doesn't exist in CI environment. When creating competencies in non-existent framework, context resolution fails during cleanup.

**Location:** `scripts/test/property_test_circular_dependency_prevention.php` lines 99, 159, 219

### Fix: Create Test Framework If Missing

```php
// scripts/test/property_test_circular_dependency_prevention.php
// Replace lines 99, 159, 219 with this helper function:

/**
 * Get or create test framework
 */
function get_or_create_test_framework() {
    global $DB;
    
    // Try to find existing framework
    $framework = $DB->get_record('competency_framework', 
        ['idnumber' => 'OPHTHAL_FELLOW_2025'], 
        '*', 
        IGNORE_MISSING
    );
    
    if ($framework) {
        return $framework;
    }
    
    // Create temporary test framework
    $framework_data = (object)[
        'shortname' => 'Test Framework (Circular Dependency Tests)',
        'idnumber' => 'TEST_CIRC_DEP_' . time(),
        'description' => 'Temporary framework for circular dependency property tests',
        'descriptionformat' => FORMAT_HTML,
        'contextid' => context_system::instance()->id,
        'scaleid' => $DB->get_field_sql('SELECT MIN(id) FROM {scale}'),
        'scaleconfiguration' => json_encode([
            ['id' => 1, 'name' => 'Not competent'],
            ['id' => 2, 'name' => 'Competent']
        ]),
        'visible' => 1,
    ];
    
    return api::create_framework($framework_data);
}

// Then in each test function, replace:
// $framework = $DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);
// With:
$framework = get_or_create_test_framework();
```

---

## Implementation Priority

### Immediate (Fix CI)

1. **Add config scripts to CI workflow** (5 minutes)
   - Ensures trainer has required capabilities
   - Ensures mock users exist
   - Ensures roles configured

2. **Add framework creation to circular dependency test** (10 minutes)
   - Makes test self-contained
   - No dependency on existing data

### Short-term (Improve Test Robustness)

3. **Add capability checks to all tests** (30 minutes)
   - Fail fast with clear error messages
   - Document required setup in test headers

4. **Create test setup script** (1 hour)
   - `scripts/test/setup_test_environment.php`
   - Runs all required config scripts
   - Creates test data (frameworks, categories, etc.)
   - CI calls this once before all tests

---

## CI Workflow Example

```yaml
name: Moodle Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Start Docker Compose
        run: docker-compose -f docker-compose.moodlehq.yml up -d
      
      - name: Wait for Moodle
        run: |
          timeout 120 bash -c 'until docker exec moodlehq-dev-moodle-1 test -f /var/www/html/config.php; do sleep 2; done'
      
      - name: Setup Test Environment
        run: |
          docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_roles.php
          docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_trainer_visibility_permissions.php
          docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_mock_users.php
          docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_categories.php
      
      - name: Run Tests
        run: docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/test/run_all_tests.php
```

---

## Summary

**Both failures are environment setup issues, not code bugs:**

1. **AHW-AT-99:** Trainer missing capability → Run config script in CI
2. **Circular Dependency:** Missing test framework → Create framework in test

**Fixes are straightforward:**
- Add 4 lines to CI workflow (config scripts)
- Add 20 lines to circular dependency test (framework creation)

**Estimated time:** 15 minutes to fix both issues

---

**Document Version:** 1.0  
**Last Updated:** 2026-02-23  
**Status:** Ready for implementation
