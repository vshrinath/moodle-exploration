<?php
/**
 * Check permissions for username 'user'
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

echo "\n=== Checking User 'user' Permissions ===\n\n";

// Find the user
$user = $DB->get_record('user', ['username' => 'user']);
if (!$user) {
    echo "✗ User 'user' not found\n";
    exit(1);
}

echo "User found:\n";
echo "  ID: {$user->id}\n";
echo "  Username: {$user->username}\n";
echo "  Email: {$user->email}\n";
echo "  First name: {$user->firstname}\n";
echo "  Last name: {$user->lastname}\n\n";

// Get user's role assignments
$roleassignments = $DB->get_records_sql(
    "SELECT ra.*, r.shortname, r.name, c.contextlevel
     FROM {role_assignments} ra
     JOIN {role} r ON r.id = ra.roleid
     JOIN {context} c ON c.id = ra.contextid
     WHERE ra.userid = ?
     ORDER BY c.contextlevel",
    [$user->id]
);

echo "Role Assignments (" . count($roleassignments) . "):\n";
foreach ($roleassignments as $ra) {
    $contextlevels = [
        10 => 'System',
        30 => 'User',
        40 => 'Course Category',
        50 => 'Course',
        70 => 'Module',
        80 => 'Block'
    ];
    $level = $contextlevels[$ra->contextlevel] ?? "Unknown ({$ra->contextlevel})";
    echo "  - {$ra->name} ({$ra->shortname}) at {$level} level (Context: {$ra->contextid})\n";
}

// Check specific capabilities
echo "\nKey Capabilities:\n";
$context = context_system::instance();

$capabilities = [
    'moodle/site:config' => 'Site admin',
    'moodle/course:update' => 'Edit courses',
    'moodle/course:view' => 'View courses',
    'moodle/user:update' => 'Edit users',
    'block/sceh_dashboard:addinstance' => 'Add dashboard block',
    'block/sceh_dashboard:myaddinstance' => 'Add dashboard to My page'
];

foreach ($capabilities as $cap => $desc) {
    $has = has_capability($cap, $context, $user->id);
    $status = $has ? '✓' : '✗';
    echo "  $status $desc ($cap)\n";
}

echo "\n";
