<?php
/**
 * Configuration script for Ophthalmology Fellowship Plugins
 * 
 * This script configures:
 * - Database Activity plugin settings
 * - Scheduler plugin settings
 * - Payment gateway configurations
 * - Custom user profile fields for trainee registration
 * 
 * Requirements: 18.1, 19.1, 20.1, 21.1, 25.1
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
require_once($CFG->libdir . '/accesslib.php');

echo "========================================\n";
echo "Fellowship Plugins Configuration\n";
echo "========================================\n\n";

// Ensure we're running as admin in CLI
$admin = get_admin();
if (!$admin) {
    fwrite(STDERR, "ERROR: No admin user found\n");
    exit(1);
}
\core\session\manager::set_user($admin);
if (!is_siteadmin()) {
    echo "Error: This script must be run as a site administrator\n";
    exit(1);
}

/**
 * Configure Database Activity plugin settings
 */
function configure_database_activity() {
    global $DB;
    
    echo "Step 1: Configuring Database Activity plugin...\n";
    
    // Enable Database Activity module
    $module = $DB->get_record('modules', array('name' => 'data'));
    if ($module) {
        if ($module->visible == 0) {
            $DB->set_field('modules', 'visible', 1, array('name' => 'data'));
            echo "  ✓ Database Activity module enabled\n";
        } else {
            echo "  ✓ Database Activity module already enabled\n";
        }
    } else {
        echo "  ✗ Database Activity module not found\n";
        return false;
    }
    
    // Set default configurations for Database Activity
    set_config('maxbytes', 10485760, 'data'); // 10MB max file size
    set_config('enablerssfeeds', 0, 'data'); // Disable RSS feeds
    
    echo "  ✓ Database Activity default settings configured\n";
    echo "  Note: Create templates for Case Logbook, Credentialing Sheet, and Research Publications\n";
    
    return true;
}

/**
 * Configure Scheduler plugin settings
 */
function configure_scheduler() {
    global $DB;
    
    echo "\nStep 2: Configuring Scheduler plugin...\n";
    
    // Check if Scheduler module exists
    $module = $DB->get_record('modules', array('name' => 'scheduler'));
    if (!$module) {
        echo "  ✗ Scheduler module not found - please install it first\n";
        return false;
    }
    
    // Enable Scheduler module
    if ($module->visible == 0) {
        $DB->set_field('modules', 'visible', 1, array('name' => 'scheduler'));
        echo "  ✓ Scheduler module enabled\n";
    } else {
        echo "  ✓ Scheduler module already enabled\n";
    }
    
    // Configure Scheduler settings
    set_config('allteachersgrading', 0, 'scheduler'); // Only assigned teachers can grade
    set_config('showemailplain', 1, 'scheduler'); // Show email in plain text
    set_config('groupscheduling', 1, 'scheduler'); // Enable group scheduling
    
    echo "  ✓ Scheduler default settings configured\n";
    echo "  Note: Scheduler supports rotation scheduling and mentor meetings\n";
    
    return true;
}

/**
 * Configure payment gateway settings
 */
function configure_payment_gateways() {
    global $CFG;
    
    echo "\nStep 3: Configuring Payment Gateway settings...\n";
    
    // Enable payment subsystem (Moodle 4.0+)
    if (file_exists($CFG->dirroot . '/payment/classes/helper.php')) {
        set_config('enablepaymentsubsystem', 1);
        echo "  ✓ Payment subsystem enabled\n";
        
        // PayPal configuration placeholder
        echo "  Note: Configure PayPal gateway at: Site administration > Payments > Payment gateways\n";
        echo "  Note: For Razorpay/Stripe, install respective plugins and configure API keys\n";
    } else {
        echo "  ⚠ Payment subsystem not available (requires Moodle 4.0+)\n";
        echo "  Note: Use Enrolment plugins with payment options for older Moodle versions\n";
    }
    
    return true;
}

/**
 * Create custom user profile fields for trainee registration
 */
