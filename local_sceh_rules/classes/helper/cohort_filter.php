<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Helper class for trainer cohort-based course filtering.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves trainer-visible courses from assigned cohorts.
 */
class cohort_filter {
    /**
     * Get courses that should be visible to a trainer via cohort assignments.
     *
     * @param int $userid Moodle user id
     * @return array Array of course records keyed by id
     */
    public static function get_trainer_courses($userid) {
        global $DB;

        $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname, c.category
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {cohort} co ON co.id = e.customint1
                  JOIN {cohort_members} cm ON cm.cohortid = co.id
                 WHERE e.enrol = :enroltype
                   AND cm.userid = :userid
              ORDER BY c.fullname";

        return $DB->get_records_sql($sql, [
            'enroltype' => 'cohort',
            'userid' => $userid,
        ]);
    }
}

