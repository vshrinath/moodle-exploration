<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Roster rule evaluator
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\rules;

use local_sceh_rules\engine\rule_evaluator;
use core_competency\api;
use core_competency\user_competency;

defined('MOODLE_INTERNAL') || die();

/**
 * Evaluates roster-to-competency progression rules
 */
class roster_rule extends rule_evaluator {
    
    /**
     * Evaluate roster rule and award competency if applicable
     *
     * @param int $userid User ID to evaluate
     * @param object $rule Rule object with rostertype, competencyid, evidencedesc
     * @return bool True if rule was applied successfully
     */
    public function evaluate($userid, $rule) {
        global $DB;
        
        // Check if roster rules are enabled
        if (!get_config('local_sceh_rules', 'roster_rules_enabled')) {
            return false;
        }
        
        try {
            // Check if user already has this competency
            $usercompetency = user_competency::get_record([
                'userid' => $userid,
                'competencyid' => $rule->competencyid
            ]);
            
            if ($usercompetency && $usercompetency->get('proficiency')) {
                // User already proficient, no need to award again
                $this->log_audit(
                    'roster',
                    $rule->id,
                    $userid,
                    'already_proficient',
                    [
                        'competencyid' => $rule->competencyid,
                        'rostertype' => $rule->rostertype
                    ]
                );
                return true;
            }
            
            // Award competency evidence
            $this->award_competency_evidence($userid, $rule);
            
            // Log the award
            $this->log_audit(
                'roster',
                $rule->id,
                $userid,
                'evidence_awarded',
                [
                    'competencyid' => $rule->competencyid,
                    'rostertype' => $rule->rostertype,
                    'evidence' => $rule->evidencedesc
                ]
            );
            
            return true;
            
        } catch (\Exception $e) {
            // Log error
            $this->log_audit(
                'roster',
                $rule->id,
                $userid,
                'error',
                [
                    'competencyid' => $rule->competencyid,
                    'rostertype' => $rule->rostertype,
                    'error' => $e->getMessage()
                ]
            );
            return false;
        }
    }
    
    /**
     * Get all active roster rules
     *
     * @return array Array of rule objects
     */
    public function get_active_rules() {
        global $DB;
        return $DB->get_records('local_sceh_roster_rules', ['enabled' => 1]);
    }
    
    /**
     * Get rules for a specific roster type
     *
     * @param string $rostertype Roster type
     * @return array Array of rule objects
     */
    public function get_rules_for_roster_type($rostertype) {
        global $DB;
        return $DB->get_records('local_sceh_roster_rules', [
            'rostertype' => $rostertype,
            'enabled' => 1
        ]);
    }
    
    /**
     * Award competency evidence to a user
     *
     * @param int $userid User ID
     * @param object $rule Rule object
     * @return void
     */
    protected function award_competency_evidence($userid, $rule) {
        global $USER;
        
        // Get or create user competency record
        $usercompetency = user_competency::get_record([
            'userid' => $userid,
            'competencyid' => $rule->competencyid
        ]);
        
        if (!$usercompetency) {
            // Create new user competency
            $usercompetency = user_competency::create_relation($userid, $rule->competencyid);
        }
        
        // Add evidence
        $evidence = api::add_evidence(
            $userid,
            $rule->competencyid,
            \context_system::instance(),
            \core_competency\evidence::ACTION_COMPLETE,
            'local_sceh_rules',
            'roster_completion',
            $rule->evidencedesc,
            false,
            null,
            null,
            $USER->id
        );
        
        // Check if this evidence makes the user proficient
        $competency = api::read_competency($rule->competencyid);
        if ($competency) {
            // Trigger competency framework to re-evaluate proficiency
            api::user_competency_viewed($usercompetency);
        }
    }
    
    /**
     * Process roster completion for a user
     *
     * @param int $userid User ID
     * @param string $rostertype Type of roster completed
     * @return array Results of processing
     */
    public function process_roster_completion($userid, $rostertype) {
        $rules = $this->get_rules_for_roster_type($rostertype);
        $results = [];
        
        foreach ($rules as $rule) {
            $results[$rule->id] = $this->evaluate($userid, $rule);
        }
        
        return $results;
    }
}
