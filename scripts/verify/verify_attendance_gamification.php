<?php
/**
 * Verification script for Attendance and Gamification plugins
 * Task 2.5: Install attendance and gamification plugins
 * Requirements: 14.1, 15.2, 16.1
 */

define('CLI_SCRIPT', true);
// Detect Moodle config
if (!defined('MOODLE_INTERNAL')) {
    $config_paths = [
        '/var/www/html/public/config.php',
        '/bitnami/moodle/config.php',
        dirname(__DIR__, 2) . '/moodle-core/public/config.php',
        dirname(__DIR__, 1) . '/config.php',
        __DIR__ . '/config.php'
    ];
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            break;
        }
    }
}

require_once($CFG->libdir.'/adminlib.php');

echo "========================================\n";
echo "Verifying Attendance and Gamification Plugins\n";
echo "========================================\n\n";

$results = [];
$all_passed = true;

/**
 * Check if a plugin is installed
 */
function check_plugin($component, $plugin_name, $requirement) {
    global $DB, $results, $all_passed;
    
    echo "Checking $plugin_name...\n";
    
    $plugin_info = core_plugin_manager::instance()->get_plugin_info($component);
    
    if ($plugin_info) {
        $version = $plugin_info->versiondb;
        $release = $plugin_info->release ?? 'N/A';
        echo "  ✓ Plugin installed\n";
        echo "    Version: $version\n";
        echo "    Release: $release\n";
        echo "    Requirement: $requirement\n";
        $results[$plugin_name] = [
            'status' => 'installed',
            'version' => $version,
            'release' => $release,
            'requirement' => $requirement
        ];
    } else {
        echo "  ✗ Plugin NOT installed\n";
        echo "    Requirement: $requirement\n";
        $results[$plugin_name] = [
            'status' => 'not_installed',
            'requirement' => $requirement
        ];
        $all_passed = false;
    }
    echo "\n";
}

/**
 * Check plugin capabilities
 */
function check_plugin_capabilities($component, $plugin_name) {
    global $DB;
    
    $capabilities = $DB->get_records('capabilities', ['component' => $component]);
    
    if (!empty($capabilities)) {
        echo "  Capabilities registered: " . count($capabilities) . "\n";
        $cap_names = array_map(function($cap) {
            return $cap->name;
        }, array_slice($capabilities, 0, 3));
        echo "  Sample capabilities: " . implode(', ', $cap_names) . "\n";
    }
}

// 1. Check Attendance Plugin (mod_attendance)
// Requirement 14.1: Session attendance tracking
check_plugin('mod_attendance', 'Attendance Plugin', '14.1 - Session attendance tracking');
if (isset($results['Attendance Plugin']) && $results['Attendance Plugin']['status'] === 'installed') {
    check_plugin_capabilities('mod_attendance', 'Attendance Plugin');
}

// 2. Check Level Up! Plugin (block_xp)
// Requirement 16.1: Gamification with XP points and leveling
check_plugin('block_xp', 'Level Up! Plugin', '16.1 - Gamification with XP points');
if (isset($results['Level Up! Plugin']) && $results['Level Up! Plugin']['status'] === 'installed') {
    check_plugin_capabilities('block_xp', 'Level Up! Plugin');
}

// 3. Check Stash Plugin (block_stash)
// Requirement 16.1: Collectible items and engagement rewards
check_plugin('block_stash', 'Stash Plugin', '16.1 - Collectible items and rewards');
if (isset($results['Stash Plugin']) && $results['Stash Plugin']['status'] === 'installed') {
    check_plugin_capabilities('block_stash', 'Stash Plugin');
}

// 4. Check Custom Certificate Plugin (mod_customcert)
// Requirement 15.2: Competency-based certification
check_plugin('mod_customcert', 'Custom Certificate Plugin', '15.2 - Digital credentials');
if (isset($results['Custom Certificate Plugin']) && $results['Custom Certificate Plugin']['status'] === 'installed') {
    check_plugin_capabilities('mod_customcert', 'Custom Certificate Plugin');
}

// Summary
echo "========================================\n";
echo "Verification Summary\n";
echo "========================================\n\n";

$installed_count = 0;
$total_count = count($results);

foreach ($results as $plugin_name => $info) {
    if ($info['status'] === 'installed') {
        $installed_count++;
        echo "✓ $plugin_name: INSTALLED\n";
        echo "  Requirement: {$info['requirement']}\n";
    } else {
        echo "✗ $plugin_name: NOT INSTALLED\n";
        echo "  Requirement: {$info['requirement']}\n";
    }
}

echo "\n";
echo "Installed: $installed_count / $total_count plugins\n";
echo "\n";

if ($all_passed) {
    echo "========================================\n";
    echo "✓ ALL PLUGINS VERIFIED SUCCESSFULLY\n";
    echo "========================================\n";
    echo "\nTask 2.5 Requirements Met:\n";
    echo "  ✓ 14.1 - Attendance tracking for session management\n";
    echo "  ✓ 15.2 - Custom Certificate for credentialing\n";
    echo "  ✓ 16.1 - Level Up! and Stash for gamification\n";
    exit(0);
} else {
    echo "========================================\n";
    echo "✗ SOME PLUGINS NOT INSTALLED\n";
    echo "========================================\n";
    echo "\nPlease run the installation script:\n";
    echo "  bash install_attendance_gamification.sh\n";
    echo "\nThen complete the installation via Moodle admin interface:\n";
    echo "  1. Access http://localhost:8080\n";
    echo "  2. Login as administrator\n";
    echo "  3. Navigate to Site administration > Notifications\n";
    echo "  4. Complete plugin installation wizard\n";
    exit(1);
}
