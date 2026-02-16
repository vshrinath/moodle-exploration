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

use local_sceh_importer\form\upload_form;
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

$mform = new upload_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'local_sceh_importer']));
}

$preview = null;

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

    $quizrows = [];
    if (!empty($data->quizsheet)) {
        $quizfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->quizsheet, 'itemid, filepath, filename', false);
        if (!empty($quizfiles)) {
            $quizrows = $quizparser->parse(reset($quizfiles));
        }
    }

    $manifest = $manifestbuilder->build(
        $scan,
        $quizrows,
        (string)$data->importmode,
        !empty($data->dryrun),
        trim((string)$data->changenote)
    );

    $validation = $manifestbuilder->validate($manifest, $scan['files']);

    $preview = [
        'manifest' => $manifest,
        'yaml' => $manifestbuilder->to_yaml($manifest),
        'errors' => $validation['errors'],
        'warnings' => $validation['warnings'],
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importpage', 'local_sceh_importer'));
echo $OUTPUT->notification(get_string('importintro', 'local_sceh_importer'), \core\output\notification::NOTIFY_INFO);

$mform->display();

if ($preview !== null) {
    echo $OUTPUT->heading(get_string('previewheading', 'local_sceh_importer'), 3);

    foreach ($preview['errors'] as $error) {
        echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
    }
    foreach ($preview['warnings'] as $warning) {
        echo $OUTPUT->notification($warning, \core\output\notification::NOTIFY_WARNING);
    }

    $summary = [
        get_string('sections', 'local_sceh_importer') => count($preview['manifest']['sections']),
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

    $activitytable = new html_table();
    $activitytable->head = ['ID Number', 'Type', 'Section', 'Title'];
    foreach ($preview['manifest']['activities'] as $activity) {
        $activitytable->data[] = [
            $activity['idnumber'] ?? get_string('empty', 'local_sceh_importer'),
            $activity['type'] ?? get_string('empty', 'local_sceh_importer'),
            $activity['section_idnumber'] ?? get_string('empty', 'local_sceh_importer'),
            $activity['title'] ?? get_string('empty', 'local_sceh_importer'),
        ];
    }
    echo $OUTPUT->heading(get_string('activities', 'local_sceh_importer'), 4);
    echo html_writer::table($activitytable);

    echo $OUTPUT->heading(get_string('manifestyaml', 'local_sceh_importer'), 4);
    echo html_writer::tag('pre', s($preview['yaml']), ['style' => 'max-height: 420px; overflow: auto;']);
}

echo $OUTPUT->footer();
