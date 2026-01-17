<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Base rule evaluator class for the rules engine
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\engine;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract base class for rule evaluation
 */
abstract class rule_evaluator {
    
    /**
     * Evaluate a rule for a specific user
     *
     * @param int $userid User ID to evaluate
     * @param object $rule Rule object to evaluate
     * @return bool True if rule passes, false otherwise
     */
    abstract public function evaluate($userid, $rule);
    
    /**
     * Get all active rules of this type
     *
     * @return array Array of rule objects
     */
    abstract public function get_active_rules();
    
    /**
     * Log an audit entry for rule evaluation
     *
     * @param string $ruletype Type of rule (attendance, roster)
     * @param int $ruleid ID of the rule
     * @param int $userid User affected
     * @param string $action Action taken
     * @param array $details Additional details
     * @return bool Success status
     */
    protected function log_audit($ruletype, $ruleid, $userid, $action, $details = []) {
        global $DB;
        
        $record = new \stdClass();
        $record->ruletype = $ruletype;
        $record->ruleid = $ruleid;
        $record->userid = $userid;
        $record->action = $action;
        $record->details = json_encode($details);
        $record->timecreated = time();
        
        return $DB->insert_record('local_sceh_rules_audit', $record);
    }
    
    /**
     * Check if rules engine is enabled
     *
     * @return bool True if enabled
     */
    protected function is_enabled() {
        return get_config('local_sceh_rules', 'enabled');
    }
}
