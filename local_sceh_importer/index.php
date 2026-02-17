<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SCEH package importer upload and preview page.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

use local_sceh_importer\form\upload_form;
use local_sceh_importer\local\import_executor;
use local_sceh_importer\local\manifest_builder;
use local_sceh_importer\local\package_scanner;
use local_sceh_importer\local\quiz_sheet_parser;

require_login();

$systemcontext = context_system::instance();
require_capability('local/sceh_importer:manage', $systemcontext);

$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/local/sceh_importer/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('importpage', 'local_sceh_importer'));
$PAGE->set_heading(get_string('importpage', 'local_sceh_importer'));

$courses = $DB->get_records_sql(
    'SELECT id, idnumber, shortname, fullname
       FROM {course}
      WHERE id > :sitecourseid
   ORDER BY fullname',
    ['sitecourseid' => 1]
);
$courseoptions = [];
foreach ($courses as $course) {
    $courseoptions[$course->id] = format_string($course->fullname);
}

$programrecords = $DB->get_records_sql(
    'SELECT programidnumber, MAX(programname) AS programname
       FROM {local_sceh_importer_prog}
   GROUP BY programidnumber
   ORDER BY programidnumber'
);
$programoptions = [];
$programmap = [];
foreach ($programrecords as $programrecord) {
    $programidnumber = (string)$programrecord->programidnumber;
    $programname = trim((string)$programrecord->programname);
    $label = $programidnumber . ($programname !== '' ? ' — ' . $programname : '');
    $programoptions[$programidnumber] = $label;
    $programmap[$programidnumber] = [
        'idnumber' => $programidnumber,
        'name' => $programname,
    ];
}

$mform = new upload_form(null, [
    'courses' => $courseoptions,
    'programs' => $programoptions,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'local_sceh_importer']));
}

$preview = null;
$execution = null;
$previewkey = 'local_sceh_importer_preview_' . (int)$USER->id;

if (optional_param('doimport', 0, PARAM_BOOL)) {
    require_sesskey();
    $savedpreview = $SESSION->$previewkey ?? null;
    if (empty($savedpreview['manifest']) || empty($savedpreview['extractdir']) || empty($savedpreview['targetcourseid'])) {
        throw new moodle_exception('error_importstate', 'local_sceh_importer');
    }
    if (!empty($savedpreview['errors'])) {
        throw new moodle_exception('error_importvalidation', 'local_sceh_importer');
    }
    if (!is_dir((string)$savedpreview['extractdir'])) {
        throw new moodle_exception('error_importexpired', 'local_sceh_importer');
    }

    $preview = [
        'manifest' => (array)$savedpreview['manifest'],
        'yaml' => (string)($savedpreview['yaml'] ?? ''),
        'errors' => (array)($savedpreview['errors'] ?? []),
        'warnings' => (array)($savedpreview['warnings'] ?? []),
    ];

    try {
        $executor = new import_executor();
        $execution = $executor->execute(
            (int)$savedpreview['targetcourseid'],
            (int)$USER->id,
            (string)$savedpreview['extractdir'],
            (array)$savedpreview['manifest']
        );
        $execution['blocked'] = false;
        unset($SESSION->$previewkey);
    } catch (Throwable $e) {
        $preview['errors'][] = 'Execution failed: ' . $e->getMessage();
        if (!empty($e->debuginfo)) {
            $preview['errors'][] = 'Debug: ' . $e->debuginfo;
        }
        $execution = [
            'blocked' => true,
            'created' => [],
            'skipped' => [],
            'warnings' => [],
        ];
    }
}

