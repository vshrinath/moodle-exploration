<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Attendance rule evaluator
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\rules;

use local_sceh_rules\engine\rule_evaluator;

defined('MOODLE_INTERNAL') || die();

/**
 * Evaluates attendance-based competency locking rules
 */
class attendance_rule extends rule_evaluator {
    
    /**
     * Evaluate attendance rule for a user
     *
     * @param int $userid User ID to evaluate
     * @param object $rule Rule object with competencyid, courseid, threshold
     * @return bool True if attendance meets threshold, false otherwise
     */
    public function evaluate($userid, $rule) {
        global $DB;
        
        // Check if attendance rules are enabled
        if (!get_config('local_sceh_rules', 'attendance_rules_enabled')) {
            return true; // Allow access if rules disabled
        }
        
        // Get user's attendance percentage for this course
        $attendance = $this->get_user_attendance_percentage($userid, $rule->courseid);
        
        // Check if attendance meets threshold
        $passes = ($attendance >= $rule->threshold);
        
        // Log the evaluation
        $this->log_audit(
            'attendance',
            $rule->id,
            $userid,
            $passes ? 'allowed' : 'blocked',
            [
                'competencyid' => $rule->competencyid,
                'courseid' => $rule->courseid,
                'attendance' => $attendance,
                'threshold' => $rule->threshold
            ]
        );
        
        return $passes;
    }
    
    /**
     * Get all active attendance rules
     *
     * @return array Array of rule objects
     */
    public function get_active_rules() {
        global $DB;
        return $DB->get_records('local_sceh_attendance_rules', ['enabled' => 1]);
    }
    
    /**
     * Get rules for a specific competency
     *
     * @param int $competencyid Competency ID
     * @return array Array of rule objects
     */
    public function get_rules_for_competency($competencyid) {
        global $DB;
        return $DB->get_records('local_sceh_attendance_rules', [
            'competencyid' => $competencyid,
            'enabled' => 1
        ]);
    }
    
    /**
     * Calculate user's attendance percentage for a course
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return float Attendance percentage (0-100)
     */
    protected function get_user_attendance_percentage($userid, $courseid) {
        global $DB;
        
        // Get all attendance instances in this course
        $sql = "SELECT a.id
                FROM {attendance} a
                WHERE a.course = :courseid";
        $attendances = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        
        if (empty($attendances)) {
            return 100; // No attendance tracking, allow access
        }
        
        $totalSessions = 0;
        $attendedSessions = 0;
        
        foreach ($attendances as $attendance) {
            // Get all sessions for this attendance instance
            $sessions = $DB->get_records('attendance_sessions', ['attendanceid' => $attendance->id]);
            
            foreach ($sessions as $session) {
                // Only count sessions that have already occurred
                if ($session->sessdate > time()) {
                    continue;
                }
                
                $totalSessions++;
                
                // Check if user attended this session
                $log = $DB->get_record('attendance_log', [
                    'sessionid' => $session->id,
                    'studentid' => $userid
                ]);
                
                if ($log) {
                    // Get the status to check if it counts as present
                    $status = $DB->get_record('attendance_statuses', ['id' => $log->statusid]);
                    if ($status && $status->grade > 0) {
                        $attendedSessions++;
                    }
                }
            }
        }
        
        if ($totalSessions == 0) {
            return 100; // No sessions yet, allow access
        }
        
        return ($attendedSessions / $totalSessions) * 100;
    }
    
    /**
     * Check if user can access a competency based on attendance rules
     *
     * @param int $userid User ID
     * @param int $competencyid Competency ID
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function check_competency_access($userid, $competencyid) {
        $rules = $this->get_rules_for_competency($competencyid);
        
        if (empty($rules)) {
            return ['allowed' => true, 'message' => ''];
        }
        
        foreach ($rules as $rule) {
            if (!$this->evaluate($userid, $rule)) {
                $attendance = $this->get_user_attendance_percentage($userid, $rule->courseid);
                return [
                    'allowed' => false,
                    'message' => get_string('attendance_blocked', 'local_sceh_rules', [
                        'current' => round($attendance, 1),
                        'required' => $rule->threshold
                    ])
                ];
            }
        }
        
        return ['allowed' => true, 'message' => ''];
    }
}
