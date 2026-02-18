<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Program Owner role dependency observer.
 *
 * Enforces role dependency:
 * - Assigning `sceh_program_owner` auto-assigns `sceh_program_owner_competency` at system context.
 * - Unassigning `sceh_program_owner` auto-removes managed competency assignment
 *   if the user no longer has any Program Owner assignments.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\observer;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer for Program Owner role dependency sync.
 */
class program_owner_role_observer {
    /** @var string Role shortname for program owners. */
    private const PROGRAM_OWNER_ROLE = 'sceh_program_owner';
    /** @var string Role shortname for competency manager dependency. */
    private const PROGRAM_OWNER_COMP_ROLE = 'sceh_program_owner_competency';
    /** @var string Component tag used for managed assignments. */
    private const MANAGED_COMPONENT = 'local_sceh_rules';

    /**
     * Handle Program Owner role assignment events.
     *
     * @param \core\event\role_assigned $event
     * @return void
     */
    public static function role_assigned(\core\event\role_assigned $event): void {
        global $DB;

        $programownerroleid = self::get_role_id(self::PROGRAM_OWNER_ROLE);
        if ((int)$event->objectid !== $programownerroleid) {
            return;
        }

        $competencyroleid = self::get_role_id(self::PROGRAM_OWNER_COMP_ROLE);
        $userid = (int)$event->relateduserid;
        $sysctx = \context_system::instance();

        if (user_has_role_assignment($userid, $competencyroleid, $sysctx->id)) {
            return;
        }

        role_assign($competencyroleid, $userid, $sysctx->id, self::MANAGED_COMPONENT, 0);
    }

    /**
     * Handle Program Owner role unassignment events.
     *
     * @param \core\event\role_unassigned $event
     * @return void
     */
    public static function role_unassigned(\core\event\role_unassigned $event): void {
        global $DB;

        $programownerroleid = self::get_role_id(self::PROGRAM_OWNER_ROLE);
        if ((int)$event->objectid !== $programownerroleid) {
            return;
        }

        $userid = (int)$event->relateduserid;
        if (self::user_has_any_program_owner_assignment($userid, $programownerroleid)) {
            return;
        }

        $competencyroleid = self::get_role_id(self::PROGRAM_OWNER_COMP_ROLE);
        $sysctx = \context_system::instance();

        // Remove only the assignment managed by this plugin.
        role_unassign($competencyroleid, $userid, $sysctx->id, self::MANAGED_COMPONENT, 0);
    }

    /**
     * Resolve a role ID from shortname.
     *
     * @param string $shortname
     * @return int
     * @throws \coding_exception
     */
    private static function get_role_id(string $shortname): int {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => $shortname], 'id,shortname', IGNORE_MISSING);
        if (!$role) {
            throw new \coding_exception("Required role missing: {$shortname}");
        }
        return (int)$role->id;
    }

    /**
     * Check whether user still has any Program Owner assignment in any context.
     *
     * @param int $userid
     * @param int $programownerroleid
     * @return bool
     */
    private static function user_has_any_program_owner_assignment(int $userid, int $programownerroleid): bool {
        global $DB;
        return $DB->record_exists('role_assignments', [
            'userid' => $userid,
            'roleid' => $programownerroleid,
        ]);
    }
}

