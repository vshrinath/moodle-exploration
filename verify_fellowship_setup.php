<?php
/**
 * Verification script for Ophthalmology Fellowship Plugins
 * 
 * This script verifies:
 * - Database Activity plugin installation and configuration
 * - Scheduler plugin installation and configuration
 * - Payment gateway availability
 * - Custom user profile fields setup
 * 
 * Requirements: 18.1, 19.1, 20.1, 21.1, 25.1
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

echo "========================================\n";
echo "Fellowship Plugins Verification\n";
echo "========================================\n\n";

$all_checks_passed = true;

/**
 * Verify Database Activity plugin
 */
function verify_database_activity() {
    global $DB;
    
    echo "Check 1: Database Activity Plugin\n";
    echo "-----------------------------------\n";
    
    $checks = array();
    
    // Check if module exists
    $module = $DB->get_record('modules', array('name' => 'data'));
    if ($module) {
        $checks['installed'] = true;
        $checks['enabled'] = ($module->visible == 1);
        echo "  ✓ Database Activity module installed\n";
        echo "  " . ($checks['enabled'] ? "✓" : "✗") . " Module is " . ($checks['enabled'] ? "enabled" : "disabled") . "\n";
    } else {
        $checks['installed'] = false;
        $checks['enabled'] = false;
        echo "  ✗ Database Activity module not found\n";
    }
    
    // Check configuration
    $maxbytes = get_config('data', 'maxbytes');
    echo "  - Max file size: " . display_size($maxbytes) . "\n";
    
    // Check for existing Database activities
    $count = $DB->count_records('data');
    echo "  - Existing Database activities: {$count}\n";
    
    echo "  Note: Create templates for Case Logbook, Credentialing, and Research\n";
    
    return $checks['installed'] && $checks['enabled'];
}

/**
 * Verify Scheduler plugin
 */
function verify_scheduler() {
    global $DB, $CFG;
    
    echo "\nCheck 2: Scheduler Plugin\n";
    echo "-----------------------------------\n";
    
    $checks = array();
    
    // Check if module exists
    $module = $DB->get_record('modules', array('name' => 'scheduler'));
    if ($module) {
        $checks['installed'] = true;
        $checks['enabled'] = ($module->visible == 1);
        echo "  ✓ Scheduler module installed\n";
        echo "  " . ($checks['enabled'] ? "✓" : "✗") . " Module is " . ($checks['enabled'] ? "enabled" : "disabled") . "\n";
    } else {
        $checks['installed'] = false;
        $checks['enabled'] = false;
        echo "  ✗ Scheduler module not found\n";
        echo "  Install from: https://github.com/bostelm/moodle-mod_scheduler\n";
    }
    
    // Check if directory exists
    if (file_exists($CFG->dirroot . '/mod/scheduler')) {
        echo "  ✓ Scheduler directory exists\n";
        
        // Check version file
        if (file_exists($CFG->dirroot . '/mod/scheduler/version.php')) {
            echo "  ✓ Scheduler version file found\n";
        }
    } else {
        echo "  ✗ Scheduler directory not found\n";
    }
    
    // Check for existing schedulers
    if ($checks['installed']) {
        $count = $DB->count_records('scheduler');
        echo "  - Existing Scheduler activities: {$count}\n";
    }
    
    echo "  Use case: Rotation scheduling and mentor meetings\n";
    
    return $checks['installed'] && $checks['enabled'];
}

/**
 * Verify payment gateway configuration
 */
function verify_payment_gateways() {
    global $CFG;
    
    echo "\nCheck 3: Payment Gateway Configuration\n";
    echo "-----------------------------------\n";
    
    $checks = array();
    
    // Check if payment subsystem exists (Moodle 4.0+)
    if (file_exists($CFG->dirroot . '/payment/classes/helper.php')) {
        $checks['subsystem'] = true;
        echo "  ✓ Payment subsystem available\n";
        
        $enabled = get_config('core', 'enablepaymentsubsystem');
        $checks['enabled'] = ($enabled == 1);
        echo "  " . ($checks['enabled'] ? "✓" : "✗") . " Payment subsystem " . ($checks['enabled'] ? "enabled" : "disabled") . "\n";
        
        // Check for PayPal gateway
        if (file_exists($CFG->dirroot . '/payment/gateway/paypal')) {
            echo "  ✓ PayPal gateway available\n";
            $checks['paypal'] = true;
        } else {
            echo "  ✗ PayPal gateway not found\n";
            $checks['paypal'] = false;
        }
        
        // Check for third-party gateways
        if (file_exists($CFG->dirroot . '/payment/gateway/razorpay')) {
            echo "  ✓ Razorpay gateway installed\n";
            $checks['razorpay'] = true;
        } else {
            echo "  - Razorpay gateway not installed (optional)\n";
            $checks['razorpay'] = false;
        }
        
        if (file_exists($CFG->dirroot . '/payment/gateway/stripe')) {
            echo "  ✓ Stripe gateway installed\n";
            $checks['stripe'] = true;
        } else {
            echo "  - Stripe gateway not installed (optional)\n";
            $checks['stripe'] = false;
        }
        
    } else {
        $checks['subsystem'] = false;
        echo "  ⚠ Payment subsystem not available (requires Moodle 4.0+)\n";
        echo "  Note: Use enrolment plugins with payment for older versions\n";
    }
    
    echo "  Use case: Registration fee collection\n";
    
    return $checks['subsystem'];
}