function create_custom_profile_fields() {
    global $DB;
    
    echo "\nStep 4: Creating custom user profile fields...\n";
    
    // Define custom profile fields for ophthalmology fellowship
    $profile_fields = array(
        array(
            'shortname' => 'fellowship_type',
            'name' => 'Fellowship Type',
            'datatype' => 'menu',
            'description' => 'Type of fellowship program',
            'param1' => "Long-term Fellowship\nShort-term Training\nObserver",
            'categoryid' => 1,
            'sortorder' => 1,
            'required' => 1,
            'visible' => 2,
            'signup' => 1
        ),
        array(
            'shortname' => 'subspecialty',
            'name' => 'Primary Subspecialty',
            'datatype' => 'menu',
            'description' => 'Primary ophthalmology subspecialty',
            'param1' => "Cataract\nRetina\nCornea\nGlaucoma\nOculoplasty\nPediatric Ophthalmology\nNeuro-ophthalmology",
            'categoryid' => 1,
            'sortorder' => 2,
            'required' => 1,
            'visible' => 2,
            'signup' => 1
        ),
        array(
            'shortname' => 'secondary_subspecialty',
            'name' => 'Secondary Subspecialty',
            'datatype' => 'menu',
            'description' => 'Secondary ophthalmology subspecialty (optional)',
            'param1' => "None\nCataract\nRetina\nCornea\nGlaucoma\nOculoplasty\nPediatric Ophthalmology\nNeuro-ophthalmology",
            'categoryid' => 1,
            'sortorder' => 3,
            'required' => 0,
            'visible' => 2,
            'signup' => 1
        ),
        array(
            'shortname' => 'medical_registration',
            'name' => 'Medical Registration Number',
            'datatype' => 'text',
            'description' => 'Medical council registration number',
            'categoryid' => 1,
            'sortorder' => 4,
            'required' => 1,
            'visible' => 2,
            'signup' => 1
        ),
        array(
            'shortname' => 'emergency_contact',
            'name' => 'Emergency Contact',
            'datatype' => 'text',
            'description' => 'Emergency contact name and phone',
            'categoryid' => 1,
            'sortorder' => 5,
            'required' => 1,
            'visible' => 2,
            'signup' => 1
        ),
        array(
            'shortname' => 'training_start_date',
            'name' => 'Training Start Date',
            'datatype' => 'datetime',
            'description' => 'Date when training commenced',
            'categoryid' => 1,
            'sortorder' => 6,
            'required' => 0,
            'visible' => 2,
            'signup' => 0
        ),
        array(
            'shortname' => 'training_end_date',
            'name' => 'Training End Date',
            'datatype' => 'datetime',
            'description' => 'Expected training completion date',
            'categoryid' => 1,
            'sortorder' => 7,
            'required' => 0,
            'visible' => 2,
            'signup' => 0
        ),
        array(
            'shortname' => 'mentor_id',
            'name' => 'Assigned Mentor',
            'datatype' => 'text',
            'description' => 'Mentor user ID or name',
            'categoryid' => 1,
            'sortorder' => 8,
            'required' => 0,
            'visible' => 2,
            'signup' => 0
        ),
        array(
            'shortname' => 'alumni_status',
            'name' => 'Alumni Status',
            'datatype' => 'checkbox',
            'description' => 'Whether trainee is now an alumnus',
            'categoryid' => 1,
            'sortorder' => 9,
            'required' => 0,
            'visible' => 2,
            'signup' => 0
        )
    );
    
    $created_count = 0;
    $existing_count = 0;
    
    foreach ($profile_fields as $field_data) {
        // Check if field already exists
        $existing = $DB->get_record('user_info_field', array('shortname' => $field_data['shortname']));
        
        if (!$existing) {
            // Create new profile field
            $field = new stdClass();
            foreach ($field_data as $key => $value) {
                $field->$key = $value;
            }
            
            $DB->insert_record('user_info_field', $field);
            $created_count++;
            echo "  ✓ Created profile field: {$field_data['name']}\n";
        } else {
            $existing_count++;
            echo "  - Profile field already exists: {$field_data['name']}\n";
        }
    }
    
    echo "  Summary: {$created_count} fields created, {$existing_count} already existed\n";
    
    return true;
}

