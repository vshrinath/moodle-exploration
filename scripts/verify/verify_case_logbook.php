<?php
/**
 * Verify Case and Surgical Logbook Configuration
 * 
 * This script verifies that the case logbook is properly configured with:
 * - Approval workflow
 * - Mentor permissions
 * - Competency integration
 * - Analytics capabilities
 * 
 * Requirements: 18.1, 18.2, 18.3, 18.4
 * 
 * Usage: php verify_case_logbook.php --courseid=<id>
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
require_once($CFG->libdir . '/clilib.php');

// Ensure we're running as admin in CLI
$admin = get_admin();
if (!$admin) {
    fwrite(STDERR, "ERROR: No admin user found\n");
    exit(1);
}
\core\session\manager::set_user($admin);

// Get CLI options
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'courseid' => null,
        'dataid' => null
    ),
    array('h' => 'help')
);

if ($options['help'] || !$options['courseid']) {
    $help = "Verify Case and Surgical Logbook Configuration

This script verifies the case logbook database activity configuration.

Options:
--courseid=<id>     Course ID where the logbook is located (required)
--dataid=<id>       Database activity ID (optional)
-h, --help          Print this help

Example:
php verify_case_logbook.php --courseid=2
";
    echo $help;
    exit(0);
}

$courseid = $options['courseid'];
$dataid = $options['dataid'];

echo "=== Case and Surgical Logbook Verification ===\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

// Verify course exists
try {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    echo "✓ Course found: {$course->fullname}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ Course not found\n";
    $failed++;
    exit(1);
}

// Find database activity
if ($dataid) {
    $data = $DB->get_record('data', array('id' => $dataid, 'course' => $courseid));
} else {
    $databases = $DB->get_records('data', array('course' => $courseid));
    $data = null;
    foreach ($databases as $db) {
        if (stripos($db->name, 'case') !== false && stripos($db->name, 'logbook') !== false) {
            $data = $db;
            break;
        }
    }
}

if ($data) {
    echo "✓ Case Logbook database found: {$data->name}\n";
    $passed++;
} else {
    echo "✗ Case Logbook database not found\n";
    $failed++;
    exit(1);
}

echo "\n--- Database Configuration ---\n";

// Check approval settings
if ($data->approval == 1) {
    echo "✓ Approval required: Enabled\n";
    $passed++;
} else {
    echo "✗ Approval required: Disabled\n";
    $failed++;
}

if ($data->manageapproved == 1) {
    echo "✓ Manage approved entries: Enabled\n";
    $passed++;
} else {
    echo "⚠ Manage approved entries: Disabled\n";
    $warnings++;
}

// Check comments
if ($data->comments == 1) {
    echo "✓ Comments: Enabled\n";
    $passed++;
} else {
    echo "⚠ Comments: Disabled\n";
    $warnings++;
}

echo "\n--- Required Fields ---\n";

// Get fields
$fields = $DB->get_records('data_fields', array('dataid' => $data->id));

$requiredfields = array(
    'case_date' => false,
    'subspecialty' => false,
    'procedure_type' => false,
    'procedure_details' => false,
    'diagnosis' => false,
    'surgical_role' => false,
    'outcomes' => false,
    'complications' => false,
    'learning_points' => false,
    'approval_status' => false,
    'mentor_feedback' => false
);

foreach ($fields as $field) {
    if (isset($requiredfields[$field->name])) {
        $requiredfields[$field->name] = true;
    }
}

foreach ($requiredfields as $fieldname => $exists) {
    if ($exists) {
        echo "✓ Field exists: $fieldname\n";
        $passed++;
    } else {
        echo "✗ Field missing: $fieldname\n";
        $failed++;
    }
}

echo "\n--- Permissions ---\n";

$context = context_course::instance($courseid);

// Check teacher permissions
$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
if (!$teacherrole) {
    $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
}

if ($teacherrole) {
    $teachercaps = array(
        'mod/data:approve',
        'mod/data:manageentries',
        'mod/data:viewentry',
    );
    
    foreach ($teachercaps as $cap) {
        if ($DB->record_exists('capabilities', array('name' => $cap))) {
            $rc = $DB->get_record('role_capabilities', [
                'roleid' => $teacherrole->id,
                'capability' => $cap,
                'contextid' => $context->id
            ], 'permission');
            if ($rc && (int)$rc->permission === CAP_ALLOW) {
                echo "✓ Teacher capability: $cap\n";
                $passed++;
            } else {
                echo "✗ Teacher missing capability: $cap\n";
                $failed++;
            }
        }
    }
} else {
    echo "⚠ Teacher role not found\n";
    $warnings++;
}

// Check student permissions
$studentrole = $DB->get_record('role', array('shortname' => 'student'));

if ($studentrole) {
    $studentcaps = array(
        'mod/data:viewentry',
        'mod/data:writeentry',
    );
    
    foreach ($studentcaps as $cap) {
        if ($DB->record_exists('capabilities', array('name' => $cap))) {
            $rc = $DB->get_record('role_capabilities', [
                'roleid' => $studentrole->id,
                'capability' => $cap,
                'contextid' => $context->id
            ], 'permission');
            if ($rc && (int)$rc->permission === CAP_ALLOW) {
                echo "✓ Student capability: $cap\n";
                $passed++;
            } else {
                echo "✗ Student missing capability: $cap\n";
                $failed++;
            }
        }
    }
    
    // Verify students cannot approve
    if ($DB->record_exists('capabilities', array('name' => 'mod/data:approve'))) {
        $rc = $DB->get_record('role_capabilities', [
            'roleid' => $studentrole->id,
            'capability' => 'mod/data:approve',
            'contextid' => $context->id
        ], 'permission');
        if (!$rc || (int)$rc->permission !== CAP_ALLOW) {
            echo "✓ Students cannot approve entries\n";
            $passed++;
        } else {
            echo "✗ Students can approve entries (should be prevented)\n";
            $failed++;
        }
    }
} else {
    echo "⚠ Student role not found\n";
    $warnings++;
}

echo "\n--- Competency Integration ---\n";

// Check if competency framework is enabled
if (get_config('core_competency', 'enabled')) {
    echo "✓ Competency framework: Enabled\n";
    $passed++;
    
    // Check for competencies in course
    $competencies = \core_competency\api::list_course_competencies($courseid);
    if (!empty($competencies)) {
        echo "✓ Course competencies: " . count($competencies) . " found\n";
        $passed++;
    } else {
        echo "⚠ No competencies found in course\n";
        $warnings++;
    }
} else {
    echo "⚠ Competency framework: Disabled\n";
    $warnings++;
}

echo "\n--- Data Quality ---\n";

// Check for entries
$entrycount = $DB->count_records('data_records', array('dataid' => $data->id));
echo "ℹ Total entries: $entrycount\n";

if ($entrycount > 0) {
    // Check approved entries
    $approvedcount = $DB->count_records('data_records', array('dataid' => $data->id, 'approved' => 1));
    echo "ℹ Approved entries: $approvedcount\n";
    
    // Check pending entries
    $pendingcount = $entrycount - $approvedcount;
    echo "ℹ Pending approval: $pendingcount\n";
    
    // Check entries by subspecialty
    $sql = "SELECT d.content as subspecialty, COUNT(*) as count
            FROM {data_records} dr
            JOIN {data_content} d ON dr.id = d.recordid
            JOIN {data_fields} f ON d.fieldid = f.id
            WHERE dr.dataid = :dataid
            AND f.name = 'subspecialty'
            AND dr.approved = 1
            GROUP BY d.content
            ORDER BY count DESC";
    
    $subspecialties = $DB->get_records_sql($sql, array('dataid' => $data->id));
    
    if (!empty($subspecialties)) {
        echo "\nℹ Cases by subspecialty:\n";
        foreach ($subspecialties as $sub) {
            echo "  - {$sub->subspecialty}: {$sub->count}\n";
        }
    }
} else {
    echo "ℹ No entries yet (system is ready for use)\n";
}

echo "\n--- Summary ---\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Warnings: $warnings\n";
echo "\n";

if ($failed == 0) {
    echo "✓ Case Logbook is properly configured!\n";
    
    if ($warnings > 0) {
        echo "\nRecommendations:\n";
        echo "- Review warnings above\n";
        echo "- Enable competency framework if not enabled\n";
        echo "- Create competencies if none exist\n";
    }
    
    echo "\nNext Steps:\n";
    echo "1. Enroll mentors (teachers) in the course\n";
    echo "2. Enroll trainees (students) in the course\n";
    echo "3. Test submission workflow with sample entry\n";
    echo "4. Test approval workflow\n";
    echo "5. Set up analytics reports\n";
    
    exit(0);
} else {
    echo "✗ Configuration issues found. Please fix the failed checks.\n";
    echo "\nTo fix issues:\n";
    echo "1. Run configure_case_logbook.php to apply configuration\n";
    echo "2. Check role permissions in course settings\n";
    echo "3. Verify database activity settings\n";
    
    exit(1);
}
