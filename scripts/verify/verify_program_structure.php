<?php
/**
 * Verify Program Structure Configuration
 * 
 * This script verifies that program structure is correctly configured
 * including metadata tables, categories, roles, and templates.
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB, $CFG;

echo "=== Program Structure Verification ===\n\n";

$errors = [];
$warnings = [];

// Check 1: Program metadata table
echo "Check 1: Program metadata table...\n";
$dbman = $DB->get_manager();
$table = new xmldb_table('local_program_metadata');

if ($dbman->table_exists($table)) {
    echo "  ✓ local_program_metadata table exists\n";
    
    // Check table structure
    $columns = $DB->get_columns('local_program_metadata');
    $required_columns = ['id', 'courseid', 'program_version', 'outcomes', 'target_audience', 'owner_userid', 'created', 'modified'];
    
    foreach ($required_columns as $col) {
        if (!isset($columns[$col])) {
            $errors[] = "Missing column '$col' in local_program_metadata table";
        }
    }
    
    if (empty($errors)) {
        echo "  ✓ All required columns present\n";
    }
} else {
    $errors[] = "local_program_metadata table does not exist";
}

// Check 2: Program categories
echo "\nCheck 2: Program categories...\n";
$programs_cat = $DB->get_record('course_categories', ['name' => 'Programs']);

if ($programs_cat) {
    echo "  ✓ 'Programs' category exists (ID: {$programs_cat->id})\n";
    
    $subcategories = ['Technical Programs', 'Management Programs', 'Fellowship Programs', 'Archived Programs'];
    foreach ($subcategories as $subcat) {
        $cat = $DB->get_record('course_categories', ['name' => $subcat, 'parent' => $programs_cat->id]);
        if ($cat) {
            echo "  ✓ '$subcat' subcategory exists (ID: {$cat->id})\n";
        } else {
            $warnings[] = "Subcategory '$subcat' not found";
        }
    }
} else {
    $errors[] = "'Programs' category does not exist";
}

// Check 3: Program Owner role
echo "\nCheck 3: Program Owner role...\n";
$role = $DB->get_record('role', ['shortname' => 'programowner']);

if ($role) {
    echo "  ✓ Program Owner role exists (ID: {$role->id})\n";
    
    // Check key capabilities
    $context = context_system::instance();
    $required_caps = [
        'moodle/course:create',
        'moodle/competency:competencymanage',
        'moodle/competency:planmanage',
        'moodle/backup:backupcourse',
        'moodle/restore:restorecourse'
    ];
    
    foreach ($required_caps as $cap) {
        $has_cap = has_capability($cap, $context, null, false);
        if ($has_cap) {
            echo "  ✓ Capability assigned: $cap\n";
        } else {
            $warnings[] = "Capability not assigned: $cap";
        }
    }
} else {
    $errors[] = "Program Owner role does not exist";
}

// Check 4: Program templates
echo "\nCheck 4: Program templates...\n";
$metadata_count = $DB->count_records('local_program_metadata');

if ($metadata_count > 0) {
    echo "  ✓ Found $metadata_count program template(s)\n";
    
    $programs = $DB->get_records('local_program_metadata');
    foreach ($programs as $program) {
        $course = $DB->get_record('course', ['id' => $program->courseid]);
        if ($course) {
            echo "    - {$course->shortname}: {$course->fullname} (Version: {$program->program_version})\n";
        }
    }
} else {
    $warnings[] = "No program templates found (this is OK if you haven't created any yet)";
}

// Check 5: Backup/Restore capabilities
echo "\nCheck 5: Backup/Restore configuration...\n";
if (file_exists($CFG->dirroot . '/backup/util/includes/backup_includes.php')) {
    echo "  ✓ Backup functionality available\n";
} else {
    $errors[] = "Backup functionality not available";
}

if (file_exists($CFG->dirroot . '/backup/util/includes/restore_includes.php')) {
    echo "  ✓ Restore functionality available\n";
} else {
    $errors[] = "Restore functionality not available";
}

// Summary
echo "\n=== Verification Summary ===\n";

if (empty($errors)) {
    echo "✓ All critical checks passed\n";
} else {
    echo "✗ Found " . count($errors) . " error(s):\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠ Found " . count($warnings) . " warning(s):\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

if (empty($errors) && empty($warnings)) {
    echo "\n✓ Program structure is fully configured and ready to use!\n";
    exit(0);
} elseif (empty($errors)) {
    echo "\n✓ Program structure is configured (warnings can be addressed as needed)\n";
    exit(0);
} else {
    echo "\n✗ Please address the errors above before proceeding\n";
    exit(1);
}
