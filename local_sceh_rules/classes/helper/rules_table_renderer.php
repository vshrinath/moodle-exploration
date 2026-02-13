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
        global $DB, $OUTPUT;
        
        if (empty($rules)) {
            return \html_writer::tag('p', get_string('norulesfound', 'local_sceh_rules'));
        }
        
        $table = new \html_table();
        $table->head = [
            get_string('attendance_rule_competency', 'local_sceh_rules'),
            get_string('course'),
            get_string('attendance_rule_threshold', 'local_sceh_rules'),
            get_string('enabled', 'core'),
            get_string('actions'),
        ];
        
        foreach ($rules as $rule) {
            $competency = $DB->get_record('competency', ['id' => $rule->competencyid]);
            $course = $DB->get_record('course', ['id' => $rule->courseid]);
            
            $editurl = new \moodle_url('/local/sceh_rules/' . $edit_page, ['id' => $rule->id]);
            $deleteurl = new \moodle_url('/local/sceh_rules/' . $list_page, [
                'action' => 'delete',
                'id' => $rule->id,
                'sesskey' => sesskey()
            ]);
            
            $actions = \html_writer::link($editurl, get_string('edit')) . ' | ' .
                       \html_writer::link($deleteurl, get_string('delete'), [
                           'onclick' => 'return confirm("' . get_string('confirmdeletion', 'core') . '");'
                       ]);
            
            $table->data[] = [
                $competency ? format_string($competency->shortname) : get_string('notfound', 'core'),
                $course ? format_string($course->fullname) : get_string('notfound', 'core'),
                $rule->threshold . '%',
                $rule->enabled ? get_string('yes') : get_string('no'),
                $actions
            ];
        }
        
        return \html_writer::table($table);
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
        global $DB, $OUTPUT;
        
        if (empty($rules)) {
            return \html_writer::tag('p', get_string('norulesfound', 'local_sceh_rules'));
        }
        
        $table = new \html_table();
        $table->head = [
            get_string('roster_rule_name', 'local_sceh_rules'),
            get_string('course'),
            get_string('roster_rule_action', 'local_sceh_rules'),
            get_string('enabled', 'core'),
            get_string('actions'),
        ];
        
        foreach ($rules as $rule) {
            $course = $DB->get_record('course', ['id' => $rule->courseid]);
            
            $editurl = new \moodle_url('/local/sceh_rules/' . $edit_page, ['id' => $rule->id]);
            $deleteurl = new \moodle_url('/local/sceh_rules/' . $list_page, [
                'action' => 'delete',
                'id' => $rule->id,
                'sesskey' => sesskey()
            ]);
            
            $actions = \html_writer::link($editurl, get_string('edit')) . ' | ' .
                       \html_writer::link($deleteurl, get_string('delete'), [
                           'onclick' => 'return confirm("' . get_string('confirmdeletion', 'core') . '");'
                       ]);
            
            $table->data[] = [
                format_string($rule->name),
                $course ? format_string($course->fullname) : get_string('notfound', 'core'),
                format_string($rule->action),
                $rule->enabled ? get_string('yes') : get_string('no'),
                $actions
            ];
        }
        
        return \html_writer::table($table);
    }
}
