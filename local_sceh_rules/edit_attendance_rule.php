<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Edit attendance rule
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_sceh_rules');

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
require_capability('local/sceh_rules:managerules', $context);

$PAGE->set_url('/local/sceh_rules/edit_attendance_rule.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('attendance_rule_add', 'local_sceh_rules'));
$PAGE->set_heading(get_string('attendance_rule_add', 'local_sceh_rules'));

$form = new \local_sceh_rules\form\attendance_rule_form();

if ($id) {
    $rule = $DB->get_record('local_sceh_attendance_rules', ['id' => $id], '*', MUST_EXIST);
    $form->set_data($rule);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/sceh_rules/attendance_rules.php'));
} else if ($data = $form->get_data()) {
    $data->timemodified = time();
    
    if ($data->id) {
        // Update existing rule
        $DB->update_record('local_sceh_attendance_rules', $data);
    } else {
        // Create new rule
        $data->timecreated = time();
        $DB->insert_record('local_sceh_attendance_rules', $data);
    }
    
    redirect(
        new moodle_url('/local/sceh_rules/attendance_rules.php'),
        get_string('attendance_rule_saved', 'local_sceh_rules')
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('attendance_rule_add', 'local_sceh_rules'));

$form->display();

echo $OUTPUT->footer();
