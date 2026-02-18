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
$string['selectactivitiesreplacehelp'] = 'If you select an existing activity, the importer will archive it and create a new version (V2, V3, etc.).';
$string['confirmactivityreplace'] = 'This will archive the selected existing activities and import new versioned activities. Learners will see only the latest versions. Do you want to continue?';
$string['confirmactivityreplace_title'] = 'Replace existing activities?';
$string['confirmactivityreplace_cancel'] = 'Cancel';
$string['confirmactivityreplace_continue'] = 'Yes, replace and import';
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
$string['error_nozipfile'] = 'Please upload a ZIP file to continue.';
$string['error_zipopen'] = 'The ZIP file appears to be corrupted. Please try uploading it again.';
$string['error_invalidzip'] = 'Only ZIP files are supported. Please upload a .zip file.';
$string['error_extract'] = 'Could not extract the ZIP file. It may be corrupted or password-protected.';
$string['error_missingcolumn'] = 'Your quiz file is missing the "{$a}" column. Please check the template and try again.';
$string['error_csvopen'] = 'Could not read the quiz file. Please make sure it\'s a valid CSV.';
$string['error_replace_notsupported'] = 'Replace mode is not available yet. Please use "upsert" mode instead.';
$string['error_assert_conflict'] = 'An activity with ID "{$a}" already exists. Choose a different ID or use "upsert" mode.';
$string['error_missingactivityfile'] = 'The file for "{$a}" is missing from your ZIP. Please check and re-upload.';
$string['error_missingfilepath'] = 'Could not find the file: {$a}. Please check your ZIP contents.';
$string['error_selecttargetcourse'] = 'Please select the target course.';
$string['error_newcoursefullname'] = 'Please enter a name for the new course.';
$string['error_newcoursefullname_taken'] = 'A course with this name already exists. Please choose a different name.';
$string['error_programrequired'] = 'Please select an existing program or create a new one.';
$string['error_programname_required'] = 'Please enter a name for the new program.';
$string['error_programidnumber_taken'] = 'This Program ID is already in use. Please choose a different one.';
$string['error_programname_taken'] = 'This Program name is already in use. Please choose a different one.';
$string['error_importstate'] = 'Your upload session expired. Please upload the ZIP file again.';
$string['error_importvalidation'] = 'Please fix the errors above before importing.';
$string['error_importexpired'] = 'Your upload session expired. Please upload the ZIP file again.';
$string['error_noselectedactivities'] = 'Please select at least one activity to import.';
$string['error_activityreplaceconfirmrequired'] = 'Activity replacement was not confirmed. No changes were made for: {$a}.';
$string['executedheading'] = 'Execution result';
$string['executedcreated'] = 'Created';
$string['executedadded'] = 'Added';
$string['executedskipped'] = 'Skipped';
$string['executedreplaced'] = 'Replaced';
$string['executedwarnings'] = 'Execution warnings';
$string['executionblocked'] = 'Execution skipped: disable dry run to apply changes.';
$string['executionok'] = 'Import execution completed successfully.';
$string['executionok_title'] = 'Import complete';
$string['executionok_body'] = 'Your package was imported successfully.';
$string['executionok_cta'] = 'Continue';
$string['privacy:metadata'] = 'The SCEH package importer plugin does not store personal data.';
$string['selectall_new'] = 'Select all new';
$string['deselectall_existing'] = 'Deselect all existing';
$string['versioning_help'] = 'When you replace an existing activity, the old version is archived and a new version (V2, V3, etc.) is created. Learners will only see the latest version.';
$string['versioning_help_title'] = 'About activity versioning';
$string['learnerimpact_warning'] = 'Warning: {$a->count} learner(s) have already started this activity. Replacing it will archive their progress.';
$string['learnerimpact_details'] = 'View details';
$string['collapseall'] = 'Collapse all';
$string['expandall'] = 'Expand all';
$string['updatepage'] = 'Update course content';
$string['updateintro'] = 'Select a course to update its content with new files or bulk uploads.';
$string['updatefor'] = 'Update content for: {$a}';
$string['selectcourse'] = 'Select course';
$string['bulkupdate'] = 'Upload bulk update ZIP';
$string['individualupdate'] = 'Replace individual files';
$string['hidefilelist'] = 'Hide file list';
$string['coursestructure'] = 'Course structure';
$string['nocontent'] = 'This course has no content yet.';
$string['replace'] = 'Replace';
$string['replacefile'] = 'Replace activity file';
$string['replacefileintro'] = 'Upload a new file to replace: {$a}';
$string['replacementfile'] = 'Replacement file';
$string['preview'] = 'Preview';
$string['confirmreplacement'] = 'Confirm replacement';
$string['currentactivity'] = 'Current activity';
$string['newfile'] = 'New file';
$string['newidnumber'] = 'New ID number';
$string['replacementwarning'] = 'The existing activity will be archived and hidden. A new version will be created with the uploaded file. Learner progress on the old version will be preserved but not visible.';
$string['confirmreplace'] = 'Confirm and replace';
$string['replacesuccess'] = 'Successfully replaced "{$a->oldname}" with "{$a->newname}" (ID: {$a->newidnumber})';
$string['backtoupdate'] = 'Back to update page';
$string['error_archivefailed'] = 'Could not archive the existing activity. Please try again.';
$string['updateexisting'] = 'Update existing course';
$string['downloadtemplate'] = 'Download folder template';
$string['downloadtemplatehelp'] = 'Use this template to organize your files before zipping';
$string['showstructurehelp'] = 'Show supported folder structures';
$string['supportedstructures'] = 'Supported folder structures';
$string['structurehelp_intro'] = 'Your ZIP file can use either of these structures:';
$string['structurehelp_tips'] = 'Tips: Numbers control order. Only create folders you need. File names become activity titles.';
