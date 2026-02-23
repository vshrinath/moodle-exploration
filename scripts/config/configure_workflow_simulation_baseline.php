<?php
/**
 * Configure Workflow Simulation Baseline (WF-01 to WF-04)
 *
 * Idempotent setup for local workflow testing:
 * - Ensures mock users exist with stable idnumbers
 * - Ensures required role assignments
 * - Enforces Program Owner category scoping (no system-level assignment)
 * - Ensures mock cohort membership baseline
 * - Ensures Program Owner can review/manage course + quiz content
 *
 * Usage:
 *   php scripts/config/configure_workflow_simulation_baseline.php [--mode=local|verify-real-env|apply-real-env|prod] [--dry-run] [--category-idnumber=<id>] [--program-owner-usernames=<u1,u2,...>]
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

global $DB;

init_cli_admin('moodle/site:config');

$mode = 'local';
$dryrun = false;
$categoryidnumber = null;
$programownerusernames = [];
foreach ($argv as $arg) {
    if (strpos($arg, '--mode=') === 0) {
        $mode = substr($arg, 7);
    }
    if ($arg === '--dry-run') {
        $dryrun = true;
    }
    if (strpos($arg, '--category-idnumber=') === 0) {
        $categoryidnumber = substr($arg, 20);
    }
    if (strpos($arg, '--program-owner-usernames=') === 0) {
        $raw = trim(substr($arg, 27));
        if ($raw !== '') {
            $programownerusernames = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
    }
}

if ($mode === 'prod') {
    $mode = 'verify-real-env';
}

if (!in_array($mode, ['local', 'verify-real-env', 'apply-real-env'], true)) {
    fwrite(STDERR, "ERROR: Invalid mode '{$mode}'. Use --mode=local|verify-real-env|apply-real-env.\n");
    exit(1);
}

if (in_array($mode, ['verify-real-env', 'apply-real-env'], true) && empty($categoryidnumber)) {
    fwrite(STDERR, "ERROR: --category-idnumber is required for real environment modes.\n");
    exit(1);
}

if ($mode === 'apply-real-env' && $dryrun) {
    fwrite(STDERR, "ERROR: --dry-run is not valid with --mode=apply-real-env. Use --mode=verify-real-env --dry-run for read-only checks.\n");
    exit(1);
}

$GLOBALS['wf_baseline_mode'] = $mode;
$GLOBALS['wf_baseline_dryrun'] = $dryrun;

echo "=== Workflow Simulation Baseline (WF-01 to WF-04) ===\n";
echo "MODE\t{$mode}\n";
echo "DRY_RUN\t" . ($dryrun ? 'true' : 'false') . "\n\n";

/**
 * Execute mutating action unless dry-run or prod mode.
 */
function apply_change(string $label, callable $action): void {
    $mode = $GLOBALS['wf_baseline_mode'];
    $dryrun = $GLOBALS['wf_baseline_dryrun'];
    if ($mode === 'verify-real-env') {
        echo "VERIFY_ONLY\t{$label}\n";
        return;
    }
    if ($dryrun) {
        echo "DRYRUN\t{$label}\n";
        return;
    }
    $action();
}

/**
 * Ensure role exists by shortname.
 */
function get_role_id_by_shortname(string $shortname): int {
    global $DB;
    $role = $DB->get_record('role', ['shortname' => $shortname], 'id,shortname', MUST_EXIST);
    return (int)$role->id;
}

/**
 * Ensure role exists by shortname, create if missing.
 */
function ensure_role_exists(string $shortname, string $name, string $description, string $archetype = 'manager'): int {
    global $DB;
    $role = $DB->get_record('role', ['shortname' => $shortname], 'id,shortname', IGNORE_MISSING);
    if ($role) {
        echo "ROLE_EXISTS\t{$shortname}\tID={$role->id}\n";
        return (int)$role->id;
    }

    apply_change("CREATE_ROLE {$shortname}", function() use ($name, $shortname, $description, $archetype): void {
        create_role($name, $shortname, $description, $archetype);
    });

    $created = $DB->get_record('role', ['shortname' => $shortname], 'id,shortname', IGNORE_MISSING);
    if (!$created) {
        throw new RuntimeException("Required role missing: {$shortname}");
    }
    echo "ROLE_CREATED\t{$shortname}\tID={$created->id}\n";
    return (int)$created->id;
}

