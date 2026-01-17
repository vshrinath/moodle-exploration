<?php
/**
 * Check available scales for competency framework
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');

echo "=== Checking Available Scales ===\n\n";

global $DB;

// Get all scales
$scales = $DB->get_records('scale');

echo "Available scales:\n";
foreach ($scales as $scale) {
    echo "ID: {$scale->id}\n";
    echo "Name: {$scale->name}\n";
    echo "Scale: {$scale->scale}\n";
    echo "Description: {$scale->description}\n";
    echo "---\n";
}

// Check if there's a default competency scale
$default_scale = $DB->get_record('scale', ['name' => 'Competency default scale']);
if ($default_scale) {
    echo "\n✓ Default competency scale found (ID: {$default_scale->id})\n";
} else {
    echo "\n⚠ No default competency scale found\n";
}

?>
