<?php
/**
 * Configure Certificate Generation System
 * 
 * Sets up Custom Certificate plugin for professional credentials linked to competencies
 * Implements Requirements 15.2, 15.5
 * 
 * Usage: php configure_certificate_system.php
 */

require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/mod/customcert/lib.php');

// Ensure we're running as admin
require_login();
require_capability('moodle/site:config', context_system::instance());

echo "=== Certificate Generation System Configuration ===\n\n";

// Step 1: Verify Custom Certificate plugin is installed
echo "Step 1: Verifying Custom Certificate plugin installation...\n";
$plugin_installed = $DB->record_exists('modules', ['name' => 'customcert']);
if ($plugin_installed) {
    echo "✓ Custom Certificate plugin is installed\n\n";
} else {
    echo "✗ ERROR: Custom Certificate plugin not found\n";
    echo "Please install the plugin from: https://moodle.org/plugins/mod_customcert\n";
    exit(1);
}

// Step 2: Enable certificate module globally
echo "Step 2: Enabling certificate module...\n";
$module = $DB->get_record('modules', ['name' => 'customcert']);
if ($module) {
    $module->visible = 1;
    $DB->update_record('modules', $module);
    echo "✓ Custom Certificate module enabled\n\n";
}

// Step 3: Create certificate templates for competency achievements
echo "Step 3: Creating certificate templates...\n";

$certificate_templates = [
    [
        'name' => 'Competency Achievement Certificate',
        'description' => 'Professional certificate awarded for achieving competency proficiency',
        'type' => 'competency',
        'orientation' => 'L', // Landscape
        'width' => 297,
        'height' => 210
    ],
    [
        'name' => 'Learning Path Completion Certificate',
        'description' => 'Certificate awarded for completing all competencies in a learning path',
        'type' => 'learning_path',
        'orientation' => 'L',
        'width' => 297,
        'height' => 210
    ],
    [
        'name' => 'Program Completion Certificate',
        'description' => 'Professional certificate awarded for completing all core competencies in a program',
        'type' => 'program',
        'orientation' => 'L',
        'width' => 297,
        'height' => 210
    ],
    [
        'name' => 'Fellowship Completion Certificate',
        'description' => 'Professional certificate for ophthalmology fellowship program completion',
        'type' => 'fellowship',
        'orientation' => 'L',
        'width' => 297,
        'height' => 210
    ],
    [
        'name' => 'Credentialing Certificate',
        'description' => 'Certificate documenting surgical procedures and clinical competencies',
        'type' => 'credentialing',
        'orientation' => 'P', // Portrait
        'width' => 210,
        'height' => 297
    ]
];

