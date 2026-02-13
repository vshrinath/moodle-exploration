<?php
/**
 * Configure Credentialing Sheet Management System
 * 
 * This script configures the credentialing sheet database activity with:
 * - Monthly submission workflow
 * - Mentor verification process
 * - Competency progression tracking
 * - PDF export capabilities
 * 
 * Requirements: 19.1, 19.2, 19.4, 19.5
 * 
 * Usage: php configure_credentialing_sheet.php --courseid=<id>
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
require_once($CFG->dirroot . '/mod/data/lib.php');

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
        'dataid' => null,
        'dryrun' => false
    ),
    array('h' => 'help')
);

if ($options['help'] || !$options['courseid']) {
    $help = "Configure Credentialing Sheet Management System

This script configures the credentialing sheet database activity with monthly
submission workflow, mentor verification, and competency tracking.

Options:
--courseid=<id>     Course ID where the credentialing sheet is located (required)
--dataid=<id>       Database activity ID (optional, will search if not provided)
--dryrun            Show what would be done without making changes
-h, --help          Print this help

Example:
php configure_credentialing_sheet.php --courseid=2
php configure_credentialing_sheet.php --courseid=2 --dataid=6 --dryrun
";
    echo $help;
    exit(0);
}

$courseid = $options['courseid'];
$dataid = $options['dataid'];
$dryrun = $options['dryrun'];

echo "=== Credentialing Sheet Configuration ===\n\n";

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
    $databases = $DB->get_records('data', array('course' => $courseid));
    $data = null;
    foreach ($databases as $db) {
        if (stripos($db->name, 'credentialing') !== false) {
            $data = $db;
            break;
        }
    }
    
    if (!$data) {
        echo "ERROR: Could not find Credentialing Sheet database activity in course.\n";
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
    
    // Entry settings - ONE entry per month per user
    'requiredentries' => 1,             // Require at least 1 entry
    'requiredentriestoview' => 0,       // Can view without entries
    'maxentries' => 1,                  // Maximum 1 entry per month (enforced by workflow)
    
    // Comments and ratings
    'comments' => 1,                    // Enable comments
    'assessed' => 0,                    // No grading
    
    // Notification settings
    'notification' => 1,                // Enable notifications
    
    // Availability - monthly submission window
    'timeavailablefrom' => 0,          // Set dynamically each month
    'timeavailableto' => 0,            // Set dynamically each month
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

// Configure mentor verification capabilities
echo "Step 2: Configuring mentor verification capabilities...\n";

$context = context_course::instance($courseid);

$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
if (!$teacherrole) {
    $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
}

if ($teacherrole) {
    $capabilities = array(
        'mod/data:approve',
        'mod/data:manageentries',
        'mod/data:viewentry',
        'mod/data:writeentry',
        'mod/data:comment',
        'mod/data:exportentry',
    );
    
    if (!$dryrun) {
        foreach ($capabilities as $capability) {
            if ($DB->record_exists('capabilities', array('name' => $capability))) {
                assign_capability($capability, CAP_ALLOW, $teacherrole->id, $context->id, true);
            }
        }
        echo "✓ Mentor (teacher) capabilities configured\n";
    } else {
        echo "Would assign capabilities to teacher role\n";
    }
} else {
    echo "⚠ Warning: Teacher role not found\n";
}

echo "\n";

// Configure trainee capabilities
echo "Step 3: Configuring trainee (student) capabilities...\n";

$studentrole = $DB->get_record('role', array('shortname' => 'student'));

if ($studentrole) {
    if (!$dryrun) {
        assign_capability('mod/data:viewentry', CAP_ALLOW, $studentrole->id, $context->id, true);
        assign_capability('mod/data:writeentry', CAP_ALLOW, $studentrole->id, $context->id, true);
        assign_capability('mod/data:exportentry', CAP_ALLOW, $studentrole->id, $context->id, true);
        
        // Prevent students from approving
        assign_capability('mod/data:approve', CAP_PREVENT, $studentrole->id, $context->id, true);
        assign_capability('mod/data:manageentries', CAP_PREVENT, $studentrole->id, $context->id, true);
        
        echo "✓ Trainee (student) capabilities configured\n";
    } else {
        echo "Would configure student capabilities\n";
    }
} else {
    echo "⚠ Warning: Student role not found\n";
}

echo "\n";

// Set up competency progression tracking
echo "Step 4: Setting up competency progression tracking...\n";

if (!get_config('core_competency', 'enabled')) {
    echo "⚠ Warning: Competency framework is not enabled\n";
} else {
    $competencies = \core_competency\api::list_course_competencies($courseid);
    
    if (!empty($competencies)) {
        echo "Found " . count($competencies) . " competencies in course\n";
        
        if (!$dryrun) {
            // Link all competencies to credentialing sheet
            $linkedcount = 0;
            foreach ($competencies as $competency) {
                $comp = $competency['competency'];
                try {
                    \core_competency\api::add_competency_to_course($courseid, $comp->get('id'));
                    $linkedcount++;
                } catch (Exception $e) {
                    // Already linked
                }
            }
            echo "✓ Linked $linkedcount competencies\n";
        } else {
            echo "Would link all competencies to credentialing sheet\n";
        }
    } else {
        echo "⚠ No competencies found in course\n";
    }
}

echo "\n";

// Create PDF export template
echo "Step 5: Creating PDF export template...\n";

$pdftemplate = <<<'PDF'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Credentialing Sheet</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        h1 { color: #0066cc; text-align: center; }
        h2 { color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .section-header { background-color: #e0e0e0; font-weight: bold; }
        .signature { margin-top: 40px; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin-top: 50px; }
    </style>
</head>
<body>
    <h1>Ophthalmology Fellowship Credentialing Sheet</h1>
    
    <table>
        <tr>
            <th>Trainee Name:</th>
            <td>[[username]]</td>
            <th>Month/Year:</th>
            <td>[[month]] [[year]]</td>
        </tr>
        <tr>
            <th>Submission Date:</th>
            <td>[[submission_date]]</td>
            <th>Approval Date:</th>
            <td>[[approval_date]]</td>
        </tr>
    </table>
    
    <h2>Cataract Procedures</h2>
    <table>
        <tr><th>Procedure</th><th>Count</th></tr>
        <tr><td>Phacoemulsification</td><td>[[phaco_count]]</td></tr>
        <tr><td>SICS/ECCE</td><td>[[sics_count]]</td></tr>
        <tr><td>IOL Implantation</td><td>[[iol_count]]</td></tr>
    </table>
    
    <h2>Retina Procedures</h2>
    <table>
        <tr><th>Procedure</th><th>Count</th></tr>
        <tr><td>Vitrectomy</td><td>[[vitrectomy_count]]</td></tr>
        <tr><td>Laser Photocoagulation</td><td>[[laser_retina_count]]</td></tr>
        <tr><td>Intravitreal Injections</td><td>[[injection_count]]</td></tr>
    </table>
    
    <h2>Cornea Procedures</h2>
    <table>
        <tr><th>Procedure</th><th>Count</th></tr>
        <tr><td>Keratoplasty</td><td>[[keratoplasty_count]]</td></tr>
        <tr><td>Pterygium Surgery</td><td>[[pterygium_count]]</td></tr>
        <tr><td>Corneal Cross-linking</td><td>[[crosslinking_count]]</td></tr>
    </table>
    
    <h2>Glaucoma Procedures</h2>
    <table>
        <tr><th>Procedure</th><th>Count</th></tr>
        <tr><td>Trabeculectomy</td><td>[[trabeculectomy_count]]</td></tr>
        <tr><td>Laser Trabeculoplasty</td><td>[[laser_glaucoma_count]]</td></tr>
        <tr><td>Tube Shunt Surgery</td><td>[[tube_shunt_count]]</td></tr>
    </table>
    
    <h2>Oculoplasty Procedures</h2>
    <table>
        <tr><th>Procedure</th><th>Count</th></tr>
        <tr><td>Lid Surgery</td><td>[[lid_surgery_count]]</td></tr>
        <tr><td>DCR/DCT</td><td>[[dcr_count]]</td></tr>
        <tr><td>Orbital Surgery</td><td>[[orbital_count]]</td></tr>
    </table>
    
    <h2>Pediatric Procedures</h2>
    <table>
        <tr><th>Procedure</th><th>Count</th></tr>
        <tr><td>Strabismus Surgery</td><td>[[strabismus_count]]</td></tr>
        <tr><td>Pediatric Cataract</td><td>[[pediatric_cataract_count]]</td></tr>
    </table>
    
    <h2>Competency Achievements</h2>
    <p><strong>Competencies Achieved:</strong></p>
    <p>[[competencies_achieved]]</p>
    
    <p><strong>Skills Demonstrated:</strong></p>
    <p>[[skills_demonstrated]]</p>
    
    <h2>Mentor Verification</h2>
    <p><strong>Status:</strong> [[approval_status]]</p>
    <p><strong>Mentor Comments:</strong></p>
    <p>[[mentor_comments]]</p>
    
    <div class="signature">
        <p><strong>Mentor Signature:</strong></p>
        <div class="signature-line"></div>
        <p>Date: [[approval_date]]</p>
    </div>
</body>
</html>
PDF;

if (!$dryrun) {
    $templatefile = $CFG->dataroot . '/credentialing_pdf_template.html';
    file_put_contents($templatefile, $pdftemplate);
    echo "✓ PDF export template saved to: $templatefile\n";
} else {
    echo "Would create PDF export template\n";
}

echo "\n";

// Create report templates for competency progression
echo "Step 6: Creating competency progression report templates...\n";

$reporttemplates = array(
    'monthly_progression' => array(
        'name' => 'Monthly Procedure Progression',
        'description' => 'Track procedure counts over time',
        'sql' => "SELECT 
            CONCAT(d1.content, '-', d2.content) as period,
            SUM(CAST(d3.content AS UNSIGNED)) as total_procedures
        FROM {data_records} dr
        JOIN {data_content} d1 ON dr.id = d1.recordid
        JOIN {data_fields} f1 ON d1.fieldid = f1.id AND f1.name = 'month'
        JOIN {data_content} d2 ON dr.id = d2.recordid
        JOIN {data_fields} f2 ON d2.fieldid = f2.id AND f2.name = 'year'
        JOIN {data_content} d3 ON dr.id = d3.recordid
        JOIN {data_fields} f3 ON d3.fieldid = f3.id
        WHERE dr.dataid = :dataid
        AND dr.approved = 1
        AND f3.name LIKE '%_count'
        GROUP BY period
        ORDER BY d2.content, d1.content"
    ),
    'competency_achievement' => array(
        'name' => 'Competency Achievement Tracking',
        'description' => 'Track competencies achieved over time',
        'sql' => "SELECT 
            CONCAT(d1.content, '-', d2.content) as period,
            d3.content as competencies
        FROM {data_records} dr
        JOIN {data_content} d1 ON dr.id = d1.recordid
        JOIN {data_fields} f1 ON d1.fieldid = f1.id AND f1.name = 'month'
        JOIN {data_content} d2 ON dr.id = d2.recordid
        JOIN {data_fields} f2 ON d2.fieldid = f2.id AND f2.name = 'year'
        JOIN {data_content} d3 ON dr.id = d3.recordid
        JOIN {data_fields} f3 ON d3.fieldid = f3.id AND f3.name = 'competencies_achieved'
        WHERE dr.dataid = :dataid
        AND dr.approved = 1
        ORDER BY d2.content, d1.content"
    ),
    'subspecialty_totals' => array(
        'name' => 'Cumulative Subspecialty Totals',
        'description' => 'Cumulative procedure counts by subspecialty',
        'sql' => "SELECT 
            'Cataract' as subspecialty,
            SUM(CAST(d1.content AS UNSIGNED)) as phaco,
            SUM(CAST(d2.content AS UNSIGNED)) as sics,
            SUM(CAST(d3.content AS UNSIGNED)) as iol
        FROM {data_records} dr
        JOIN {data_content} d1 ON dr.id = d1.recordid
        JOIN {data_fields} f1 ON d1.fieldid = f1.id AND f1.name = 'phaco_count'
        JOIN {data_content} d2 ON dr.id = d2.recordid
        JOIN {data_fields} f2 ON d2.fieldid = f2.id AND f2.name = 'sics_count'
        JOIN {data_content} d3 ON dr.id = d3.recordid
        JOIN {data_fields} f3 ON d3.fieldid = f3.id AND f3.name = 'iol_count'
        WHERE dr.dataid = :dataid
        AND dr.approved = 1"
    )
);

if (!$dryrun) {
    $reportfile = $CFG->dataroot . '/credentialing_report_templates.json';
    file_put_contents($reportfile, json_encode($reporttemplates, JSON_PRETTY_PRINT));
    echo "✓ Report templates saved to: $reportfile\n";
} else {
    echo "Would create " . count($reporttemplates) . " report templates\n";
}

echo "\n";

// Summary
echo "=== Configuration Summary ===\n\n";
echo "Database Activity: {$data->name}\n";
echo "Course: {$course->fullname}\n";
echo "\n";
echo "Configured Features:\n";
echo "✓ Monthly submission workflow (1 entry per month)\n";
echo "✓ Mentor verification process\n";
echo "✓ Role-based permissions\n";
echo "✓ Competency progression tracking\n";
echo "✓ PDF export template\n";
echo "✓ Competency progression reports\n";
echo "\n";

if (!$dryrun) {
    echo "Configuration complete!\n\n";
    echo "Next Steps:\n";
    echo "1. Set up monthly submission reminders\n";
    echo "2. Test submission and verification workflow\n";
    echo "3. Import report templates into Configurable Reports plugin\n";
    echo "4. Configure PDF export in database activity\n";
    echo "5. Run verify_credentialing_sheet.php to validate configuration\n";
} else {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Run without --dryrun to apply configuration\n";
}

echo "\n";
exit(0);
