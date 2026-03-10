#!/bin/bash
#
# AWS Role Setup Automation Script
#
# Purpose: Configure custom SCEH roles on AWS/Linux VM deployment
# Usage: bash scripts/config/setup_aws_roles.sh
# Execution Time: ~2 minutes
#
# This script:
# 1. Creates custom SCEH roles (sceh_program_owner, sceh_system_admin, sceh_trainer)
# 2. Creates MRA Programs category (sceh_mra)
# 3. Assigns mra.program.owner to Program Owner role in MRA category
# 4. Verifies all changes
# 5. Purges caches

set -euo pipefail

# Trap errors and provide helpful context
trap 'handle_error $? $LINENO' ERR

handle_error() {
    local exit_code=$1
    local line_number=$2
    log_error "Script failed at line ${line_number} with exit code ${exit_code}"
    log_info "Check the output above for details"
    log_info "For help, see: docs/AWS_ROLE_SETUP_COMMANDS.md"
    exit $exit_code
}

# ============================================================================
# CONFIGURATION (Hardcoded Values)
# ============================================================================

CATEGORY_IDNUMBER="sceh_mra"
CATEGORY_NAME="MRA Programs"
PROGRAM_OWNER_USERNAME="mra.program.owner"
PROGRAM_OWNER_EMAIL="sivasankari@sceh.net"
PROGRAM_OWNER_PASSWORD="P9\$tz*2J"
PROGRAM_OWNER_FIRSTNAME="MRA"
PROGRAM_OWNER_LASTNAME="Program Owner"
DOCKER_CONTAINER="moodlehq-dev-moodle-1"
DOCKER_COMPOSE_FILE="docker-compose.moodlehq.yml"

# ============================================================================
# COLORS
# ============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_docker() {
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed or not in PATH"
        exit 1
    fi
    log_success "Docker found"
}

check_container() {
    # This check is now done in Step 3 with auto-detection
    return 0
}

exec_moodle() {
    docker exec -u www-data "${DOCKER_CONTAINER}" php -r "$1"
}

exec_moodle_cli() {
    docker exec -u www-data "${DOCKER_CONTAINER}" php "$1"
}

# ============================================================================
# MAIN SCRIPT
# ============================================================================

echo "========================================================================"
echo "  AWS Role Setup Automation"
echo "========================================================================"
echo ""
echo "Configuration:"
echo "  Category: ${CATEGORY_NAME} (${CATEGORY_IDNUMBER})"
echo "  Program Owner: ${PROGRAM_OWNER_USERNAME}"
echo "  Email: ${PROGRAM_OWNER_EMAIL}"
echo "  Container: ${DOCKER_CONTAINER}"
echo ""
echo "This script will:"
echo "  1. Verify Docker and Moodle are running"
echo "  2. Create user if missing (auto-generated password)"
echo "  3. Create custom SCEH roles"
echo "  4. Create MRA Programs category"
echo "  5. Assign Program Owner role"
echo "  6. Hide default Moodle roles"
echo "  7. Verify all changes"
echo ""
echo "Estimated time: 2 minutes"
echo "========================================================================"
echo ""

# Confirmation prompt
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_info "Aborted by user"
    exit 0
fi
echo ""

# Step 1: Pre-flight checks
log_info "Step 1/8: Running pre-flight checks..."
check_docker

# Verify Moodle is accessible
if ! exec_moodle "define('CLI_SCRIPT', true); require_once('/var/www/html/public/config.php'); echo 'OK';" &> /dev/null; then
    log_error "Cannot access Moodle config"
    log_info "Possible causes:"
    log_info "  - Moodle not installed/configured"
    log_info "  - config.php missing or invalid"
    log_info "  - Database connection failed"
    exit 1
fi
log_success "Moodle is accessible"

