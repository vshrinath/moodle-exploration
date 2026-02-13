<?php
/**
 * Add SCEH Dashboard block to the user's dashboard
 */
define('CLI_SCRIPT', true);
require('/bitnami/moodle/config.php');
require_once($CFG->dirroot . '/my/lib.php');

echo "\n=== Adding Fellowship Dashboard Block ===\n\n";

// Get the default my page
$page = my_get_page(null, MY_PAGE_PRIVATE);

if (!$page) {
    echo "✗ Could not find user dashboard page\n";
    exit(1);
}

// Check if block already exists
$existing = $DB->get_record('block_instances', [
    'blockname' => 'sceh_dashboard',
    'parentcontextid' => context_system::instance()->id
]);

if ($existing) {
    echo "✓ Block already exists (ID: {$existing->id})\n";
} else {
    // Create block instance
    $blockinstance = new stdClass();
    $blockinstance->blockname = 'sceh_dashboard';
    $blockinstance->parentcontextid = context_system::instance()->id;
    $blockinstance->showinsubcontexts = 0;
    $blockinstance->pagetypepattern = 'my-index';
    $blockinstance->subpagepattern = $page->id;
    $blockinstance->defaultregion = 'content';
    $blockinstance->defaultweight = 0;
    $blockinstance->configdata = '';
    $blockinstance->timecreated = time();
    $blockinstance->timemodified = time();
    
    $blockid = $DB->insert_record('block_instances', $blockinstance);
    
    if ($blockid) {
        echo "✓ Block added successfully (ID: {$blockid})\n";
        
        // Add to block positions
        $position = new stdClass();
        $position->blockinstanceid = $blockid;
        $position->contextid = context_system::instance()->id;
        $position->pagetype = 'my-index';
        $position->subpage = $page->id;
        $position->visible = 1;
        $position->region = 'content';
        $position->weight = 0;
        
        $DB->insert_record('block_positions', $position);
        echo "✓ Block positioned on dashboard\n";
    } else {
        echo "✗ Failed to add block\n";
        exit(1);
    }
}

echo "\n=== Success! ===\n";
echo "Refresh your browser at: http://localhost:8080/my/\n";
echo "You should see the Fellowship Training Dashboard!\n\n";
