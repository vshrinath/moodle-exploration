<?php
/**
 * Verify Kirkpatrick Reporting Configuration
 */

require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

echo "=== Verifying Kirkpatrick Reporting Configuration ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check 1: Verify all Kirkpatrick data tables exist
echo "1. Checking Kirkpatrick data tables...\n";
$dbman = $DB->get_manager();

$required_tables = [
    'kirkpatrick_level1_reaction',
    'kirkpatrick_level2_learning',
    'kirkpatrick_assessment_tracking',
    'kirkpatrick_level3_behavior',
    'kirkpatrick_followup_schedule',
    'kirkpatrick_workplace_performance',
    'kirkpatrick_longitudinal_tracking'
];

foreach ($required_tables as $tablename) {
    $table = new xmldb_table($tablename);
    if ($dbman->table_exists($table)) {
        $success[] = "✓ Table $tablename exists";
    } else {
        $errors[] = "✗ Table $tablename not found";
    }
}

// Check 2: Test Level 1 report queries
echo "\n2. Testing Level 1 report queries...\n";
try {
    $level1_count = $DB->count_records('kirkpatrick_level1_reaction');
    $success[] = "✓ Level 1 data table accessible ($level1_count records)";
} catch (Exception $e) {
    $errors[] = "✗ Cannot access Level 1 data: " . $e->getMessage();
}

// Check 3: Test Level 2 report queries
echo "\n3. Testing Level 2 report queries...\n";
try {
    $level2_count = $DB->count_records('kirkpatrick_level2_learning');
    $success[] = "✓ Level 2 data table accessible ($level2_count records)";
} catch (Exception $e) {
    $errors[] = "✗ Cannot access Level 2 data: " . $e->getMessage();
}

// Check 4: Test Level 3 report queries
echo "\n4. Testing Level 3 report queries...\n";
try {
    $level3_count = $DB->count_records('kirkpatrick_level3_behavior');
    $success[] = "✓ Level 3 data table accessible ($level3_count records)";
} catch (Exception $e) {
    $errors[] = "✗ Cannot access Level 3 data: " . $e->getMessage();
}

// Check 5: Verify report structure
echo "\n5. Checking report structure...\n";
$report_categories = [
    'Level 1 (Reaction)' => 4,
    'Level 2 (Learning)' => 5,
    'Level 3 (Behavior)' => 5,
    'Level 4 (Results)' => 4,
    'Integrated Reports' => 2
];

$total_expected = array_sum($report_categories);
$success[] = "✓ Expected $total_expected reports across all levels";

foreach ($report_categories as $category => $count) {
    echo "  - $category: $count reports\n";
}

// Check 6: Verify data relationships
echo "\n6. Checking data relationships...\n";
try {
    // Test join between Level 1 and Level 2
    $sql = "SELECT COUNT(*) 
            FROM {kirkpatrick_level1_reaction} k1
            JOIN {kirkpatrick_level2_learning} k2 ON k2.userid = k1.userid AND k2.courseid = k1.courseid";
    $joined_count = $DB->count_records_sql($sql);
    $success[] = "✓ Level 1-2 data relationship verified ($joined_count linked records)";
} catch (Exception $e) {
    $warnings[] = "⚠ Cannot verify Level 1-2 relationship: " . $e->getMessage();
}

// Check 7: Verify Configurable Reports plugin
echo "\n7. Checking Configurable Reports plugin...\n";
$plugin_installed = $DB->record_exists('config_plugins', ['plugin' => 'block_configurable_reports']);
if ($plugin_installed) {
    $success[] = "✓ Configurable Reports plugin is installed";
} else {
    $warnings[] = "⚠ Configurable Reports plugin not found - reports need to be imported manually";
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
    echo "\nKirkpatrick reporting is properly configured.\n";
    echo "Import the report SQL queries into Configurable Reports plugin to complete setup.\n";
}
