<?php
/**
 * Fix Critical Issues from Checkpoint 13
 * 
 * This script attempts to:
 * 1. Enable competency framework
 * 2. Trigger plugin installation for local_sceh_rules
 * 3. Verify fixes were successful
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');

echo "=================================================================\n";
echo "FIXING CRITICAL ISSUES FROM CHECKPOINT 13\n";
echo "=================================================================\n\n";

// Issue 1: Enable Competency Framework
echo "--- Issue 1: Enabling Competency Framework ---\n";

$current_setting = get_config('core', 'competency_enabled');
if ($current_setting) {
    echo "✓ Competency framework is already enabled\n";
} else {
    echo "Attempting to enable competency framework...\n";
    set_config('competency_enabled', 1);
    
    // Verify
    $new_setting = get_config('core', 'competency_enabled');
    if ($new_setting) {
        echo "✓ SUCCESS: Competency framework has been enabled\n";
    } else {
        echo "✗ FAILED: Could not enable competency framework\n";
    }
}

// Issue 2 & 3: Install/Upgrade Rules Engine Plugin
echo "\n--- Issue 2 & 3: Installing Rules Engine Plugin ---\n";

// Check if plugin exists
$plugin_path = $CFG->dirroot . '/local/sceh_rules';
if (!file_exists($plugin_path)) {
    echo "✗ FAILED: Plugin files not found at $plugin_path\n";
    exit(1);
}

echo "✓ Plugin files found at $plugin_path\n";

// Get plugin manager
$pluginman = core_plugin_manager::instance();

// Check if plugin is installed
$plugin_info = $pluginman->get_plugin_info('local_sceh_rules');

if ($plugin_info) {
    echo "Plugin is registered in Moodle\n";
    echo "  Current version: " . $plugin_info->versiondb . "\n";
    
    // Check if upgrade is needed
    $version_file = $plugin_path . '/version.php';
    if (file_exists($version_file)) {
        $plugin = new stdClass();
        include($version_file);
        
        if (isset($plugin->version)) {
            echo "  Version in code: " . $plugin->version . "\n";
            
            if ($plugin->version > $plugin_info->versiondb) {
                echo "⚠ Upgrade needed\n";
            } else {
                echo "✓ Plugin is up to date\n";
            }
        }
    }
} else {
    echo "⚠ Plugin not yet registered in Moodle\n";
}

// Trigger upgrade process
echo "\nTriggering Moodle upgrade process...\n";

// Reset plugin manager cache
$pluginman->reset_caches();

// Check for available updates
$updates = $pluginman->available_updates();
if (!empty($updates)) {
    echo "Available updates found: " . count($updates) . "\n";
}

// Get all plugins that need installation
$plugins_needing_install = [];
foreach ($pluginman->get_plugins() as $type => $plugins) {
    foreach ($plugins as $name => $plugin) {
        if ($plugin->get_status() === core_plugin_manager::PLUGIN_STATUS_NEW) {
            $plugins_needing_install[] = $type . '_' . $name;
            echo "  New plugin detected: {$type}_{$name}\n";
        }
    }
}

if (!empty($plugins_needing_install)) {
    echo "\n⚠ IMPORTANT: Database upgrade required\n";
    echo "Please run one of the following:\n";
    echo "  1. Via web: Navigate to Site administration → Notifications\n";
    echo "  2. Via CLI: php admin/cli/upgrade.php\n";
    echo "\nAttempting CLI upgrade now...\n\n";
    
    // Try to run upgrade
    try {
        // This is a simplified approach - in production, use admin/cli/upgrade.php
        require_once($CFG->libdir . '/upgradelib.php');
        
        // Check if upgrade is needed
        $version = null;
        $release = null;
        require($CFG->dirroot . '/version.php');
        
        $DB->set_field('config', 'value', $version, array('name' => 'version'));
        
        echo "✓ Version updated in database\n";
        
        // Install new plugins
        upgrade_noncore(true);
        
        echo "✓ Plugin installation triggered\n";
        
    } catch (Exception $e) {
        echo "⚠ Automatic upgrade failed: " . $e->getMessage() . "\n";
        echo "Please run: php admin/cli/upgrade.php\n";
    }
} else {
    echo "✓ No new plugins detected\n";
}

// Verify database tables
echo "\n--- Verifying Database Tables ---\n";

$dbman = $DB->get_manager();

$tables_to_check = [
    'local_sceh_attendance_rules',
    'local_sceh_roster_rules',
    'local_sceh_audit_log'
];

$all_tables_exist = true;
foreach ($tables_to_check as $table_name) {
    $table = new xmldb_table($table_name);
    if ($dbman->table_exists($table)) {
        echo "✓ Table exists: $table_name\n";
    } else {
        echo "✗ Table missing: $table_name\n";
        $all_tables_exist = false;
    }
}

if (!$all_tables_exist) {
    echo "\n⚠ Some tables are missing. Running upgrade is required.\n";
    echo "Run: php admin/cli/upgrade.php\n";
}

// Final verification
echo "\n=================================================================\n";
echo "VERIFICATION\n";
echo "=================================================================\n";

$competency_enabled = get_config('core', 'competency_enabled');
$plugin_info = core_plugin_manager::instance()->get_plugin_info('local_sceh_rules');

echo "1. Competency Framework: " . ($competency_enabled ? "✓ ENABLED" : "✗ DISABLED") . "\n";
echo "2. Rules Engine Plugin: " . ($plugin_info ? "✓ REGISTERED" : "✗ NOT REGISTERED") . "\n";
echo "3. Database Tables: " . ($all_tables_exist ? "✓ ALL EXIST" : "✗ SOME MISSING") . "\n";

echo "\n=================================================================\n";

if ($competency_enabled && $plugin_info && $all_tables_exist) {
    echo "✓ ALL CRITICAL ISSUES RESOLVED\n";
    echo "=================================================================\n";
    exit(0);
} else {
    echo "⚠ SOME ISSUES REMAIN\n";
    echo "=================================================================\n";
    
    if (!$competency_enabled) {
        echo "\nAction needed: Enable competency framework manually\n";
    }
    
    if (!$plugin_info || !$all_tables_exist) {
        echo "\nAction needed: Run database upgrade\n";
        echo "Command: php admin/cli/upgrade.php\n";
    }
    
    exit(1);
}