/**
 * Ensure a user exists and has expected profile data.
 */
function ensure_mock_user(array $spec): stdClass {
    global $DB;

    $user = $DB->get_record('user', ['username' => $spec['username'], 'deleted' => 0], '*', IGNORE_MISSING);
    if (!$user) {
        apply_change("CREATE_USER {$spec['username']}", function() use (&$user, $spec, $DB): void {
            $password = bin2hex(random_bytes(12)) . 'Aa1!';
            $created = create_user_record($spec['username'], $password, 'manual');
            $user = $DB->get_record('user', ['id' => $created->id], '*', MUST_EXIST);
        });
        if (!$user) {
            echo "MISSING_USER\t{$spec['username']}\n";
            return (object)[
                'id' => 0,
                'username' => $spec['username'],
                'firstname' => '',
                'lastname' => '',
                'email' => '',
                'idnumber' => '',
            ];
        }
        echo "CREATED_USER\t{$spec['username']}\n";
    }

    $update = new stdClass();
    $update->id = $user->id;
    $update->firstname = $spec['firstname'];
    $update->lastname = $spec['lastname'];
    $update->email = $spec['email'];
    $update->idnumber = $spec['idnumber'];
    $update->country = 'US';
    $update->confirmed = 1;
    $update->forcepasswordchange = 1;
    apply_change("SYNC_USER {$spec['username']}", function() use ($update): void {
        user_update_user($update, false, false);
    });
    echo "SYNC_USER\t{$spec['username']}\tIDNUMBER={$spec['idnumber']}\n";

    return $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
}

/**
 * Ensure role assignment exists.
 */
function ensure_role_assignment(int $userid, int $roleid, int $contextid): void {
    if ($userid <= 0) {
        echo "SKIP_ROLE_ASSIGNMENT\tUSER_MISSING\tROLE={$roleid}\tCTX={$contextid}\n";
        return;
    }
    if (!user_has_role_assignment($userid, $roleid, $contextid)) {
        apply_change("ASSIGN_ROLE USER={$userid} ROLE={$roleid} CTX={$contextid}", function() use ($roleid, $userid, $contextid): void {
            role_assign($roleid, $userid, $contextid);
        });
        echo "ASSIGNED_ROLE\tUSER={$userid}\tROLE={$roleid}\tCTX={$contextid}\n";
        return;
    }
    echo "ROLE_EXISTS\tUSER={$userid}\tROLE={$roleid}\tCTX={$contextid}\n";
}

/**
 * Ensure role assignment is removed.
 */
function ensure_role_unassigned(int $userid, int $roleid, int $contextid): void {
    if ($userid <= 0) {
        echo "SKIP_ROLE_UNASSIGN\tUSER_MISSING\tROLE={$roleid}\tCTX={$contextid}\n";
        return;
    }
    if (!user_has_role_assignment($userid, $roleid, $contextid)) {
        echo "ROLE_ABSENT\tUSER={$userid}\tROLE={$roleid}\tCTX={$contextid}\n";
        return;
    }
    apply_change("UNASSIGN_ROLE USER={$userid} ROLE={$roleid} CTX={$contextid}", function() use ($roleid, $userid, $contextid): void {
        role_unassign($roleid, $userid, $contextid);
    });
    echo "UNASSIGNED_ROLE\tUSER={$userid}\tROLE={$roleid}\tCTX={$contextid}\n";
}

/**
 * Ensure capability exists and is allowed for role at context.
 */
function ensure_capability_allow(int $roleid, int $contextid, string $capability): void {
    global $DB;
    if (!$DB->record_exists('capabilities', ['name' => $capability])) {
        throw new RuntimeException("Missing capability: {$capability}");
    }
    apply_change("ALLOW_CAP ROLE={$roleid} CTX={$contextid} {$capability}", function() use ($capability, $roleid, $contextid): void {
        assign_capability($capability, CAP_ALLOW, $roleid, $contextid, true);
    });
    echo "ALLOW_CAP\tROLE={$roleid}\tCTX={$contextid}\t{$capability}\n";
}

/**
 * Ensure course category exists by idnumber.
 */
