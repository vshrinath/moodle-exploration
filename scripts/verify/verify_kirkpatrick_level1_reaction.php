<?php
/**
 * Verify Kirkpatrick Level 1 (Reaction) Data Collection Configuration
 * 
 * This script verifies that Level 1 feedback collection is properly configured.
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

echo "=== Verifying Kirkpatrick Level 1 (Reaction) Configuration ===\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check 1: Verify feedback template exists
echo "1. Checking feedback template...\n";
$feedback_template = $DB->get_record('feedback', ['name' => 'Kirkpatrick Level 1 - Reaction Survey Template']);
if ($feedback_template) {
    $success[] = "✓ Feedback template found (ID: {$feedback_template->id})";
    
    // Check feedback questions
    $question_count = $DB->count_records('feedback_item', ['feedback' => $feedback_template->id]);
    if ($question_count >= 10) {
        $success[] = "✓ Feedback template has $question_count questions";
    } else {
        $warnings[] = "⚠ Feedback template has only $question_count questions (expected at least 10)";
    }
} else {
    $errors[] = "✗ Feedback template not found";
}

// Check 2: Verify engagement metrics configuration
echo "\n2. Checking engagement metrics configuration...\n";
$completion_enabled = get_config('core', 'enablecompletion');
if ($completion_enabled) {
    $success[] = "✓ Completion tracking enabled";
} else {
    $errors[] = "✗ Completion tracking not enabled";
}

$analytics_enabled = get_config('analytics', 'enabled');
if ($analytics_enabled) {
    $success[] = "✓ Analytics engine enabled";
} else {
    $warnings[] = "⚠ Analytics engine not enabled";
}

// Check 3: Verify Level 1 data table
echo "\n3. Checking Level 1 data table...\n";
$dbman = $DB->get_manager();
$table = new xmldb_table('kirkpatrick_level1_reaction');
if ($dbman->table_exists($table)) {
    $success[] = "✓ kirkpatrick_level1_reaction table exists";
    
    // Check table structure
    $required_fields = ['userid', 'courseid', 'satisfaction_score', 'engagement_rating'];
    foreach ($required_fields as $field) {
        $field_obj = new xmldb_field($field);
        if ($dbman->field_exists($table, $field_obj)) {
            $success[] = "✓ Field '$field' exists";
        } else {
            $errors[] = "✗ Field '$field' missing";
        }
    }
} else {
    $errors[] = "✗ kirkpatrick_level1_reaction table not found";
}

// Check 4: Verify dashboard configuration
echo "\n4. Checking dashboard configuration...\n";
$dashboard_enabled = get_config('core', 'enabledashboard');
if ($dashboard_enabled) {
    $success[] = "✓ Dashboard functionality enabled";
} else {
    $warnings[] = "⚠ Dashboard functionality not enabled";
}

// Check 5: Verify alert configuration
echo "\n5. Checking alert configuration...\n";
$messaging_enabled = get_config('core', 'messaging');
if ($messaging_enabled) {
    $success[] = "✓ Messaging system enabled";
} else {
    $warnings[] = "⚠ Messaging system not enabled";
}

$alert_threshold = get_config('kirkpatrick_level1', 'low_satisfaction_threshold');
if ($alert_threshold) {
    $success[] = "✓ Alert threshold configured: $alert_threshold";
} else {
    $warnings[] = "⚠ Alert threshold not configured";
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
    echo "\nLevel 1 (Reaction) data collection is properly configured.\n";
}
