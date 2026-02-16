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
$string['importintro'] = 'Upload a course package zip and optional quiz CSV to generate and validate a draft manifest.';
$string['packagezip'] = 'Package zip file';
$string['packagezip_help'] = 'Upload one .zip containing assets, stream folders, lesson plans, roleplay docs, and quiz files.';
$string['quizsheet'] = 'Quiz spreadsheet (optional CSV)';
$string['quizsheet_help'] = 'Optional CSV for non-technical quiz authoring. Required columns: question_id, question_type, question_text, correct_option.';
$string['importmode'] = 'Import mode';
$string['importmode_assert'] = 'assert (strict, fail on conflict)';
$string['importmode_upsert'] = 'upsert (create or update by idnumber)';
$string['importmode_replace'] = 'replace (preview first, scoped replacement)';
$string['dryrun'] = 'Dry run';
$string['dryrun_desc'] = 'When enabled, only preview actions; do not execute imports.';
$string['changenote'] = 'Change note';
$string['previewheading'] = 'Draft manifest preview';
$string['summary'] = 'Summary';
$string['sections'] = 'Sections';
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
$string['privacy:metadata'] = 'The SCEH package importer plugin does not store personal data.';