if ($data = $mform->get_data()) {
    $scanner = new package_scanner();
    $manifestbuilder = new manifest_builder();
    $quizparser = new quiz_sheet_parser();

    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();

    $zipfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->packagezip, 'itemid, filepath, filename', false);
    if (empty($zipfiles)) {
        throw new moodle_exception('error_nozipfile', 'local_sceh_importer');
    }
    $zipfile = reset($zipfiles);

    $extractdir = $scanner->extract_zip($zipfile, (int)$USER->id);
    $scan = $scanner->scan($extractdir);

    $inlinequizactivities = [];
    if (!empty($scan['quiz_csv_activities'])) {
        foreach ($scan['quiz_csv_activities'] as $quizcsvactivity) {
            $quizcsvpath = (string)($quizcsvactivity['quiz_csv_path'] ?? '');
            if ($quizcsvpath === '') {
                continue;
            }
            $fullquizpath = $extractdir . '/' . ltrim($quizcsvpath, '/');
            if (!is_readable($fullquizpath)) {
                continue;
            }
            $rows = $quizparser->parse_path($fullquizpath);
            if (!empty($rows)) {
                $inlinequizactivities[] = [
                    'idnumber' => (string)($quizcsvactivity['idnumber'] ?? ''),
                    'title' => (string)($quizcsvactivity['title'] ?? 'Quiz'),
                    'section_idnumber' => (string)($quizcsvactivity['section_idnumber'] ?? 'SEC-COMMON'),
                    'topic_idnumber' => (string)($quizcsvactivity['topic_idnumber'] ?? ''),
                    'audience' => (string)($quizcsvactivity['audience'] ?? 'learner'),
                    'rows' => $rows,
                ];
            }
        }
    }

    $targetcourseid = (int)($data->targetcourseid ?? 0);
    $coursemode = (string)($data->coursemode ?? 'existing');
    if ($coursemode === 'new') {
        $newcoursefullname = trim((string)$data->newcoursefullname);

        $defaultcategoryid = 1;
        $categoryoptions = core_course_category::make_categories_list();
        foreach ($categoryoptions as $categoryid => $categoryname) {
            if ($categoryid <= 0) {
                continue;
            }
            $defaultcategoryid = (int)$categoryid;
            if (stripos((string)$categoryname, 'Allied Health Programs') !== false || stripos((string)$categoryname, 'Programs') !== false) {
                break;
            }
        }

        $shortbase = preg_replace('/[^A-Za-z0-9]+/', '_', strtoupper($newcoursefullname));
        $shortbase = trim((string)$shortbase, '_');
        if ($shortbase === '') {
            $shortbase = 'COURSE';
        }

        $newcourse = new stdClass();
        $newcourse->fullname = $newcoursefullname;
        $newcourse->shortname = substr($shortbase, 0, 60) . '_' . date('His');
        $newcourse->category = $defaultcategoryid;
        $newcourse->format = 'topics';
        $newcourse->visible = 1;
        $newcourse->idnumber = 'CRS-' . date('YmdHis');

        $createdcourse = create_course($newcourse);
        $targetcourseid = (int)$createdcourse->id;
        $courses[$targetcourseid] = $createdcourse;
    }

    if (empty($targetcourseid) || empty($courses[$targetcourseid])) {
        throw new moodle_exception('error_selecttargetcourse', 'local_sceh_importer');
    }

    $programmode = (string)($data->programmode ?? 'existing');
    $programidnumber = '';
    $programname = '';
    if ($programmode === 'new') {
        $programidnumber = trim((string)($data->programidnumber ?? ''));
        $programname = trim((string)($data->programname ?? ''));
    } else {
        $selectedprogramidnumber = trim((string)($data->selectedprogramidnumber ?? ''));
        if (!empty($programmap[$selectedprogramidnumber])) {
            $programidnumber = $programmap[$selectedprogramidnumber]['idnumber'];
            $programname = $programmap[$selectedprogramidnumber]['name'];
        }
    }

    $manifestbuilder = new manifest_builder();
    $manifest = $manifestbuilder->build(
        $scan,
        [],
        'upsert',
        true,
        $inlinequizactivities,
        '',
        $programidnumber,
        $programname,
        [
            'idnumber' => (string)($courses[$targetcourseid]->idnumber ?? ''),
            'shortname' => (string)($courses[$targetcourseid]->shortname ?? ''),
            'fullname' => (string)($courses[$targetcourseid]->fullname ?? ''),
        ]
    );

    $validation = $manifestbuilder->validate($manifest, $scan['files']);

    $preview = [
        'manifest' => $manifest,
        'yaml' => $manifestbuilder->to_yaml($manifest),
        'errors' => $validation['errors'],
        'warnings' => $validation['warnings'],
    ];

    $SESSION->$previewkey = [
        'manifest' => $manifest,
        'yaml' => $preview['yaml'],
        'errors' => $preview['errors'],
        'warnings' => $preview['warnings'],
        'extractdir' => $extractdir,
        'targetcourseid' => $targetcourseid,
        'time' => time(),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importpage', 'local_sceh_importer'));
echo $OUTPUT->notification(get_string('importintro', 'local_sceh_importer'), core\output\notification::NOTIFY_INFO);

$mform->display();

if ($preview !== null) {
    echo $OUTPUT->heading(get_string('validationheading', 'local_sceh_importer'), 3);

    if (empty($preview['errors'])) {
        echo $OUTPUT->notification(get_string('validationok', 'local_sceh_importer'), core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->notification(get_string('validationfail', 'local_sceh_importer'), core\output\notification::NOTIFY_ERROR);
    }

    foreach ($preview['errors'] as $error) {
        echo $OUTPUT->notification($error, core\output\notification::NOTIFY_ERROR);
    }
    foreach ($preview['warnings'] as $warning) {
        echo $OUTPUT->notification($warning, core\output\notification::NOTIFY_WARNING);
    }

    if (empty($preview['errors'])) {
        $importurl = new moodle_url('/local/sceh_importer/index.php');
        $importbutton = html_writer::tag('button', get_string('importbutton', 'local_sceh_importer'), [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]);
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $importurl->out(false)]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'doimport', 'value' => 1]);
        echo $importbutton;
        echo html_writer::end_tag('form');
    } else {
        echo html_writer::tag('button', get_string('importdisabled', 'local_sceh_importer'), [
            'class' => 'btn btn-secondary',
            'disabled' => 'disabled',
            'type' => 'button',
        ]);
        echo html_writer::tag('div', get_string('importmustvalidate', 'local_sceh_importer'), ['class' => 'text-muted mt-2']);
    }

    echo html_writer::start_tag('details', ['class' => 'mt-4']);
    echo html_writer::tag('summary', get_string('showdebug', 'local_sceh_importer'));

    $summary = [
        get_string('sections', 'local_sceh_importer') => count($preview['manifest']['sections']),
        get_string('topics', 'local_sceh_importer') => count($preview['manifest']['topics'] ?? []),
        get_string('activities', 'local_sceh_importer') => count($preview['manifest']['activities']),
        get_string('errors', 'local_sceh_importer') => count($preview['errors']),
        get_string('warnings', 'local_sceh_importer') => count($preview['warnings']),
    ];

    $summarytable = new html_table();
    $summarytable->head = [get_string('summary', 'local_sceh_importer'), get_string('importer', 'local_sceh_importer')];
    foreach ($summary as $label => $value) {
        $summarytable->data[] = [$label, $value];
    }
    echo html_writer::table($summarytable);

    $sectiontable = new html_table();
    $sectiontable->head = ['ID Number', get_string('sections', 'local_sceh_importer')];
    foreach ($preview['manifest']['sections'] as $section) {
        $sectiontable->data[] = [$section['idnumber'], format_string($section['name'])];
    }
    echo $OUTPUT->heading(get_string('sections', 'local_sceh_importer'), 4);
    echo html_writer::table($sectiontable);

    if (!empty($preview['manifest']['topics'])) {
        $topictable = new html_table();
        $topictable->head = ['ID Number', get_string('sections', 'local_sceh_importer'), get_string('topics', 'local_sceh_importer')];
        foreach ($preview['manifest']['topics'] as $topic) {
            $topictable->data[] = [
                $topic['idnumber'] ?? get_string('empty', 'local_sceh_importer'),
                $topic['section_idnumber'] ?? get_string('empty', 'local_sceh_importer'),
                $topic['name'] ?? get_string('empty', 'local_sceh_importer'),
            ];
        }
        echo $OUTPUT->heading(get_string('topics', 'local_sceh_importer'), 4);
        echo html_writer::table($topictable);
    }

    $activitytable = new html_table();
    $activitytable->head = ['ID Number', 'Type', 'Section', 'Topic', 'Title'];
    foreach ($preview['manifest']['activities'] as $activity) {
        $activitytable->data[] = [
            $activity['idnumber'] ?? get_string('empty', 'local_sceh_importer'),
            $activity['type'] ?? get_string('empty', 'local_sceh_importer'),
            $activity['section_idnumber'] ?? get_string('empty', 'local_sceh_importer'),
            $activity['topic_idnumber'] ?? get_string('empty', 'local_sceh_importer'),
            $activity['title'] ?? get_string('empty', 'local_sceh_importer'),
        ];
    }
    echo $OUTPUT->heading(get_string('activities', 'local_sceh_importer'), 4);
    echo html_writer::table($activitytable);

    echo $OUTPUT->heading(get_string('manifestyaml', 'local_sceh_importer'), 4);
    echo html_writer::tag('pre', s($preview['yaml']), ['style' => 'max-height: 420px; overflow: auto;']);
    echo html_writer::end_tag('details');
}