function ensure_category(string $idnumber, string $name, int $parent = 0): stdClass {
    global $DB;
    $category = $DB->get_record('course_categories', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
    if ($category) {
        echo "CATEGORY_EXISTS\tID={$category->id}\tIDNUMBER={$idnumber}\n";
        return $category;
    }

    apply_change("CREATE_CATEGORY {$idnumber}", function() use ($name, $idnumber, $parent): void {
        core_course_category::create([
            'name' => $name,
            'idnumber' => $idnumber,
            'parent' => $parent,
            'visible' => 1,
        ]);
    });

    $created = $DB->get_record('course_categories', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
    if ($created) {
        echo "CATEGORY_CREATED\tID={$created->id}\tIDNUMBER={$idnumber}\n";
        return $created;
    }

    throw new RuntimeException("Required category missing: {$idnumber}");
}

/**
 * Ensure cohort exists by idnumber.
 */
function ensure_cohort(string $idnumber, string $name, int $contextid): stdClass {
    global $DB;
    $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
    if ($cohort) {
        echo "COHORT_EXISTS\tID={$cohort->id}\tIDNUMBER={$idnumber}\n";
        return $cohort;
    }

    $record = new stdClass();
    $record->name = $name;
    $record->idnumber = $idnumber;
    $record->description = 'Workflow simulation baseline cohort';
    $record->descriptionformat = FORMAT_HTML;
    $record->contextid = $contextid;
    $record->visible = 1;
    $record->component = '';
    $record->timecreated = time();
    $record->timemodified = time();

    apply_change("CREATE_COHORT {$idnumber}", function() use ($record): void {
        cohort_add_cohort($record);
    });

    $created = $DB->get_record('cohort', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
    if ($created) {
        echo "COHORT_CREATED\tID={$created->id}\tIDNUMBER={$idnumber}\n";
        return $created;
    }

    throw new RuntimeException("Required cohort missing: {$idnumber}");
}

// 1) Roles and users.
$sysctx = context_system::instance();

$role_sysadmin = ensure_role_exists(
    'sceh_system_admin',
    'SCEH System Administrator',
    'Full system administration for SCEH'
);
$role_program_owner = ensure_role_exists(
    'sceh_program_owner',
    'SCEH Program Owner',
    'Management of specific course categories'
);
$role_trainer = ensure_role_exists(
    'sceh_trainer',
    'SCEH Trainer',
    'Training and assessment role'
);
$role_student = get_role_id_by_shortname('student');
$role_program_owner_competency = ensure_role_exists(
    'sceh_program_owner_competency',
    'SCEH Program Owner Competency',
    'Narrow competency management role for program owners',
    'manager'
);

$userspecs = [
    [
        'username' => 'mock.sysadmin',
        'firstname' => 'Mock',
        'lastname' => 'System Admin',
        'email' => 'mock.sysadmin@example.local',
        'idnumber' => 'MOCK-SYSADMIN-001',
    ],
    [
        'username' => 'mock.programowner',
        'firstname' => 'Mock',
        'lastname' => 'Program Owner',
        'email' => 'mock.programowner@example.local',
        'idnumber' => 'MOCK-PROGRAMOWNER-001',
    ],
    [
        'username' => 'mock.trainer',
        'firstname' => 'Mock',
        'lastname' => 'Trainer',
        'email' => 'mock.trainer@example.local',
        'idnumber' => 'MOCK-TRAINER-001',
    ],
    [
        'username' => 'mock.learner',
        'firstname' => 'Mock',
        'lastname' => 'Learner',
        'email' => 'mock.learner@example.local',
        'idnumber' => 'MOCK-LEARNER-001',
    ],
];

$users = [];
if ($mode === 'local') {
    foreach ($userspecs as $spec) {
        $users[$spec['username']] = ensure_mock_user($spec);
    }

    ensure_role_assignment((int)$users['mock.sysadmin']->id, $role_sysadmin, $sysctx->id);
    ensure_role_assignment((int)$users['mock.trainer']->id, $role_trainer, $sysctx->id);
    ensure_role_assignment((int)$users['mock.learner']->id, $role_student, $sysctx->id);
    ensure_role_assignment((int)$users['mock.programowner']->id, $role_program_owner_competency, $sysctx->id);
} else {
    // Explicitly block mock account writes in real environment modes.
    echo "VERIFY_ONLY\tSkipping all mock-user and mock-cohort mutations in real environment modes\n";
    // Optional safety signal if mock users already exist.
    foreach (['mock.sysadmin', 'mock.programowner', 'mock.trainer', 'mock.learner'] as $mockname) {
        $mock = $DB->get_record('user', ['username' => $mockname, 'deleted' => 0], 'id,username', IGNORE_MISSING);
        if ($mock) {
            echo "WARNING\tMOCK_USER_PRESENT\t{$mock->username}\n";
        }
    }
}

// 2) Program Owner category scoping.
$targetcategoryidnumber = $mode === 'local' ? 'allied-health' : $categoryidnumber;
$targetcategoryname = $mode === 'local' ? 'Allied Health Programs' : $categoryidnumber;
$targetcategory = ensure_category($targetcategoryidnumber, $targetcategoryname, 0);
$targetctx = context_coursecat::instance((int)$targetcategory->id);

if ($mode === 'local') {
    ensure_role_unassigned((int)$users['mock.programowner']->id, $role_program_owner, $sysctx->id);
    ensure_role_assignment((int)$users['mock.programowner']->id, $role_program_owner, $targetctx->id);
} else {
    if (empty($programownerusernames)) {
        echo "VERIFY_ONLY\tNo --program-owner-usernames provided; skipping real user assignment updates\n";
    } else {
        foreach ($programownerusernames as $username) {
            if (strpos($username, 'mock.') === 0) {
                throw new RuntimeException("Mock username '{$username}' is not allowed in real environment modes");
            }
            $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], 'id,username', MUST_EXIST);
            ensure_role_assignment((int)$user->id, $role_program_owner, $targetctx->id);
            ensure_role_assignment((int)$user->id, $role_program_owner_competency, $sysctx->id);
            echo "REAL_USER_ASSIGNMENT_OK\t{$user->username}\n";
        }
    }
}

// 3) WF-04 capability baseline for Program Owner role.
$program_owner_caps = [
    'moodle/course:view',
    'moodle/course:manageactivities',
    'moodle/question:add',
    'moodle/question:editall',
    'mod/resource:addinstance',
    'mod/quiz:addinstance',
    'mod/quiz:view',
    'mod/quiz:manage',
];
foreach ($program_owner_caps as $cap) {
    ensure_capability_allow($role_program_owner, $sysctx->id, $cap);
}

$program_owner_comp_caps = [
    'moodle/competency:competencymanage',
    'moodle/competency:competencyview',
    'moodle/competency:coursecompetencymanage',
    'moodle/competency:coursecompetencyconfigure',
];
foreach ($program_owner_comp_caps as $cap) {
    ensure_capability_allow($role_program_owner_competency, $sysctx->id, $cap);
}

// 4) Cohort baseline for WF-02.
if ($mode === 'local') {
    $cohort = ensure_cohort('mock-allied-2026', 'Mock Allied Cohort 2026', $sysctx->id);
    apply_change("ADD_COHORT_MEMBER mock.trainer", function() use ($cohort, $users): void {
        cohort_add_member((int)$cohort->id, (int)$users['mock.trainer']->id);
    });
    echo "COHORT_MEMBER_OK\tCOHORT={$cohort->id}\tUSER={$users['mock.trainer']->username}\n";

    apply_change("ADD_COHORT_MEMBER mock.learner", function() use ($cohort, $users): void {
        cohort_add_member((int)$cohort->id, (int)$users['mock.learner']->id);
    });
    echo "COHORT_MEMBER_OK\tCOHORT={$cohort->id}\tUSER={$users['mock.learner']->username}\n";
}

// 5) Cache purge.
if ($mode === 'local' || $mode === 'apply-real-env') {
    apply_change("PURGE_CACHES", function(): void {
        purge_all_caches();
    });
    if ($mode === 'local') {
        echo "\nDONE\tWorkflow simulation baseline applied.\n";
    } else {
        echo "\nDONE\tReal environment baseline capabilities applied.\n";
    }
} else {
    echo "\nDONE\tReal environment verification completed (no mutations).\n";
}
