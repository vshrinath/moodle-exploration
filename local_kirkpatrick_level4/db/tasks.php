<?php
/**
 * Scheduled tasks for Kirkpatrick Level 4 plugin
 *
 * @package    local_kirkpatrick_level4
 * @copyright  2025 Competency-Based Learning System
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_kirkpatrick_level4\task\sync_external_data',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    [
        'classname' => 'local_kirkpatrick_level4\task\calculate_roi',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '3',
        'day' => '1',
        'month' => '*',
        'dayofweek' => '*'
    ],
    [
        'classname' => 'local_kirkpatrick_level4\task\correlate_learner_outcomes',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '4',
        'day' => '1',
        'month' => '*',
        'dayofweek' => '*'
    ]
];