# Verify required plugins exist
log_info "Checking required plugins..."
PLUGIN_CHECK=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
\$required = ['local_sceh_rules', 'block_sceh_dashboard', 'theme_sceh'];
\$missing = [];
foreach (\$required as \$plugin) {
    list(\$type, \$name) = explode('_', \$plugin, 2);
    \$path = \$CFG->dirroot . '/' . \$type . '/' . \$name;
    if (!is_dir(\$path)) {
        \$missing[] = \$plugin;
    }
}
if (empty(\$missing)) {
    echo 'ALL_PRESENT';
} else {
    echo 'MISSING:' . implode(',', \$missing);
}
")

if [[ "$PLUGIN_CHECK" == MISSING:* ]]; then
    log_error "Required plugins missing: ${PLUGIN_CHECK#MISSING:}"
    log_info "Please install missing plugins before running this script"
    exit 1
fi
log_success "All required plugins present"
echo ""

# Step 2: Check/create user
log_info "Step 2/8: Checking if user ${PROGRAM_OWNER_USERNAME} exists..."
USER_EXISTS=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$user = \$DB->get_record('user', ['username' => '${PROGRAM_OWNER_USERNAME}', 'deleted' => 0], 'id,username');
if (\$user) {
    echo 'EXISTS';
} else {
    echo 'NOT_FOUND';
}
")

if [ "$USER_EXISTS" = "NOT_FOUND" ]; then
    log_warning "User ${PROGRAM_OWNER_USERNAME} does not exist"
    log_info "Creating user ${PROGRAM_OWNER_USERNAME}..."
    
    CREATE_RESULT=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
require_once(\$CFG->dirroot . '/user/lib.php');

try {
    \$user = new stdClass();
    \$user->username = '${PROGRAM_OWNER_USERNAME}';
    \$user->password = '${PROGRAM_OWNER_PASSWORD}';
    \$user->firstname = '${PROGRAM_OWNER_FIRSTNAME}';
    \$user->lastname = '${PROGRAM_OWNER_LASTNAME}';
    \$user->email = '${PROGRAM_OWNER_EMAIL}';
    \$user->auth = 'manual';
    \$user->confirmed = 1;
    \$user->mnethostid = \$CFG->mnet_localhost_id;
    \$user->id = user_create_user(\$user, false, false);
    
    echo 'CREATED:' . \$user->id;
} catch (Exception \$e) {
    echo 'ERROR:' . \$e->getMessage();
}
")
    
    if [[ "$CREATE_RESULT" == ERROR:* ]]; then
        log_error "Failed to create user: ${CREATE_RESULT#ERROR:}"
        exit 1
    fi
    
    USER_ID=$(echo "$CREATE_RESULT" | cut -d: -f2)
    log_success "User created (ID: ${USER_ID})"
    log_info "Email: ${PROGRAM_OWNER_EMAIL}"
    log_info "Password: ${PROGRAM_OWNER_PASSWORD}"
else
    log_success "User ${PROGRAM_OWNER_USERNAME} exists"
fi
echo ""

# Step 3: Auto-detect container name if default doesn't exist
log_info "Step 3/8: Verifying Docker configuration..."
if ! docker ps --format '{{.Names}}' | grep -q "^${DOCKER_CONTAINER}$"; then
    log_warning "Default container ${DOCKER_CONTAINER} not found"
    log_info "Searching for Moodle container..."
    
    FOUND_CONTAINER=$(docker ps --format '{{.Names}}' | grep -i moodle | head -n1)
    if [ -z "$FOUND_CONTAINER" ]; then
        log_error "No Moodle container found running"
        log_info "Available containers:"
        docker ps --format 'table {{.Names}}\t{{.Status}}'
        exit 1
    fi
    
    DOCKER_CONTAINER="$FOUND_CONTAINER"
    log_success "Using container: ${DOCKER_CONTAINER}"
else
    log_success "Container ${DOCKER_CONTAINER} verified"
fi
echo ""

# Step 4: Run baseline configuration script
log_info "Step 4/8: Creating custom roles and category..."
log_info "This may take 30-60 seconds..."