$created_templates = [];
foreach ($certificate_templates as $template) {
    // Create a site-level course for certificate templates if it doesn't exist
    $cert_course = $DB->get_record('course', ['shortname' => 'CERT_TEMPLATES']);
    if (!$cert_course) {
        $cert_course = new stdClass();
        $cert_course->category = 0; // Miscellaneous category
        $cert_course->fullname = 'Certificate Templates';
        $cert_course->shortname = 'CERT_TEMPLATES';
        $cert_course->summary = 'Container for certificate templates';
        $cert_course->format = 'topics';
        $cert_course->visible = 0; // Hidden from students
        $cert_course->timecreated = time();
        $cert_course->timemodified = time();
        $cert_course_id = $DB->insert_record('course', $cert_course);
    } else {
        $cert_course_id = $cert_course->id;
    }
    
    // Create certificate template
    $cert = new stdClass();
    $cert->course = $cert_course_id;
    $cert->name = $template['name'];
    $cert->intro = $template['description'];
    $cert->introformat = FORMAT_HTML;
    $cert->requiredtime = 0;
    $cert->protection = 'print'; // Allow printing
    $cert->deliveryoption = 'download'; // Allow download
    $cert->timecreated = time();
    $cert->timemodified = time();
    
    // Add as course module
    $cm = new stdClass();
    $cm->course = $cert_course_id;
    $cm->module = $module->id;
    $cm->instance = 0; // Will be updated after insert
    $cm->section = 0;
    $cm->visible = 1;
    $cm->added = time();
    
    $cm_id = add_course_module($cm);
    
    // Now create the certificate instance
    $cert->id = $DB->insert_record('customcert', $cert);
    
    // Update course module with instance
    $DB->set_field('course_modules', 'instance', $cert->id, ['id' => $cm_id]);
    
    // Create certificate template pages
    $page = new stdClass();
    $page->templateid = $cert->id;
    $page->width = $template['width'];
    $page->height = $template['height'];
    $page->leftmargin = 10;
    $page->rightmargin = 10;
    $page->sequence = 1;
    $page_id = $DB->insert_record('customcert_pages', $page);
    
    // Add certificate elements (text, images, etc.)
    $elements = [];
    
    // Element 1: Certificate title
    $elements[] = [
        'pageid' => $page_id,
        'element' => 'text',
        'data' => $template['name'],
        'font' => 'helvetica',
        'fontsize' => 24,
        'colour' => '#000000',
        'posx' => 148,
        'posy' => 40,
        'width' => 0,
        'refpoint' => 2, // Center
        'sequence' => 1
    ];
    
    // Element 2: Recipient name
    $elements[] = [
        'pageid' => $page_id,
        'element' => 'studentname',
        'data' => '',
        'font' => 'helvetica',
        'fontsize' => 18,
        'colour' => '#000000',
        'posx' => 148,
        'posy' => 80,
        'width' => 0,
        'refpoint' => 2,
        'sequence' => 2
    ];
    
    // Element 3: Competency/Program name (dynamic)
    $elements[] = [
        'pageid' => $page_id,
        'element' => 'text',
        'data' => 'For achieving proficiency in: {competency_name}',
        'font' => 'helvetica',
        'fontsize' => 14,
        'colour' => '#333333',
        'posx' => 148,
        'posy' => 110,
        'width' => 0,
        'refpoint' => 2,
        'sequence' => 3
    ];
    
    // Element 4: Date
    $elements[] = [
        'pageid' => $page_id,
        'element' => 'date',
        'data' => 'd F Y',
        'font' => 'helvetica',
        'fontsize' => 12,
        'colour' => '#666666',
        'posx' => 148,
        'posy' => 160,
        'width' => 0,
        'refpoint' => 2,
        'sequence' => 4
    ];
    
    // Element 5: Verification code
    $elements[] = [
        'pageid' => $page_id,
        'element' => 'code',
        'data' => '',
        'font' => 'courier',
        'fontsize' => 10,
        'colour' => '#999999',
        'posx' => 148,
        'posy' => 190,
        'width' => 0,
        'refpoint' => 2,
        'sequence' => 5
    ];
    
    foreach ($elements as $element_data) {
        $element = new stdClass();
        foreach ($element_data as $key => $value) {
            $element->$key = $value;
        }
        $element->timecreated = time();
        $element->timemodified = time();
        $DB->insert_record('customcert_elements', $element);
    }
    
    $created_templates[] = [
        'id' => $cert->id,
        'name' => $template['name'],
        'type' => $template['type'],
        'cm_id' => $cm_id
    ];
    
    echo "  ✓ Created template: {$template['name']} (ID: {$cert->id})\n";
}

echo "\n";

// Step 4: Configure certificate delivery options
echo "Step 4: Configuring certificate delivery options...\n";
set_config('emailstudents', 1, 'customcert'); // Email certificates to students
set_config('emailteachers', 0, 'customcert'); // Don't email teachers
set_config('emailothers', '', 'customcert'); // No additional recipients
set_config('verifyany', 1, 'customcert'); // Allow anyone to verify certificates
set_config('showposxy', 0, 'customcert'); // Hide position coordinates in editor
echo "✓ Certificate delivery options configured\n\n";

// Step 5: Link certificates to competency framework
echo "Step 5: Configuring competency-certificate linkage...\n";

