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

use local_sceh_rules\output\sceh_card;

$courseid = optional_param('id', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$filterissues = optional_param('filter', '', PARAM_ALPHA) === 'issues';

require_login();

$systemcontext = context_system::instance();
$has_po_capability = has_any_capability([
    'local/sceh_rules:programowner',
    'local/sceh_rules:systemadmin',
    'moodle/site:config',
], $systemcontext);

if (!$has_po_capability) {
    // Fallback: check if user has a program owner role in any category.
    $sql = "SELECT DISTINCT ra.id
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
              JOIN {context} ctx ON ctx.id = ra.contextid
             WHERE ra.userid = :userid
               AND ctx.contextlevel = :contextlevel
               AND r.shortname IN (:short1, :short2)";
    $has_role = $DB->record_exists_sql($sql, [
        'userid' => $USER->id,
        'contextlevel' => CONTEXT_COURSECAT,
        'short1' => 'sceh_program_owner',
        'short2' => 'programowner',
    ]);
    if (!$has_role) {
        throw new required_capability_exception($systemcontext, 'local/sceh_rules:programowner', 'nopermissions', '');
    }
}

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/sceh_rules/stream_setup_check.php', [
    'id' => $courseid,
    'categoryid' => $categoryid,
    'filter' => $filterissues ? 'issues' : '',
]);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('streamsetupcheck', 'local_sceh_rules'));
$PAGE->set_heading(get_string('streamsetupcheck', 'local_sceh_rules'));
$PAGE->requires->css(new moodle_url('/local/sceh_rules/styles/sceh_card_system.css'));

// Resolve program-owner-accessible courses.
if (has_capability('moodle/site:config', $systemcontext)) {
    $sql = "SELECT id, fullname
              FROM {course}
             WHERE id > :sitecourseid
          ORDER BY fullname";
    $courses = $DB->get_records_sql($sql, ['sitecourseid' => 1]);
} else {
    $params = [
        'contextlevel' => CONTEXT_COURSECAT,
        'userid' => $USER->id,
        'shortname' => 'sceh_program_owner',
        'fallbackshortname' => 'programowner',
    ];
    $filtersql = "";
    if ($categoryid > 0) {
        $filtersql = " AND cc.id = :categoryid";
        $params['categoryid'] = $categoryid;
    }

    $sql = "SELECT DISTINCT c.id, c.fullname
              FROM {course} c
              JOIN {course_categories} cc ON cc.id = c.category
              JOIN {context} ctx ON ctx.instanceid = cc.id AND ctx.contextlevel = :contextlevel
              JOIN {role_assignments} ra ON ra.contextid = ctx.id
              JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = :userid
               AND r.shortname IN (:shortname, :fallbackshortname)
               {$filtersql}
          ORDER BY c.fullname";
    $courses = $DB->get_records_sql($sql, $params);
}

// If filter=issues is active, filter the course list to only those with setup problems.
if ($filterissues) {
    $issuecourses = [];
    foreach ($courses as $id => $acourse) {
        // Simple issue check (mirrors dashboard logic).
        $hascommon = \local_sceh_rules\helper\stream_helper::has_named_common_foundation_section($id);
        $streamsections = \local_sceh_rules\helper\stream_helper::get_course_stream_sections($id);
        
        // Check for stream choice with options.
        $haschoice = $DB->record_exists_sql(
            "SELECT c.id FROM {choice} c 
               JOIN {choice_options} co ON co.choiceid = c.id 
              WHERE c.course = :courseid 
                AND (LOWER(c.name) LIKE :streamname OR LOWER(c.name) LIKE :specializationname)",
            ['courseid' => $id, 'streamname' => '%stream%', 'specializationname' => '%specialization%']
        );

        if (!$hascommon || empty($streamsections) || !$haschoice) {
            $issuecourses[$id] = $acourse;
        }
    }
    $courses = $issuecourses;
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
        'onchange' => "window.location.href='" . (new moodle_url('/local/sceh_rules/stream_setup_check.php', [
            'categoryid' => $categoryid,
            'filter' => $filterissues ? 'issues' : '',
        ]))->out(false) . "&id='+this.value",
    ]
);

