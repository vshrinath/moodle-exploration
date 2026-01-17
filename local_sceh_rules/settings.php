<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Settings for local_sceh_rules plugin
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_sceh_rules', get_string('pluginname', 'local_sceh_rules'));
    
    // Enable/disable rules engine
    $settings->add(new admin_setting_configcheckbox(
        'local_sceh_rules/enabled',
        get_string('enable_attendance_rules', 'local_sceh_rules'),
        get_string('enable_attendance_rules_desc', 'local_sceh_rules'),
        1
    ));
    
    // Enable attendance rules
    $settings->add(new admin_setting_configcheckbox(
        'local_sceh_rules/attendance_rules_enabled',
        get_string('enable_attendance_rules', 'local_sceh_rules'),
        get_string('enable_attendance_rules_desc', 'local_sceh_rules'),
        1
    ));
    
    // Enable roster rules
    $settings->add(new admin_setting_configcheckbox(
        'local_sceh_rules/roster_rules_enabled',
        get_string('enable_roster_rules', 'local_sceh_rules'),
        get_string('enable_roster_rules_desc', 'local_sceh_rules'),
        1
    ));
    
    // Link to attendance rules management
    $url = new moodle_url('/local/sceh_rules/attendance_rules.php');
    $link = html_writer::link($url, get_string('attendance_rules', 'local_sceh_rules'));
    $settings->add(new admin_setting_heading(
        'local_sceh_rules/attendance_rules_link',
        '',
        $link
    ));
    
    // Link to roster rules management
    $url = new moodle_url('/local/sceh_rules/roster_rules.php');
    $link = html_writer::link($url, get_string('roster_rules', 'local_sceh_rules'));
    $settings->add(new admin_setting_heading(
        'local_sceh_rules/roster_rules_link',
        '',
        $link
    ));
    
    // Link to audit log
    $url = new moodle_url('/local/sceh_rules/audit.php');
    $link = html_writer::link($url, get_string('audit_log', 'local_sceh_rules'));
    $settings->add(new admin_setting_heading(
        'local_sceh_rules/audit_log_link',
        '',
        $link
    ));
    
    $ADMIN->add('localplugins', $settings);
}
