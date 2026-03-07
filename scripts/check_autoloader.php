<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');

$loader = include($CFG->dirroot . '/vendor/autoload.php');
// Moodle uses its own autoloader for plugins usually.
echo "Plugin local_sceh_rules path: " . core_component::get_plugin_directory('local', 'sceh_rules') . "\n";
echo "Block sceh_dashboard path: " . core_component::get_plugin_directory('block', 'sceh_dashboard') . "\n";
