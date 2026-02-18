<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Helper class for rendering rules management tables
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\helper;

use local_sceh_rules\output\sceh_card;

defined('MOODLE_INTERNAL') || die();

/**
 * Renders rules management tables with common functionality
 */
class rules_table_renderer {
    
    /**
     * Render attendance rules table
     *
     * @param array $rules Array of rule objects
     * @param string $edit_page Edit page URL (e.g., 'edit_attendance_rule.php')
     * @param string $list_page List page URL (e.g., 'attendance_rules.php')
     * @return string HTML table
     */
    public static function render_attendance_rules_table($rules, $edit_page, $list_page) {
        global $DB;
        
        if (empty($rules)) {
            return \html_writer::tag('p', get_string('norulesfound', 'local_sceh_rules'));
        }

        $html = \html_writer::start_div('sceh-rules-grid');
        foreach ($rules as $rule) {
            $competency = $DB->get_record('competency', ['id' => $rule->competencyid]);
            $course = $DB->get_record('course', ['id' => $rule->courseid]);

            $editurl = new \moodle_url('/local/sceh_rules/' . $edit_page, ['id' => $rule->id]);
            $deleteurl = new \moodle_url('/local/sceh_rules/' . $list_page, [
                'action' => 'delete',
                'id' => $rule->id,
            ]);

            $enabled = !empty($rule->enabled);
            $status = $enabled ? 'success' : 'warning';
            $statuslabel = $enabled ? get_string('enabled', 'local_sceh_rules') : get_string('disabled', 'local_sceh_rules');
            $competencyname = $competency ? format_string($competency->shortname) : get_string('notfound', 'core');
            $coursename = $course ? format_string($course->fullname) : get_string('notfound', 'core');

            $html .= sceh_card::detail([
                'size' => 'medium',
                'status' => $status,
                'status_text' => $statuslabel,
                'icon' => 'fa-calendar-check',
                'title' => $competencyname,
                'subtitle' => get_string('attendance_rule_competency', 'local_sceh_rules'),
                'badges' => [
                    [
                        'text' => $statuslabel,
                        'type' => $enabled ? 'success' : 'secondary',
                    ],
                ],
                'stats' => [
                    [
                        'value' => format_float($rule->threshold, 0) . '%',
                        'label' => get_string('attendance_rule_threshold', 'local_sceh_rules'),
                    ],
                ],
                'sections' => [
                    [
                        'title' => get_string('course'),
                        'content' => $coursename,
                    ],
                ],
                'actions' => [
                    [
                        'text' => get_string('edit'),
                        'url' => $editurl,
                        'style' => 'secondary',
                    ],
                    [
                        'text' => get_string('delete'),
                        'url' => $deleteurl,
                        'style' => 'danger',
                        'method' => 'post',
                        'confirm' => get_string('confirmruledeletion', 'local_sceh_rules'),
                    ],
                ],
            ]);
        }
        $html .= \html_writer::end_div();

        return $html;
    }
    
    /**
     * Render roster rules table
     *
     * @param array $rules Array of rule objects
     * @param string $edit_page Edit page URL
     * @param string $list_page List page URL
     * @return string HTML table
     */
    public static function render_roster_rules_table($rules, $edit_page, $list_page) {
        global $DB;
        
        if (empty($rules)) {
            return \html_writer::tag('p', get_string('norulesfound', 'local_sceh_rules'));
        }

        $html = \html_writer::start_div('sceh-rules-grid');
        foreach ($rules as $rule) {
            $competency = $DB->get_record('competency', ['id' => $rule->competencyid]);
            $editurl = new \moodle_url('/local/sceh_rules/' . $edit_page, ['id' => $rule->id]);
            $deleteurl = new \moodle_url('/local/sceh_rules/' . $list_page, [
                'action' => 'delete',
                'id' => $rule->id,
            ]);

            $enabled = !empty($rule->enabled);
            $status = $enabled ? 'success' : 'warning';
            $statuslabel = $enabled ? get_string('enabled', 'local_sceh_rules') : get_string('disabled', 'local_sceh_rules');
            $typekey = 'roster_type_' . $rule->rostertype;
            $rostertype = get_string_manager()->string_exists($typekey, 'local_sceh_rules')
                ? get_string($typekey, 'local_sceh_rules')
                : format_string($rule->rostertype);
            $competencyname = $competency ? format_string($competency->shortname) : get_string('notfound', 'core');

            $html .= sceh_card::detail([
                'size' => 'medium',
                'status' => $status,
                'status_text' => $statuslabel,
                'icon' => 'fa-users',
                'title' => $rostertype,
                'subtitle' => get_string('roster_rule_type', 'local_sceh_rules'),
                'badges' => [
                    [
                        'text' => $statuslabel,
                        'type' => $enabled ? 'success' : 'secondary',
                    ],
                ],
                'sections' => [
                    [
                        'title' => get_string('roster_rule_competency', 'local_sceh_rules'),
                        'content' => $competencyname,
                    ],
                    [
                        'title' => get_string('roster_rule_evidence', 'local_sceh_rules'),
                        'content' => format_text($rule->evidencedesc, FORMAT_PLAIN),
                    ],
                ],
                'actions' => [
                    [
                        'text' => get_string('edit'),
                        'url' => $editurl,
                        'style' => 'secondary',
                    ],
                    [
                        'text' => get_string('delete'),
                        'url' => $deleteurl,
                        'style' => 'danger',
                        'method' => 'post',
                        'confirm' => get_string('confirmruledeletion', 'local_sceh_rules'),
                    ],
                ],
            ]);
        }
        $html .= \html_writer::end_div();

        return $html;
    }
}
