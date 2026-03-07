<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../config.php');

$context = context_system::instance();
$learner = $DB->get_record('user', ['username' => 'mock.learner']);
$caps = [
    'local/sceh_rules:systemadmin',
    'local/sceh_rules:programowner',
    'local/sceh_rules:trainer',
    'local/sceh_rules:trainercoach',
];

echo "Checking capabilities for mock.learner (ID: " . $learner->id . "):\n";
foreach ($caps as $cap) {
    echo "$cap: " . (has_capability($cap, $context, $learner->id) ? "ALLOW" : "DENY") . "\n";
}
