<?php
/**
 * Simple framework creation test
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/competency/classes/api.php');

use core_competency\api;

// Set admin user
$admin = get_admin();
\core\session\manager::set_user($admin);

echo "=== Simple Framework Creation Test ===\n\n";

global $DB;

// Get scale
$scale = $DB->get_record('scale', ['name' => 'Default competence scale']);
echo "Scale ID: {$scale->id}\n";
echo "Scale items: {$scale->scale}\n\n";

// Try creating with minimal data
$data = new stdClass();
$data->shortname = 'Test Framework';
$data->idnumber = 'TEST_' . time();
$data->description = 'Test description';
$data->descriptionformat = FORMAT_HTML;
$data->scaleid = $scale->id;
$data->scaleconfiguration = [
    ['id' => 1, 'scaledefault' => 0, 'proficient' => 0],
    ['id' => 2, 'scaledefault' => 1, 'proficient' => 1]
];
$data->contextid = context_system::instance()->id;
$data->visible = 1;
$data->taxonomies = '';

echo "Attempting to create framework with data:\n";
print_r($data);
echo "\n";

try {
    $framework = api::create_framework($data);
    echo "✓ Framework created successfully! ID: " . $framework->get('id') . "\n";
    
    // Clean up
    api::delete_framework($framework->get('id'));
    echo "✓ Test framework deleted\n";
    
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    
    // Try to get more details
    if (method_exists($e, 'errorcode')) {
        echo "Error code: " . $e->errorcode . "\n";
    }
    if (method_exists($e, 'debuginfo')) {
        echo "Debug info: " . $e->debuginfo . "\n";
    }
}

?>
