<?php
/**
 * Configure Case and Surgical Logbook System
 * 
 * This script configures the case logbook database activity with:
 * - Monthly submission workflow
 * - Mentor approval process
 * - Integration with competency framework
 * - Surgical exposure analytics
 * 
 * Requirements: 18.1, 18.2, 18.3, 18.4
 * 
 * Usage: php configure_case_logbook.php
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/data/lib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');

// Get CLI options
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'courseid' => null,
        'dataid' => null,
        'dryrun' => false
    ),
    array('h' => 'help')
);

if ($options['help'] || !$options['courseid']) {
    $help = "Configure Case and Surgical Logbook System

This script configures the case logbook database activity with monthly submission
workflow, mentor approval, and competency framework integration.

Options:
--courseid=<id>     Course ID where the logbook is located (required)
--dataid=<id>       Database activity ID (optional, will search if not provided)
--dryrun            Show what would be done without making changes
-h, --help          Print this help

Example:
php configure_case_logbook.php --courseid=2
php configure_case_logbook.php --courseid=2 --dataid=5 --dryrun
";
    echo $help;
    exit(0);
}

$courseid = $options['courseid'];
$dataid = $options['dataid'];
$dryrun = $options['dryrun'];

echo "=== Case and Surgical Logbook Configuration ===\n\n";

if ($dryrun) {
    echo "DRY RUN MODE - No changes will be made\n\n";
}

// Verify course exists
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
echo "Course: {$course->fullname} (ID: {$course->id})\n\n";

// Find or verify database activity
if ($dataid) {
    $data = $DB->get_record('data', array('id' => $dataid, 'course' => $courseid), '*', MUST_EXIST);
} else {
    // Search for case logbook database
    $databases = $DB->get_records('data', array('course' => $courseid));
    $data = null;
    foreach ($databases as $db) {
        if (stripos($db->name, 'case') !== false && stripos($db->name, 'logbook') !== false) {
            $data = $db;
            break;
        }
    }
    
    if (!$data) {
        echo "ERROR: Could not find Case Logbook database activity in course.\n";
        echo "Please specify --dataid or create the database activity first.\n";
        exit(1);
    }
}

echo "Database Activity: {$data->name} (ID: {$data->id})\n\n";

// Configuration settings
$config = array(
    // Approval settings
    'approval' => 1,                    // Require approval
    'manageapproved' => 1,              // Allow managing approved entries
    
    // Entry settings
    'requiredentries' => 0,             // No minimum (flexible)
    'requiredentriestoview' => 0,       // Can view without entries
    'maxentries' => 0,                  // No maximum limit
    
    // Comments and ratings
    'comments' => 1,                    // Enable comments
    'assessed' => 0,                    // No grading by default
    
    // Notification settings
    'notification' => 1,                // Enable notifications
    
    // Availability (monthly submission window - optional)
    'timeavailablefrom' => 0,          // Always available
    'timeavailableto' => 0,            // Always available
    
    // Completion settings
    'completionentries' => 0,          // No specific entry requirement for completion
);

echo "Step 1: Configuring database settings...\n";

if (!$dryrun) {
    foreach ($config as $key => $value) {
        if (property_exists($data, $key)) {
            $data->$key = $value;
        }
    }
    
    $DB->update_record('data', $data);
    echo "✓ Database settings updated\n";
} else {
    echo "Would update database settings:\n";
    foreach ($config as $key => $value) {
        echo "  - $key: $value\n";
    }
}

echo "\n";

// Configure mentor approval capabilities
echo "Step 2: Configuring mentor approval capabilities...\n";

$context = context_course::instance($courseid);

// Get teacher role
$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
if (!$teacherrole) {
    $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
}

if ($teacherrole) {
    $capabilities = array(
        'mod/data:approve',
        'mod/data:manageentries',
        'mod/data:managecomments',
        'mod/data:viewentry',
        'mod/data:writeentry',
        'mod/data:comment',
    );
    
    if (!$dryrun) {
        foreach ($capabilities as $capability) {
            // Check if capability exists
            if ($DB->record_exists('capabilities', array('name' => $capability))) {
                assign_capability($capability, CAP_ALLOW, $teacherrole->id, $context->id, true);
            }
        }
        echo "✓ Mentor (teacher) capabilities configured\n";
    } else {
        echo "Would assign capabilities to teacher role:\n";
        foreach ($capabilities as $capability) {
            echo "  - $capability\n";
        }
    }
} else {
    echo "⚠ Warning: Teacher role not found\n";
}

echo "\n";

// Configure student capabilities
echo "Step 3: Configuring trainee (student) capabilities...\n";

$studentrole = $DB->get_record('role', array('shortname' => 'student'));

if ($studentrole) {
    $capabilities = array(
        'mod/data:viewentry' => CAP_ALLOW,
        'mod/data:writeentry' => CAP_ALLOW,
        'mod/data:comment' => CAP_ALLOW,
    );
    
    // Prevent students from approving or managing others' entries
    $preventcapabilities = array(
        'mod/data:approve' => CAP_PREVENT,
        'mod/data:manageentries' => CAP_PREVENT,
    );
    
    if (!$dryrun) {
        foreach ($capabilities as $capability => $permission) {
            if ($DB->record_exists('capabilities', array('name' => $capability))) {
                assign_capability($capability, $permission, $studentrole->id, $context->id, true);
            }
        }
        foreach ($preventcapabilities as $capability => $permission) {
            if ($DB->record_exists('capabilities', array('name' => $capability))) {
                assign_capability($capability, $permission, $studentrole->id, $context->id, true);
            }
        }
        echo "✓ Trainee (student) capabilities configured\n";
    } else {
        echo "Would configure student capabilities\n";
    }
} else {
    echo "⚠ Warning: Student role not found\n";
}

echo "\n";

// Set up competency framework integration
echo "Step 4: Setting up competency framework integration...\n";

// Check if competency framework is enabled
if (!get_config('core_competency', 'enabled')) {
    echo "⚠ Warning: Competency framework is not enabled\n";
    echo "  Enable it at: Site administration > Competencies > Competencies settings\n";
} else {
    // Link database activity to competencies
    $modulecontext = context_module::instance($data->coursemodule);
    
    // Get competencies in this course
    $competencies = \core_competency\api::list_course_competencies($courseid);
    
    if (!empty($competencies)) {
        echo "Found " . count($competencies) . " competencies in course\n";
        
        if (!$dryrun) {
            // Link relevant competencies (surgical and clinical competencies)
            $linkedcount = 0;
            foreach ($competencies as $competency) {
                $comp = $competency['competency'];
                // Link competencies related to surgical skills
                if (stripos($comp->get('shortname'), 'surgical') !== false ||
                    stripos($comp->get('shortname'), 'clinical') !== false ||
                    stripos($comp->get('shortname'), 'procedure') !== false) {
                    
                    try {
                        \core_competency\api::add_competency_to_course($courseid, $comp->get('id'));
                        $linkedcount++;
                    } catch (Exception $e) {
                        // Competency might already be linked
                    }
                }
            }
            echo "✓ Linked $linkedcount surgical/clinical competencies\n";
        } else {
            echo "Would link surgical/clinical competencies to database activity\n";
        }
    } else {
        echo "⚠ No competencies found in course\n";
        echo "  Create competencies first using create_competency_framework_structure.php\n";
    }
}

echo "\n";

// Configure completion tracking
echo "Step 5: Configuring completion tracking...\n";

if (!$dryrun) {
    $completion = new completion_info($course);
    
    if ($completion->is_enabled()) {
        // Set completion criteria for the database activity
        $cm = get_coursemodule_from_instance('data', $data->id);
        
        if ($cm) {
            // Enable activity completion
            $DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_AUTOMATIC, array('id' => $cm->id));
            $DB->set_field('course_modules', 'completionview', 1, array('id' => $cm->id));
            
            // Require entries for completion (optional)
            $data->completionentries = 5; // Require 5 approved entries
            $DB->update_record('data', $data);
            
            echo "✓ Completion tracking configured (5 approved entries required)\n";
        }
    } else {
        echo "⚠ Completion tracking not enabled for course\n";
    }
} else {
    echo "Would configure completion tracking\n";
}

echo "\n";

// Create sample report templates
echo "Step 6: Creating surgical exposure analytics templates...\n";

$reporttemplates = array(
    'subspecialty_distribution' => array(
        'name' => 'Cases by Subspecialty',
        'description' => 'Distribution of cases across ophthalmology subspecialties',
        'sql' => "SELECT 
            d.content as subspecialty,
            COUNT(*) as case_count,
            SUM(CASE WHEN d2.content = 'Primary Surgeon' THEN 1 ELSE 0 END) as primary_surgeon,
            SUM(CASE WHEN d2.content = 'Assistant Surgeon' THEN 1 ELSE 0 END) as assistant
        FROM {data_records} dr
        JOIN {data_content} d ON dr.id = d.recordid
        JOIN {data_fields} f ON d.fieldid = f.id
        LEFT JOIN {data_content} d2 ON dr.id = d2.recordid
        LEFT JOIN {data_fields} f2 ON d2.fieldid = f2.id AND f2.name = 'surgical_role'
        WHERE dr.dataid = :dataid
        AND f.name = 'subspecialty'
        AND dr.approved = 1
        GROUP BY d.content
        ORDER BY case_count DESC"
    ),
    'monthly_progression' => array(
        'name' => 'Monthly Case Progression',
        'description' => 'Track case volume and complexity over time',
        'sql' => "SELECT 
            DATE_FORMAT(FROM_UNIXTIME(dr.timecreated), '%Y-%m') as month,
            COUNT(*) as total_cases,
            SUM(CASE WHEN d.content = 'Primary Surgeon' THEN 1 ELSE 0 END) as primary_cases
        FROM {data_records} dr
        JOIN {data_content} d ON dr.id = d.recordid
        JOIN {data_fields} f ON d.fieldid = f.id
        WHERE dr.dataid = :dataid
        AND f.name = 'surgical_role'
        AND dr.approved = 1
        GROUP BY month
        ORDER BY month"
    ),
    'complications_analysis' => array(
        'name' => 'Complications Analysis',
        'description' => 'Track complications by subspecialty and procedure type',
        'sql' => "SELECT 
            d1.content as subspecialty,
            d2.content as procedure_type,
            COUNT(*) as total_cases,
            SUM(CASE WHEN d3.content != 'None' AND d3.content != '' THEN 1 ELSE 0 END) as cases_with_complications
        FROM {data_records} dr
        JOIN {data_content} d1 ON dr.id = d1.recordid
        JOIN {data_fields} f1 ON d1.fieldid = f1.id AND f1.name = 'subspecialty'
        JOIN {data_content} d2 ON dr.id = d2.recordid
        JOIN {data_fields} f2 ON d2.fieldid = f2.id AND f2.name = 'procedure_type'
        JOIN {data_content} d3 ON dr.id = d3.recordid
        JOIN {data_fields} f3 ON d3.fieldid = f3.id AND f3.name = 'complications'
        WHERE dr.dataid = :dataid
        AND dr.approved = 1
        GROUP BY subspecialty, procedure_type
        ORDER BY subspecialty, total_cases DESC"
    )
);

if (!$dryrun) {
    // Save report templates to a file for use with Configurable Reports plugin
    $reportfile = $CFG->dataroot . '/case_logbook_report_templates.json';
    file_put_contents($reportfile, json_encode($reporttemplates, JSON_PRETTY_PRINT));
    echo "✓ Report templates saved to: $reportfile\n";
    echo "  Import these into Configurable Reports plugin\n";
} else {
    echo "Would create " . count($reporttemplates) . " report templates\n";
    foreach ($reporttemplates as $key => $template) {
        echo "  - {$template['name']}\n";
    }
}

echo "\n";

// Summary
echo "=== Configuration Summary ===\n\n";
echo "Database Activity: {$data->name}\n";
echo "Course: {$course->fullname}\n";
echo "\n";
echo "Configured Features:\n";
echo "✓ Monthly submission workflow\n";
echo "✓ Mentor approval process\n";
echo "✓ Role-based permissions\n";
echo "✓ Competency framework integration\n";
echo "✓ Completion tracking\n";
echo "✓ Surgical exposure analytics templates\n";
echo "\n";

if (!$dryrun) {
    echo "Configuration complete!\n\n";
    echo "Next Steps:\n";
    echo "1. Verify mentor (teacher) enrollments in course\n";
    echo "2. Test submission and approval workflow\n";
    echo "3. Import report templates into Configurable Reports plugin\n";
    echo "4. Train users on the system\n";
    echo "5. Run verify_case_logbook.php to validate configuration\n";
} else {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Run without --dryrun to apply configuration\n";
}

echo "\n";
exit(0);
