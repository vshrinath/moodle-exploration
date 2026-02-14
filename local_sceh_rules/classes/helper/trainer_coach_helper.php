<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Helper methods for Trainer Coach cohort detection.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Cohort-based trainer coach checks.
 */
class trainer_coach_helper {
    /** @var string Expected trainer coach cohort idnumber. */
    public const TRAINER_COACH_COHORT_IDNUMBER = 'trainer-coaches';

    /**
     * Determine whether a user belongs to the trainer-coaches cohort.
     *
     * @param int $userid Moodle user id
     * @return bool
     */
    public static function is_trainer_coach(int $userid): bool {
        global $DB;

        $sql = "SELECT 1
                  FROM {cohort_members} cm
                  JOIN {cohort} c ON c.id = cm.cohortid
                 WHERE cm.userid = :userid
                   AND (c.idnumber = :idnumber OR " . $DB->sql_compare_text('c.name') . " = :name)";

        return $DB->record_exists_sql($sql, [
            'userid' => $userid,
            'idnumber' => self::TRAINER_COACH_COHORT_IDNUMBER,
            'name' => 'Trainer Coaches',
        ]);
    }
}

