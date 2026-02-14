<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Learner stream-filtered progress view.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/completionlib.php');

use local_sceh_rules\output\sceh_card;

$courseid = optional_param('id', 0, PARAM_INT);

require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/sceh_rules/stream_progress.php', ['id' => $courseid]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('streamprogress', 'local_sceh_rules'));
$PAGE->set_heading(get_string('streamprogress', 'local_sceh_rules'));
$PAGE->requires->css(new moodle_url('/local/sceh_rules/styles/sceh_card_system.css'));

$usercourses = enrol_get_users_courses($USER->id, true, 'id,fullname');

if (empty($usercourses)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('streamprogress_no_courses', 'local_sceh_rules'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$course = null;
if ($courseid > 0) {
    if (!isset($usercourses[$courseid])) {
        throw new moodle_exception('invalidcourseid');
    }
    $course = $usercourses[$courseid];
} else {
    $course = reset($usercourses);
}

$courseselector = [];
foreach ($usercourses as $ucourse) {
    $courseselector[$ucourse->id] = format_string($ucourse->fullname);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('streamprogress_course', 'local_sceh_rules', format_string($course->fullname)), 3);
echo html_writer::select(
    $courseselector,
    'courseid',
    $course->id,
    false,
    [
        'onchange' => "window.location.href='" . (new moodle_url('/local/sceh_rules/stream_progress.php'))->out(false) . "?id='+this.value",
    ]
);

$streamname = \local_sceh_rules\helper\stream_helper::get_user_selected_stream($course->id, $USER->id);
if ($streamname) {
    echo $OUTPUT->notification(
        get_string('streamprogress_selected_stream', 'local_sceh_rules', format_string($streamname)),
        'success'
    );
} else {
    echo $OUTPUT->notification(get_string('streamprogress_no_stream', 'local_sceh_rules'), 'warning');
}

$relevantsections = \local_sceh_rules\helper\stream_helper::get_relevant_section_numbers_for_user($course->id, $USER->id);
$modinfo = get_fast_modinfo($course->id, $USER->id);
$completion = new completion_info($course);

echo html_writer::start_div('sceh-rules-grid');
foreach ($relevantsections as $sectionnumber) {
    $sectioninfo = $modinfo->get_section_info($sectionnumber);
    if (!$sectioninfo) {
        continue;
    }

    $sectiontitle = trim((string)$sectioninfo->name);
    if ($sectiontitle === '') {
        $sectiontitle = 'Section ' . $sectionnumber;
    }
    $cmids = array_filter(explode(',', (string)$sectioninfo->sequence));
    if (empty($cmids)) {
        echo sceh_card::detail([
            'size' => 'medium',
            'status' => 'info',
            'status_text' => get_string('streamprogress_no_activities', 'local_sceh_rules'),
            'icon' => 'fa-list-check',
            'title' => format_string($sectiontitle),
            'sections' => [[
                'title' => get_string('streamprogress_activity', 'local_sceh_rules'),
                'content' => get_string('streamprogress_no_activities', 'local_sceh_rules'),
            ]],
        ]);
        continue;
    }

    $items = [];
    $completecount = 0;
    $trackedcount = 0;

    foreach ($cmids as $cmid) {
        if (empty($modinfo->cms[$cmid])) {
            continue;
        }

        $cm = $modinfo->cms[$cmid];
        if (!$cm->uservisible) {
            continue;
        }

        $status = get_string('streamprogress_status_nottracked', 'local_sceh_rules');
        if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
            $trackedcount++;
            $data = $completion->get_data($cm, true, $USER->id);
            $iscomplete = in_array((int)$data->completionstate, [
                COMPLETION_COMPLETE,
                COMPLETION_COMPLETE_PASS,
            ], true);
            if ($iscomplete) {
                $completecount++;
            }

            $status = $iscomplete
                ? get_string('streamprogress_status_complete', 'local_sceh_rules')
                : get_string('streamprogress_status_incomplete', 'local_sceh_rules');
        }

        $items[] = [
            'icon' => 'fa-circle-play',
            'text' => format_string($cm->name),
            'subtext' => get_string('streamprogress_type', 'local_sceh_rules') . ': ' .
                format_string($cm->modname) . ' | ' .
                get_string('streamprogress_status', 'local_sceh_rules') . ': ' . $status,
        ];
    }

    if (empty($items)) {
        echo sceh_card::detail([
            'size' => 'medium',
            'status' => 'info',
            'status_text' => get_string('streamprogress_no_activities', 'local_sceh_rules'),
            'icon' => 'fa-list-check',
            'title' => format_string($sectiontitle),
            'sections' => [[
                'title' => get_string('streamprogress_activity', 'local_sceh_rules'),
                'content' => get_string('streamprogress_no_activities', 'local_sceh_rules'),
            ]],
        ]);
        continue;
    }

    $sectionstatus = 'info';
    $sectionstatustext = get_string('streamprogress_status_nottracked', 'local_sceh_rules');
    if ($trackedcount > 0) {
        if ($completecount === $trackedcount) {
            $sectionstatus = 'success';
            $sectionstatustext = get_string('streamprogress_status_complete', 'local_sceh_rules');
        } else {
            $sectionstatus = 'warning';
            $sectionstatustext = get_string('streamprogress_status_incomplete', 'local_sceh_rules');
        }
    }

    echo sceh_card::list([
        'size' => 'large',
        'status' => $sectionstatus,
        'status_text' => $sectionstatustext,
        'icon' => 'fa-list-check',
        'title' => format_string($sectiontitle),
        'count' => count($items),
        'items' => $items,
    ]);
}
echo html_writer::end_div();

echo $OUTPUT->footer();
