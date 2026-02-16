<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Settings for local_sceh_importer.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_sceh_importer', get_string('pluginname', 'local_sceh_importer'));

    $url = new moodle_url('/local/sceh_importer/index.php');
    $link = html_writer::link($url, get_string('importpage', 'local_sceh_importer'));
    $settings->add(new admin_setting_heading('local_sceh_importer/importlink', '', $link));

    $ADMIN->add('localplugins', $settings);
}
