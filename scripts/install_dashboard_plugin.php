<?php
/**
 * Install SCEH Dashboard block plugin
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/upgradelib.php');

echo "\n=== Installing SCEH Dashboard Plugin ===\n\n";

// Trigger plugin discovery
$pluginman = core_plugin_manager::instance();
$pluginman->reset_caches();

echo "✓ Plugin cache cleared\n";

// Check if plugin is detected
$plugin = $pluginman->get_plugin_info('block_sceh_dashboard');

if ($plugin) {
    echo "✓ Plugin detected: {$plugin->displayname}\n";
    echo "  Version: {$plugin->versiondisk}\n";
    echo "  Status: " . ($plugin->is_installed() ? "Installed" : "Not installed") . "\n";
    
    if (!$plugin->is_installed()) {
        echo "\nInstalling plugin...\n";
        upgrade_noncore(true);
        echo "✓ Plugin installed\n";
    }
} else {
    echo "✗ Plugin not detected. Check if files are in /bitnami/moodle/blocks/sceh_dashboard/\n";
    exit(1);
}

echo "\n=== Success! ===\n\n";