$checks = [];

// Check 1: Common Foundation section.
$hascommon = \local_sceh_rules\helper\stream_helper::has_named_common_foundation_section($selectedcourseid);
$checks[] = [
    'title' => get_string('streamsetupcheck_common', 'local_sceh_rules'),
    'pass' => $hascommon,
    'detail' => $hascommon
        ? get_string('streamsetupcheck_detail_common_pass', 'local_sceh_rules')
        : get_string('streamsetupcheck_detail_common_fail', 'local_sceh_rules'),
    'actions' => !$hascommon ? [[
        'text' => get_string('streamsetupcheck_action_fix', 'local_sceh_rules'),
        'url' => new moodle_url('/course/view.php', ['id' => $selectedcourseid, 'edit' => 1]),
        'style' => 'primary',
    ]] : [],
];

// Check 2: Stream sections.
$streamsections = \local_sceh_rules\helper\stream_helper::get_course_stream_sections($selectedcourseid);
$hasstreams = !empty($streamsections);
$checks[] = [
    'title' => get_string('streamsetupcheck_streamsections', 'local_sceh_rules'),
    'pass' => $hasstreams,
    'detail' => $hasstreams
        ? get_string('streamsetupcheck_detail_stream_pass', 'local_sceh_rules', count($streamsections))
        : get_string('streamsetupcheck_detail_stream_fail', 'local_sceh_rules'),
    'actions' => !$hasstreams ? [[
        'text' => get_string('streamsetupcheck_action_fix', 'local_sceh_rules'),
        'url' => new moodle_url('/course/view.php', ['id' => $selectedcourseid, 'edit' => 1]),
        'style' => 'primary',
    ]] : [],
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
$checks[] = [
    'title' => get_string('streamsetupcheck_choice', 'local_sceh_rules'),
    'pass' => $haschoice,
    'detail' => $haschoice
        ? get_string('streamsetupcheck_detail_choice_pass', 'local_sceh_rules', [
            'name' => format_string($validchoice->name),
            'count' => (int)$validchoice->optioncount,
        ])
        : get_string('streamsetupcheck_detail_choice_fail', 'local_sceh_rules'),
    'actions' => !$haschoice ? [[
        'text' => get_string('streamsetupcheck_action_add_choice', 'local_sceh_rules'),
        'url' => new moodle_url('/course/modedit.php', ['add' => 'choice', 'course' => $selectedcourseid, 'section' => 0]),
        'style' => 'primary',
    ]] : [],
];

echo html_writer::start_div('sceh-rules-grid');
foreach ($checks as $check) {
    $pass = !empty($check['pass']);
    $status = $pass ? 'success' : 'danger';
    $resulttext = $pass
        ? get_string('streamsetupcheck_pass', 'local_sceh_rules')
        : get_string('streamsetupcheck_fail', 'local_sceh_rules');

    echo sceh_card::detail([
        'size' => 'medium',
        'status' => $status,
        'status_text' => $resulttext,
        'icon' => 'fa-tasks',
        'title' => $check['title'],
        'badges' => [[
            'text' => $resulttext,
            'type' => $pass ? 'success' : 'danger',
        ]],
        'sections' => [[
            'title' => get_string('streamsetupcheck_details', 'local_sceh_rules'),
            'content' => $check['detail'],
        ]],
        'actions' => $check['actions'] ?? [],
    ]);
}
echo html_writer::end_div();

// Direct course edit button if any failures exist.
$allpass = true;
foreach ($checks as $check) {
    if (empty($check['pass'])) {
        $allpass = false;
        break;
    }
}

if (!$allpass) {
    echo html_writer::start_div('mt-4 text-center');
    echo html_writer::link(
        new moodle_url('/course/view.php', ['id' => $selectedcourseid, 'edit' => 1]),
        get_string('streamsetupcheck_edit_course', 'local_sceh_rules', format_string($course->fullname)),
        ['class' => 'btn btn-primary btn-lg']
    );
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