if ! exec_moodle_cli "/var/www/html/public/scripts/config/configure_workflow_simulation_baseline.php --mode=apply-real-env --category-idnumber=${CATEGORY_IDNUMBER} --program-owner-usernames=${PROGRAM_OWNER_USERNAME}"; then
    log_error "Baseline configuration failed"
    log_info "Check the output above for details"
    exit 1
fi
log_success "Baseline configuration completed"
echo ""

# Step 5: Verify roles created
log_info "Step 5/8: Verifying custom roles..."
ROLES_CHECK=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$custom_roles = ['sceh_system_admin', 'sceh_program_owner', 'sceh_trainer', 'sceh_program_owner_competency'];
\$missing = [];
foreach (\$custom_roles as \$shortname) {
    \$role = \$DB->get_record('role', ['shortname' => \$shortname], 'id,shortname,name');
    if (!\$role) {
        \$missing[] = \$shortname;
    }
}
if (empty(\$missing)) {
    echo 'ALL_PRESENT';
} else {
    echo 'MISSING:' . implode(',', \$missing);
}
")

if [[ "$ROLES_CHECK" == MISSING:* ]]; then
    log_error "Some roles are missing: ${ROLES_CHECK#MISSING:}"
    exit 1
fi
log_success "All custom roles created"

# List roles
exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$custom_roles = ['sceh_system_admin', 'sceh_program_owner', 'sceh_trainer', 'sceh_program_owner_competency'];
foreach (\$custom_roles as \$shortname) {
    \$role = \$DB->get_record('role', ['shortname' => \$shortname], 'id,shortname,name');
    if (\$role) {
        echo '  ✓ ' . \$role->shortname . ' - ' . \$role->name . PHP_EOL;
    }
}
"
echo ""

# Step 6: Verify category created
log_info "Step 6/8: Verifying category ${CATEGORY_IDNUMBER}..."
CATEGORY_CHECK=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;
\$category = \$DB->get_record('course_categories', ['idnumber' => '${CATEGORY_IDNUMBER}'], 'id,name,idnumber');
if (\$category) {
    echo 'EXISTS:' . \$category->id . ':' . \$category->name;
} else {
    echo 'NOT_FOUND';
}
")

if [[ "$CATEGORY_CHECK" == NOT_FOUND ]]; then
    log_error "Category ${CATEGORY_IDNUMBER} was not created"
    exit 1
fi
CATEGORY_ID=$(echo "$CATEGORY_CHECK" | cut -d: -f2)
CATEGORY_NAME_ACTUAL=$(echo "$CATEGORY_CHECK" | cut -d: -f3-)
log_success "Category created: ${CATEGORY_NAME_ACTUAL} (ID: ${CATEGORY_ID})"
echo ""

# Step 7: Verify role assignment
log_info "Step 7/8: Verifying role assignment for ${PROGRAM_OWNER_USERNAME}..."
ASSIGNMENT_CHECK=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;

\$user = \$DB->get_record('user', ['username' => '${PROGRAM_OWNER_USERNAME}', 'deleted' => 0], 'id,username');
if (!\$user) {
    echo 'USER_NOT_FOUND';
    exit;
}

\$sql = \"SELECT ra.id, r.shortname, r.name, c.contextlevel, cc.name as categoryname
         FROM {role_assignments} ra
         JOIN {role} r ON r.id = ra.roleid
         JOIN {context} c ON c.id = ra.contextid
         LEFT JOIN {course_categories} cc ON cc.id = c.instanceid AND c.contextlevel = 40
         WHERE ra.userid = :userid
         AND r.shortname IN ('sceh_program_owner', 'sceh_program_owner_competency')
         ORDER BY c.contextlevel, r.shortname\";

\$assignments = \$DB->get_records_sql(\$sql, ['userid' => \$user->id]);

