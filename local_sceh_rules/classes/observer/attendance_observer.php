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
        global $DB;
        
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
            global $SESSION;
            if (!isset($SESSION->local_sceh_rules_messages)) {
                $SESSION->local_sceh_rules_messages = [];
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
        
        // Get affected users from the event
        $courseid = $this->get_course_from_event($event);
        
        // Re-evaluate all attendance rules for this course
        $this->reevaluate_course_attendance($courseid);
    }
    
    /**
     * Re-evaluate attendance rules for all users in a course
     *
     * @param int $courseid Course ID
     * @return void
     */
    protected function reevaluate_course_attendance($courseid) {
        global $DB;
        
        // Get all attendance rules for this course
        $rules = $DB->get_records('local_sceh_attendance_rules', [
            'courseid' => $courseid,
            'enabled' => 1
        ]);
        
        if (empty($rules)) {
            return;
        }
        
        // Get all enrolled users in the course
        $context = \context_course::instance($courseid);
        $enrolledusers = get_enrolled_users($context, '', 0, 'u.id');
        
        $evaluator = new attendance_rule();
        
        foreach ($rules as $rule) {
            foreach ($enrolledusers as $user) {
                // Evaluate the rule for this user
                $evaluator->evaluate($user->id, $rule);
            }
        }
    }
}
