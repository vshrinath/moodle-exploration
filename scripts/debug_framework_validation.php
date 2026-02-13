<?php
/**
 * Debug framework validation
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/competency/classes/competency_framework.php');

use core_competency\competency_framework;

// Set admin user
$admin = get_admin();
\core\session\manager::set_user($admin);

echo "=== Debugging Framework Validation ===\n\n";

global $DB;

// Get scale
$scale = $DB->get_record('scale', ['name' => 'Default competence scale']);

// Create a framework object to check validation
$framework = new competency_framework(0);

echo "Required properties:\n";
$properties = $framework::properties_definition();
foreach ($properties as $name => $def) {
    $required = isset($def['null']) && $def['null'] == NULL_NOT_ALLOWED ? 'REQUIRED' : 'optional';
    $type = isset($def['type']) ? $def['type'] : 'unknown';
    $default = isset($def['default']) ? $def['default'] : 'none';
    echo "  {$name}: {$type} ({$required}) default={$default}\n";
}

// Try setting properties one by one
echo "\n\nTrying to create framework step by step...\n";

$framework->set('shortname', 'Test Framework');
echo "✓ Set shortname\n";

$framework->set('idnumber', 'TEST_' . time());
echo "✓ Set idnumber\n";

$framework->set('description', 'Test');
echo "✓ Set description\n";

$framework->set('descriptionformat', FORMAT_HTML);
echo "✓ Set descriptionformat\n";

$framework->set('scaleid', $scale->id);
echo "✓ Set scaleid\n";

$framework->set('contextid', context_system::instance()->id);
echo "✓ Set contextid\n";

$framework->set('visible', 1);
echo "✓ Set visible\n";

$framework->set('taxonomies', '');
echo "✓ Set taxonomies\n";

// Set scale configuration - must be JSON that decodes to objects
$scale_config = [
    (object)['scaleid' => $scale->id],  // First element with scale ID
    (object)['id' => 1, 'scaledefault' => 0, 'proficient' => 0],
    (object)['id' => 2, 'scaledefault' => 1, 'proficient' => 1]
];
$framework->set('scaleconfiguration', json_encode($scale_config));
echo "✓ Set scaleconfiguration\n";

// Try to validate
echo "\nValidating...\n";
try {
    $errors = $framework->validate();
    
    if ($errors === true) {
        echo "✓ Validation passed!\n";
        
        // Try to create
        echo "\nAttempting to create...\n";
        $framework->create();
        echo "✓ Framework created! ID: " . $framework->get('id') . "\n";
        
        // Clean up
        $framework->delete();
        echo "✓ Cleaned up\n";
        
    } else {
        echo "✗ Validation errors:\n";
        foreach ($errors as $field => $error) {
            if (is_object($error) && method_exists($error, 'get_string')) {
                echo "  {$field}: " . $error->get_string() . "\n";
            } else {
                echo "  {$field}: " . print_r($error, true) . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

?>
