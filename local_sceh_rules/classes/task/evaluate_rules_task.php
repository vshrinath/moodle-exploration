<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Adhoc task for evaluating rules engine rules asynchronously.
 *
 * Receives {ruletype, courseid, userid} via set_custom_data().
 * Acquires a per-user lock to prevent duplicate evaluations from concurrent events.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\task;

use core\task\adhoc_task;
use local_sceh_rules\rules\attendance_rule;
use local_sceh_rules\rules\roster_rule;
use local_sceh_rules\helper\metrics_collector;

defined('MOODLE_INTERNAL') || die();

/**
 * Evaluates attendance or roster rules for a user, with locking.
 */
class evaluate_rules_task extends adhoc_task {

    /**
     * Get the task name for display.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_evaluate_rules', 'local_sceh_rules');
    }

    /**
     * Execute the task.
     *
     * Custom data must contain:
     * - ruletype: 'attendance' or 'roster'
     * - courseid: (for attendance) the course that triggered the event
     * - userid: (for roster) the user who completed the roster
     * - rostertype: (for roster) the type of roster completed
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();

        if (empty($data->ruletype)) {
            debugging('evaluate_rules_task: missing ruletype in custom_data', DEBUG_DEVELOPER);
            return;
        }

        $ruletype = $data->ruletype;
        $lockkey = $this->build_lock_key($data);

        // Acquire lock to prevent concurrent evaluation for the same scope.
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_sceh_rules');
        $lock = $lockfactory->get_lock($lockkey, 0); // Non-blocking: 0 timeout.

        if (!$lock) {
            // Another task is already evaluating this scope. Skip — the other task
            // will cover this evaluation. This is safe because rules are idempotent.
            mtrace("evaluate_rules_task: lock '{$lockkey}' unavailable, skipping (dedup).");
            return;
        }

        try {
            if ($ruletype === 'attendance') {
                $this->evaluate_attendance_rules($data);
            } else if ($ruletype === 'roster') {
                $this->evaluate_roster_rules($data);
            } else {
                debugging("evaluate_rules_task: unknown ruletype '{$ruletype}'", DEBUG_DEVELOPER);
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Evaluate all attendance rules for a course.
     *
     * @param \stdClass $data Task custom data with courseid.
     */
    protected function evaluate_attendance_rules(\stdClass $data) {
        global $DB;

        $courseid = (int) ($data->courseid ?? 0);
        if (!$courseid) {
            debugging('evaluate_rules_task: missing courseid for attendance evaluation', DEBUG_DEVELOPER);
            return;
        }

        $rules = $DB->get_records('local_sceh_attendance_rules', [
            'courseid' => $courseid,
            'enabled' => 1,
        ]);

        if (empty($rules)) {
            return;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return;
        }

        $enrolledusers = get_enrolled_users($context, '', 0, 'u.id');
        $evaluator = new attendance_rule();

        foreach ($rules as $rule) {
            foreach ($enrolledusers as $user) {
                $start = microtime(true);
                try {
                    $evaluator->evaluate($user->id, $rule);
                    $durationms = (microtime(true) - $start) * 1000;
                    metrics_collector::record_success('attendance', $rule->id, $durationms);
                } catch (\Exception $e) {
                    $durationms = (microtime(true) - $start) * 1000;
                    metrics_collector::record_failure('attendance', $rule->id, $e->getMessage(), $durationms);
                }
            }
        }
    }

    /**
     * Evaluate roster rules for a user and roster type.
     *
     * @param \stdClass $data Task custom data with userid and rostertype.
     */
    protected function evaluate_roster_rules(\stdClass $data) {
        $userid = (int) ($data->userid ?? 0);
        $rostertype = (string) ($data->rostertype ?? '');

        if (!$userid || $rostertype === '') {
            debugging('evaluate_rules_task: missing userid or rostertype for roster evaluation', DEBUG_DEVELOPER);
            return;
        }

        $evaluator = new roster_rule();
        $rules = $evaluator->get_rules_for_roster_type($rostertype);

        foreach ($rules as $rule) {
            $start = microtime(true);
            try {
                $evaluator->evaluate($userid, $rule);
                $durationms = (microtime(true) - $start) * 1000;
                metrics_collector::record_success('roster', $rule->id, $durationms);
            } catch (\Exception $e) {
                $durationms = (microtime(true) - $start) * 1000;
                metrics_collector::record_failure('roster', $rule->id, $e->getMessage(), $durationms);
            }
        }
    }

    /**
     * Build a lock key from the task data to prevent concurrent evaluation.
     *
     * @param \stdClass $data Task custom data.
     * @return string Lock key.
     */
    protected function build_lock_key(\stdClass $data): string {
        if ($data->ruletype === 'attendance') {
            return 'sceh_rules_attendance_course_' . (int) ($data->courseid ?? 0);
        }
        // Roster: lock per user + roster type.
        return 'sceh_rules_roster_' . (int) ($data->userid ?? 0) . '_' . ($data->rostertype ?? '');
    }
}