/**
 * Create Database Activity templates documentation
 */
function create_template_documentation() {
    echo "\nStep 5: Creating Database Activity template documentation...\n";
    
    $doc = <<<EOT
========================================
Database Activity Templates Guide
========================================

Three Database Activity templates need to be created manually:

1. CASE LOGBOOK TEMPLATE
   Fields:
   - Date (Date field)
   - Subspecialty (Menu: Cataract, Retina, Cornea, Glaucoma, Oculoplasty, Pediatric, Neuro)
   - Procedure Type (Text field)
   - Procedure Details (Textarea)
   - Patient Outcome (Menu: Excellent, Good, Fair, Poor)
   - Complications (Textarea)
   - Learning Points (Textarea)
   - Mentor Approval (Menu: Pending, Approved, Needs Revision)
   - Mentor Feedback (Textarea)
   
   Settings:
   - Approval required: Yes
   - Comments enabled: Yes
   - Entries per page: 10

2. CREDENTIALING SHEET TEMPLATE
   Fields:
   - Month/Year (Date field)
   - Cataract Procedures (Number)
   - Retina Procedures (Number)
   - Cornea Procedures (Number)
   - Glaucoma Procedures (Number)
   - Oculoplasty Procedures (Number)
   - Pediatric Procedures (Number)
   - Neuro Procedures (Number)
   - Competencies Achieved (Textarea)
   - Mentor Verification (Menu: Pending, Verified, Rejected)
   - Mentor Comments (Textarea)
   
   Settings:
   - Approval required: Yes
   - One entry per month: Yes

3. RESEARCH PUBLICATIONS TEMPLATE
   Fields:
   - Title (Text field)
   - Publication Year (Number)
   - Journal Name (Text field)
   - Authors (Textarea)
   - Publication Link (URL field)
   - Research Type (Menu: Original Research, Case Report, Review Article, Meta-analysis)
   - Submission Status (Menu: Draft, Submitted, Under Review, Accepted, Published)
   - Mentor Review (Menu: Pending, Approved, Needs Revision)
   - Mentor Comments (Textarea)
   
   Settings:
   - Approval required: Yes
   - Comments enabled: Yes

To create these templates:
1. Go to Site administration > Plugins > Activity modules > Database
2. Create a new Database activity in a course
3. Add all fields as specified above
4. Configure the settings
5. Export the template using Backup functionality
6. Import into fellowship courses as needed

EOT;
    
    file_put_contents('DATABASE_ACTIVITY_TEMPLATES.md', $doc);
    echo "  ✓ Created DATABASE_ACTIVITY_TEMPLATES.md\n";
    
    return true;
}

// Run all configuration steps
$success = true;

$success = configure_database_activity() && $success;
$success = configure_scheduler() && $success;
$success = configure_payment_gateways() && $success;
$success = create_custom_profile_fields() && $success;
$success = create_template_documentation() && $success;

echo "\n========================================\n";
if ($success) {
    echo "Configuration Complete!\n";
    echo "========================================\n\n";
    echo "Next Steps:\n";
    echo "1. Run: php verify_fellowship_setup.php\n";
    echo "2. Create Database Activity templates (see DATABASE_ACTIVITY_TEMPLATES.md)\n";
    echo "3. Configure payment gateway API keys in Site administration\n";
    echo "4. Test scheduler functionality with rotation scheduling\n";
    echo "5. Test registration workflow with custom profile fields\n";
} else {
    echo "Configuration completed with warnings\n";
    echo "========================================\n\n";
    echo "Please review the warnings above and complete manual configuration steps.\n";
}

exit(0);
