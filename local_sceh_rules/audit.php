<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Audit log viewer for rules engine
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_sceh_rules');

$context = context_system::instance();
require_capability('local/sceh_rules:viewaudit', $context);

$PAGE->set_url('/local/sceh_rules/audit.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('audit_log', 'local_sceh_rules'));
$PAGE->set_heading(get_string('audit_log', 'local_sceh_rules'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('audit_log', 'local_sceh_rules'));

// Get audit records
$records = $DB->get_records('local_sceh_rules_audit', null, 'timecreated DESC', '*', 0, 100);

if (empty($records)) {
    echo html_writer::tag('p', get_string('noauditrecords', 'local_sceh_rules'));
} else {
    $table = new html_table();
    $table->head = [
        get_string('audit_timestamp', 'local_sceh_rules'),
        get_string('audit_rule_type', 'local_sceh_rules'),
        get_string('audit_user', 'local_sceh_rules'),
        get_string('audit_action', 'local_sceh_rules'),
        get_string('audit_details', 'local_sceh_rules'),
    ];
    
    foreach ($records as $record) {
        $user = $DB->get_record('user', ['id' => $record->userid]);
        $username = $user ? fullname($user) : 'N/A';
        
        $details = json_decode($record->details, true);
        $detailstext = '';
        if (is_array($details)) {
            foreach ($details as $key => $value) {
                $detailstext .= html_writer::tag('div', "$key: $value");
            }
        }
        
        $table->data[] = [
            userdate($record->timecreated),
            $record->ruletype,
            $username,
            $record->action,
            $detailstext
        ];
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
