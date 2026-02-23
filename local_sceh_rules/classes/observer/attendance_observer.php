<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Attendance event observer
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\observer;

use local_sceh_rules\engine\event_handler;
use local_sceh_rules\rules\attendance_rule;
use local_sceh_rules\task\evaluate_rules_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer for attendance-related events
 */
class attendance_observer extends event_handler {
    
    /**
     * Handle attendance taken event
     *
     * @param \core\event\base $event The attendance taken event
     * @return void
     */
    public static function attendance_taken(\core\event\base $event) {
        $observer = new self();
        $observer->handle($event);
    }
    
    /**
     * Check attendance requirements when user views competency
     *
     * @param \core\event\base $event The competency viewed event
     * @return void
     */
    public static function check_attendance_requirements(\core\event\base $event) {
        global $DB, $SESSION;
        
        if (!get_config('local_sceh_rules', 'attendance_rules_enabled')) {
            return;
        }
        
        // Get competency ID from event
        $competencyid = $event->other['competencyid'] ?? null;
        if (!$competencyid) {
            return;
        }
        
        $userid = $event->userid;
        
        // Check attendance rules for this competency
        $evaluator = new attendance_rule();
        $result = $evaluator->check_competency_access($userid, $competencyid);
        
        if (!$result['allowed']) {
            // Store the block message in session for display
            if (!isset($SESSION->local_sceh_rules_messages)) {
                $SESSION->local_sceh_rules_messages = [];
            }
            
            // Limit to last 10 messages to prevent session bloat
            if (count($SESSION->local_sceh_rules_messages) >= 10) {
                array_shift($SESSION->local_sceh_rules_messages);
            }
            
            $SESSION->local_sceh_rules_messages[] = $result['message'];
        }
    }
    
    /**
     * Handle the event
     *
     * @param \core\event\base $event The event to handle
     * @return void
     */
    public function handle(\core\event\base $event) {
        if (!$this->should_process($event)) {
            return;
        }
        
        $courseid = $this->get_course_from_event($event);
        
        // Queue async evaluation instead of blocking the web request.
        $this->queue_attendance_evaluation($courseid);
    }
    
    /**
     * Queue an adhoc task to evaluate attendance rules for a course.
     *
     * Replaces the previous synchronous O(rules × enrolled_users) loop.
     *
     * @param int $courseid Course ID
     * @return void
     */
    protected function queue_attendance_evaluation($courseid) {
        $task = new evaluate_rules_task();
        $task->set_custom_data((object) [
            'ruletype' => 'attendance',
            'courseid' => $courseid,
        ]);
        // set_component ensures the task is tracked against this plugin.
        $task->set_component('local_sceh_rules');
        \core\task\manager::queue_adhoc_task($task, true); // true = deduplicate.
    }
}
