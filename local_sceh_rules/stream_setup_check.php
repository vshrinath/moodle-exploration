<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Program Owner stream setup readiness checklist.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = optional_param('id', 0, PARAM_INT);

require_login();

$systemcontext = context_system::instance();
if (!has_any_capability([
    'local/sceh_rules:programowner',
    'local/sceh_rules:systemadmin',
    'moodle/site:config',
], $systemcontext)) {
    throw new required_capability_exception($systemcontext, 'local/sceh_rules:programowner', 'nopermissions', '');
}

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/sceh_rules/stream_setup_check.php', ['id' => $courseid]);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('streamsetupcheck', 'local_sceh_rules'));
$PAGE->set_heading(get_string('streamsetupcheck', 'local_sceh_rules'));

// Resolve program-owner-accessible courses.
if (has_capability('moodle/site:config', $systemcontext)) {
    $sql = "SELECT id, fullname
              FROM {course}
             WHERE id > :sitecourseid
          ORDER BY fullname";
    $courses = $DB->get_records_sql($sql, ['sitecourseid' => 1]);
} else {
    $sql = "SELECT DISTINCT c.id, c.fullname
              FROM {course} c
              JOIN {course_categories} cc ON cc.id = c.category
              JOIN {context} ctx ON ctx.instanceid = cc.id AND ctx.contextlevel = :contextlevel
              JOIN {role_assignments} ra ON ra.contextid = ctx.id
              JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = :userid
               AND r.shortname IN (:shortname, :fallbackshortname)
          ORDER BY c.fullname";
    $courses = $DB->get_records_sql($sql, [
        'contextlevel' => CONTEXT_COURSECAT,
        'userid' => $USER->id,
        'shortname' => 'sceh_program_owner',
        'fallbackshortname' => 'programowner',
    ]);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('streamsetupcheck_heading', 'local_sceh_rules'), 3);

if (empty($courses)) {
    echo $OUTPUT->notification(get_string('streamsetupcheck_no_courses', 'local_sceh_rules'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

if ($courseid > 0 && !isset($courses[$courseid])) {
    throw new moodle_exception('invalidcourseid');
}

$course = ($courseid > 0) ? $courses[$courseid] : reset($courses);
$selectedcourseid = (int)$course->id;

$courseselector = [];
foreach ($courses as $acourse) {
    $courseselector[$acourse->id] = format_string($acourse->fullname);
}

echo $OUTPUT->heading(get_string('streamsetupcheck_course', 'local_sceh_rules', format_string($course->fullname)), 4);
echo html_writer::select(
    $courseselector,
    'courseid',
    $selectedcourseid,
    false,
    [
        'onchange' => "window.location.href='" . (new moodle_url('/local/sceh_rules/stream_setup_check.php'))->out(false) . "?id='+this.value",
    ]
);

$rows = [];

// Check 1: Common Foundation section.
$hascommon = \local_sceh_rules\helper\stream_helper::has_named_common_foundation_section($selectedcourseid);
$rows[] = [
    get_string('streamsetupcheck_common', 'local_sceh_rules'),
    $hascommon ? get_string('streamsetupcheck_pass', 'local_sceh_rules') : get_string('streamsetupcheck_fail', 'local_sceh_rules'),
    $hascommon
        ? get_string('streamsetupcheck_detail_common_pass', 'local_sceh_rules')
        : get_string('streamsetupcheck_detail_common_fail', 'local_sceh_rules'),
];

// Check 2: Stream sections.
$streamsections = \local_sceh_rules\helper\stream_helper::get_course_stream_sections($selectedcourseid);
$hasstreams = !empty($streamsections);
$rows[] = [
    get_string('streamsetupcheck_streamsections', 'local_sceh_rules'),
    $hasstreams ? get_string('streamsetupcheck_pass', 'local_sceh_rules') : get_string('streamsetupcheck_fail', 'local_sceh_rules'),
    $hasstreams
        ? get_string('streamsetupcheck_detail_stream_pass', 'local_sceh_rules', count($streamsections))
        : get_string('streamsetupcheck_detail_stream_fail', 'local_sceh_rules'),
];

// Check 3: Stream choice with options.
$sql = "SELECT c.id, c.name, COUNT(co.id) AS optioncount
          FROM {choice} c
     LEFT JOIN {choice_options} co ON co.choiceid = c.id
         WHERE c.course = :courseid
           AND (LOWER(c.name) LIKE :streamname OR LOWER(c.name) LIKE :specializationname)
      GROUP BY c.id, c.name
      ORDER BY c.id ASC";
$choices = $DB->get_records_sql($sql, [
    'courseid' => $selectedcourseid,
    'streamname' => '%stream%',
    'specializationname' => '%specialization%',
]);

$validchoice = null;
foreach ($choices as $choice) {
    if ((int)$choice->optioncount > 0) {
        $validchoice = $choice;
        break;
    }
}

$haschoice = !empty($validchoice);
$rows[] = [
    get_string('streamsetupcheck_choice', 'local_sceh_rules'),
    $haschoice ? get_string('streamsetupcheck_pass', 'local_sceh_rules') : get_string('streamsetupcheck_fail', 'local_sceh_rules'),
    $haschoice
        ? get_string('streamsetupcheck_detail_choice_pass', 'local_sceh_rules', [
            'name' => format_string($validchoice->name),
            'count' => (int)$validchoice->optioncount,
        ])
        : get_string('streamsetupcheck_detail_choice_fail', 'local_sceh_rules'),
];

$table = new html_table();
$table->head = [
    get_string('streamsetupcheck_item', 'local_sceh_rules'),
    get_string('streamsetupcheck_result', 'local_sceh_rules'),
    get_string('streamsetupcheck_details', 'local_sceh_rules'),
];
$table->data = $rows;

echo html_writer::table($table);
echo $OUTPUT->footer();

