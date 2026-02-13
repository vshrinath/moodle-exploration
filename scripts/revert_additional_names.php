<?php
/**
 * Revert Additional Names hiding
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

echo "\n=== Reverting Additional Names Changes ===\n\n";

// Remove from hidden fields
set_config('hiddenuserfields', '');
echo "✓ Cleared hidden user fields\n";

// Unlock the fields
$fields_to_unlock = [
    'firstnamephonetic',
    'lastnamephonetic',
    'middlename',
    'alternatename'
];

foreach ($fields_to_unlock as $field) {
    $configkey = 'locked_' . $field;
    unset_config($configkey);
    echo "Unlocked field: $field\n";
}

echo "\n✓ Reverted - Additional names section restored to default\n\n";
