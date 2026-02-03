<?php
/**
 * Verify Certificate Generation System Configuration
 * 
 * Validates Custom Certificate plugin setup and competency linkage
 * Tests Requirements 15.2, 15.5
 * 
 * Usage: php verify_certificate_system.php
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

// Ensure we're running as admin in CLI
$admin = get_admin();
if (!$admin) {
    fwrite(STDERR, "ERROR: No admin user found\n");
    exit(1);
}
\core\session\manager::set_user($admin);
require_capability('moodle/site:config', context_system::instance());

echo "=== Certificate Generation System Verification ===\n\n";

$all_checks_passed = true;

// Check 1: Verify Custom Certificate plugin is installed and enabled
echo "Check 1: Verifying Custom Certificate plugin...\n";
$module = $DB->get_record('modules', ['name' => 'customcert']);
if ($module && $module->visible == 1) {
    echo "✓ PASS: Custom Certificate plugin is installed and enabled\n";
} else {
    echo "✗ FAIL: Custom Certificate plugin not found or disabled\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 2: Verify certificate templates exist
echo "Check 2: Verifying certificate templates...\n";
$expected_templates = [
    'Competency Achievement Certificate',
    'Learning Path Completion Certificate',
    'Program Completion Certificate',
    'Fellowship Completion Certificate',
    'Credentialing Certificate'
];

$found_templates = [];
foreach ($expected_templates as $template_name) {
    $template = $DB->get_record('customcert', ['name' => $template_name]);
    if ($template) {
        $found_templates[] = $template;
        echo "  ✓ Found: {$template_name} (ID: {$template->id})\n";
    } else {
        echo "  ✗ Missing: {$template_name}\n";
        $all_checks_passed = false;
    }
}

if (count($found_templates) === count($expected_templates)) {
    echo "✓ PASS: All certificate templates created\n";
} else {
    echo "✗ FAIL: Missing " . (count($expected_templates) - count($found_templates)) . " certificate templates\n";
}
echo "\n";

// Check 3: Verify certificate template pages
echo "Check 3: Verifying certificate template pages...\n";
$templates_with_pages = 0;
foreach ($found_templates as $template) {
    $pages = $DB->get_records('customcert_pages', ['templateid' => $template->id]);
    if (!empty($pages)) {
        $templates_with_pages++;
        $page_count = count($pages);
        echo "  ✓ Template '{$template->name}' has {$page_count} page(s)\n";
    } else {
        echo "  ✗ Template '{$template->name}' has no pages\n";
        $all_checks_passed = false;
    }
}

if ($templates_with_pages === count($found_templates)) {
    echo "✓ PASS: All templates have pages configured\n";
} else {
    echo "✗ FAIL: {$templates_with_pages}/" . count($found_templates) . " templates have pages\n";
}
echo "\n";

// Check 4: Verify certificate elements
echo "Check 4: Verifying certificate elements...\n";
$templates_with_elements = 0;
foreach ($found_templates as $template) {
    $pages = $DB->get_records('customcert_pages', ['templateid' => $template->id]);
    $has_elements = false;
    foreach ($pages as $page) {
        $elements = $DB->get_records('customcert_elements', ['pageid' => $page->id]);
        if (!empty($elements)) {
            $has_elements = true;
            $element_count = count($elements);
            echo "  ✓ Template '{$template->name}' has {$element_count} element(s)\n";
            break;
        }
    }
    if ($has_elements) {
        $templates_with_elements++;
    } else {
        echo "  ✗ Template '{$template->name}' has no elements\n";
        $all_checks_passed = false;
    }
}

if ($templates_with_elements === count($found_templates)) {
    echo "✓ PASS: All templates have elements configured\n";
} else {
    echo "✗ FAIL: {$templates_with_elements}/" . count($found_templates) . " templates have elements\n";
}
echo "\n";

// Check 5: Verify certificate delivery configuration
echo "Check 5: Verifying certificate delivery configuration...\n";
$email_students = get_config('customcert', 'emailstudents');
$verify_any = get_config('customcert', 'verifyany');

if ($email_students && $verify_any) {
    echo "✓ PASS: Certificate delivery configured correctly\n";
    echo "  - Email to students: Enabled\n";
    echo "  - Public verification: Enabled\n";
} else {
    echo "✗ FAIL: Certificate delivery configuration incomplete\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 6: Verify competency-certificate linkage table
echo "Check 6: Verifying competency-certificate linkage...\n";
$dbman = $DB->get_manager();
$linkage_table = new xmldb_table('customcert_competency_link');
if ($dbman->table_exists($linkage_table)) {
    echo "✓ PASS: Competency-certificate linkage table exists\n";
} else {
    echo "✗ FAIL: Competency-certificate linkage table not found\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 7: Verify credential tracking table
echo "Check 7: Verifying long-term credential tracking...\n";
$tracking_table = new xmldb_table('customcert_credential_tracking');
if ($dbman->table_exists($tracking_table)) {
    echo "✓ PASS: Credential tracking table exists\n";
    
    // Check table structure
    $fields = $DB->get_columns('customcert_credential_tracking');
    $required_fields = ['userid', 'certificateid', 'competencyid', 'code', 'timecreated'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($fields[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (empty($missing_fields)) {
        echo "  ✓ All required fields present\n";
    } else {
        echo "  ✗ Missing fields: " . implode(', ', $missing_fields) . "\n";
        $all_checks_passed = false;
    }
} else {
    echo "✗ FAIL: Credential tracking table not found\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 8: Verify certificate management permissions
echo "Check 8: Verifying certificate management permissions...\n";
$context = context_system::instance();

$permission_checks = [
    'manager' => [
        'mod/customcert:addinstance',
        'mod/customcert:manage',
        'mod/customcert:viewreport'
    ],
    'editingteacher' => [
        'mod/customcert:view',
        'mod/customcert:viewreport'
    ],
    'student' => [
        'mod/customcert:view',
        'mod/customcert:receiveissue'
    ]
];

$permissions_ok = true;
foreach ($permission_checks as $role_shortname => $capabilities) {
    $role = $DB->get_record('role', ['shortname' => $role_shortname], 'id,shortname');
    if ($role) {
        echo "  Checking {$role_shortname} role:\n";
        foreach ($capabilities as $capability) {
            $rc = $DB->get_record('role_capabilities', [
                'roleid' => $role->id,
                'capability' => $capability,
                'contextid' => $context->id
            ], 'permission');
            if ($rc && (int)$rc->permission === CAP_ALLOW) {
                echo "    ✓ {$capability}\n";
            } else {
                echo "    ✗ {$capability} - NOT GRANTED\n";
                $permissions_ok = false;
            }
        }
    }
}

if ($permissions_ok) {
    echo "✓ PASS: Certificate management permissions configured correctly\n";
} else {
    echo "✗ FAIL: Some certificate permissions missing\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 9: Verify certificate template course
echo "Check 9: Verifying certificate template container...\n";
$cert_course = $DB->get_record('course', ['shortname' => 'CERT_TEMPLATES']);
if ($cert_course) {
    echo "✓ PASS: Certificate template course exists (ID: {$cert_course->id})\n";
    echo "  - Course name: {$cert_course->fullname}\n";
    echo "  - Visibility: " . ($cert_course->visible ? 'Visible' : 'Hidden') . "\n";
} else {
    echo "✗ FAIL: Certificate template course not found\n";
    $all_checks_passed = false;
}
echo "\n";

// Final summary
echo "=== Verification Summary ===\n\n";
if ($all_checks_passed) {
    echo "✓ ALL CHECKS PASSED\n\n";
    echo "Certificate Generation System Status:\n";
    echo "  ✓ Custom Certificate plugin operational\n";
    echo "  ✓ " . count($found_templates) . " professional certificate templates ready\n";
    echo "  ✓ Competency-certificate linkage configured\n";
    echo "  ✓ Long-term credential tracking enabled\n";
    echo "  ✓ Role-based permissions configured\n\n";
    
    echo "Certificate Management:\n";
    echo "  - Manage certificates: {$CFG->wwwroot}/mod/customcert/\n";
    echo "  - View my certificates: {$CFG->wwwroot}/mod/customcert/my_certificates.php\n";
    echo "  - Verify certificate: {$CFG->wwwroot}/mod/customcert/verify_certificate.php\n\n";
    
    echo "Requirements Validated:\n";
    echo "  ✓ Requirement 15.2: Professional PDF certificates for competency achievements\n";
    echo "  ✓ Requirement 15.5: Long-term credential tracking across programs\n\n";
    
    exit(0);
} else {
    echo "✗ SOME CHECKS FAILED\n\n";
    echo "Please review the failed checks above and run configure_certificate_system.php again.\n\n";
    exit(1);
}
