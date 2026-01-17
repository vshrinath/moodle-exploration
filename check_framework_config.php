<?php
/**
 * Check existing framework configurations
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/competency/classes/competency_framework.php');

use core_competency\competency_framework;

echo "=== Checking Existing Framework Configurations ===\n\n";

global $DB;

// Get all frameworks
$frameworks = $DB->get_records('competency_framework');

if (empty($frameworks)) {
    echo "No existing frameworks found\n";
    
    // Let's try to create a simple one using the API properly
    echo "\nAttempting to create a test framework using proper API...\n";
    
    $scale = $DB->get_record('scale', ['name' => 'Default competence scale']);
    
    // Parse the scale items
    $scale_items = explode(',', $scale->scale);
    $scale_config = [];
    foreach ($scale_items as $index => $item) {
        $scale_config[] = [
            'id' => $index + 1,
            'scaledefault' => ($index == count($scale_items) - 1) ? 1 : 0,
            'proficient' => ($index == count($scale_items) - 1) ? 1 : 0
        ];
    }
    
    echo "Scale items: " . print_r($scale_items, true) . "\n";
    echo "Scale config: " . print_r($scale_config, true) . "\n";
    
} else {
    echo "Found " . count($frameworks) . " frameworks:\n\n";
    
    foreach ($frameworks as $framework) {
        echo "Framework: {$framework->shortname}\n";
        echo "  ID: {$framework->id}\n";
        echo "  Scale ID: {$framework->scaleid}\n";
        echo "  Scale Configuration: {$framework->scaleconfiguration}\n";
        
        $config = json_decode($framework->scaleconfiguration, true);
        echo "  Decoded config: " . print_r($config, true) . "\n";
        echo "---\n";
    }
}

?>
