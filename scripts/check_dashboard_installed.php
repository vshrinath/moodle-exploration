<?php
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

echo "\n=== Dashboard Block Status ===\n\n";

$block = $DB->get_record('block', ['name' => 'sceh_dashboard']);
if ($block) {
    echo "✓ Block registered in database\n";
    echo "  ID: {$block->id}\n";
    echo "  Name: {$block->name}\n";
} else {
    echo "✗ Block not found in database\n";
    exit(1);
}

$instances = $DB->count_records('block_instances', ['blockname' => 'sceh_dashboard']);
echo "\n✓ Block instances: {$instances}\n";

if ($instances == 0) {
    echo "\n⚠ No instances yet - add the block to a page!\n";
}

echo "\n=== Installation Complete! ===\n";
echo "Open http://localhost:8080 in your browser\n";
echo "Log in and add the 'Fellowship Training Dashboard' block\n\n";
