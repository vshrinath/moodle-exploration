<?php
/**
 * Verification script for SCEH Dashboard Block
 * 
 * Run from Moodle root: php verify_sceh_dashboard.php
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');

echo "\n========================================\n";
echo "SCEH Dashboard Block Verification\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check 1: Block plugin installed
echo "Checking block installation...\n";
$plugin = $DB->get_record('block', ['name' => 'sceh_dashboard']);
if ($plugin) {
    $success[] = "✓ Block plugin registered in database";
} else {
    $errors[] = "✗ Block plugin not found in database";
}

// Check 2: Block files exist
$blockpath = $CFG->dirroot . '/blocks/sceh_dashboard';
if (file_exists($blockpath . '/block_sceh_dashboard.php')) {
    $success[] = "✓ Block class file exists";
} else {
    $errors[] = "✗ Block class file missing";
}

if (file_exists($blockpath . '/version.php')) {
    $success[] = "✓ Version file exists";
} else {
    $errors[] = "✗ Version file missing";
}

if (file_exists($blockpath . '/styles.css')) {
    $success[] = "✓ Stylesheet exists";
} else {
    $warnings[] = "⚠ Stylesheet missing (optional but recommended)";
}

// Check 3: Language strings
if (file_exists($blockpath . '/lang/en/block_sceh_dashboard.php')) {
    $success[] = "✓ Language file exists";
} else {
    $errors[] = "✗ Language file missing";
}

// Check 4: Capabilities
if (file_exists($blockpath . '/db/access.php')) {
    $success[] = "✓ Capabilities file exists";
} else {
    $errors[] = "✗ Capabilities file missing";
}

// Check 5: Block instances
$instances = $DB->count_records('block_instances', ['blockname' => 'sceh_dashboard']);
if ($instances > 0) {
    $success[] = "✓ Block has {$instances} instance(s) on pages";
} else {
    $warnings[] = "⚠ No block instances found - add the block to a page";
}

// Check 6: Required plugins for links
echo "\nChecking linked features...\n";

// Competency framework
if ($DB->get_manager()->table_exists('competency_framework')) {
    $frameworks = $DB->count_records('competency_framework');
    if ($frameworks > 0) {
        $success[] = "✓ Competency framework available ({$frameworks} framework(s))";
    } else {
        $warnings[] = "⚠ No competency frameworks created yet";
    }
}

// Kirkpatrick dashboard
if (file_exists($CFG->dirroot . '/local/kirkpatrick_dashboard')) {
    $success[] = "✓ Training Evaluation (Kirkpatrick) dashboard available";
} else {
    $warnings[] = "⚠ Training Evaluation dashboard not installed";
}

// SCEH Rules
if (file_exists($CFG->dirroot . '/local/sceh_rules')) {
    $success[] = "✓ SCEH Rules plugin available";
} else {
    $warnings[] = "⚠ SCEH Rules plugin not installed";
}

// Attendance module
if ($DB->record_exists('modules', ['name' => 'attendance'])) {
    $success[] = "✓ Attendance module available";
} else {
    $warnings[] = "⚠ Attendance module not installed";
}

// Database module (for case logbook)
if ($DB->record_exists('modules', ['name' => 'data'])) {
    $databases = $DB->count_records('data');
    $success[] = "✓ Database module available ({$databases} database(s))";
} else {
    $warnings[] = "⚠ Database module not available";
}

// Display results
echo "\n========================================\n";
echo "VERIFICATION RESULTS\n";
echo "========================================\n\n";

if (!empty($success)) {
    echo "SUCCESS:\n";
    foreach ($success as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "WARNINGS:\n";
    foreach ($warnings as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "ERRORS:\n";
    foreach ($errors as $msg) {
        echo "  $msg\n";
    }
    echo "\n";
}

// Final status
echo "========================================\n";
if (empty($errors)) {
    echo "STATUS: ✓ PASSED\n";
    echo "========================================\n\n";
    
    if ($instances == 0) {
        echo "To use the dashboard:\n";
        echo "1. Go to your Moodle homepage or Dashboard\n";
        echo "2. Turn editing on\n";
        echo "3. Click 'Add a block'\n";
        echo "4. Select 'Fellowship Training Dashboard'\n\n";
    } else {
        echo "Dashboard is ready to use!\n\n";
    }
} else {
    echo "STATUS: ✗ FAILED\n";
    echo "========================================\n\n";
    echo "Please fix the errors above and run verification again.\n\n";
    exit(1);
}
