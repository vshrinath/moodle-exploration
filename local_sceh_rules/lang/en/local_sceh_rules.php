<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Language strings for local_sceh_rules plugin
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'SCEH Rules Engine';
$string['privacy:metadata'] = 'The SCEH Rules Engine plugin does not store any personal data.';

// Settings
$string['settings_header'] = 'Rules Engine Configuration';
$string['enable_attendance_rules'] = 'Enable Attendance Rules';
$string['enable_attendance_rules_desc'] = 'Enable automatic competency locking based on attendance thresholds';
$string['enable_roster_rules'] = 'Enable Roster Rules';
$string['enable_roster_rules_desc'] = 'Enable automatic competency progression based on roster completion';

// Attendance rules
$string['attendance_rules'] = 'Attendance Rules';
$string['attendance_rule_add'] = 'Add Attendance Rule';
$string['attendance_rule_competency'] = 'Competency';
$string['attendance_rule_threshold'] = 'Minimum Attendance (%)';
$string['attendance_rule_threshold_desc'] = 'Minimum attendance percentage required to unlock this competency';
$string['attendance_rule_saved'] = 'Attendance rule saved successfully';
$string['attendance_rule_deleted'] = 'Attendance rule deleted successfully';
$string['attendance_blocked'] = 'This competency is locked due to insufficient attendance ({$a->current}% of {$a->required}% required)';

// Roster rules
$string['roster_rules'] = 'Roster Rules';
$string['roster_rule_add'] = 'Add Roster Rule';
$string['roster_rule_type'] = 'Roster Type';
$string['roster_rule_competency'] = 'Target Competency';
$string['roster_rule_evidence'] = 'Evidence Description';
$string['roster_rule_saved'] = 'Roster rule saved successfully';
$string['roster_rule_deleted'] = 'Roster rule deleted successfully';
$string['roster_evidence_auto'] = 'Automatically awarded for completing {$a->rostertype} roster';

// Roster types
$string['roster_type_morning'] = 'Morning Class';
$string['roster_type_night'] = 'Night Duty';
$string['roster_type_training'] = 'Training OT';
$string['roster_type_satellite'] = 'Satellite Visits';
$string['roster_type_posting'] = 'Postings Schedule';

// Audit trail
$string['audit_log'] = 'Rules Engine Audit Log';
$string['audit_rule_type'] = 'Rule Type';
$string['audit_action'] = 'Action';
$string['audit_user'] = 'User';
$string['audit_timestamp'] = 'Timestamp';
$string['audit_details'] = 'Details';

// Capabilities
$string['sceh_rules:systemadmin'] = 'Use system admin dashboard view';
$string['sceh_rules:programowner'] = 'Use program owner dashboard view';
$string['sceh_rules:trainer'] = 'Use trainer dashboard view';
$string['sceh_rules:viewassignedcohortsonly'] = 'View only assigned cohorts';
$string['sceh_rules:managerules'] = 'Manage rules engine configuration';
$string['sceh_rules:viewaudit'] = 'View rules engine audit log';

// Additional strings
$string['norulesfound'] = 'No rules configured yet';
$string['noauditrecords'] = 'No audit records found';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['confirmruledeletion'] = 'Are you sure you want to delete this rule?';
$string['error_threshold_range'] = 'Threshold must be between 0 and 100';
$string['streamprogress'] = 'Stream Progress';
$string['streamprogress_course'] = 'Course: {$a}';
$string['streamprogress_selected_stream'] = 'Selected stream: {$a}';
$string['streamprogress_no_stream'] = 'No stream selected yet. Showing Common Foundation only.';
$string['streamprogress_no_courses'] = 'No enrolled courses found.';
$string['streamprogress_no_activities'] = 'No visible activities in this section.';
$string['streamprogress_activity'] = 'Activity';
$string['streamprogress_type'] = 'Type';
$string['streamprogress_status'] = 'Status';
$string['streamprogress_status_complete'] = 'Complete';
$string['streamprogress_status_incomplete'] = 'Incomplete';
$string['streamprogress_status_nottracked'] = 'Not tracked';
$string['streamsetupcheck'] = 'Stream Setup Check';
$string['streamsetupcheck_heading'] = 'Program stream setup checklist';
$string['streamsetupcheck_course'] = 'Course: {$a}';
$string['streamsetupcheck_no_courses'] = 'No accessible program-owner courses found.';
$string['streamsetupcheck_item'] = 'Checklist item';
$string['streamsetupcheck_result'] = 'Result';
$string['streamsetupcheck_details'] = 'Details';
$string['streamsetupcheck_pass'] = 'Pass';
$string['streamsetupcheck_fail'] = 'Fail';
$string['streamsetupcheck_common'] = 'Named Common Foundation section exists';
$string['streamsetupcheck_streamsections'] = 'At least one stream section exists (`STREAM - ...`)';
$string['streamsetupcheck_choice'] = 'Stream Choice activity exists with options';
$string['streamsetupcheck_detail_common_pass'] = 'Found section(s) with name starting `Common`.';
$string['streamsetupcheck_detail_common_fail'] = 'No section name starts with `Common` (for example: `Common Foundation`).';
$string['streamsetupcheck_detail_stream_pass'] = 'Found {$a} stream section(s).';
$string['streamsetupcheck_detail_stream_fail'] = 'No stream section found. Use names like `STREAM - Front Desk Management`.';
$string['streamsetupcheck_detail_choice_pass'] = 'Choice "{$a->name}" has {$a->count} option(s).';
$string['streamsetupcheck_detail_choice_fail'] = 'No stream/specialization Choice with options found.';
$string['streamsetupcheck_action_fix'] = 'Fix in Course';
$string['streamsetupcheck_action_view'] = 'View Course';
$string['streamsetupcheck_action_add_choice'] = 'Add Choice Activity';
$string['streamsetupcheck_summary_issues'] = 'This course has setup issues that need your attention.';
$string['streamsetupcheck_edit_course'] = 'Edit "{$a}"';
