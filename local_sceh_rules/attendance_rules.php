<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Attendance rules management interface
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_sceh_rules');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
require_capability('local/sceh_rules:managerules', $context);

$PAGE->set_url('/local/sceh_rules/attendance_rules.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('attendance_rules', 'local_sceh_rules'));
$PAGE->set_heading(get_string('attendance_rules', 'local_sceh_rules'));

// Handle actions
if ($action === 'delete' && $id && confirm_sesskey()) {
    $DB->delete_records('local_sceh_attendance_rules', ['id' => $id]);
    redirect($PAGE->url, get_string('attendance_rule_deleted', 'local_sceh_rules'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('attendance_rules', 'local_sceh_rules'));

// Add new rule button
$addurl = new moodle_url('/local/sceh_rules/edit_attendance_rule.php');
echo $OUTPUT->single_button($addurl, get_string('attendance_rule_add', 'local_sceh_rules'), 'get');

// Display existing rules
$rules = $DB->get_records('local_sceh_attendance_rules', null, 'timecreated DESC');

if (empty($rules)) {
    echo html_writer::tag('p', get_string('norulesfound', 'local_sceh_rules'));
} else {
    $table = new html_table();
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
        
        $editurl = new moodle_url('/local/sceh_rules/edit_attendance_rule.php', ['id' => $rule->id]);
        $deleteurl = new moodle_url('/local/sceh_rules/attendance_rules.php', [
            'action' => 'delete',
            'id' => $rule->id,
            'sesskey' => sesskey()
        ]);
        
        $actions = html_writer::link($editurl, get_string('edit')) . ' | ' .
                   html_writer::link($deleteurl, get_string('delete'), [
                       'onclick' => 'return confirm("' . get_string('confirmdeletion', 'core') . '");'
                   ]);
        
        $table->data[] = [
            $competency ? $competency->shortname : 'N/A',
            $course ? $course->fullname : 'N/A',
            $rule->threshold . '%',
            $rule->enabled ? get_string('yes') : get_string('no'),
            $actions
        ];
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
