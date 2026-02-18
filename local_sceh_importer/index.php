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
$PAGE->requires->css(new moodle_url('/local/sceh_importer/styles.css'));

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
$programnames = [];
foreach ($programrecords as $programrecord) {
    $programidnumber = (string)$programrecord->programidnumber;
    $programname = trim((string)$programrecord->programname);
    $label = $programidnumber . ($programname !== '' ? ' — ' . $programname : '');
    $programoptions[$programidnumber] = $label;
    $programmap[$programidnumber] = [
        'idnumber' => $programidnumber,
        'name' => $programname,
    ];
    if ($programname !== '') {
        $programnames[] = $programname;
    }
}

$coursefullnames = [];
foreach ($courses as $course) {
    $coursefullnames[] = trim((string)$course->fullname);
}

$mform = new upload_form(null, [
    'courses' => $courseoptions,
    'programs' => $programoptions,
    'programnames' => $programnames,
    'coursefullnames' => $coursefullnames,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'local_sceh_importer']));
}

$preview = null;
$execution = null;
$pageerrors = [];
$previewkey = 'local_sceh_importer_preview_' . (int)$USER->id;

if (optional_param('doimport', 0, PARAM_BOOL)) {
    try {
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
    $totaluploadedactivities = count((array)($preview['manifest']['activities'] ?? []));

    $selectedactivityids = optional_param_array('selectedactivityids', [], PARAM_ALPHANUMEXT);
    $allowactivityreplace = optional_param('allowactivityreplace', 0, PARAM_BOOL);
    $selectedlookup = array_fill_keys($selectedactivityids, true);

    $existingactivityrows = $DB->get_records_sql(
        'SELECT cm.id AS cmid, cm.idnumber, cm.instance, m.name AS modname
           FROM {course_modules} cm
           JOIN {modules} m ON m.id = cm.module
          WHERE cm.course = :courseid
            AND cm.deletioninprogress = 0
            AND cm.idnumber <> \'\'',
        ['courseid' => (int)$savedpreview['targetcourseid']]
    );
    $existingactivitylookup = [];
    foreach ($existingactivityrows as $row) {
        $idkey = \core_text::strtolower(trim((string)$row->idnumber));
        if ($idkey === '') {
            continue;
        }
        $existingactivitylookup[$idkey] = [
            'cmid' => (int)$row->cmid,
            'instance' => (int)$row->instance,
            'modname' => (string)$row->modname,
        ];
    }
    $reservedidnumbers = array_fill_keys(array_keys($existingactivitylookup), true);

    $nextversionedidnumber = static function(string $idnumber, array &$reserved): string {
        $base = preg_replace('/-V\d+$/i', '', $idnumber);
        $version = 2;
        if (preg_match('/-V(\d+)$/i', $idnumber, $matches)) {
            $version = ((int)$matches[1]) + 1;
        }
        do {
            $candidate = $base . '-V' . $version;
            $candidatekey = \core_text::strtolower($candidate);
            $version++;
        } while (isset($reserved[$candidatekey]));
        $reserved[$candidatekey] = true;
        return $candidate;
    };

    $nextversionedtitle = static function(string $title, string $versionedidnumber): string {
        $base = trim((string)preg_replace('/\s+\(V\d+\)$/i', '', $title));
        if ($base === '') {
            $base = 'Quiz';
        }
        $version = 2;
        if (preg_match('/-V(\d+)$/i', $versionedidnumber, $matches)) {
            $version = (int)$matches[1];
        }
        return $base . ' (V' . $version . ')';
    };

    $selectedactivities = [];
    $quizconfirmrequired = [];
    foreach ((array)$preview['manifest']['activities'] as $activity) {
        $idnumber = (string)($activity['idnumber'] ?? '');
        $idkey = \core_text::strtolower(trim($idnumber));
        if ($idnumber !== '' && isset($selectedlookup[$idnumber])) {
            $existingmodname = \core_text::strtolower((string)($existingactivitylookup[$idkey]['modname'] ?? ''));
            if ($idkey !== '' && isset($existingactivitylookup[$idkey])) {
                if (!$allowactivityreplace) {
                    $quizconfirmrequired[] = $idnumber;
                    continue;
                }
                $versionedidnumber = $nextversionedidnumber($idnumber, $reservedidnumbers);
                $activity['archive_existing_activity'] = [
                    'cmid' => (int)$existingactivitylookup[$idkey]['cmid'],
                    'idnumber' => $idnumber,
                    'modname' => (string)$existingmodname,
                ];
                $activity['idnumber'] = $versionedidnumber;
                $activity['title'] = $nextversionedtitle((string)($activity['title'] ?? 'Activity'), $versionedidnumber);
            }
            $reservedidnumbers[\core_text::strtolower((string)($activity['idnumber'] ?? $idnumber))] = true;
            $selectedactivities[] = $activity;
        }
    }
    if (empty($selectedactivities)) {
        throw new moodle_exception('error_noselectedactivities', 'local_sceh_importer');
    }
    $preview['manifest']['activities'] = $selectedactivities;

    if (!empty($quizconfirmrequired)) {
        $preview['errors'][] = get_string(
            'error_activityreplaceconfirmrequired',
            'local_sceh_importer',
            implode(', ', $quizconfirmrequired)
        );
        $execution = [
            'blocked' => true,
            'created' => [],
            'skipped' => [],
            'replaced' => [],
            'warnings' => [],
            'totaluploadedactivities' => $totaluploadedactivities,
        ];
    }

        if ($execution === null) {
            try {
                $executor = new import_executor();
                $execution = $executor->execute(
                    (int)$savedpreview['targetcourseid'],
                    (int)$USER->id,
                    (string)$savedpreview['extractdir'],
                    (array)$preview['manifest']
                );
                $execution['blocked'] = false;
                $execution['totaluploadedactivities'] = $totaluploadedactivities;
                unset($SESSION->$previewkey);
                $preview = null;
            } catch (Throwable $e) {
                $preview['errors'][] = 'Execution failed: ' . $e->getMessage();
                if (!empty($e->debuginfo)) {
                    $preview['errors'][] = 'Debug: ' . $e->debuginfo;
                }
                $execution = [
                    'blocked' => true,
                    'created' => [],
                    'skipped' => [],
                    'replaced' => [],
                    'warnings' => [],
                    'totaluploadedactivities' => $totaluploadedactivities,
                ];
            }
        }
    } catch (moodle_exception $e) {
        $pageerrors[] = $e->getMessage();
        $execution = [
            'blocked' => true,
            'created' => [],
            'skipped' => [],
            'replaced' => [],
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
        foreach ($courses as $existingcourse) {
            if (\core_text::strtolower(trim((string)$existingcourse->fullname)) === \core_text::strtolower($newcoursefullname)) {
                throw new moodle_exception('error_newcoursefullname_taken', 'local_sceh_importer');
            }
        }

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
        foreach (array_keys($programoptions) as $existingprogramidnumber) {
            if (\core_text::strtolower(trim((string)$existingprogramidnumber)) === \core_text::strtolower($programidnumber)) {
                throw new moodle_exception('error_programidnumber_taken', 'local_sceh_importer');
            }
        }
        foreach ($programnames as $existingprogramname) {
            if (\core_text::strtolower(trim((string)$existingprogramname)) === \core_text::strtolower($programname)) {
                throw new moodle_exception('error_programname_taken', 'local_sceh_importer');
            }
        }
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
        'targetcourseid' => $targetcourseid,
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
foreach ($pageerrors as $pageerror) {
    echo $OUTPUT->notification($pageerror, core\output\notification::NOTIFY_ERROR);
}

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
        $existingactivityrows = $DB->get_records_sql(
            'SELECT cm.idnumber
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = :courseid
                AND cm.deletioninprogress = 0
                AND cm.idnumber <> \'\'',
            ['courseid' => (int)($preview['targetcourseid'] ?? 0)]
        );
        $existingactivitylookup = [];
        foreach ($existingactivityrows as $row) {
            $idkey = \core_text::strtolower(trim((string)$row->idnumber));
            if ($idkey === '') {
                continue;
            }
            $existingactivitylookup[$idkey] = true;
        }

        $learnerimpactdata = [];
        $existingactivitydetails = $DB->get_records_sql(
            'SELECT cm.id AS cmid, cm.idnumber, cm.instance, m.name AS modname
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.course = :courseid
                AND cm.deletioninprogress = 0
                AND cm.idnumber <> \'\'',
            ['courseid' => (int)($preview['targetcourseid'] ?? 0)]
        );
        foreach ($existingactivitydetails as $detail) {
            $idkey = \core_text::strtolower(trim((string)$detail->idnumber));
            if ($idkey === '') {
                continue;
            }
            $learnercount = 0;
            if ($detail->modname === 'quiz') {
                $learnercount = $DB->count_records('quiz_attempts', ['quiz' => (int)$detail->instance]);
            } else if ($detail->modname === 'assign') {
                $learnercount = $DB->count_records('assign_submission', ['assignment' => (int)$detail->instance, 'status' => 'submitted']);
            }
            if ($learnercount > 0) {
                $learnerimpactdata[$idkey] = $learnercount;
            }
        }

        $importurl = new moodle_url('/local/sceh_importer/index.php');
        echo $OUTPUT->heading(get_string('selectactivitiesheading', 'local_sceh_importer'), 4);

        $groupedactivities = [];
        foreach ((array)$preview['manifest']['activities'] as $activity) {
            $sectionid = (string)($activity['section_idnumber'] ?? '');
            $topicid = (string)($activity['topic_idnumber'] ?? '');
            $groupkey = $sectionid . '||' . $topicid;
            if (!isset($groupedactivities[$groupkey])) {
                $groupedactivities[$groupkey] = [
                    'section_idnumber' => $sectionid,
                    'topic_idnumber' => $topicid,
                    'activities' => [],
                ];
            }
            $groupedactivities[$groupkey]['activities'][] = $activity;
        }

        $sectionnamemap = [];
        foreach ((array)$preview['manifest']['sections'] as $section) {
            $sectionid = (string)($section['idnumber'] ?? '');
            $sectionnamemap[$sectionid] = (string)($section['name'] ?? $sectionid);
        }
        $topicnamemap = [];
        foreach ((array)$preview['manifest']['topics'] as $topic) {
            $topicid = (string)($topic['idnumber'] ?? '');
            $topicnamemap[$topicid] = (string)($topic['name'] ?? $topicid);
        }

        $selectiontable = new html_table();
        $preselectedcount = 0;
        $selectiontable->attributes['class'] = 'generaltable sceh-import-selection-table';
        $selectiontable->head = ['', get_string('activities', 'local_sceh_importer'), 'Type', get_string('status', 'local_sceh_importer')];
        foreach ($groupedactivities as $group) {
            $sectionid = (string)$group['section_idnumber'];
            $topicid = (string)$group['topic_idnumber'];
            $sectionname = $sectionnamemap[$sectionid] ?? $sectionid;
            $topicname = ($topicid !== '') ? ($topicnamemap[$topicid] ?? $topicid) : '';
            $grouplabel = format_string($sectionname) . ($topicname !== '' ? ' / ' . format_string($topicname) : '');

            $groupcell = new html_table_cell(
                html_writer::tag('span', '▼', ['class' => 'sceh-import-group-toggle']) .
                html_writer::tag('strong', $grouplabel) .
                html_writer::tag('div', s($sectionid . ($topicid !== '' ? ' / ' . $topicid : '')), ['class' => 'text-muted'])
            );
            $groupcell->colspan = 4;
            $grouprow = new html_table_row([$groupcell]);
            $grouprow->attributes['class'] = 'sceh-import-group-row';
            $grouprow->attributes['data-group'] = $sectionid . '||' . $topicid;
            $selectiontable->data[] = $grouprow;

            foreach ((array)$group['activities'] as $activity) {
            $idnumber = (string)($activity['idnumber'] ?? '');
            $idkey = \core_text::strtolower(trim($idnumber));
            $title = (string)($activity['title'] ?? $idnumber);
            $type = (string)($activity['type'] ?? get_string('empty', 'local_sceh_importer'));
            $isexisting = $idkey !== '' && !empty($existingactivitylookup[$idkey]);
            $statuslabel = $isexisting ? get_string('status_existing', 'local_sceh_importer') : get_string('status_new', 'local_sceh_importer');
            $checkboxattrs = [
                'type' => 'checkbox',
                'name' => 'selectedactivityids[]',
                'value' => $idnumber,
            ];
            if ($isexisting) {
                $checkboxattrs['data-existingactivity'] = '1';
            }
            if (!$isexisting) {
                $checkboxattrs['checked'] = 'checked';
                $preselectedcount++;
            }
            $checkbox = html_writer::empty_tag('input', $checkboxattrs);
            $sourcepath = (string)($activity['file'] ?? $activity['quiz_source']['path'] ?? '');
            $learnerimpactwarning = '';
            if ($isexisting && isset($learnerimpactdata[$idkey]) && $learnerimpactdata[$idkey] > 0) {
                $learnerimpactwarning = html_writer::empty_tag('br') .
                    html_writer::tag('small', 
                        get_string('learnerimpact_warning', 'local_sceh_importer', ['count' => $learnerimpactdata[$idkey]]),
                        ['class' => 'text-warning']
                    );
            }
            $titlehtml = s($title) . html_writer::empty_tag('br') .
                html_writer::tag('small', s($idnumber), ['class' => 'text-muted']) .
                ($sourcepath !== '' ? html_writer::empty_tag('br') . html_writer::tag('small', s($sourcepath), ['class' => 'text-muted']) : '') .
                $learnerimpactwarning;
            $statusbadgeclass = $isexisting ? 'badge rounded-pill text-bg-secondary' : 'badge rounded-pill text-bg-success';
            $statusbadge = html_writer::tag('span', s($statuslabel), ['class' => $statusbadgeclass]);

            $checkboxcell = new html_table_cell($checkbox);
            $checkboxcell->attributes['class'] = 'sceh-import-select-cell';
            $titlecell = new html_table_cell($titlehtml);
            $typecell = new html_table_cell(s($type));
            $statuscell = new html_table_cell($statusbadge);

            $row = new html_table_row([$checkboxcell, $titlecell, $typecell, $statuscell]);
            $row->attributes['data-group'] = $sectionid . '||' . $topicid;
            if ($isexisting) {
                $row->attributes['class'] = 'sceh-import-existing-row';
            } else {
                $row->attributes['class'] = 'sceh-import-new-row';
            }
            $selectiontable->data[] = $row;
        }
        }

        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $importurl->out(false),
            'id' => 'sceh-import-selection-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'doimport', 'value' => 1]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'allowactivityreplace', 'value' => 0, 'id' => 'sceh-import-allow-activity-replace']);
        
        echo html_writer::start_tag('div', ['class' => 'mb-3 d-flex gap-2 align-items-center']);
        echo html_writer::tag('button', get_string('selectall_new', 'local_sceh_importer'), [
            'type' => 'button',
            'class' => 'btn btn-sm btn-secondary',
            'id' => 'sceh-select-all-new',
        ]);
        echo html_writer::tag('button', get_string('deselectall_existing', 'local_sceh_importer'), [
            'type' => 'button',
            'class' => 'btn btn-sm btn-secondary',
            'id' => 'sceh-deselect-all-existing',
        ]);
        echo html_writer::tag('button', get_string('collapseall', 'local_sceh_importer'), [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary',
            'id' => 'sceh-collapse-all',
        ]);
        echo html_writer::tag('button', get_string('expandall', 'local_sceh_importer'), [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary',
            'id' => 'sceh-expand-all',
        ]);
        echo html_writer::tag('span', '?', [
            'class' => 'badge rounded-circle bg-info text-white',
            'style' => 'cursor: pointer; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;',
            'id' => 'sceh-versioning-help-trigger',
            'title' => get_string('versioning_help_title', 'local_sceh_importer'),
        ]);
        echo html_writer::end_tag('div');
        
        echo html_writer::table($selectiontable);
        echo html_writer::tag('div', get_string('selectactivitieshelp', 'local_sceh_importer'), ['class' => 'text-muted mb-3']);
        echo html_writer::tag('div', get_string('selectactivitiesreplacehelp', 'local_sceh_importer'), ['class' => 'text-muted mb-3']);
        $buttonattrs = [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'id' => 'sceh-import-submit',
        ];
        if ($preselectedcount === 0) {
            $buttonattrs['disabled'] = 'disabled';
        }
        echo html_writer::tag('button', get_string('importbutton', 'local_sceh_importer'), $buttonattrs);
        echo html_writer::tag('div', get_string('error_noselectedactivities', 'local_sceh_importer'), [
            'class' => 'text-muted mt-2',
            'id' => 'sceh-import-selection-hint',
            'style' => $preselectedcount === 0 ? '' : 'display:none;',
        ]);
        echo html_writer::end_tag('form');

        echo html_writer::start_tag('div', [
            'id' => 'sceh-activity-replace-modal',
            'class' => 'sceh-import-modal',
            'hidden' => 'hidden',
            'aria-hidden' => 'true',
        ]);
        echo html_writer::start_tag('div', [
            'class' => 'sceh-import-modal__backdrop',
            'id' => 'sceh-activity-replace-backdrop',
        ]);
        echo html_writer::start_tag('div', [
            'class' => 'sceh-import-modal__dialog',
            'role' => 'dialog',
            'aria-modal' => 'true',
            'aria-labelledby' => 'sceh-activity-replace-title',
        ]);
        echo html_writer::tag('h5', get_string('confirmactivityreplace_title', 'local_sceh_importer'), [
            'id' => 'sceh-activity-replace-title',
            'class' => 'mb-3',
        ]);
        echo html_writer::tag('p', get_string('confirmactivityreplace', 'local_sceh_importer'), ['class' => 'mb-4']);
        echo html_writer::start_tag('div', ['class' => 'sceh-import-modal__actions']);
        echo html_writer::tag('button', get_string('confirmactivityreplace_cancel', 'local_sceh_importer'), [
            'type' => 'button',
            'class' => 'btn btn-secondary',
            'id' => 'sceh-activity-replace-cancel',
        ]);
        echo html_writer::tag('button', get_string('confirmactivityreplace_continue', 'local_sceh_importer'), [
            'type' => 'button',
            'class' => 'btn btn-primary',
            'id' => 'sceh-activity-replace-confirm',
        ]);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', [
            'id' => 'sceh-versioning-help-modal',
            'class' => 'sceh-import-modal',
            'hidden' => 'hidden',
            'aria-hidden' => 'true',
        ]);
        echo html_writer::start_tag('div', [
            'class' => 'sceh-import-modal__backdrop',
            'id' => 'sceh-versioning-help-backdrop',
        ]);
        echo html_writer::start_tag('div', [
            'class' => 'sceh-import-modal__dialog',
            'role' => 'dialog',
            'aria-modal' => 'true',
            'aria-labelledby' => 'sceh-versioning-help-title',
        ]);
        echo html_writer::tag('h5', get_string('versioning_help_title', 'local_sceh_importer'), [
            'id' => 'sceh-versioning-help-title',
            'class' => 'mb-3',
        ]);
        echo html_writer::tag('p', get_string('versioning_help', 'local_sceh_importer'), ['class' => 'mb-4']);
        echo html_writer::start_tag('div', ['class' => 'sceh-import-modal__actions']);
        echo html_writer::tag('button', 'Close', [
            'type' => 'button',
            'class' => 'btn btn-primary',
            'id' => 'sceh-versioning-help-close',
        ]);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        $PAGE->requires->js_init_code(
            "(() => {
                const form = document.getElementById('sceh-import-selection-form');
                if (!form) { return; }
                const submit = document.getElementById('sceh-import-submit');
                const hint = document.getElementById('sceh-import-selection-hint');
                const allowActivityReplace = document.getElementById('sceh-import-allow-activity-replace');
                const modal = document.getElementById('sceh-activity-replace-modal');
                const cancelBtn = document.getElementById('sceh-activity-replace-cancel');
                const confirmBtn = document.getElementById('sceh-activity-replace-confirm');
                const backdrop = document.getElementById('sceh-activity-replace-backdrop');
                const boxes = Array.from(form.querySelectorAll('input[name=\"selectedactivityids[]\"]'));
                let confirmedActivityReplace = false;

                const closeModal = () => {
                    if (!modal) { return; }
                    modal.hidden = true;
                    modal.setAttribute('aria-hidden', 'true');
                };

                const openModal = () => {
                    if (!modal) { return; }
                    modal.hidden = false;
                    modal.setAttribute('aria-hidden', 'false');
                    if (cancelBtn) { cancelBtn.focus(); }
                };

                const sync = () => {
                    const any = boxes.some((cb) => cb.checked);
                    if (submit) { submit.disabled = !any; }
                    if (hint) { hint.style.display = any ? 'none' : ''; }
                };
                boxes.forEach((cb) => cb.addEventListener('change', sync));
                sync();
                
                const selectAllNew = document.getElementById('sceh-select-all-new');
                if (selectAllNew) {
                    selectAllNew.addEventListener('click', () => {
                        boxes.forEach((cb) => {
                            if (!cb.dataset.existingactivity) {
                                cb.checked = true;
                            }
                        });
                        sync();
                    });
                }
                
                const deselectAllExisting = document.getElementById('sceh-deselect-all-existing');
                if (deselectAllExisting) {
                    deselectAllExisting.addEventListener('click', () => {
                        boxes.forEach((cb) => {
                            if (cb.dataset.existingactivity === '1') {
                                cb.checked = false;
                            }
                        });
                        sync();
                    });
                }
                
                const groupRows = Array.from(document.querySelectorAll('.sceh-import-group-row'));
                groupRows.forEach((groupRow) => {
                    groupRow.addEventListener('click', () => {
                        const groupKey = groupRow.dataset.group;
                        const isCollapsed = groupRow.classList.contains('collapsed');
                        const activityRows = Array.from(document.querySelectorAll('tr[data-group=\"' + groupKey + '\"]')).filter(r => !r.classList.contains('sceh-import-group-row'));
                        
                        if (isCollapsed) {
                            groupRow.classList.remove('collapsed');
                            activityRows.forEach(r => r.style.display = '');
                        } else {
                            groupRow.classList.add('collapsed');
                            activityRows.forEach(r => r.style.display = 'none');
                        }
                    });
                });
                
                const collapseAll = document.getElementById('sceh-collapse-all');
                if (collapseAll) {
                    collapseAll.addEventListener('click', () => {
                        groupRows.forEach((groupRow) => {
                            const groupKey = groupRow.dataset.group;
                            const activityRows = Array.from(document.querySelectorAll('tr[data-group=\"' + groupKey + '\"]')).filter(r => !r.classList.contains('sceh-import-group-row'));
                            groupRow.classList.add('collapsed');
                            activityRows.forEach(r => r.style.display = 'none');
                        });
                    });
                }
                
                const expandAll = document.getElementById('sceh-expand-all');
                if (expandAll) {
                    expandAll.addEventListener('click', () => {
                        groupRows.forEach((groupRow) => {
                            const groupKey = groupRow.dataset.group;
                            const activityRows = Array.from(document.querySelectorAll('tr[data-group=\"' + groupKey + '\"]')).filter(r => !r.classList.contains('sceh-import-group-row'));
                            groupRow.classList.remove('collapsed');
                            activityRows.forEach(r => r.style.display = '');
                        });
                    });
                }
                
                const versioningHelpTrigger = document.getElementById('sceh-versioning-help-trigger');
                const versioningHelpModal = document.getElementById('sceh-versioning-help-modal');
                const versioningHelpClose = document.getElementById('sceh-versioning-help-close');
                const versioningHelpBackdrop = document.getElementById('sceh-versioning-help-backdrop');
                
                if (versioningHelpTrigger && versioningHelpModal) {
                    versioningHelpTrigger.addEventListener('click', () => {
                        versioningHelpModal.hidden = false;
                        versioningHelpModal.setAttribute('aria-hidden', 'false');
                        if (versioningHelpClose) { versioningHelpClose.focus(); }
                    });
                }
                
                if (versioningHelpClose) {
                    versioningHelpClose.addEventListener('click', () => {
                        if (versioningHelpModal) {
                            versioningHelpModal.hidden = true;
                            versioningHelpModal.setAttribute('aria-hidden', 'true');
                        }
                    });
                }
                
                if (versioningHelpBackdrop) {
                    versioningHelpBackdrop.addEventListener('click', () => {
                        if (versioningHelpModal) {
                            versioningHelpModal.hidden = true;
                            versioningHelpModal.setAttribute('aria-hidden', 'true');
                        }
                    });
                }
                
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => {
                        confirmedActivityReplace = false;
                        if (allowActivityReplace) { allowActivityReplace.value = '0'; }
                        closeModal();
                    });
                }
                if (backdrop) {
                    backdrop.addEventListener('click', () => {
                        confirmedActivityReplace = false;
                        if (allowActivityReplace) { allowActivityReplace.value = '0'; }
                        closeModal();
                    });
                }
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', () => {
                        confirmedActivityReplace = true;
                        if (allowActivityReplace) { allowActivityReplace.value = '1'; }
                        closeModal();
                        if (form.requestSubmit) {
                            form.requestSubmit(submit || undefined);
                        } else {
                            form.submit();
                        }
                    });
                }
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        if (modal && !modal.hidden) {
                            confirmedActivityReplace = false;
                            if (allowActivityReplace) { allowActivityReplace.value = '0'; }
                            closeModal();
                        }
                        if (versioningHelpModal && !versioningHelpModal.hidden) {
                            versioningHelpModal.hidden = true;
                            versioningHelpModal.setAttribute('aria-hidden', 'true');
                        }
                    }
                });
                form.addEventListener('submit', (event) => {
                    const selectedExistingActivity = boxes.some((cb) => cb.checked && cb.dataset.existingactivity === '1');
                    if (!selectedExistingActivity) {
                        confirmedActivityReplace = false;
                        if (allowActivityReplace) { allowActivityReplace.value = '0'; }
                        return;
                    }
                    if (!confirmedActivityReplace) {
                        if (allowActivityReplace) { allowActivityReplace.value = '0'; }
                        event.preventDefault();
                        openModal();
                        return;
                    }
                    confirmedActivityReplace = false;
                    if (allowActivityReplace) { allowActivityReplace.value = '1'; }
                });
            })();"
        );
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
    $createdcount = count($execution['created'] ?? []);
    $replacedcount = count($execution['replaced'] ?? []);
    $addedcount = $createdcount;
    $totaluploadedactivities = (int)($execution['totaluploadedactivities'] ?? 0);
    $skippeddisplay = $totaluploadedactivities > 0
        ? max(0, $totaluploadedactivities - $addedcount)
        : count($execution['skipped'] ?? []);

    echo $OUTPUT->heading(get_string('executedheading', 'local_sceh_importer'), 3);
    if (!empty($execution['blocked'])) {
        echo $OUTPUT->notification(get_string('executionblocked', 'local_sceh_importer'), core\output\notification::NOTIFY_WARNING);
    } else {
        echo $OUTPUT->notification(get_string('executionok', 'local_sceh_importer'), core\output\notification::NOTIFY_SUCCESS);
        echo html_writer::start_tag('div', [
            'id' => 'sceh-import-success-modal',
            'class' => 'sceh-import-modal',
            'hidden' => 'hidden',
            'aria-hidden' => 'true',
        ]);
        echo html_writer::tag('div', '', ['class' => 'sceh-import-modal__backdrop']);
        echo html_writer::start_tag('div', [
            'class' => 'sceh-import-modal__dialog',
            'role' => 'dialog',
            'aria-modal' => 'true',
            'aria-labelledby' => 'sceh-import-success-title',
        ]);
        echo html_writer::tag('h5', get_string('executionok_title', 'local_sceh_importer'), [
            'id' => 'sceh-import-success-title',
            'class' => 'mb-3',
        ]);
        echo html_writer::tag('p', get_string('executionok_body', 'local_sceh_importer'), ['class' => 'mb-3']);
        echo html_writer::tag(
            'p',
            get_string('executedadded', 'local_sceh_importer') . ': ' . $addedcount .
            ' | ' . get_string('executedskipped', 'local_sceh_importer') . ': ' . $skippeddisplay .
            ' | ' . get_string('executedreplaced', 'local_sceh_importer') . ': ' . $replacedcount,
            ['class' => 'text-muted mb-4']
        );
        echo html_writer::tag('button', get_string('executionok_cta', 'local_sceh_importer'), [
            'type' => 'button',
            'class' => 'btn btn-primary',
            'id' => 'sceh-import-success-close',
        ]);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

        $PAGE->requires->js_init_code(
            "(() => {
                const modal = document.getElementById('sceh-import-success-modal');
                const closeBtn = document.getElementById('sceh-import-success-close');
                if (!modal || !closeBtn) { return; }
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                closeBtn.focus();
                const close = () => {
                    modal.hidden = true;
                    modal.setAttribute('aria-hidden', 'true');
                };
                closeBtn.addEventListener('click', close);
                modal.addEventListener('click', (event) => {
                    if (event.target && event.target.classList && event.target.classList.contains('sceh-import-modal__backdrop')) {
                        close();
                    }
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.hidden) {
                        close();
                    }
                });
            })();"
        );
    }

    if (!empty($execution['warnings'])) {
        foreach ($execution['warnings'] as $warning) {
            echo $OUTPUT->notification($warning, core\output\notification::NOTIFY_WARNING);
        }
    }

    $executiontable = new html_table();
    $executiontable->head = [get_string('summary', 'local_sceh_importer'), get_string('importer', 'local_sceh_importer')];
    $executiontable->data[] = [get_string('executedadded', 'local_sceh_importer'), $addedcount];
    $executiontable->data[] = [get_string('executedskipped', 'local_sceh_importer'), $skippeddisplay];
    $executiontable->data[] = [get_string('executedreplaced', 'local_sceh_importer'), $replacedcount];
    $executiontable->data[] = [get_string('executedwarnings', 'local_sceh_importer'), count($execution['warnings'])];
    echo html_writer::table($executiontable);

    if (!empty($execution['created'])) {
        echo html_writer::tag('h5', get_string('executedadded', 'local_sceh_importer'));
        echo html_writer::alist($execution['created']);
    }
    if (!empty($execution['skipped'])) {
        echo html_writer::tag('h5', get_string('executedskipped', 'local_sceh_importer'));
        echo html_writer::alist($execution['skipped']);
    }
    if (!empty($execution['replaced'])) {
        echo html_writer::tag('h5', get_string('executedreplaced', 'local_sceh_importer'));
        echo html_writer::alist($execution['replaced']);
    }
}

echo $OUTPUT->footer();
