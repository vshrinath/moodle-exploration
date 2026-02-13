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

use local_sceh_rules\helper\rules_table_renderer;

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
echo rules_table_renderer::render_attendance_rules_table($rules, 'edit_attendance_rule.php', 'attendance_rules.php');

echo $OUTPUT->footer();
