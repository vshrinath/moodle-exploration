<?php
/**
 * Verify Advanced Cohort Management Configuration
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB, $CFG;

echo "=== Advanced Cohort Management Verification ===\n\n";

$errors = [];
$warnings = [];

// Check 1: Cohort metadata table
echo "Check 1: Cohort metadata table...\n";
$dbman = $DB->get_manager();
$table = new xmldb_table('local_cohort_metadata');

if ($dbman->table_exists($table)) {
    echo "  ✓ local_cohort_metadata table exists\n";
    
    $columns = $DB->get_columns('local_cohort_metadata');
    $required_columns = ['id', 'cohortid', 'cohort_type', 'delivery_mode', 'mixed_delivery', 'access_rules', 'content_restrictions'];
    
    foreach ($required_columns as $col) {
        if (!isset($columns[$col])) {
            $errors[] = "Missing column '$col' in local_cohort_metadata table";
        }
    }
    
    if (empty($errors)) {
        echo "  ✓ All required columns present\n";
    }
} else {
    $errors[] = "local_cohort_metadata table does not exist";
}

// Check 2: Sample cohorts
echo "\nCheck 2: Sample cohorts...\n";
$cohort_count = $DB->count_records('local_cohort_metadata');

if ($cohort_count > 0) {
    echo "  ✓ Found $cohort_count cohort(s) with metadata\n";
    
    $cohorts = $DB->get_records_sql("
        SELECT c.id, c.name, cm.cohort_type, cm.delivery_mode, cm.mixed_delivery
        FROM {cohort} c
        JOIN {local_cohort_metadata} cm ON c.id = cm.cohortid
    ");
    
    foreach ($cohorts as $cohort) {
        $mixed = $cohort->mixed_delivery ? ' (Mixed)' : '';
        echo "    - {$cohort->name}: Type={$cohort->cohort_type}, Delivery={$cohort->delivery_mode}$mixed\n";
    }
} else {
    $warnings[] = "No cohorts with metadata found";
}

// Check 3: Cohort types
echo "\nCheck 3: Cohort types...\n";
$types = $DB->get_records_sql("
    SELECT DISTINCT cohort_type 
    FROM {local_cohort_metadata}
");

if (!empty($types)) {
    echo "  ✓ Found " . count($types) . " cohort type(s):\n";
    foreach ($types as $type) {
        echo "    - {$type->cohort_type}\n";
    }
} else {
    $warnings[] = "No cohort types configured";
}

// Check 4: Delivery modes
echo "\nCheck 4: Delivery modes...\n";
$modes = $DB->get_records_sql("
    SELECT DISTINCT delivery_mode 
    FROM {local_cohort_metadata}
");

if (!empty($modes)) {
    echo "  ✓ Found " . count($modes) . " delivery mode(s):\n";
    foreach ($modes as $mode) {
        echo "    - {$mode->delivery_mode}\n";
    }
} else {
    $warnings[] = "No delivery modes configured";
}

// Check 5: Mixed delivery support
echo "\nCheck 5: Mixed delivery support...\n";
$mixed_count = $DB->count_records('local_cohort_metadata', ['mixed_delivery' => 1]);

if ($mixed_count > 0) {
    echo "  ✓ Found $mixed_count cohort(s) with mixed delivery mode\n";
} else {
    echo "  ℹ No cohorts with mixed delivery (this is OK)\n";
}

// Check 6: Access rules and content restrictions
echo "\nCheck 6: Access rules and content restrictions...\n";
$cohorts_with_rules = $DB->get_records_sql("
    SELECT cohortid, access_rules, content_restrictions
    FROM {local_cohort_metadata}
    WHERE access_rules IS NOT NULL OR content_restrictions IS NOT NULL
");

if (!empty($cohorts_with_rules)) {
    echo "  ✓ Found " . count($cohorts_with_rules) . " cohort(s) with configured rules\n";
    
    foreach ($cohorts_with_rules as $cohort) {
        $rules = json_decode($cohort->access_rules, true);
        $restrictions = json_decode($cohort->content_restrictions, true);
        
        if ($rules) {
            echo "    - Cohort {$cohort->cohortid}: Access rules configured\n";
        }
        if ($restrictions) {
            echo "    - Cohort {$cohort->cohortid}: Content restrictions configured\n";
        }
    }
} else {
    $warnings[] = "No cohorts with access rules or content restrictions";
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
    echo "\n✓ Advanced cohort management is fully configured!\n";
    exit(0);
} elseif (empty($errors)) {
    echo "\n✓ Advanced cohort management is configured (warnings can be addressed as needed)\n";
    exit(0);
} else {
    echo "\n✗ Please address the errors above before proceeding\n";
    exit(1);
}
