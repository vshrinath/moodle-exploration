<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');

global $CFG;
echo "dirroot: " . $CFG->dirroot . "\n";
echo "Block directory: " . $CFG->dirroot . "/blocks/sceh_dashboard\n";
if (is_dir($CFG->dirroot . "/blocks/sceh_dashboard")) {
    echo "✓ Block directory exists\n";
} else {
    echo "✗ Block directory DOES NOT exist\n";
}

$allblocks = core_component::get_plugin_list('block');
if (isset($allblocks['sceh_dashboard'])) {
    echo "✓ Moodle found block at: " . $allblocks['sceh_dashboard'] . "\n";
} else {
    echo "✗ Moodle COULD NOT FIND block in component list\n";
}

$alllocals = core_component::get_plugin_list('local');
if (isset($alllocals['sceh_rules'])) {
    echo "✓ Moodle found local plugin at: " . $alllocals['sceh_rules'] . "\n";
} else {
    echo "✗ Moodle COULD NOT FIND local plugin in component list\n";
}
