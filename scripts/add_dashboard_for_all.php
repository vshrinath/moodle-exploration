<?php
/**
 * Add dashboard block to site index (homepage) for all users
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');

echo "\n=== Adding Dashboard Block to Homepage ===\n\n";

// Check if block already exists on site-index
$existing = $DB->get_record('block_instances', [
    'blockname' => 'sceh_dashboard',
    'pagetypepattern' => 'site-index'
]);

if ($existing) {
    echo "✓ Block already on homepage (ID: {$existing->id})\n";
} else {
    // Create block instance for site homepage
    $blockinstance = new stdClass();
    $blockinstance->blockname = 'sceh_dashboard';
    $blockinstance->parentcontextid = context_system::instance()->id;
    $blockinstance->showinsubcontexts = 0;
    $blockinstance->pagetypepattern = 'site-index';
    $blockinstance->subpagepattern = '';
    $blockinstance->defaultregion = 'content';
    $blockinstance->defaultweight = -10; // Show at top
    $blockinstance->configdata = '';
    $blockinstance->timecreated = time();
    $blockinstance->timemodified = time();
    
    $blockid = $DB->insert_record('block_instances', $blockinstance);
    
    if ($blockid) {
        echo "✓ Block added to homepage (ID: {$blockid})\n";
        
        // Add to block positions
        $position = new stdClass();
        $position->blockinstanceid = $blockid;
        $position->contextid = context_system::instance()->id;
        $position->pagetype = 'site-index';
        $position->subpage = '';
        $position->visible = 1;
        $position->region = 'content';
        $position->weight = -10;
        
        $DB->insert_record('block_positions', $position);
        echo "✓ Block positioned on homepage\n";
    }
}

// Also ensure it's on the default my-index page (no subpage)
$existing_my = $DB->get_record('block_instances', [
    'blockname' => 'sceh_dashboard',
    'pagetypepattern' => 'my-index',
    'subpagepattern' => ''
]);

if (!$existing_my) {
    $blockinstance = new stdClass();
    $blockinstance->blockname = 'sceh_dashboard';
    $blockinstance->parentcontextid = context_system::instance()->id;
    $blockinstance->showinsubcontexts = 0;
    $blockinstance->pagetypepattern = 'my-index';
    $blockinstance->subpagepattern = '';
    $blockinstance->defaultregion = 'content';
    $blockinstance->defaultweight = -10;
    $blockinstance->configdata = '';
    $blockinstance->timecreated = time();
    $blockinstance->timemodified = time();
    
    $blockid = $DB->insert_record('block_instances', $blockinstance);
    echo "✓ Block added to default Dashboard (ID: {$blockid})\n";
}

echo "\n=== Complete ===\n";
echo "The Fellowship Dashboard should now appear on:\n";
echo "- Homepage (http://localhost:8080)\n";
echo "- Dashboard (http://localhost:8080/my/)\n\n";
echo "Clear cache and refresh your browser!\n\n";
