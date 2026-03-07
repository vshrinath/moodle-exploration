<?php
/**
 * Add SCEH Dashboard block to the DEFAULT dashboard layout
 * 
 * This adds the block to the default my-index page (subpagepattern='')
 * so it appears for ALL users automatically, not just specific users.
 * 
 * This is idempotent - safe to run multiple times.
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/lib/config_helper.php');
require_moodle_config();

echo "\n=== Adding Fellowship Dashboard Block to Default Layout ===\n\n";

$systemcontext = context_system::instance();

// Check if block already exists on the DEFAULT my-index page (no subpage)
$existing = $DB->get_record('block_instances', [
    'blockname' => 'sceh_dashboard',
    'parentcontextid' => $systemcontext->id,
    'pagetypepattern' => 'my-index',
    'subpagepattern' => '',  // Empty = default layout for all users
]);

if ($existing) {
    echo "✓ Block already exists on default layout (ID: {$existing->id})\n";
} else {
    // Create block instance on default my-index page
    $blockinstance = new stdClass();
    $blockinstance->blockname = 'sceh_dashboard';
    $blockinstance->parentcontextid = $systemcontext->id;
    $blockinstance->showinsubcontexts = 0;
    $blockinstance->pagetypepattern = 'my-index';
    $blockinstance->subpagepattern = '';  // Empty = default layout
    $blockinstance->defaultregion = 'content';
    $blockinstance->defaultweight = -10;  // Negative weight = top of region
    $blockinstance->configdata = '';
    $blockinstance->timecreated = time();
    $blockinstance->timemodified = time();
    
    $blockid = $DB->insert_record('block_instances', $blockinstance);
    
    if ($blockid) {
        echo "✓ Block added to default layout (ID: {$blockid})\n";
        echo "  All users will see this block on their dashboard\n";
    } else {
        echo "✗ Failed to add block\n";
        exit(1);
    }
}

echo "\n=== Success! ===\n";
echo "The Fellowship Training Dashboard is now on the default layout.\n";
echo "All users will see it at: http://localhost:8080/my/\n\n";