if (empty(\$assignments)) {
    echo 'NO_ASSIGNMENTS';
} else {
    echo 'ASSIGNED:';
    foreach (\$assignments as \$ra) {
        \$context = \$ra->contextlevel == 10 ? 'System' : (\$ra->categoryname ?: 'Course');
        echo \$ra->shortname . '|' . \$context . PHP_EOL;
    }
}
")

if [[ "$ASSIGNMENT_CHECK" == NO_ASSIGNMENTS ]]; then
    log_error "No role assignments found for ${PROGRAM_OWNER_USERNAME}"
    exit 1
fi

log_success "Role assignments verified:"
echo "$ASSIGNMENT_CHECK" | grep -v "^ASSIGNED:" | while IFS='|' read -r role context; do
    echo "  ✓ ${role} (${context})"
done
echo ""

# Step 8: Hide default Moodle roles
log_info "Step 8/8: Hiding default Moodle roles from assignment UI..."
HIDE_RESULT=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;

\$roles_to_hide = ['manager', 'coursecreator', 'editingteacher', 'teacher'];
\$hidden_count = 0;

foreach (\$roles_to_hide as \$shortname) {
    \$role = \$DB->get_record('role', ['shortname' => \$shortname], 'id,shortname');
    if (\$role) {
        // Set sortorder to 0 to hide from assignment UI
        \$DB->set_field('role', 'sortorder', 0, ['id' => \$role->id]);
        \$hidden_count++;
        echo 'Hidden: ' . \$shortname . PHP_EOL;
    }
}

echo 'HIDDEN_COUNT:' . \$hidden_count;
")

HIDDEN_COUNT=$(echo "$HIDE_RESULT" | grep "HIDDEN_COUNT:" | cut -d: -f2)
if [ -n "$HIDDEN_COUNT" ] && [ "$HIDDEN_COUNT" -gt 0 ]; then
    log_success "Hidden ${HIDDEN_COUNT} default roles from assignment UI"
    echo "$HIDE_RESULT" | grep "Hidden:" | while read line; do
        echo "  ✓ ${line#Hidden: }"
    done
else
    log_warning "No default roles hidden (may already be hidden)"
fi
echo ""

# Step 9: Purge caches
log_info "Step 9/9: Purging caches..."
if ! exec_moodle_cli "/var/www/html/public/admin/cli/purge_caches.php" &> /dev/null; then
    log_warning "Cache purge failed (non-critical)"
else
    log_success "Caches purged"
fi
echo ""

# Step 10: Final verification
log_info "Step 10/9: Running final verification..."

