<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SCEH package importer - individual file replacement page.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

use local_sceh_importer\local\import_executor;

require_login();

$systemcontext = context_system::instance();
require_capability('local/sceh_importer:manage', $systemcontext);

$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_id('', $cmid, $courseid, false, MUST_EXIST);
$coursecontext = context_course::instance($courseid);

$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/local/sceh_importer/update_file.php', ['courseid' => $courseid, 'cmid' => $cmid]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('replacefile', 'local_sceh_importer'));
$PAGE->set_heading(get_string('replacefile', 'local_sceh_importer'));

$mform = new \local_sceh_importer\form\file_upload_form(null, [
    'courseid' => $courseid,
    'cmid' => $cmid,
    'activityname' => $cm->name,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/sceh_importer/update.php', ['courseid' => $courseid]));
}

$preview = null;
$execution = null;

if ($confirm && confirm_sesskey()) {
    $previewkey = 'local_sceh_importer_file_preview_' . (int)$USER->id;
    $savedpreview = $SESSION->$previewkey ?? null;
    
    if (empty($savedpreview) || (int)$savedpreview['cmid'] !== $cmid) {
        throw new moodle_exception('error_importexpired', 'local_sceh_importer');
    }
    
    try {
        $executor = new import_executor();
        $versionedidnumber = $cm->idnumber . '-V' . time();
        
        $archived = $executor->archive_existing_activity($cmid, $cm->idnumber, $cm->modname);
        if (!$archived) {
            throw new moodle_exception('error_archivefailed', 'local_sceh_importer');
        }
        
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $savedpreview['draftitemid'], 'itemid, filepath, filename', false);
        
        if (empty($draftfiles)) {
            throw new moodle_exception('error_nozipfile', 'local_sceh_importer');
        }
        
        $uploadedfile = reset($draftfiles);
        $tempdir = make_temp_directory('local_sceh_importer/' . $USER->id . '/' . time());
        $filepath = $tempdir . '/' . $uploadedfile->get_filename();
        $uploadedfile->copy_content_to($filepath);
        
        $moduleinfo = new stdClass();
        $moduleinfo->course = $courseid;
        $moduleinfo->coursemodule = 0;
        $moduleinfo->section = $cm->sectionnum;
        $moduleinfo->module = $cm->module;
        $moduleinfo->modulename = $cm->modname;
        $moduleinfo->instance = 0;
        $moduleinfo->add = $cm->modname;
        $moduleinfo->update = 0;
        $moduleinfo->return = 0;
        $moduleinfo->sr = 0;
        $moduleinfo->idnumber = $versionedidnumber;
        $moduleinfo->name = $cm->name . ' (V' . date('YmdHis') . ')';
        $moduleinfo->visible = $cm->visible;
        $moduleinfo->visibleoncoursepage = $cm->visibleoncoursepage;
        $moduleinfo->cmidnumber = $versionedidnumber;
        
        if ($cm->modname === 'resource') {
            $moduleinfo->files = $savedpreview['draftitemid'];
            $moduleinfo->display = RESOURCELIB_DISPLAY_AUTO;
            $moduleinfo->printintro = 0;
        }
        
        $newcm = create_module($moduleinfo);
        
        $execution = [
            'success' => true,
            'oldname' => $cm->name,
            'newname' => $moduleinfo->name,
            'newidnumber' => $versionedidnumber,
        ];
        
        unset($SESSION->$previewkey);
        
    } catch (Throwable $e) {
        $execution = [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

if ($data = $mform->get_data()) {
    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();
    
    $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->replacementfile, 'itemid, filepath, filename', false);
    if (empty($draftfiles)) {
        throw new moodle_exception('error_nozipfile', 'local_sceh_importer');
    }
    
    $uploadedfile = reset($draftfiles);
    $preview = [
        'filename' => $uploadedfile->get_filename(),
        'filesize' => display_size($uploadedfile->get_filesize()),
        'activityname' => $cm->name,
        'activitytype' => get_string('modulename', $cm->modname),
        'newidnumber' => $cm->idnumber . '-V' . time(),
    ];
    
    $previewkey = 'local_sceh_importer_file_preview_' . (int)$USER->id;
    $SESSION->$previewkey = [
        'cmid' => $cmid,
        'draftitemid' => $data->replacementfile,
        'time' => time(),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('replacefile', 'local_sceh_importer'));

if ($execution !== null) {
    if ($execution['success']) {
        echo $OUTPUT->notification(
            get_string('replacesuccess', 'local_sceh_importer', $execution),
            core\output\notification::NOTIFY_SUCCESS
        );
        echo html_writer::tag('a', get_string('backtoupdate', 'local_sceh_importer'), [
            'href' => new moodle_url('/local/sceh_importer/update.php', ['courseid' => $courseid]),
            'class' => 'btn btn-primary',
        ]);
    } else {
        echo $OUTPUT->notification($execution['error'], core\output\notification::NOTIFY_ERROR);
    }
} else if ($preview !== null) {
    echo $OUTPUT->heading(get_string('confirmreplacement', 'local_sceh_importer'), 3);
    
    $table = new html_table();
    $table->data = [
        [get_string('currentactivity', 'local_sceh_importer'), format_string($preview['activityname'])],
        [get_string('type'), $preview['activitytype']],
        [get_string('newfile', 'local_sceh_importer'), s($preview['filename']) . ' (' . $preview['filesize'] . ')'],
        [get_string('newidnumber', 'local_sceh_importer'), s($preview['newidnumber'])],
    ];
    echo html_writer::table($table);
    
    echo html_writer::tag('p', get_string('replacementwarning', 'local_sceh_importer'), ['class' => 'alert alert-warning']);
    
    $confirmurl = new moodle_url('/local/sceh_importer/update_file.php', [
        'courseid' => $courseid,
        'cmid' => $cmid,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);
    echo html_writer::tag('a', get_string('confirmreplace', 'local_sceh_importer'), [
        'href' => $confirmurl,
        'class' => 'btn btn-primary',
    ]);
    echo html_writer::tag('a', get_string('cancel'), [
        'href' => new moodle_url('/local/sceh_importer/update.php', ['courseid' => $courseid]),
        'class' => 'btn btn-secondary ml-2',
    ]);
} else {
    echo html_writer::tag('p', get_string('replacefileintro', 'local_sceh_importer', format_string($cm->name)));
    $mform->display();
}

echo $OUTPUT->footer();