/**
 * Verify custom user profile fields
 */
function verify_custom_profile_fields() {
    global $DB;
    
    echo "\nCheck 4: Custom User Profile Fields\n";
    echo "-----------------------------------\n";
    
    $required_fields = array(
        'fellowship_type' => 'Fellowship Type',
        'subspecialty' => 'Primary Subspecialty',
        'secondary_subspecialty' => 'Secondary Subspecialty',
        'medical_registration' => 'Medical Registration Number',
        'emergency_contact' => 'Emergency Contact',
        'training_start_date' => 'Training Start Date',
        'training_end_date' => 'Training End Date',
        'mentor_id' => 'Assigned Mentor',
        'alumni_status' => 'Alumni Status'
    );
    
    $found_count = 0;
    $missing_fields = array();
    
    foreach ($required_fields as $shortname => $name) {
        $field = $DB->get_record('user_info_field', array('shortname' => $shortname));
        if ($field) {
            echo "  ✓ {$name}\n";
            $found_count++;
        } else {
            echo "  ✗ {$name} (missing)\n";
            $missing_fields[] = $name;
        }
    }
    
    echo "\n  Summary: {$found_count}/" . count($required_fields) . " fields configured\n";
    
    if (count($missing_fields) > 0) {
        echo "  Missing fields: " . implode(', ', $missing_fields) . "\n";
        echo "  Run: php configure_fellowship_plugins.php\n";
    }
    
    echo "  Use case: Trainee registration and profile management\n";
    
    return ($found_count == count($required_fields));
}

/**
 * Check Database Activity templates
 */
function check_database_templates() {
    echo "\nCheck 5: Database Activity Templates\n";
    echo "-----------------------------------\n";
    
    if (file_exists('DATABASE_ACTIVITY_TEMPLATES.md')) {
        echo "  ✓ Template documentation found\n";
        echo "  Note: Templates must be created manually in Moodle\n";
        echo "  Required templates:\n";
        echo "    1. Case Logbook Template\n";
        echo "    2. Credentialing Sheet Template\n";
        echo "    3. Research Publications Template\n";
    } else {
        echo "  ✗ Template documentation not found\n";
        echo "  Run: php configure_fellowship_plugins.php\n";
    }
    
    return true; // Documentation check only
}

/**
 * Integration check
 */
function check_integration() {
    global $DB;
    
    echo "\nCheck 6: Integration Readiness\n";
    echo "-----------------------------------\n";
    
    // Check competency framework
    $competencies = $DB->count_records('competency');
    echo "  - Competencies defined: {$competencies}\n";
    
    // Check courses
    $courses = $DB->count_records('course', array('visible' => 1));
    echo "  - Active courses: {$courses}\n";
    
    // Check cohorts
    $cohorts = $DB->count_records('cohort');
    echo "  - Cohorts configured: {$cohorts}\n";
    
    echo "  Note: Fellowship features integrate with competency framework\n";
    
    return true;
}

// Run all verification checks
$results = array();

$results['database'] = verify_database_activity();
$results['scheduler'] = verify_scheduler();
$results['payment'] = verify_payment_gateways();
$results['profile_fields'] = verify_custom_profile_fields();
$results['templates'] = check_database_templates();
$results['integration'] = check_integration();

// Summary
echo "\n========================================\n";
echo "Verification Summary\n";
echo "========================================\n";

$passed = 0;
$total = count($results);

foreach ($results as $check => $result) {
    $status = $result ? "✓ PASS" : "✗ FAIL";
    echo "{$status} - " . ucfirst(str_replace('_', ' ', $check)) . "\n";
    if ($result) $passed++;
}

echo "\nOverall: {$passed}/{$total} checks passed\n";

if ($passed == $total) {
    echo "\n✓ All fellowship plugins are properly configured!\n";
    echo "\nNext Steps:\n";
    echo "1. Create Database Activity templates (see DATABASE_ACTIVITY_TEMPLATES.md)\n";
    echo "2. Configure payment gateway API keys\n";
    echo "3. Test rotation scheduling with Scheduler plugin\n";
    echo "4. Test trainee registration workflow\n";
    echo "5. Run: php test_fellowship_integration.php\n";
    exit(0);
} else {
    echo "\n⚠ Some checks failed. Please review the output above.\n";
    echo "\nRecommended Actions:\n";
    echo "1. Run: bash install_fellowship_plugins.sh\n";
    echo "2. Run: php admin/cli/upgrade.php --non-interactive\n";
    echo "3. Run: php configure_fellowship_plugins.php\n";
    echo "4. Run this verification script again\n";
    exit(1);
}
