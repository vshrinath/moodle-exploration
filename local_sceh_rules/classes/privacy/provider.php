<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Privacy API implementation for local_sceh_rules.
 *
 * Declares stored user data and implements export/delete for GDPR compliance.
 * Only the audit table stores direct PII (userid). Rules tables reference
 * competencies and courses, not users.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_sceh_rules.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the types of data stored by this plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_sceh_rules_audit',
            [
                'userid' => 'privacy:metadata:audit:userid',
                'ruletype' => 'privacy:metadata:audit:ruletype',
                'ruleid' => 'privacy:metadata:audit:ruleid',
                'action' => 'privacy:metadata:audit:action',
                'details' => 'privacy:metadata:audit:details',
                'timecreated' => 'privacy:metadata:audit:timecreated',
            ],
            'privacy:metadata:audit'
        );

        // Rules tables store competency/course IDs, not user IDs.
        // Declared for transparency but no user data to export/delete.
        $collection->add_database_table(
            'local_sceh_attendance_rules',
            [
                'competencyid' => 'privacy:metadata:attendance_rules:competencyid',
                'courseid' => 'privacy:metadata:attendance_rules:courseid',
                'threshold' => 'privacy:metadata:attendance_rules:threshold',
            ],
            'privacy:metadata:attendance_rules'
        );

        $collection->add_database_table(
            'local_sceh_roster_rules',
            [
                'rostertype' => 'privacy:metadata:roster_rules:rostertype',
                'competencyid' => 'privacy:metadata:roster_rules:competencyid',
            ],
            'privacy:metadata:roster_rules'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user data.
     *
     * Audit data is always in the system context.
     *
     * @param int $userid The user ID.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_sceh_rules_audit} a ON a.userid = :userid
                 WHERE ctx.contextlevel = :contextlevel
                   AND ctx.instanceid = 0";

        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'contextlevel' => CONTEXT_SYSTEM,
        ]);

        return $contextlist;
    }

    /**
     * Export all user data for the specified approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            $records = $DB->get_records('local_sceh_rules_audit', [
                'userid' => $userid,
            ], 'timecreated ASC');

            if (empty($records)) {
                continue;
            }

            $exportdata = [];
            foreach ($records as $record) {
                $exportdata[] = [
                    'ruletype' => $record->ruletype,
                    'ruleid' => $record->ruleid,
                    'action' => $record->action,
                    'details' => $record->details,
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_sceh_rules'), 'audit_trail'],
                (object) ['records' => $exportdata]
            );
        }
    }

    /**
     * Delete all user data in the specified context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('local_sceh_rules_audit');
    }

    /**
     * Delete user data for the specified user and approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts for the user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }

            $DB->delete_records('local_sceh_rules_audit', ['userid' => $userid]);
        }
    }
}
