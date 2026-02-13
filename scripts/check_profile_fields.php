<?php
/**
 * Check all profile fields
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

echo "\n=== Custom Profile Fields ===\n";
$fields = $DB->get_records('user_info_field', null, 'sortorder');
foreach ($fields as $field) {
    echo "ID: {$field->id} | Name: {$field->name} | Required: {$field->required} | Visible: {$field->visible}\n";
}

echo "\n=== Checking Additional Names Setting ===\n";
// Check if additional names are enabled in Moodle config
$config = get_config('core');
if (isset($config->fullnamedisplay)) {
    echo "Full name display: {$config->fullnamedisplay}\n";
}

// Check user profile fields setting
echo "\nChecking user fields configuration...\n";
$result = $DB->get_record('config', ['name' => 'hiddenuserfields']);
if ($result) {
    echo "Hidden user fields: {$result->value}\n";
} else {
    echo "No hidden user fields set\n";
}
