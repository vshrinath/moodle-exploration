<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Event observers for local_sceh_rules plugin
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_attendance\event\attendance_taken',
        'callback' => '\local_sceh_rules\observer\attendance_observer::attendance_taken',
    ],
    [
        'eventname' => '\core\event\user_competency_viewed',
        'callback' => '\local_sceh_rules\observer\attendance_observer::check_attendance_requirements',
    ],
    [
        'eventname' => '\mod_scheduler\event\appointment_added',
        'callback' => '\local_sceh_rules\observer\roster_observer::roster_completed',
    ],
    [
        'eventname' => '\core\event\role_assigned',
        'callback' => '\local_sceh_rules\observer\program_owner_role_observer::role_assigned',
    ],
    [
        'eventname' => '\core\event\role_unassigned',
        'callback' => '\local_sceh_rules\observer\program_owner_role_observer::role_unassigned',
    ],
];
