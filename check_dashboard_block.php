<?php
/**
 * Check dashboard block status
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

echo "\n=== Checking Dashboard Block ===\n\n";

// Check if block is registered
$block = $DB->get_record('block', ['name' => 'sceh_dashboard']);
if ($block) {
    echo "✓ Block registered (ID: {$block->id})\n";
} else {
    echo "✗ Block not registered\n";
    exit(1);
}

// Check block instances
$instances = $DB->get_records('block_instances', ['blockname' => 'sceh_dashboard']);
echo "\nBlock instances: " . count($instances) . "\n";
foreach ($instances as $instance) {
    echo "  Instance ID: {$instance->id}\n";
    echo "  Page type: {$instance->pagetypepattern}\n";
    echo "  Subpage: {$instance->subpagepattern}\n";
    echo "  Region: {$instance->defaultregion}\n";
    echo "  Context: {$instance->parentcontextid}\n\n";
}

// Check block positions
$positions = $DB->get_records_sql(
    "SELECT bp.*, bi.blockname 
     FROM {block_positions} bp
     JOIN {block_instances} bi ON bi.id = bp.blockinstanceid
     WHERE bi.blockname = 'sceh_dashboard'"
);
echo "Block positions: " . count($positions) . "\n";
foreach ($positions as $pos) {
    echo "  Position ID: {$pos->id}\n";
    echo "  Page type: {$pos->pagetype}\n";
    echo "  Region: {$pos->region}\n";
    echo "  Visible: {$pos->visible}\n\n";
}

// Check if block files exist
$blockpath = $CFG->dirroot . '/blocks/sceh_dashboard';
if (file_exists($blockpath)) {
    echo "✓ Block files exist at: $blockpath\n";
    echo "  Files:\n";
    $files = scandir($blockpath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "    - $file\n";
        }
    }
} else {
    echo "✗ Block files not found\n";
}

echo "\n";
