<?php
/**
 * Verify Kirkpatrick Level 2 (Learning) Assessment Framework Configuration
 */

define('CLI_SCRIPT', true);
$config_paths = [
    __DIR__ . '/config.php',
    '/bitnami/moodle/config.php',
    '/opt/bitnami/moodle/config.php',
];
$config_path = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}
if (!$config_path) {
    fwrite(STDERR, "ERROR: Moodle config.php not found\n");
    exit(1);
}
require_once($config_path);
require_once($CFG->libdir . '/adminlib.php');

// Ensure we're running as admin in CLI
$admin = get_admin();
if (!$admin) {
    fwrite(STDERR, "ERROR: No admin user found\n");
    exit(1);
}
\core\session\manager::set_user($admin);
require_capability('moodle/site:config', context_system::instance());

echo "=== Verifying Kirkpatrick Level 2 (Learning) Configuration ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check 1: Verify Level 2 data tables
echo "1. Checking Level 2 data tables...\n";
$dbman = $DB->get_manager();

$tables = ['kirkpatrick_level2_learning', 'kirkpatrick_assessment_tracking'];
foreach ($tables as $tablename) {
    $table = new xmldb_table($tablename);
    if ($dbman->table_exists($table)) {
        $success[] = "✓ Table $tablename exists";
    } else {
        $errors[] = "✗ Table $tablename not found";
    }
}

// Check 2: Verify pre/post assessment configuration
echo "\n2. Checking pre/post assessment configuration...\n";
$prepost_enabled = get_config('kirkpatrick_level2', 'enable_prepost_comparison');
if ($prepost_enabled) {
    $success[] = "✓ Pre/post assessment comparison enabled";
    
    $threshold = get_config('kirkpatrick_level2', 'minimum_improvement_threshold');
    $success[] = "✓ Improvement threshold: $threshold%";
} else {
    $warnings[] = "⚠ Pre/post assessment comparison not enabled";
}

// Check 3: Verify competency framework
echo "\n3. Checking competency framework...\n";
$competency_enabled = get_config('core_competency', 'enabled');
if ($competency_enabled) {
    $success[] = "✓ Competency framework enabled";
    
    $push_ratings = get_config('core_competency', 'pushcourseratingstouserplans');
    if ($push_ratings) {
        $success[] = "✓ Course ratings push to learning plans enabled";
    }
} else {
    $errors[] = "✗ Competency framework not enabled";
}

// Check 4: Verify badge system integration
echo "\n4. Checking badge system integration...\n";
$badges_enabled = get_config('core', 'enablebadges');
if ($badges_enabled) {
    $success[] = "✓ Badges system enabled";
    
    $auto_award = get_config('kirkpatrick_level2', 'auto_award_on_competency');
    if ($auto_award) {
        $success[] = "✓ Auto-award badges on competency achievement enabled";
    }
} else {
    $errors[] = "✗ Badges system not enabled";
}

// Check 5: Verify learning analytics
echo "\n5. Checking learning analytics...\n";
$analytics_enabled = get_config('analytics', 'enabled');
if ($analytics_enabled) {
    $success[] = "✓ Analytics engine enabled";
    
    $show_progress = get_config('kirkpatrick_level2', 'show_competency_progress');
    if ($show_progress) {
        $success[] = "✓ Competency progress visualization enabled";
    }
} else {
    $warnings[] = "⚠ Analytics engine not enabled";
}

// Check 6: Verify learning objectives
echo "\n6. Checking learning objectives configuration...\n";
$outcomes_enabled = get_config('core', 'enableoutcomes');
if ($outcomes_enabled) {
    $success[] = "✓ Learning outcomes/objectives enabled";
} else {
    $warnings[] = "⚠ Learning outcomes not enabled";
}

// Summary
echo "\n=== Verification Summary ===\n\n";

if (!empty($success)) {
    echo "Successes:\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
}

if (!empty($warnings)) {
    echo "\nWarnings:\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
}

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
    echo "\n✗ Verification FAILED\n";
    exit(1);
} else {
    echo "\n✓ Verification PASSED\n";
    echo "\nLevel 2 (Learning) assessment framework is properly configured.\n";
}
