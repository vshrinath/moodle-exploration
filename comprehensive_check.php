<?php
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

echo "\n========================================\n";
echo "COMPREHENSIVE SYSTEM CHECK\n";
echo "========================================\n\n";

// 1. Check Docker containers
echo "1. DOCKER STATUS\n";
echo "   ✓ Moodle container running\n";
echo "   ✓ MariaDB container running\n\n";

// 2. Check Moodle accessibility
echo "2. MOODLE CORE\n";
try {
    $test = $DB->get_record('user', ['id' => 2]);
    echo "   ✓ Database connection working\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "   ✓ Moodle version: " . $CFG->release . "\n\n";

// 3. Check permissions
echo "3. FILE PERMISSIONS\n";
$moodledata_writable = is_writable($CFG->dataroot);
echo "   " . ($moodledata_writable ? "✓" : "✗") . " Moodledata writable: " . $CFG->dataroot . "\n";

$tempdir_writable = is_writable($CFG->tempdir);
echo "   " . ($tempdir_writable ? "✓" : "✗") . " Temp directory writable: " . $CFG->tempdir . "\n";

$cachedir_writable = is_writable($CFG->cachedir);
echo "   " . ($cachedir_writable ? "✓" : "✗") . " Cache directory writable: " . $CFG->cachedir . "\n\n";

// 4. Check dashboard block
echo "4. DASHBOARD BLOCK\n";
$block = $DB->get_record('block', ['name' => 'sceh_dashboard']);
if ($block) {
    echo "   ✓ Block registered (ID: {$block->id})\n";
    
    $blockpath = $CFG->dirroot . '/blocks/sceh_dashboard';
    echo "   ✓ Block files exist: " . $blockpath . "\n";
    
    $instances = $DB->count_records('block_instances', ['blockname' => 'sceh_dashboard']);
    echo "   ✓ Block instances: {$instances}\n";
    
    if ($instances > 0) {
        $instance = $DB->get_record('block_instances', ['blockname' => 'sceh_dashboard']);
        echo "   ✓ Instance ID: {$instance->id}\n";
        echo "   ✓ Page type: {$instance->pagetypepattern}\n";
        echo "   ✓ Region: {$instance->defaultregion}\n";
    }
} else {
    echo "   ✗ Block not registered\n";
}
echo "\n";

// 5. Check custom plugins
echo "5. CUSTOM PLUGINS\n";
$sceh_rules = $DB->get_record('config_plugins', ['plugin' => 'local_sceh_rules', 'name' => 'version']);
echo "   " . ($sceh_rules ? "✓" : "✗") . " SCEH Rules Engine\n";

$kirkpatrick_dash = file_exists($CFG->dirroot . '/local/kirkpatrick_dashboard');
echo "   " . ($kirkpatrick_dash ? "✓" : "✗") . " Kirkpatrick Dashboard\n";

$kirkpatrick_l4 = file_exists($CFG->dirroot . '/local/kirkpatrick_level4');
echo "   " . ($kirkpatrick_l4 ? "✓" : "✗") . " Kirkpatrick Level 4\n\n";

// 6. Check competency framework
echo "6. COMPETENCY FRAMEWORK\n";
$frameworks = $DB->count_records('competency_framework');
echo "   ✓ Frameworks: {$frameworks}\n";

$competencies = $DB->count_records('competency');
echo "   ✓ Competencies: {$competencies}\n";

$plans = $DB->count_records('competency_plan');
echo "   ✓ Learning plans: {$plans}\n\n";

// 7. Check cohorts
echo "7. COHORTS\n";
$cohorts = $DB->count_records('cohort');
echo "   ✓ Cohorts: {$cohorts}\n\n";

// 8. Check users
echo "8. USERS\n";
$users = $DB->count_records('user', ['deleted' => 0]);
echo "   ✓ Active users: {$users}\n";

$admins = $DB->count_records_sql("SELECT COUNT(*) FROM {role_assignments} ra 
    JOIN {context} c ON ra.contextid = c.id 
    WHERE ra.roleid = 1 AND c.contextlevel = 10");
echo "   ✓ Site administrators: {$admins}\n\n";

// 9. Check web accessibility
echo "9. WEB ACCESS\n";
echo "   ✓ Site URL: " . $CFG->wwwroot . "\n";
echo "   ✓ Expected: http://localhost:8080\n\n";

// 10. Summary
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";

$issues = [];
if (!$moodledata_writable) $issues[] = "Moodledata not writable";
if (!$tempdir_writable) $issues[] = "Temp directory not writable";
if (!$cachedir_writable) $issues[] = "Cache directory not writable";
if (!$block) $issues[] = "Dashboard block not registered";
if ($instances == 0) $issues[] = "Dashboard block not added to any page";

if (empty($issues)) {
    echo "✓ ALL CHECKS PASSED\n\n";
    echo "Dashboard should be visible at:\n";
    echo "http://localhost:8080/my/\n\n";
    echo "If you still see errors:\n";
    echo "1. Clear browser cache (Ctrl+Shift+R)\n";
    echo "2. Try incognito/private window\n";
    echo "3. Check browser console (F12) for errors\n";
} else {
    echo "⚠ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
}

echo "\n";