// Create custom table for tracking certificate-competency relationships
$table = new xmldb_table('customcert_competency_link');
$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table->add_field('certificateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
$table->add_field('planid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
$table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
$table->add_key('certificateid', XMLDB_KEY_FOREIGN, ['certificateid'], 'customcert', ['id']);

$dbman = $DB->get_manager();
if (!$dbman->table_exists($table)) {
    $dbman->create_table($table);
    echo "✓ Created certificate-competency linkage table\n";
} else {
    echo "✓ Certificate-competency linkage table already exists\n";
}
echo "\n";

// Step 6: Configure long-term credential tracking
echo "Step 6: Configuring long-term credential tracking...\n";

// Create table for tracking issued certificates
$tracking_table = new xmldb_table('customcert_credential_tracking');
$tracking_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$tracking_table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$tracking_table->add_field('certificateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$tracking_table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
$tracking_table->add_field('planid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
$tracking_table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
$tracking_table->add_field('code', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
$tracking_table->add_field('emailed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
$tracking_table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$tracking_table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
$tracking_table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
$tracking_table->add_key('certificateid', XMLDB_KEY_FOREIGN, ['certificateid'], 'customcert', ['id']);
$tracking_table->add_index('code', XMLDB_INDEX_UNIQUE, ['code']);

if (!$dbman->table_exists($tracking_table)) {
    $dbman->create_table($tracking_table);
    echo "✓ Created credential tracking table\n";
} else {
    echo "✓ Credential tracking table already exists\n";
}
echo "\n";

// Step 7: Configure certificate permissions
echo "Step 7: Configuring certificate management permissions...\n";

$context = context_system::instance();

// Program Owners can create and manage certificates
$manager_role = $DB->get_record('role', ['shortname' => 'manager']);
if ($manager_role) {
    assign_capability('mod/customcert:addinstance', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('mod/customcert:manage', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('mod/customcert:manageemailstudents', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('mod/customcert:viewreport', CAP_ALLOW, $manager_role->id, $context->id, true);
    echo "  ✓ Certificate management permissions granted to Program Owners\n";
}

// Trainers can view and issue certificates
$teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($teacher_role) {
    assign_capability('mod/customcert:view', CAP_ALLOW, $teacher_role->id, $context->id, true);
    assign_capability('mod/customcert:viewreport', CAP_ALLOW, $teacher_role->id, $context->id, true);
    assign_capability('mod/customcert:viewallcertificates', CAP_ALLOW, $teacher_role->id, $context->id, true);
    echo "  ✓ Certificate viewing permissions granted to Trainers\n";
}

// Learners can view and download their own certificates
$student_role = $DB->get_record('role', ['shortname' => 'student']);
if ($student_role) {
    assign_capability('mod/customcert:view', CAP_ALLOW, $student_role->id, $context->id, true);
    assign_capability('mod/customcert:receiveissue', CAP_ALLOW, $student_role->id, $context->id, true);
    echo "  ✓ Certificate viewing permissions granted to Learners\n";
}

echo "\n";

// Step 8: Summary and next steps
echo "=== Configuration Complete ===\n\n";
echo "Certificate System Status:\n";
echo "  ✓ Custom Certificate plugin configured\n";
echo "  ✓ " . count($created_templates) . " certificate templates created\n";
echo "  ✓ Competency-certificate linkage enabled\n";
echo "  ✓ Long-term credential tracking configured\n";
echo "  ✓ Role-based certificate permissions configured\n\n";

echo "Created Certificate Templates:\n";
foreach ($created_templates as $template) {
    echo "  - {$template['name']} (ID: {$template['id']})\n";
}

echo "\nNext Steps:\n";
echo "  1. Customize certificate templates with institutional branding\n";
echo "  2. Link certificates to specific competencies and learning paths\n";
echo "  3. Configure automatic certificate issuance rules\n";
echo "  4. Test certificate generation with sample competency completions\n";
echo "  5. Set up certificate verification portal\n\n";

echo "Certificate Management URL: {$CFG->wwwroot}/mod/customcert/\n";
echo "Certificate Verification: {$CFG->wwwroot}/mod/customcert/verify_certificate.php\n\n";

echo "Configuration saved successfully!\n";
