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
$string['sceh_rules:managerules'] = 'Manage rules engine configuration';
$string['sceh_rules:viewaudit'] = 'View rules engine audit log';

// Additional strings
$string['norulesfound'] = 'No rules configured yet';
$string['noauditrecords'] = 'No audit records found';
$string['enabled'] = 'Enabled';
$string['error_threshold_range'] = 'Threshold must be between 0 and 100';
