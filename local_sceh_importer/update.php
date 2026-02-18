<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SCEH package importer - course update page.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();

$systemcontext = context_system::instance();
require_capability('local/sceh_importer:manage', $systemcontext);

$courseid = optional_param('courseid', 0, PARAM_INT);

$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/local/sceh_importer/update.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('updatepage', 'local_sceh_importer'));
$PAGE->set_heading(get_string('updatepage', 'local_sceh_importer'));
$PAGE->requires->css(new moodle_url('/local/sceh_importer/styles.css'));

$courses = $DB->get_records_sql(
    'SELECT id, idnumber, shortname, fullname
       FROM {course}
      WHERE id > :sitecourseid
   ORDER BY fullname',
    ['sitecourseid' => 1]
);

if (empty($courseid) || !isset($courses[$courseid])) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('updatepage', 'local_sceh_importer'));
    
    echo html_writer::tag('p', get_string('updateintro', 'local_sceh_importer'));
    
    echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/sceh_importer/update.php')]);
    echo html_writer::start_tag('div', ['class' => 'form-group']);
    echo html_writer::tag('label', get_string('selectcourse', 'local_sceh_importer'), ['for' => 'courseid']);
    echo html_writer::start_tag('select', ['name' => 'courseid', 'id' => 'courseid', 'class' => 'form-control']);
    echo html_writer::tag('option', get_string('choosedots'), ['value' => '']);
    foreach ($courses as $course) {
        echo html_writer::tag('option', format_string($course->fullname), ['value' => $course->id]);
    }
    echo html_writer::end_tag('select');
    echo html_writer::end_tag('div');
    echo html_writer::tag('button', get_string('continue'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::end_tag('form');
    
    echo $OUTPUT->footer();
    exit;
}

$course = $courses[$courseid];
$coursecontext = context_course::instance($courseid);

// Check course-level permissions
require_capability('moodle/course:update', $coursecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('updatepage', 'local_sceh_importer'));
echo html_writer::tag('p', get_string('updatefor', 'local_sceh_importer', format_string($course->fullname)), ['class' => 'lead']);

echo html_writer::start_tag('div', ['class' => 'mb-4']);
echo html_writer::tag('a', get_string('bulkupdate', 'local_sceh_importer'), [
    'href' => new moodle_url('/local/sceh_importer/index.php', ['courseid' => $courseid]),
    'class' => 'btn btn-primary',
]);
echo html_writer::tag('span', get_string('or', 'moodle'), ['class' => 'mx-2']);
echo html_writer::tag('button', get_string('individualupdate', 'local_sceh_importer'), [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'id' => 'show-file-list',
]);
echo html_writer::end_tag('div');

$modinfo = get_fast_modinfo($courseid);
$sections = $modinfo->get_section_info_all();

$structure = [];
foreach ($sections as $section) {
    if ($section->section == 0) {
        continue;
    }
    $sectionname = get_section_name($courseid, $section);
    $sectiondata = [
        'name' => $sectionname,
        'activities' => [],
    ];
    
    if (!empty($modinfo->sections[$section->section])) {
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if ($cm->deletioninprogress || !$cm->uservisible) {
                continue;
            }
            $sectiondata['activities'][] = [
                'cmid' => $cm->id,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'idnumber' => $cm->idnumber,
            ];
        }
    }
    
    if (!empty($sectiondata['activities'])) {
        $structure[] = $sectiondata;
    }
}

echo html_writer::start_tag('div', ['id' => 'file-list-container', 'style' => 'display:none;']);
echo html_writer::tag('h3', get_string('coursestructure', 'local_sceh_importer'));

if (empty($structure)) {
    echo html_writer::tag('p', get_string('nocontent', 'local_sceh_importer'), ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable sceh-update-table';
    $table->head = [
        get_string('section'),
        get_string('activity'),
        get_string('type'),
        get_string('actions'),
    ];
    
    foreach ($structure as $sectiondata) {
        foreach ($sectiondata['activities'] as $index => $activity) {
            $sectioncell = $index === 0 ? format_string($sectiondata['name']) : '';
            $activityname = format_string($activity['name']);
            if (!empty($activity['idnumber'])) {
                $activityname .= html_writer::empty_tag('br') .
                    html_writer::tag('small', s($activity['idnumber']), ['class' => 'text-muted']);
            }
            
            $replaceurl = new moodle_url('/local/sceh_importer/update_file.php', [
                'courseid' => $courseid,
                'cmid' => $activity['cmid'],
            ]);
            $replacebutton = html_writer::tag('a', get_string('replace', 'local_sceh_importer'), [
                'href' => $replaceurl,
                'class' => 'btn btn-sm btn-secondary',
            ]);
            
            $table->data[] = [
                $sectioncell,
                $activityname,
                get_string('modulename', $activity['modname']),
                $replacebutton,
            ];
        }
    }
    
    echo html_writer::table($table);
}

echo html_writer::end_tag('div');

$PAGE->requires->js_init_code(
    "(() => {
        const showBtn = document.getElementById('show-file-list');
        const container = document.getElementById('file-list-container');
        if (showBtn && container) {
            showBtn.addEventListener('click', () => {
                container.style.display = container.style.display === 'none' ? 'block' : 'none';
                showBtn.textContent = container.style.display === 'none' 
                    ? '" . get_string('individualupdate', 'local_sceh_importer') . "'
                    : '" . get_string('hidefilelist', 'local_sceh_importer') . "';
            });
        }
    })();"
);

echo $OUTPUT->footer();