if ($execution !== null) {
    echo $OUTPUT->heading(get_string('executedheading', 'local_sceh_importer'), 3);
    if (!empty($execution['blocked'])) {
        echo $OUTPUT->notification(get_string('executionblocked', 'local_sceh_importer'), core\output\notification::NOTIFY_WARNING);
    } else {
        echo $OUTPUT->notification(get_string('executionok', 'local_sceh_importer'), core\output\notification::NOTIFY_SUCCESS);
    }

    if (!empty($execution['warnings'])) {
        foreach ($execution['warnings'] as $warning) {
            echo $OUTPUT->notification($warning, core\output\notification::NOTIFY_WARNING);
        }
    }

    $executiontable = new html_table();
    $executiontable->head = [get_string('summary', 'local_sceh_importer'), get_string('importer', 'local_sceh_importer')];
    $executiontable->data[] = [get_string('executedcreated', 'local_sceh_importer'), count($execution['created'])];
    $executiontable->data[] = [get_string('executedskipped', 'local_sceh_importer'), count($execution['skipped'])];
    $executiontable->data[] = [get_string('executedwarnings', 'local_sceh_importer'), count($execution['warnings'])];
    echo html_writer::table($executiontable);

    if (!empty($execution['created'])) {
        echo html_writer::tag('h5', get_string('executedcreated', 'local_sceh_importer'));
        echo html_writer::alist($execution['created']);
    }
    if (!empty($execution['skipped'])) {
        echo html_writer::tag('h5', get_string('executedskipped', 'local_sceh_importer'));
        echo html_writer::alist($execution['skipped']);
    }
}

echo $OUTPUT->footer();
