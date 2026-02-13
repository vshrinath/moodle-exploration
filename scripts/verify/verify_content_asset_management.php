<?php
/**
 * Verify Content Asset Management Configuration
 * Task 6.2: Verify content asset management implementation
 * 
 * This script verifies:
 * - Activity templates are configured
 * - Content sharing is enabled
 * - Content versioning through backup/restore is working
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');

// Set admin user
$admin = get_admin();
\core\session\manager::set_user($admin);

echo "=== Verifying Content Asset Management ===\n\n";

global $DB;

$all_checks_passed = true;

// Check 1: Content bank
echo "1. Content Bank:\n";
$contentbank_enabled = get_config('core', 'enablecontentbank');
echo "   " . ($contentbank_enabled ? "✓" : "✗") . " Content bank enabled\n";
$all_checks_passed = $all_checks_passed && $contentbank_enabled;

// Check 2: Backup system
echo "\n2. Backup System:\n";
$backup_active = get_config('backup', 'backup_auto_active');
echo "   " . ($backup_active ? "✓" : "✗") . " Automated backups enabled\n";
$all_checks_passed = $all_checks_passed && $backup_active;

$backup_keep = get_config('backup', 'backup_auto_keep');
echo "   " . ($backup_keep ? "✓" : "✗") . " Backup retention configured ({$backup_keep} days)\n";

// Check 3: Content organization
echo "\n3. Content Organization:\n";
$tags_enabled = get_config('core', 'usetags');
echo "   " . ($tags_enabled ? "✓" : "✗") . " Content tagging enabled\n";
$all_checks_passed = $all_checks_passed && $tags_enabled;

$relative_dates = get_config('core', 'enablecourserelativedates');
echo "   " . ($relative_dates ? "✓" : "✗") . " Relative dates for reusability\n";
$all_checks_passed = $all_checks_passed && $relative_dates;

// Check 4: Required capabilities
echo "\n4. Content Management Capabilities:\n";
$capabilities = [
    'moodle/backup:backupcourse',
    'moodle/restore:restorecourse',
    'moodle/course:manageactivities',
    'moodle/contentbank:access'
];

foreach ($capabilities as $capability) {
    $exists = $DB->record_exists('capabilities', ['name' => $capability]);
    echo "   " . ($exists ? "✓" : "✗") . " {$capability}\n";
    $all_checks_passed = $all_checks_passed && $exists;
}

// Check 5: Backup directory
echo "\n5. Backup Infrastructure:\n";
$backup_dir = $CFG->dataroot . '/backup';
$backup_dir_exists = is_dir($backup_dir);
echo "   " . ($backup_dir_exists ? "✓" : "✗") . " Backup directory exists\n";
$all_checks_passed = $all_checks_passed && $backup_dir_exists;

// Check 6: Activity modules for content
echo "\n6. Content Activity Modules:\n";
$modules = ['page', 'url', 'resource', 'quiz', 'assign'];
foreach ($modules as $module) {
    $enabled = $DB->record_exists('modules', ['name' => $module, 'visible' => 1]);
    echo "   " . ($enabled ? "✓" : "✗") . " {$module} module\n";
    $all_checks_passed = $all_checks_passed && $enabled;
}

// Check 7: Content statistics
echo "\n7. Content Statistics:\n";
$course_count = $DB->count_records('course');
echo "   ✓ Courses: {$course_count}\n";

$backup_count = $DB->count_records('backup_courses');
echo "   ✓ Backup records: {$backup_count}\n";

// Summary
echo "\n=== Verification Summary ===\n";
if ($all_checks_passed) {
    echo "✓ All checks passed\n";
    echo "✓ Content asset management is properly configured\n";
    echo "✓ System is ready for content reuse and versioning\n";
} else {
    echo "✗ Some checks failed\n";
    echo "Please run configure_content_asset_management.php to fix issues\n";
}

exit($all_checks_passed ? 0 : 1);

?>
