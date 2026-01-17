<?php
/**
 * Verify Kirkpatrick Level 3 (Behavior) Application Tracking Configuration
 */

require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

echo "=== Verifying Kirkpatrick Level 3 (Behavior) Configuration ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check 1: Verify Level 3 data tables
echo "1. Checking Level 3 data tables...\n";
$dbman = $DB->get_manager();

$tables = [
    'kirkpatrick_level3_behavior',
    'kirkpatrick_followup_schedule',
    'kirkpatrick_workplace_performance',
    'kirkpatrick_longitudinal_tracking'
];

foreach ($tables as $tablename) {
    $table = new xmldb_table($tablename);
    if ($dbman->table_exists($table)) {
        $success[] = "✓ Table $tablename exists";
    } else {
        $errors[] = "✗ Table $tablename not found";
    }
}

// Check 2: Verify Portfolio plugin configuration
echo "\n2. Checking Portfolio plugin configuration...\n";
$portfolio_enabled = get_config('core', 'enableportfolios');
if ($portfolio_enabled) {
    $success[] = "✓ Portfolio functionality enabled";
    
    $evidence_types = get_config('kirkpatrick_level3', 'portfolio_evidence_types');
    if ($evidence_types) {
        $success[] = "✓ Evidence types configured";
    }
} else {
    $warnings[] = "⚠ Portfolio functionality not enabled";
}

// Check 3: Verify follow-up survey system
echo "\n3. Checking follow-up survey system...\n";
$followup_enabled = get_config('kirkpatrick_level3', 'enable_automated_followups');
if ($followup_enabled) {
    $success[] = "✓ Automated follow-ups enabled";
    
    $intervals = get_config('kirkpatrick_level3', 'followup_intervals');
    $success[] = "✓ Follow-up intervals: $intervals days";
} else {
    $warnings[] = "⚠ Automated follow-ups not enabled";
}

// Check 4: Verify workplace integration
echo "\n4. Checking workplace performance integration...\n";
$workplace_enabled = get_config('kirkpatrick_level3', 'enable_external_data');
if ($workplace_enabled) {
    $success[] = "✓ External workplace data integration enabled";
    
    $supervisor_enabled = get_config('kirkpatrick_level3', 'supervisor_assessment_enabled');
    if ($supervisor_enabled) {
        $success[] = "✓ Supervisor assessments enabled";
    }
} else {
    $warnings[] = "⚠ Workplace data integration not enabled";
}

// Check 5: Verify longitudinal tracking
echo "\n5. Checking longitudinal tracking...\n";
$longitudinal_enabled = get_config('kirkpatrick_level3', 'enable_longitudinal_tracking');
if ($longitudinal_enabled) {
    $success[] = "✓ Longitudinal tracking enabled";
    
    $duration = get_config('kirkpatrick_level3', 'tracking_duration_months');
    $success[] = "✓ Tracking duration: $duration months";
} else {
    $warnings[] = "⚠ Longitudinal tracking not enabled";
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
    echo "\nLevel 3 (Behavior) application tracking is properly configured.\n";
}