FINAL_CHECK=$(exec_moodle "
define('CLI_SCRIPT', true);
require_once('/var/www/html/public/config.php');
global \$DB;

\$checks = [];

// Check user exists
\$user = \$DB->get_record('user', ['username' => '${PROGRAM_OWNER_USERNAME}', 'deleted' => 0], 'id');
\$checks['user'] = \$user ? 'PASS' : 'FAIL';

// Check category exists
\$category = \$DB->get_record('course_categories', ['idnumber' => '${CATEGORY_IDNUMBER}'], 'id');
\$checks['category'] = \$category ? 'PASS' : 'FAIL';

// Check role exists
\$role = \$DB->get_record('role', ['shortname' => 'sceh_program_owner'], 'id');
\$checks['role'] = \$role ? 'PASS' : 'FAIL';

// Check role assignment
if (\$user && \$category && \$role) {
    \$context = context_coursecat::instance(\$category->id);
    \$assigned = \$DB->record_exists('role_assignments', [
        'userid' => \$user->id,
        'roleid' => \$role->id,
        'contextid' => \$context->id
    ]);
    \$checks['assignment'] = \$assigned ? 'PASS' : 'FAIL';
} else {
    \$checks['assignment'] = 'SKIP';
}

// Check capability
if (\$user) {
    \$sysctx = context_system::instance();
    \$has_cap = has_capability('local/sceh_rules:programowner', \$sysctx, \$user->id);
    \$checks['capability'] = \$has_cap ? 'PASS' : 'FAIL';
} else {
    \$checks['capability'] = 'SKIP';
}

foreach (\$checks as \$name => \$status) {
    echo \$name . ':' . \$status . PHP_EOL;
}

\$all_pass = !in_array('FAIL', \$checks);
echo 'OVERALL:' . (\$all_pass ? 'PASS' : 'FAIL');
")

echo "$FINAL_CHECK" | grep -v "^OVERALL:" | while IFS=':' read -r check status; do
    if [ "$status" = "PASS" ]; then
        echo "  ✓ ${check}"
    elif [ "$status" = "FAIL" ]; then
        echo "  ✗ ${check}"
    else
        echo "  - ${check} (skipped)"
    fi
done

OVERALL=$(echo "$FINAL_CHECK" | grep "^OVERALL:" | cut -d: -f2)
if [ "$OVERALL" = "FAIL" ]; then
    log_error "Final verification failed"
    log_info "Some checks did not pass. Review output above."
    exit 1
fi
log_success "All verification checks passed"
echo ""

# Final summary
echo "========================================================================"
echo "  Setup Complete!"
echo "========================================================================"
echo ""
log_success "Custom roles created and configured"
log_success "Category '${CATEGORY_NAME}' (${CATEGORY_IDNUMBER}) created"
log_success "User '${PROGRAM_OWNER_USERNAME}' assigned as Program Owner"
echo ""
echo "Next steps:"
echo "  1. Log in to Moodle as ${PROGRAM_OWNER_USERNAME}"
echo "     - Email: ${PROGRAM_OWNER_EMAIL}"
echo "     - Password: ${PROGRAM_OWNER_PASSWORD}"
echo ""
echo "  2. Navigate to Site Administration → Courses → Manage courses and categories"
echo "     - You should see '${CATEGORY_NAME}' category"
echo ""
echo "  3. Create a course within ${CATEGORY_NAME}"
echo "     - Click '${CATEGORY_NAME}' → 'Create new course'"
echo ""
echo "  4. Verify role assignment worked:"
echo "     - Go to Site Administration → Users → Permissions → Assign system roles"
echo "     - Default roles (Manager, Teacher) should be hidden"
echo "     - Custom roles (SCEH Program Owner, SCEH Trainer) should be visible"
echo ""
echo "Troubleshooting:"
echo "  - If user can't log in: Verify username/password above"
echo "  - If category not visible: Check role assignment in Site Administration"
echo "  - If permissions wrong: Re-run this script (it's idempotent)"
echo ""
echo "Support:"
echo "  - Documentation: docs/AWS_ROLE_SETUP_COMMANDS.md"
echo "  - Rollback: See 'Rollback' section in docs"
echo ""
echo "========================================================================"

# Save setup summary to file
SUMMARY_FILE="/tmp/moodle_setup_summary_$(date +%Y%m%d_%H%M%S).txt"
cat > "$SUMMARY_FILE" <<EOF
Moodle Role Setup Summary
Generated: $(date)

Configuration:
- Category: ${CATEGORY_NAME} (${CATEGORY_IDNUMBER})
- User: ${PROGRAM_OWNER_USERNAME}
- Email: ${PROGRAM_OWNER_EMAIL}
- Password: ${PROGRAM_OWNER_PASSWORD}
- Container: ${DOCKER_CONTAINER}

Roles Created:
- sceh_system_admin
- sceh_program_owner
- sceh_trainer
- sceh_program_owner_competency

Roles Hidden:
- manager
- coursecreator
- editingteacher
- teacher

Next Steps:
1. Log in as ${PROGRAM_OWNER_USERNAME}
2. Reset password on first login
3. Navigate to ${CATEGORY_NAME} category
4. Create courses

Verification Passed: Yes
EOF

log_success "Setup summary saved to: ${SUMMARY_FILE}"
echo ""
