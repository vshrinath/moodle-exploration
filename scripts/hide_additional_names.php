<?php
/**
 * Hide Additional Names section from user profiles
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');
require_once($CFG->libdir . '/adminlib.php');

echo "\n=== Hiding Additional Names Section ===\n\n";

// The additional names fields in Moodle are:
// - firstnamephonetic
// - lastnamephonetic  
// - middlename
// - alternatename

// We need to set these as "locked" or hidden
$fields_to_hide = [
    'firstnamephonetic',
    'lastnamephonetic',
    'middlename',
    'alternatename'
];

// Get current hidden fields
$current = get_config('core', 'hiddenuserfields');
$hidden = $current ? explode(',', $current) : [];

// Add our fields to hidden list
foreach ($fields_to_hide as $field) {
    if (!in_array($field, $hidden)) {
        $hidden[] = $field;
        echo "Hiding field: $field\n";
    }
}

// Update config
$hidden_string = implode(',', $hidden);
set_config('hiddenuserfields', $hidden_string);

echo "\n✓ Additional names section hidden\n";
echo "Hidden fields: $hidden_string\n\n";

// Also lock these fields so they can't be edited
foreach ($fields_to_hide as $field) {
    $configkey = 'locked_' . $field;
    set_config($configkey, 1);
    echo "Locked field: $field\n";
}

echo "\n=== Complete ===\n";
echo "The Additional Names section is now hidden from all user profiles.\n";
echo "Clear your browser cache and refresh to see changes.\n\n";
