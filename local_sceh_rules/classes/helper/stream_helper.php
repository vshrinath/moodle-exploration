<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Helper class for stream section discovery.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves course stream sections based on section naming convention.
 */
class stream_helper {
    /** @var string */
    const STREAM_PREFIX = 'STREAM - ';

    /**
     * Get stream sections for a course using "STREAM - " section-name prefix.
     *
     * @param int $courseid Moodle course id
     * @return array
     */
    public static function get_course_stream_sections($courseid) {
        global $DB;

        $sql = "SELECT id, section, name, visible
                  FROM {course_sections}
                 WHERE course = :courseid
                   AND section > 0
                   AND name LIKE :streamprefix
              ORDER BY section ASC";

        $sections = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'streamprefix' => self::STREAM_PREFIX . '%',
        ]);

        foreach ($sections as $section) {
            $section->streamname = self::normalise_stream_name($section->name);
        }

        return $sections;
    }

    /**
     * Strip standard stream prefix from section name.
     *
     * @param string $sectionname
     * @return string
     */
    public static function normalise_stream_name($sectionname) {
        $name = trim((string)$sectionname);
        if (stripos($name, self::STREAM_PREFIX) === 0) {
            return trim(substr($name, strlen(self::STREAM_PREFIX)));
        }

        return $name;
    }

    /**
     * Resolve learner's selected stream name from a course Choice activity.
     *
     * Uses first Choice whose name includes "stream" or "specialization".
     *
     * @param int $courseid Moodle course id
     * @param int $userid Moodle user id
     * @return string|null
     */
    public static function get_user_selected_stream($courseid, $userid) {
        global $DB;

        $sql = "SELECT id, name
                  FROM {choice}
                 WHERE course = :courseid
                   AND (LOWER(name) LIKE :streamname OR LOWER(name) LIKE :specializationname)
              ORDER BY id ASC";

        $choices = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'streamname' => '%stream%',
            'specializationname' => '%specialization%',
        ], 0, 1);

        if (empty($choices)) {
            return null;
        }

        $choice = reset($choices);

        $answer = $DB->get_record('choice_answers', [
            'choiceid' => $choice->id,
            'userid' => $userid,
        ], 'id, optionid', IGNORE_MISSING);

        if (!$answer) {
            return null;
        }

        $option = $DB->get_record('choice_options', [
            'id' => $answer->optionid,
        ], 'id, text', IGNORE_MISSING);

        if (!$option || trim((string)$option->text) === '') {
            return null;
        }

        return trim((string)$option->text);
    }

    /**
     * Find stream section number for a stream name in a course.
     *
     * @param int $courseid Moodle course id
     * @param string $streamname
     * @return int
     */
    public static function get_section_number_for_stream($courseid, $streamname) {
        $target = \core_text::strtolower(trim((string)$streamname));
        if ($target === '') {
            return 0;
        }

        $sections = self::get_course_stream_sections($courseid);
        foreach ($sections as $section) {
            $current = \core_text::strtolower(trim((string)$section->streamname));
            if ($current === $target) {
                return (int)$section->section;
            }
        }

        return 0;
    }
}
