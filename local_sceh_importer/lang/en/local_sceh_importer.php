<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Language strings for local_sceh_importer.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'SCEH package importer';
$string['importer'] = 'SCEH package importer';
$string['manage'] = 'Manage package imports';
$string['importpage'] = 'Package import preview';
$string['importintro'] = 'Select or create a program and course, then upload a package zip to generate and validate a draft manifest.';
$string['validatezip'] = 'Validate ZIP file';
$string['validationheading'] = 'Validation result';
$string['validationok'] = 'ZIP file is valid and ready to import.';
$string['validationfail'] = 'ZIP file has issues that must be fixed before import.';
$string['showdebug'] = 'Show debug details';
$string['selectactivitiesheading'] = 'Select activities to import';
$string['selectactivitieshelp'] = 'New activities are pre-selected. Existing activities are unselected by default so you can choose replacements explicitly.';
$string['status'] = 'Status';
$string['status_new'] = 'New';
$string['status_existing'] = 'Existing';
$string['targetcourse'] = 'Target course';
$string['selecttargetcourse'] = 'Select target course...';
$string['programmode'] = 'Program action';
$string['programmode_existing'] = 'Use existing program';
$string['programmode_new'] = 'Create new program';
$string['programselect'] = 'Program (select existing)';
$string['programnew'] = 'Create new program (enter below)';
$string['coursemode'] = 'Course action';
$string['coursemode_existing'] = 'Use existing course';
$string['coursemode_new'] = 'Create new course';
$string['programidnumber'] = 'Program ID number';
$string['programname'] = 'Program name';
$string['newcoursefullname'] = 'New course full name';
$string['packagezip'] = 'Package zip file';
$string['packagezip_help'] = 'Upload one .zip containing assets, stream folders, lesson plans, roleplay docs, and quiz files.';
$string['importmode'] = 'Import mode';
$string['importmode_assert'] = 'assert (strict, fail on conflict)';
$string['importmode_upsert'] = 'upsert (create or update by idnumber)';
$string['importmode_replace'] = 'replace (preview first, scoped replacement)';
$string['dryrun'] = 'Dry run';
$string['dryrun_desc'] = 'When enabled, only preview actions; do not execute imports.';
$string['executeimport'] = 'Execute import now';
$string['executeimport_desc'] = 'Apply the import now. If unchecked, only preview is generated.';
$string['importbutton'] = 'Import';
$string['importdisabled'] = 'Import';
$string['importmustvalidate'] = 'Import is disabled until all validation errors are fixed.';
$string['changenote'] = 'Change note';
$string['previewheading'] = 'Draft manifest preview';
$string['summary'] = 'Summary';
$string['sections'] = 'Sections';
$string['topics'] = 'Topics';
$string['activities'] = 'Activities';
$string['warnings'] = 'Warnings';
$string['errors'] = 'Errors';
$string['manifestyaml'] = 'Manifest YAML';
$string['empty'] = '(none)';
$string['error_nozipfile'] = 'No package zip file found in upload.';
$string['error_zipopen'] = 'Unable to open uploaded zip file.';
$string['error_invalidzip'] = 'Uploaded file must be a .zip package.';
$string['error_extract'] = 'Unable to extract zip package.';
$string['error_missingcolumn'] = 'Quiz CSV is missing required column: {$a}';
$string['error_csvopen'] = 'Unable to open quiz CSV file.';
$string['error_replace_notsupported'] = 'Replace mode execution is not supported yet.';
$string['error_assert_conflict'] = 'Import conflict: activity already exists for idnumber {$a}.';
$string['error_missingactivityfile'] = 'Activity file is required for {$a}.';
$string['error_missingfilepath'] = 'Referenced package file is not readable: {$a}.';
$string['error_selecttargetcourse'] = 'Please select the target course.';
$string['error_newcoursefullname'] = 'New course full name is required.';
$string['error_newcoursefullname_taken'] = 'Course name is already taken. Please choose something else.';
$string['error_programrequired'] = 'Select an existing program or enter a new Program ID number.';
$string['error_programname_required'] = 'Program name is required when creating a new program.';
$string['error_programidnumber_taken'] = 'Program ID number is already taken. Please choose something else.';
$string['error_programname_taken'] = 'Program name is already taken. Please choose something else.';
$string['error_importstate'] = 'No validated package is available. Validate the ZIP first.';
$string['error_importvalidation'] = 'Import is blocked because validation errors still exist.';
$string['error_importexpired'] = 'Validated package data expired. Validate the ZIP again.';
$string['error_noselectedactivities'] = 'No activities were selected for import.';
$string['executedheading'] = 'Execution result';
$string['executedcreated'] = 'Created';
$string['executedskipped'] = 'Skipped';
$string['executedwarnings'] = 'Execution warnings';
$string['executionblocked'] = 'Execution skipped: disable dry run to apply changes.';
$string['executionok'] = 'Import execution completed successfully.';
$string['privacy:metadata'] = 'The SCEH package importer plugin does not store personal data.';
