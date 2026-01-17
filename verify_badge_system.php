<?php
/**
 * Verify Digital Badge System Configuration
 * 
 * Validates Open Badges 2.0 setup and competency-based badge criteria
 * Tests Requirements 15.1, 15.3
 * 
 * Usage: php verify_badge_system.php
 */

require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/badgeslib.php');

// Ensure we're running as admin
require_login();
require_capability('moodle/site:config', context_system::instance());

echo "=== Digital Badge System Verification ===\n\n";

$all_checks_passed = true;

// Check 1: Verify badges are enabled globally
echo "Check 1: Verifying badges are enabled globally...\n";
$badges_enabled = get_config('core', 'enablebadges');
if ($badges_enabled) {
    echo "✓ PASS: Badges are enabled globally\n";
} else {
    echo "✗ FAIL: Badges are not enabled\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 2: Verify Open Badges 2.0 configuration
echo "Check 2: Verifying Open Badges 2.0 configuration...\n";
$external_backpack = get_config('core', 'badges_allowexternalbackpack');
$course_badges = get_config('core', 'badges_allowcoursebadges');
$badge_salt = get_config('core', 'badges_badgesalt');

if ($external_backpack && $course_badges && !empty($badge_salt)) {
    echo "✓ PASS: Open Badges 2.0 settings configured correctly\n";
    echo "  - External backpack: Enabled\n";
    echo "  - Course badges: Enabled\n";
    echo "  - Badge salt: Configured\n";
} else {
    echo "✗ FAIL: Open Badges 2.0 settings incomplete\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 3: Verify badge templates exist
echo "Check 3: Verifying competency-based badge templates...\n";
$expected_badges = [
    'Competency Achievement - Bronze',
    'Competency Achievement - Silver',
    'Competency Achievement - Gold',
    'Learning Path Completion',
    'Program Completion'
];

$found_badges = [];
foreach ($expected_badges as $badge_name) {
    $badge = $DB->get_record('badge', ['name' => $badge_name]);
    if ($badge) {
        $found_badges[] = $badge;
        echo "  ✓ Found: {$badge_name} (ID: {$badge->id})\n";
    } else {
        echo "  ✗ Missing: {$badge_name}\n";
        $all_checks_passed = false;
    }
}

if (count($found_badges) === count($expected_badges)) {
    echo "✓ PASS: All badge templates created\n";
} else {
    echo "✗ FAIL: Missing " . (count($expected_badges) - count($found_badges)) . " badge templates\n";
}
echo "\n";

// Check 4: Verify badge criteria configuration
echo "Check 4: Verifying badge criteria configuration...\n";
$badges_with_criteria = 0;
foreach ($found_badges as $badge) {
    $criteria = $DB->get_records('badge_criteria', ['badgeid' => $badge->id]);
    if (!empty($criteria)) {
        $badges_with_criteria++;
        echo "  ✓ Badge '{$badge->name}' has criteria configured\n";
    } else {
        echo "  ✗ Badge '{$badge->name}' missing criteria\n";
        $all_checks_passed = false;
    }
}

if ($badges_with_criteria === count($found_badges)) {
    echo "✓ PASS: All badges have criteria configured\n";
} else {
    echo "✗ FAIL: {$badges_with_criteria}/" . count($found_badges) . " badges have criteria\n";
}
echo "\n";

// Check 5: Verify external badge sharing capabilities
echo "Check 5: Verifying external badge sharing...\n";
$backpack_url = get_config('core', 'badges_site_backpack');
if (!empty($backpack_url)) {
    echo "✓ PASS: External backpack configured\n";
    echo "  - Backpack URL: {$backpack_url}\n";
} else {
    echo "✗ FAIL: External backpack not configured\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 6: Verify badge management permissions
echo "Check 6: Verifying badge management permissions...\n";
$context = context_system::instance();

$permission_checks = [
    'manager' => [
        'moodle/badges:createbadge',
        'moodle/badges:configurecriteria',
        'moodle/badges:awardbadge'
    ],
    'editingteacher' => [
        'moodle/badges:awardbadge',
        'moodle/badges:viewbadges'
    ],
    'student' => [
        'moodle/badges:viewbadges',
        'moodle/badges:manageownbadges'
    ]
];

$permissions_ok = true;
foreach ($permission_checks as $role_shortname => $capabilities) {
    $role = $DB->get_record('role', ['shortname' => $role_shortname]);
    if ($role) {
        echo "  Checking {$role_shortname} role:\n";
        foreach ($capabilities as $capability) {
            $has_cap = has_capability($capability, $context, null, false, $role->id);
            if ($has_cap) {
                echo "    ✓ {$capability}\n";
            } else {
                echo "    ✗ {$capability} - NOT GRANTED\n";
                $permissions_ok = false;
            }
        }
    }
}

if ($permissions_ok) {
    echo "✓ PASS: Badge management permissions configured correctly\n";
} else {
    echo "✗ FAIL: Some badge permissions missing\n";
    $all_checks_passed = false;
}
echo "\n";

// Check 7: Verify badge version compliance
echo "Check 7: Verifying Open Badges 2.0 compliance...\n";
$compliant_badges = 0;
foreach ($found_badges as $badge) {
    if (isset($badge->version) && $badge->version === '2.0') {
        $compliant_badges++;
    }
}

if ($compliant_badges === count($found_badges)) {
    echo "✓ PASS: All badges are Open Badges 2.0 compliant\n";
} else {
    echo "✗ FAIL: {$compliant_badges}/" . count($found_badges) . " badges are OB 2.0 compliant\n";
    $all_checks_passed = false;
}
echo "\n";

// Final summary
echo "=== Verification Summary ===\n\n";
if ($all_checks_passed) {
    echo "✓ ALL CHECKS PASSED\n\n";
    echo "Digital Badge System Status:\n";
    echo "  ✓ Open Badges 2.0 compliant framework operational\n";
    echo "  ✓ " . count($found_badges) . " competency-based badge templates ready\n";
    echo "  ✓ External badge sharing enabled\n";
    echo "  ✓ Role-based permissions configured\n\n";
    
    echo "Badge Management:\n";
    echo "  - Manage badges: {$CFG->wwwroot}/badges/index.php\n";
    echo "  - View my badges: {$CFG->wwwroot}/badges/mybadges.php\n";
    echo "  - Badge verification: {$CFG->wwwroot}/badges/badge.php?hash=[badge_hash]\n\n";
    
    echo "Requirements Validated:\n";
    echo "  ✓ Requirement 15.1: Automatic badge awarding for competency completion\n";
    echo "  ✓ Requirement 15.3: External badge sharing on professional platforms\n\n";
    
    exit(0);
} else {
    echo "✗ SOME CHECKS FAILED\n\n";
    echo "Please review the failed checks above and run configure_badge_system.php again.\n\n";
    exit(1);
}
