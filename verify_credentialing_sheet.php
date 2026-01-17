<?php
/**
 * Verify Credentialing Sheet Configuration
 * 
 * Requirements: 19.1, 19.2, 19.4, 19.5
 * Usage: php verify_credentialing_sheet.php --courseid=<id>
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array('help' => false, 'courseid' => null),
    array('h' => 'help')
);

if ($options['help'] || !$options['courseid']) {
    echo "Verify Credentialing Sheet Configuration\n\n";
    echo "Usage: php verify_credentialing_sheet.php --courseid=<id>\n";
    exit(0);
}

$courseid = $options['courseid'];
echo "=== Credentialing Sheet Verification ===\n\n";

$passed = $failed = $warnings = 0;

// Verify course
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
echo "✓ Course: {$course->fullname}\n";
$passed++;

// Find database
$databases = $DB->get_records('data', array('course' => $courseid));
$data = null;
foreach ($databases as $db) {
    if (stripos($db->name, 'credentialing') !== false) {
        $data = $db;
        break;
    }
}

if ($data) {
    echo "✓ Credentialing Sheet found: {$data->name}\n";
    $passed++;
} else {
    echo "✗ Credentialing Sheet not found\n";
    exit(1);
}

// Check configuration
if ($data->approval == 1) {
    echo "✓ Approval required\n";
    $passed++;
} else {
    echo "✗ Approval not required\n";
    $failed++;
}

// Check fields
$requiredfields = array('month', 'year', 'phaco_count', 'competencies_achieved', 'approval_status');
$fields = $DB->get_records('data_fields', array('dataid' => $data->id));

foreach ($requiredfields as $fieldname) {
    $found = false;
    foreach ($fields as $field) {
        if ($field->name == $fieldname) {
            $found = true;
            break;
        }
    }
    if ($found) {
        echo "✓ Field: $fieldname\n";
        $passed++;
    } else {
        echo "✗ Missing field: $fieldname\n";
        $failed++;
    }
}

echo "\n--- Summary ---\n";
echo "Passed: $passed | Failed: $failed | Warnings: $warnings\n\n";

if ($failed == 0) {
    echo "✓ Credentialing Sheet is properly configured!\n";
    exit(0);
} else {
    echo "✗ Configuration issues found\n";
    exit(1);
}
