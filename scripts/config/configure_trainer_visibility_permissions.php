<?php
/**
 * Configure Trainer Visibility Permissions
 *
 * Grants sceh_trainer role the minimum capabilities needed to show/hide
 * Module Content folders and activities without full editing permissions.
 *
 * Capabilities granted:
 * - moodle/course:activityvisibility - Show/hide activities (eye icon)
 * - moodle/course:manageactivities - Required for visibility toggle to work
 *
 * Usage:
 *   php scripts/config/configure_trainer_visibility_permissions.php [--dry-run]
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->libdir . '/accesslib.php');

global $DB;

init_cli_admin('moodle/site:config');

$dryrun = in_array('--dry-run', $argv);

echo "=== Configure Trainer Visibility Permissions ===\n";
echo "MODE\t" . ($dryrun ? 'DRY-RUN' : 'APPLY') . "\n\n";

// Get sceh_trainer role
$trainerrole = $DB->get_record('role', ['shortname' => 'sceh_trainer'], '*', IGNORE_MISSING);
if (!$trainerrole) {
    fwrite(STDERR, "ERROR: sceh_trainer role not found. Run role setup script first.\n");
    exit(1);
}

echo "TRAINER_ROLE\tID={$trainerrole->id}\tNAME={$trainerrole->name}\n\n";

// Get system context
$sysctx = context_system::instance();

// Capabilities to grant
$capabilities = [
    'moodle/course:activityvisibility' => 'Show/hide activities (eye icon)',
    'moodle/course:manageactivities' => 'Required for visibility toggle',
];

echo "CAPABILITIES TO GRANT:\n";
foreach ($capabilities as $cap => $description) {
    echo "  - {$cap}\n";
    echo "    {$description}\n";
}
echo "\n";

// Check current state
echo "CURRENT STATE:\n";
foreach ($capabilities as $cap => $description) {
    $current = $DB->get_record('role_capabilities', [
        'roleid' => $trainerrole->id,
        'contextid' => $sysctx->id,
        'capability' => $cap,
    ]);
    
    if ($current) {
        $permission = $current->permission == CAP_ALLOW ? 'ALLOW' : 
                     ($current->permission == CAP_PREVENT ? 'PREVENT' : 'INHERIT');
        echo "  {$cap}: {$permission}\n";
    } else {
        echo "  {$cap}: NOT_SET\n";
    }
}
echo "\n";

// Apply changes
if (!$dryrun) {
    echo "APPLYING CHANGES:\n";
    
    foreach ($capabilities as $cap => $description) {
        try {
            assign_capability($cap, CAP_ALLOW, $trainerrole->id, $sysctx->id, true);
            echo "  ✓ {$cap} = ALLOW\n";
        } catch (Exception $e) {
            fwrite(STDERR, "  ✗ {$cap} FAILED: {$e->getMessage()}\n");
        }
    }
    
    echo "\n";
    
    // Verify changes
    echo "VERIFICATION:\n";
    foreach ($capabilities as $cap => $description) {
        $updated = $DB->get_record('role_capabilities', [
            'roleid' => $trainerrole->id,
            'contextid' => $sysctx->id,
            'capability' => $cap,
        ]);
        
        if ($updated && $updated->permission == CAP_ALLOW) {
            echo "  ✓ {$cap} = ALLOW\n";
        } else {
            echo "  ✗ {$cap} = NOT APPLIED\n";
        }
    }
    
    echo "\n";
    echo "DONE\tTrainer visibility permissions configured.\n";
    echo "NOTE\tTrainers can now show/hide Module Content folders using the eye icon.\n";
    echo "NOTE\tTrainers CANNOT add, delete, or edit activities - only control visibility.\n";
} else {
    echo "DRY-RUN\tNo changes applied. Remove --dry-run to apply.\n";
}
