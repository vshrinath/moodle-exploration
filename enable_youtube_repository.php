<?php
/**
 * Enable YouTube repository
 * Task 2.3: Install and configure video repository plugins
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');

echo "=== Enabling YouTube Repository ===\n\n";

try {
    // Check if YouTube repository is already enabled
    $youtube_repo = $DB->get_record('repository', ['type' => 'youtube']);
    
    if ($youtube_repo) {
        echo "✓ YouTube repository is already enabled (ID: {$youtube_repo->id})\n";
    } else {
        // Enable YouTube repository by inserting record
        $repo_data = new stdClass();
        $repo_data->type = 'youtube';
        $repo_data->visible = 1;
        $repo_data->sortorder = 10;
        
        $repo_id = $DB->insert_record('repository', $repo_data);
        
        if ($repo_id) {
            echo "✓ YouTube repository enabled successfully (ID: {$repo_id})\n";
        } else {
            echo "✗ Failed to enable YouTube repository\n";
            exit(1);
        }
    }
    
    // Check repository instances
    $instances = $DB->get_records('repository_instances', ['typeid' => $youtube_repo ? $youtube_repo->id : $repo_id]);
    
    if (empty($instances)) {
        echo "ℹ No YouTube repository instances found\n";
        echo "ℹ Instances can be created through the admin interface\n";
    } else {
        echo "✓ YouTube repository instances: " . count($instances) . "\n";
    }
    
    // Verify final status
    $final_check = $DB->get_record('repository', ['type' => 'youtube']);
    if ($final_check) {
        echo "\n✓ YouTube repository configuration COMPLETED\n";
        echo "✓ Repository ID: {$final_check->id}\n";
        echo "✓ Status: " . ($final_check->visible ? "Visible" : "Hidden") . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>