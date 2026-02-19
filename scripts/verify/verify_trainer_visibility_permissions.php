<?php
/**
 * Verify Trainer Visibility Permissions
 *
 * Tests that trainers can show/hide activities but cannot edit course structure.
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

// Get trainer role and user
$trainerrole = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', IGNORE_MISSING);
if (!$trainerrole) {
    echo "SKIP\tsceh_trainer role not found\n";
    exit(0);
}

$trainer = $DB->get_record('user', ['username' => 'mock.trainer', 'deleted' => 0], '*', IGNORE_MISSING);
if (!$trainer) {
    echo "SKIP\tmock.trainer user not found\n";
    exit(0);
}

echo "TRAINER\t{$trainer->username} (ID={$trainer->id})\n";
echo "ROLE\t{$trainerrole->name} (ID={$trainerrole->id})\n\n";

// Test at system context
$sysctx = context_system::instance();

$tests = [
    'visibility_control' => [
        'moodle/course:activityvisibility' => true,
        'moodle/course:manageactivities' => true,
    ],
    'course_editing' => [
        'moodle/course:update' => false,
        'moodle/course:managesections' => false,
        'moodle/question:add' => false,
        'moodle/question:editall' => false,
        'mod/quiz:addinstance' => false,
    ],
];

$passed = 0;
$failed = 0;

foreach ($tests as $category => $capabilities) {
    echo strtoupper(str_replace('_', ' ', $category)) . ":\n";
    
    foreach ($capabilities as $cap => $expected) {
        $has = has_capability($cap, $sysctx, $trainer->id);
        $result = ($has === $expected);
        
        if ($result) {
            echo "  ✓ {$cap}: " . ($expected ? 'ALLOW' : 'DENY') . "\n";
            $passed++;
        } else {
            echo "  ✗ {$cap}: expected " . ($expected ? 'ALLOW' : 'DENY') . 
                 ", got " . ($has ? 'ALLOW' : 'DENY') . "\n";
            $failed++;
        }
    }
    
    echo "\n";
}

// Summary
echo "SUMMARY:\n";
echo "  Passed: {$passed}\n";
echo "  Failed: {$failed}\n\n";

if ($failed === 0) {
    echo "RESULT\t✓ All tests passed\n";
    echo "NOTE\tTrainers can show/hide activities but cannot edit course structure\n";
    exit(0);
} else {
    echo "RESULT\t✗ Some tests failed\n";
    echo "ACTION\tRun scripts/config/configure_trainer_visibility_permissions.php\n";
    exit(1);
}
