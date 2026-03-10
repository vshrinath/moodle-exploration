# AWS Role Setup Commands

**Purpose:** Configure custom SCEH roles on AWS/Linux VM deployment  
**Audience:** Backend Engineers, DevOps  
**Execution Time:** ~2 minutes

---

## Prerequisites

- SSH access to AWS VM
- Docker containers running
- Admin credentials available

---

## Step 1: Verify Current State

```bash
# SSH into AWS VM
ssh user@your-aws-vm-ip

# Navigate to project directory
cd /path/to/moodle-exploration

# Check if containers are running
docker compose -f docker-compose.moodlehq.yml ps

# Verify Moodle is accessible
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/check.php
```

---

## Step 2: Check Existing Roles

```bash
# List current roles
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$roles = \$DB->get_records('role', null, 'sortorder', 'id,shortname,name');
foreach (\$roles as \$role) {
    echo \$role->shortname . ' - ' . \$role->name . PHP_EOL;
}
"
```

**Expected Output (BEFORE):**
```
manager - Manager
coursecreator - Course creator
editingteacher - Teacher
teacher - Non-editing teacher
student - Student
guest - Guest
user - Authenticated user
frontpage - Authenticated user on frontpage
```

---

## Step 3: Create Custom Roles

**Option A: Using Real Users (Recommended for Production)**

```bash
# Replace with actual category idnumber and username
docker exec -u www-data moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php \
  --mode=apply-real-env \
  --category-idnumber=allied-health \
  --program-owner-usernames=john.doe,jane.smith
```

**Option B: Using Mock Users (For Testing Only)**

```bash
# Creates mock users for testing
docker exec -u www-data moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php \
  --mode=local
```

---

## Step 4: Verify Roles Created

```bash
# Check custom roles exist
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$custom_roles = ['sceh_system_admin', 'sceh_program_owner', 'sceh_trainer', 'sceh_program_owner_competency'];
foreach (\$custom_roles as \$shortname) {
    \$role = \$DB->get_record('role', ['shortname' => \$shortname], 'id,shortname,name');
    if (\$role) {
        echo '✓ ' . \$role->shortname . ' - ' . \$role->name . PHP_EOL;
    } else {
        echo '✗ MISSING: ' . \$shortname . PHP_EOL;
    }
}
"
```

**Expected Output (AFTER):**
```
✓ sceh_system_admin - SCEH System Administrator
✓ sceh_program_owner - SCEH Program Owner
✓ sceh_trainer - SCEH Trainer
✓ sceh_program_owner_competency - SCEH Program Owner Competency
```

---

## Step 5: Verify Category Exists

```bash
# Check allied-health category
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$category = \$DB->get_record('course_categories', ['idnumber' => 'allied-health'], 'id,name,idnumber');
if (\$category) {
    echo '✓ Category: ' . \$category->name . ' (ID: ' . \$category->id . ')' . PHP_EOL;
} else {
    echo '✗ Category allied-health not found' . PHP_EOL;
}
"
```

---

## Step 6: Verify Role Assignments

```bash
# Check role assignments for a specific user
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;

// Replace with actual username
\$username = 'john.doe';
\$user = \$DB->get_record('user', ['username' => \$username, 'deleted' => 0], 'id,username');

if (!\$user) {
    echo 'User not found: ' . \$username . PHP_EOL;
    exit(1);
}

\$sql = \"SELECT ra.id, r.shortname, r.name, c.contextlevel, cc.name as categoryname
         FROM {role_assignments} ra
         JOIN {role} r ON r.id = ra.roleid
         JOIN {context} c ON c.id = ra.contextid
         LEFT JOIN {course_categories} cc ON cc.id = c.instanceid AND c.contextlevel = 40
         WHERE ra.userid = :userid
         ORDER BY c.contextlevel, r.shortname\";

\$assignments = \$DB->get_records_sql(\$sql, ['userid' => \$user->id]);

echo 'Role assignments for ' . \$user->username . ':' . PHP_EOL;
foreach (\$assignments as \$ra) {
    \$context = \$ra->contextlevel == 10 ? 'System' : (\$ra->categoryname ?: 'Course');
    echo '  ' . \$ra->shortname . ' (' . \$context . ')' . PHP_EOL;
}
"
```

---

## Step 7: Purge Caches

```bash
# Clear all caches
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php

# Restart containers (if needed)
docker compose -f docker-compose.moodlehq.yml restart moodle
```

---

## Step 8: Test Access

```bash
# Test Program Owner capability
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;

\$username = 'john.doe'; // Replace with actual username
\$user = \$DB->get_record('user', ['username' => \$username, 'deleted' => 0], 'id');

if (!\$user) {
    echo 'User not found' . PHP_EOL;
    exit(1);
}

\$sysctx = context_system::instance();
\$has_po = has_capability('local/sceh_rules:programowner', \$sysctx, \$user->id);
\$has_sa = has_capability('local/sceh_rules:systemadmin', \$sysctx, \$user->id);

echo 'User: ' . \$username . PHP_EOL;
echo 'Program Owner capability: ' . (\$has_po ? '✓ YES' : '✗ NO') . PHP_EOL;
echo 'System Admin capability: ' . (\$has_sa ? '✓ YES' : '✗ NO') . PHP_EOL;
"
```

---

## Troubleshooting

### Issue: Script fails with "Required role missing"

```bash
# Check if role creation failed
docker logs moodlehq-dev-moodle-1 | grep -i "role"

# Manually create role if needed
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
create_role('SCEH Program Owner', 'sceh_program_owner', 'Management of specific course categories', 'manager');
echo 'Role created' . PHP_EOL;
"
```

### Issue: Category not found

```bash
# Create category manually
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
core_course_category::create([
    'name' => 'Allied Health Programs',
    'idnumber' => 'allied-health',
    'parent' => 0,
    'visible' => 1,
]);
echo 'Category created' . PHP_EOL;
"
```

### Issue: User not found

```bash
# List all non-deleted users
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$users = \$DB->get_records('user', ['deleted' => 0], 'username', 'id,username,email', 0, 20);
foreach (\$users as \$user) {
    if (\$user->username !== 'guest') {
        echo \$user->username . ' (' . \$user->email . ')' . PHP_EOL;
    }
}
"
```

---

## Rollback (If Needed)

```bash
# Remove custom roles
docker exec moodlehq-dev-moodle-1 php -r "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$custom_roles = ['sceh_system_admin', 'sceh_program_owner', 'sceh_trainer', 'sceh_program_owner_competency'];
foreach (\$custom_roles as \$shortname) {
    \$role = \$DB->get_record('role', ['shortname' => \$shortname], 'id');
    if (\$role) {
        delete_role(\$role->id);
        echo 'Deleted: ' . \$shortname . PHP_EOL;
    }
}
"

# Purge caches
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php
```

---

## Quick Reference

**Create roles with real users:**
```bash
docker exec -u www-data moodlehq-dev-moodle-1 php /var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=apply-real-env --category-idnumber=allied-health --program-owner-usernames=john.doe
```

**Verify roles:**
```bash
docker exec moodlehq-dev-moodle-1 php -r "define('CLI_SCRIPT',true);require('/var/www/html/public/config.php');global \$DB;\$r=\$DB->get_records('role',null,'','shortname,name');foreach(\$r as \$x)echo \$x->shortname.PHP_EOL;"
```

**Purge caches:**
```bash
docker exec moodlehq-dev-moodle-1 php /var/www/html/public/admin/cli/purge_caches.php
```

---

**Document Version:** 1.0  
**Last Updated:** 2026-03-10  
**Execution Time:** ~2 minutes
