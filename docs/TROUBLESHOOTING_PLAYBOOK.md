# Troubleshooting Playbook — Common Issues and Solutions

**Diagnostic guide for system maintenance and debugging**

This document provides step-by-step troubleshooting procedures for common issues. Each section includes symptoms, diagnostic commands, root causes, and verified solutions.

---

## Table of Contents

1. [Dashboard Blocks Not Appearing](#dashboard-blocks-not-appearing)
2. [Plugin Not Recognized](#plugin-not-recognized)
3. [Permission Denied Errors](#permission-denied-errors)
4. [Database Connection Failures](#database-connection-failures)
5. [Script Execution Failures](#script-execution-failures)
6. [Test Failures in CI](#test-failures-in-ci)
7. [Docker Container Issues](#docker-container-issues)
8. [Cache Problems](#cache-problems)
9. [Competency Framework Errors](#competency-framework-errors)
10. [Import/Upload Failures](#importupload-failures)

---

## Dashboard Blocks Not Appearing

### Symptoms
- Dashboard page loads but custom blocks missing
- Blocks exist in database but don't render
- "Block not found" errors in logs

### Diagnostic Commands

```bash
# Check if block exists in database
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$blocks = \$DB->get_records('block_instances', ['blockname' => 'sceh_dashboard']);
echo 'Found ' . count(\$blocks) . ' block instances\n';
foreach (\$blocks as \$b) {
    echo 'ID: ' . \$b->id . ' Page: ' . \$b->pagetypepattern . ' Subpage: ' . (\$b->subpagepattern ?: 'default') . '\n';
}
"

# Check if plugin files mounted
docker exec moodlehq-dev-moodle-1 ls -la /var/www/html/public/blocks/sceh_dashboard/

# Check Moodle recognizes plugin
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/uninstall_plugins.php --show-all | grep sceh_dashboard
```

### Root Causes

**1. Plugin not mounted in Docker**
- **Cause**: Container started before plugin code existed
- **Solution**: Restart container to pick up volume mounts
```bash
docker compose -f docker-compose.moodlehq.yml restart moodle
```

**2. Block on wrong subpage**
- **Cause**: Block added to user-specific dashboard instead of default
- **Solution**: Add block to default layout (subpagepattern='')
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/add_dashboard_block.php
```

**3. User has customized dashboard**
- **Cause**: User's private dashboard overrides default layout
- **Solution**: Reset user's dashboard to default
```bash
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$DB->delete_records('my_pages', ['userid' => 2, 'private' => 1]);
echo 'Reset admin dashboard\n';
"
```

**4. Cache not purged**
- **Cause**: Moodle serving cached version
- **Solution**: Purge all caches
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/purge_caches.php
```

### Prevention
- Always restart containers after adding new plugins
- Use default layout (subpagepattern='') for universal blocks
- Purge caches after plugin changes

---

## Plugin Not Recognized

### Symptoms
- "Plugin not found" errors
- Plugin doesn't appear in admin interface
- Upgrade required message persists

### Diagnostic Commands

```bash
# Check plugin directory exists
ls -la block_sceh_dashboard/

# Check version.php exists and is valid
cat block_sceh_dashboard/version.php

# Check Moodle plugin list
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/uninstall_plugins.php --show-all | grep sceh

# Check for syntax errors
docker exec moodlehq-dev-moodle-1 php -l /var/www/html/public/blocks/sceh_dashboard/block_sceh_dashboard.php
```

### Root Causes

**1. Plugin not mounted in container**
- **Cause**: Missing volume mount in docker-compose.yml
- **Solution**: Add volume mount and restart
```yaml
volumes:
  - ./block_sceh_dashboard:/var/www/html/public/blocks/sceh_dashboard
```

**2. Invalid version.php**
- **Cause**: Syntax error or missing required fields
- **Solution**: Validate version.php format
```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_sceh_dashboard';  // REQUIRED
$plugin->version = 2026030700;                // REQUIRED
$plugin->requires = 2022041900;               // REQUIRED
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.0';
```

**3. Incorrect component name**
- **Cause**: Component name doesn't match directory
- **Solution**: Ensure component = `<type>_<directory_name>`
```php
// Directory: block_sceh_dashboard/
$plugin->component = 'block_sceh_dashboard'; // Correct
$plugin->component = 'block_dashboard';      // Wrong
```

**4. Upgrade required**
- **Cause**: Version changed but upgrade not run
- **Solution**: Run upgrade
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/upgrade.php --non-interactive
```

### Prevention
- Always validate version.php before committing
- Run upgrade after version changes
- Use consistent naming conventions

---

## Permission Denied Errors

### Symptoms
- "Permission denied" when accessing pages
- "You do not have permission" messages
- Capability check failures

### Diagnostic Commands

```bash
# Check user's capabilities
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$user = \$DB->get_record('user', ['username' => 'mock.trainer']);
\$context = context_system::instance();
\$caps = ['local/sceh_rules:trainer', 'moodle/course:activityvisibility'];
foreach (\$caps as \$cap) {
    \$has = has_capability(\$cap, \$context, \$user->id);
    echo \$cap . ': ' . (\$has ? 'ALLOW' : 'DENY') . '\n';
}
"

# Check role assignments
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$user = \$DB->get_record('user', ['username' => 'mock.trainer']);
\$assignments = \$DB->get_records('role_assignments', ['userid' => \$user->id]);
echo 'Found ' . count(\$assignments) . ' role assignments\n';
foreach (\$assignments as \$a) {
    \$role = \$DB->get_record('role', ['id' => \$a->roleid]);
    echo 'Role: ' . \$role->shortname . ' Context: ' . \$a->contextid . '\n';
}
"

# Check capability definition
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$cap = \$DB->get_record('capabilities', ['name' => 'local/sceh_rules:trainer']);
if (\$cap) {
    echo 'Capability exists: ' . \$cap->name . '\n';
} else {
    echo 'Capability NOT defined\n';
}
"
```

### Root Causes

**1. Capability not assigned to role**
- **Cause**: Role exists but capability not granted
- **Solution**: Run configuration script
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_trainer_visibility_permissions.php
```

**2. User not assigned to role**
- **Cause**: User exists but role not assigned
- **Solution**: Assign role at system context
```bash
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$user = \$DB->get_record('user', ['username' => 'mock.trainer'], '*', MUST_EXIST);
\$role = \$DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', MUST_EXIST);
\$context = context_system::instance();
role_assign(\$role->id, \$user->id, \$context->id);
echo 'Role assigned\n';
"
```

**3. Wrong context level**
- **Cause**: Checking capability at wrong context
- **Solution**: Use correct context
```php
// Wrong - checking at course context for system capability
$context = context_course::instance($courseid);
require_capability('local/sceh_rules:systemadmin', $context);

// Correct - check at system context
$context = context_system::instance();
require_capability('local/sceh_rules:systemadmin', $context);
```

**4. Capability not defined**
- **Cause**: Missing db/access.php or upgrade not run
- **Solution**: Define capability and run upgrade
```bash
# Check db/access.php exists
cat local_sceh_rules/db/access.php

# Run upgrade
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/upgrade.php --non-interactive
```

### Prevention
- Always run config scripts after role changes
- Use verification scripts to check permissions
- Document required capabilities in code comments

---

## Database Connection Failures

### Symptoms
- "Database connection failed" errors
- "Could not connect to database" messages
- Scripts fail with connection errors

### Diagnostic Commands

```bash
# Check MySQL container status
docker compose -f docker-compose.moodlehq.yml ps mysql

# Check MySQL logs
docker compose -f docker-compose.moodlehq.yml logs mysql | tail -50

# Test MySQL connection
docker exec moodlehq-dev-mysql-1 mysql -u moodle -p<password> -e "SELECT 1;"

# Check Moodle config.php
docker exec moodlehq-dev-moodle-1 cat /var/www/html/config.php | grep dbhost
```

### Root Causes

**1. MySQL container not healthy**
- **Cause**: MySQL still initializing or crashed
- **Solution**: Wait for health check or restart
```bash
# Check health
docker compose -f docker-compose.moodlehq.yml ps

# Wait for healthy
sleep 30

# Restart if needed
docker compose -f docker-compose.moodlehq.yml restart mysql
```

**2. Wrong database credentials**
- **Cause**: .env file has incorrect credentials
- **Solution**: Verify .env matches docker-compose.yml
```bash
# Check .env
cat .env | grep MOODLEHQ_DB

# Regenerate if needed
./scripts/generate-env.sh
```

**3. Moodle container started before MySQL ready**
- **Cause**: Race condition during startup
- **Solution**: Restart Moodle container
```bash
docker compose -f docker-compose.moodlehq.yml restart moodle
```

**4. Network connectivity issue**
- **Cause**: Docker network problem
- **Solution**: Recreate network
```bash
docker compose -f docker-compose.moodlehq.yml down
docker compose -f docker-compose.moodlehq.yml up -d
```

### Prevention
- Always use health checks in docker-compose.yml
- Wait for MySQL healthy before running scripts
- Use depends_on with condition: service_healthy

---

## Script Execution Failures

### Symptoms
- "config.php not found" errors
- "No admin user found" errors
- Scripts exit with code 1

### Diagnostic Commands

```bash
# Check config.php exists
docker exec moodlehq-dev-moodle-1 ls -la /var/www/html/config.php

# Check script can find config
docker exec moodlehq-dev-moodle-1 php -r "
\$paths = [
    '/var/www/html/config.php',
    '/bitnami/moodle/config.php',
];
foreach (\$paths as \$path) {
    echo \$path . ': ' . (file_exists(\$path) ? 'EXISTS' : 'NOT FOUND') . '\n';
}
"

# Check admin user exists
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
\$admin = get_admin();
echo \$admin ? 'Admin: ' . \$admin->username . '\n' : 'No admin found\n';
"
```

### Root Causes

**1. Script not using config_helper.php**
- **Cause**: Hardcoded config path doesn't exist
- **Solution**: Use config_helper.php
```php
// Wrong
require_once('/bitnami/moodle/config.php');

// Correct
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();
```

**2. Running script from wrong directory**
- **Cause**: Relative paths broken
- **Solution**: Always run from repo root
```bash
# Wrong
cd scripts/config
php configure_trainer_visibility_permissions.php

# Correct
php scripts/config/configure_trainer_visibility_permissions.php
```

**3. No admin user**
- **Cause**: Fresh install not completed
- **Solution**: Complete Moodle installation
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/install_database.php \
  --agree-license \
  --adminuser=admin \
  --adminpass=<password> \
  --adminemail=admin@example.com \
  --fullname="SCEH" \
  --shortname="SCEH"
```

**4. Missing CLI_SCRIPT definition**
- **Cause**: Script doesn't define CLI_SCRIPT
- **Solution**: Add at top of script
```php
<?php
define('CLI_SCRIPT', true);  // REQUIRED
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();
```

### Prevention
- Always use config_helper.php
- Always define CLI_SCRIPT
- Always run scripts from repo root
- Document script usage in header comments

---

## Test Failures in CI

### Symptoms
- Tests pass locally but fail in CI
- "ID number already in use" errors
- "Context not found" errors

### Diagnostic Commands

```bash
# Run test locally
php scripts/test/property_test_circular_dependency_prevention.php

# Check CI logs
cat .github/workflows/regression-tests.yml

# Check test cleanup
grep -A 10 "finally" scripts/test/property_test_circular_dependency_prevention.php
```

### Root Causes

**1. Test data not cleaned up**
- **Cause**: Previous test run left orphaned data
- **Solution**: Add cleanup in finally block
```php
$test_competencies = [];

try {
    $comp = create_competency(['idnumber' => 'TEST_COMP']);
    $test_competencies[] = $comp->id;
    
    // Test logic
    
} finally {
    foreach ($test_competencies as $compid) {
        try {
            $DB->delete_records('competency', ['id' => $compid]);
        } catch (Exception $e) {
            echo "CLEANUP_WARNING\t{$compid}\n";
        }
    }
}
```

**2. Test assumes specific data exists**
- **Cause**: Test expects framework/user that doesn't exist in CI
- **Solution**: Create test data or skip gracefully
```php
// Wrong - assumes framework exists
$framework = $DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025'], '*', MUST_EXIST);

// Correct - create if missing
$framework = get_or_create_test_framework();
```

**3. Race condition in CI**
- **Cause**: Multiple tests running simultaneously
- **Solution**: Use unique test identifiers
```php
// Wrong - conflicts with other tests
$comp = create_competency(['idnumber' => 'TEST_COMP']);

// Correct - unique per test run
$timestamp = time();
$comp = create_competency(['idnumber' => "TEST_COMP_{$timestamp}"]);
```

**4. Missing environment setup**
- **Cause**: CI doesn't run config scripts
- **Solution**: Add to CI workflow
```yaml
- name: Configure environment
  run: |
    docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_trainer_visibility_permissions.php
```

### Prevention
- Always use finally blocks for cleanup
- Always use unique test identifiers
- Always create test data, never assume it exists
- Add all config scripts to CI workflow

---

## Docker Container Issues

### Symptoms
- Containers won't start
- "Port already in use" errors
- "Volume mount failed" errors

### Diagnostic Commands

```bash
# Check container status
docker compose -f docker-compose.moodlehq.yml ps

# Check container logs
docker compose -f docker-compose.moodlehq.yml logs moodle | tail -100

# Check port conflicts
lsof -i :8081  # macOS/Linux
netstat -ano | findstr :8081  # Windows

# Check volume mounts
docker inspect moodlehq-dev-moodle-1 | grep -A 20 Mounts
```

### Root Causes

**1. Port already in use**
- **Cause**: Another service using port 8081
- **Solution**: Change port in .env or stop conflicting service
```bash
# Change port
echo "MOODLEHQ_WEB_PORT=8082" >> .env

# Or stop conflicting service
docker stop <container-using-8081>
```

**2. Volume mount path doesn't exist**
- **Cause**: Plugin directory missing
- **Solution**: Ensure all plugins exist before starting
```bash
# Check plugins exist
ls -d block_sceh_dashboard local_sceh_rules theme_sceh

# Clone if missing
git pull origin master
```

**3. Container out of memory**
- **Cause**: Docker memory limit too low
- **Solution**: Increase Docker memory
```bash
# Check Docker settings
docker info | grep Memory

# Increase in Docker Desktop settings (4GB minimum)
```

**4. Stale volumes**
- **Cause**: Old data in volumes
- **Solution**: Remove volumes and recreate
```bash
docker compose -f docker-compose.moodlehq.yml down -v
docker compose -f docker-compose.moodlehq.yml up -d
```

### Prevention
- Always check ports before starting
- Always ensure plugins exist before mounting
- Allocate sufficient Docker resources
- Document volume management in README

---

## Cache Problems

### Symptoms
- Changes not appearing
- Old content still showing
- "Class not found" after adding new class

### Diagnostic Commands

```bash
# Purge all caches
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/purge_caches.php

# Check cache config
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
echo 'Cache dir: ' . \$CFG->cachedir . '\n';
echo 'Data root: ' . \$CFG->dataroot . '\n';
"

# Clear specific cache
docker exec moodlehq-dev-moodle-1 rm -rf /var/www/moodledata/cache/*
```

### Root Causes

**1. Cache not purged after changes**
- **Cause**: Moodle serving cached version
- **Solution**: Always purge after code changes
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/purge_caches.php
```

**2. Browser cache**
- **Cause**: Browser serving cached CSS/JS
- **Solution**: Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)

**3. Autoloader cache stale**
- **Cause**: New class not in autoloader cache
- **Solution**: Purge caches and reload
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/purge_caches.php
```

**4. Theme cache**
- **Cause**: SCSS changes not recompiled
- **Solution**: Purge theme cache
```bash
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
theme_reset_all_caches();
echo 'Theme caches cleared\n';
"
```

### Prevention
- Always purge caches after code changes
- Use hard refresh for CSS/JS changes
- Document cache purge in development workflow

---

## Competency Framework Errors

### Symptoms
- "Competency framework not found" errors
- "Context not found" errors
- "Circular dependency" errors

### Diagnostic Commands

```bash
# List frameworks
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$frameworks = \$DB->get_records('competency_framework');
echo 'Found ' . count(\$frameworks) . ' frameworks\n';
foreach (\$frameworks as \$f) {
    echo 'ID: ' . \$f->id . ' IDNumber: ' . \$f->idnumber . ' Context: ' . \$f->contextid . '\n';
}
"

# Check framework context
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$framework = \$DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);
if (\$framework) {
    \$context = context::instance_by_id(\$framework->contextid, IGNORE_MISSING);
    echo \$context ? 'Context exists\n' : 'Context MISSING\n';
}
"

# List competencies
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$comps = \$DB->get_records('competency', ['competencyframeworkid' => 1]);
echo 'Found ' . count(\$comps) . ' competencies\n';
"
```

### Root Causes

**1. Framework context deleted**
- **Cause**: Category deleted but framework still references it
- **Solution**: Delete stale framework and recreate
```bash
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$framework = \$DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);
if (\$framework) {
    \$context = context::instance_by_id(\$framework->contextid, IGNORE_MISSING);
    if (!\$context) {
        \$DB->delete_records('competency_framework', ['id' => \$framework->id]);
        echo 'Deleted stale framework\n';
    }
}
"
```

**2. Circular dependency created**
- **Cause**: Prerequisite chain forms a cycle
- **Solution**: Remove circular prerequisite
```bash
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
require_once('/var/www/html/competency/classes/api.php');
// Find and remove circular prerequisite
\$DB->delete_records('competency_relatedcompetency', ['competencyid' => \$comp_a_id, 'relatedcompetencyid' => \$comp_b_id]);
"
```

**3. Scale missing**
- **Cause**: Framework references deleted scale
- **Solution**: Create scale or update framework
```bash
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$scale = new stdClass();
\$scale->courseid = 0;
\$scale->userid = 0;
\$scale->name = 'Competency Scale';
\$scale->scale = 'Not competent,Competent';
\$scale->description = '';
\$scale->descriptionformat = FORMAT_HTML;
\$scale->timemodified = time();
\$scaleid = \$DB->insert_record('scale', \$scale);
echo 'Created scale: ' . \$scaleid . '\n';
"
```

### Prevention
- Always validate context exists before using framework
- Always check for circular dependencies before adding prerequisites
- Always create scales before frameworks

---

## Import/Upload Failures

### Symptoms
- "File too large" errors
- "Upload failed" messages
- "Invalid package" errors

### Diagnostic Commands

```bash
# Check PHP upload limits
docker exec moodlehq-dev-moodle-1 php -i | grep upload_max_filesize
docker exec moodlehq-dev-moodle-1 php -i | grep post_max_size
docker exec moodlehq-dev-moodle-1 php -i | grep memory_limit

# Check Moodle upload limits
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
echo 'Max upload: ' . get_max_upload_file_size() . ' bytes\n';
"

# Check disk space
docker exec moodlehq-dev-moodle-1 df -h /var/www/moodledata
```

### Root Causes

**1. File size exceeds PHP limit**
- **Cause**: upload_max_filesize too small
- **Solution**: Increase in docker-compose.yml
```yaml
environment:
  - PHP_INI-upload_max_filesize=256M
  - PHP_INI-post_max_size=256M
  - PHP_INI-memory_limit=1024M
```

**2. Disk space full**
- **Cause**: moodledata volume full
- **Solution**: Clean up old files or increase volume size
```bash
# Check space
docker exec moodlehq-dev-moodle-1 du -sh /var/www/moodledata/*

# Clean temp files
docker exec moodlehq-dev-moodle-1 rm -rf /var/www/moodledata/temp/*
```

**3. Invalid package format**
- **Cause**: Package doesn't match expected structure
- **Solution**: Validate package structure
```bash
# Check package contents
unzip -l package.mbz | head -20
```

**4. Async task failed**
- **Cause**: Background import task crashed
- **Solution**: Check adhoc task logs
```bash
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/config.php');
\$tasks = \$DB->get_records('task_adhoc', ['classname' => '\\\local_sceh_importer\\\task\\\import_course_task'], 'timemodified DESC', '*', 0, 10);
foreach (\$tasks as \$t) {
    echo 'ID: ' . \$t->id . ' Failed: ' . \$t->faildelay . ' Next: ' . date('Y-m-d H:i:s', \$t->nextruntime) . '\n';
}
"
```

### Prevention
- Set appropriate PHP limits in docker-compose.yml
- Monitor disk space regularly
- Validate package format before upload
- Add error handling to async tasks

---

## Quick Reference

### Essential Commands

```bash
# Purge all caches
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/purge_caches.php

# Run upgrade
docker exec moodlehq-dev-moodle-1 php /var/www/html/admin/cli/upgrade.php --non-interactive

# Restart containers
docker compose -f docker-compose.moodlehq.yml restart

# Check logs
docker compose -f docker-compose.moodlehq.yml logs -f moodle

# Run config script
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/<script>.php

# Run verification script
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/verify/<script>.php

# Run test
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/scripts/test/<script>.php
```

### Diagnostic Checklist

When troubleshooting any issue:

1. [ ] Check container status: `docker compose ps`
2. [ ] Check logs: `docker compose logs moodle | tail -100`
3. [ ] Purge caches: `php admin/cli/purge_caches.php`
4. [ ] Check plugin mounted: `ls /var/www/html/public/<plugin>/`
5. [ ] Check database connection: `docker compose ps mysql`
6. [ ] Run verification script: `php scripts/verify/<feature>.php`
7. [ ] Check user permissions: Query role_assignments table
8. [ ] Review recent changes: `git log --oneline -10`

---

## Related Documentation

- [SCRIPT_PATTERNS.md](./SCRIPT_PATTERNS.md) - Script development patterns
- [PLUGIN_DEVELOPMENT_GUIDE.md](./PLUGIN_DEVELOPMENT_GUIDE.md) - Plugin patterns
- [KNOWN_LIMITATIONS.md](./KNOWN_LIMITATIONS.md) - Known system limitations
- [MOODLEHQ_MYSQL_DEV_STACK.md](./MOODLEHQ_MYSQL_DEV_STACK.md) - Docker setup
