<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade steps for local_kirkpatrick_dashboard
 *
 * @package    local_kirkpatrick_dashboard
 * @copyright  2025 Competency-Based Learning System
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_kirkpatrick_dashboard plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool Always true.
 */
function xmldb_local_kirkpatrick_dashboard_upgrade($oldversion) {
    // Future schema migrations go here.
    // Example:
    // if ($oldversion < 2025060100) {
    //     $dbman = $DB->get_manager();
    //     // ... schema changes ...
    //     upgrade_plugin_savepoint(true, 2025060100, 'local', 'kirkpatrick_dashboard');
    // }

    return true;
}
