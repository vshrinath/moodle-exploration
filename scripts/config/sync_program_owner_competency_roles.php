<?php
/**
 * Backfill Program Owner competency role dependency.
 *
 * Ensures every user with `sceh_program_owner` has `sceh_program_owner_competency`
 * at system context, and removes stale managed competency assignments when the
 * user no longer has any Program Owner role assignments.
 *
 * Usage:
 *   php scripts/config/sync_program_owner_competency_roles.php [--dry-run]
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();
require_once($CFG->libdir . '/accesslib.php');

global $DB;
init_cli_admin('moodle/site:config');

$dryrun = in_array('--dry-run', $argv, true);

echo "=== Sync Program Owner Competency Role Dependency ===\n";
echo "DRY_RUN\t" . ($dryrun ? 'true' : 'false') . "\n\n";

$programowner = $DB->get_record('role', ['shortname' => 'sceh_program_owner'], 'id,shortname', MUST_EXIST);
$competencyrole = $DB->get_record('role', ['shortname' => 'sceh_program_owner_competency'], 'id,shortname', MUST_EXIST);
$sysctx = context_system::instance();

// Users with at least one Program Owner assignment anywhere.
$ownerrows = $DB->get_records_sql(
    "SELECT DISTINCT userid
       FROM {role_assignments}
      WHERE roleid = :roleid",
    ['roleid' => $programowner->id]
);

$owneruserids = array_map(static function($row) {
    return (int)$row->userid;
}, array_values($ownerrows));

echo "PROGRAM_OWNERS_FOUND\t" . count($owneruserids) . "\n";

foreach ($owneruserids as $userid) {
    if (user_has_role_assignment($userid, $competencyrole->id, $sysctx->id)) {
        echo "OWNER_OK\tUSER={$userid}\n";
        continue;
    }

    if ($dryrun) {
        echo "DRYRUN_ASSIGN\tUSER={$userid}\tROLE={$competencyrole->shortname}\n";
    } else {
        role_assign($competencyrole->id, $userid, $sysctx->id, 'local_sceh_rules', 0);
        echo "ASSIGNED\tUSER={$userid}\tROLE={$competencyrole->shortname}\n";
    }
}

// Remove stale managed assignments (created by local_sceh_rules) where user is no longer Program Owner.
$managedassignments = $DB->get_records('role_assignments', [
    'roleid' => $competencyrole->id,
    'contextid' => $sysctx->id,
    'component' => 'local_sceh_rules',
    'itemid' => 0,
]);

$ownerlookup = array_fill_keys($owneruserids, true);
foreach ($managedassignments as $assignment) {
    $userid = (int)$assignment->userid;
    if (isset($ownerlookup[$userid])) {
        continue;
    }

    if ($dryrun) {
        echo "DRYRUN_UNASSIGN\tUSER={$userid}\tROLE={$competencyrole->shortname}\n";
    } else {
        role_unassign($competencyrole->id, $userid, $sysctx->id, 'local_sceh_rules', 0);
        echo "UNASSIGNED\tUSER={$userid}\tROLE={$competencyrole->shortname}\n";
    }
}

echo "\nDONE\tRole dependency sync completed.\n";

