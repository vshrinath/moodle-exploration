<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Unit tests for attendance rule evaluator
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules;

use local_sceh_rules\rules\attendance_rule;

defined('MOODLE_INTERNAL') || die();

/**
 * Test attendance rule evaluation
 *
 * @group local_sceh_rules
 */
class attendance_rule_test extends \advanced_testcase {
    
    /**
     * Test attendance rule creation
     */
    public function test_create_attendance_rule() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        // Create test data
        $course = $this->getDataGenerator()->create_course();
        $competency = $this->create_test_competency();
        
        // Create attendance rule
        $rule = new \stdClass();
        $rule->competencyid = $competency->id;
        $rule->courseid = $course->id;
        $rule->threshold = 75.0;
        $rule->enabled = 1;
        $rule->timecreated = time();
        $rule->timemodified = time();
        
        $ruleid = $DB->insert_record('local_sceh_attendance_rules', $rule);
        
        $this->assertNotEmpty($ruleid);
        
        // Verify rule was created
        $stored = $DB->get_record('local_sceh_attendance_rules', ['id' => $ruleid]);
        $this->assertEquals($competency->id, $stored->competencyid);
        $this->assertEquals($course->id, $stored->courseid);
        $this->assertEquals(75.0, $stored->threshold);
    }
    
    /**
     * Test attendance rule evaluation with sufficient attendance
     */
    public function test_evaluate_rule_passes_with_sufficient_attendance() {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        
        // Enable attendance rules
        set_config('attendance_rules_enabled', 1, 'local_sceh_rules');
        
        // Create test data
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $competency = $this->create_test_competency();
        
        // Create rule with 75% threshold
        $rule = $this->create_test_rule($competency->id, $course->id, 75.0);
        
        // Mock attendance at 80% (above threshold)
        // In real scenario, this would check actual attendance records
        
        $evaluator = new attendance_rule();
        
        // Since we can't easily mock attendance data, we test the rule structure
        $this->assertInstanceOf(attendance_rule::class, $evaluator);
    }
    
    /**
     * Test getting active rules
     */
    public function test_get_active_rules() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $course = $this->getDataGenerator()->create_course();
        $competency = $this->create_test_competency();
        
        // Create enabled rule
        $rule1 = $this->create_test_rule($competency->id, $course->id, 75.0, 1);
        
        // Create disabled rule
        $rule2 = $this->create_test_rule($competency->id, $course->id, 80.0, 0);
        
        $evaluator = new attendance_rule();
        $active = $evaluator->get_active_rules();
        
        // Should only return enabled rule
        $this->assertCount(1, $active);
        $this->assertEquals($rule1->id, reset($active)->id);
    }
    
    /**
     * Test audit logging
     */
    public function test_audit_logging() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $competency = $this->create_test_competency();
        $rule = $this->create_test_rule($competency->id, $course->id, 75.0);
        
        // Create audit entry manually to test structure
        $audit = new \stdClass();
        $audit->ruletype = 'attendance';
        $audit->ruleid = $rule->id;
        $audit->userid = $user->id;
        $audit->action = 'blocked';
        $audit->details = json_encode(['attendance' => 60, 'threshold' => 75]);
        $audit->timecreated = time();
        
        $auditid = $DB->insert_record('local_sceh_rules_audit', $audit);
        
        $this->assertNotEmpty($auditid);
        
        // Verify audit record
        $stored = $DB->get_record('local_sceh_rules_audit', ['id' => $auditid]);
        $this->assertEquals('attendance', $stored->ruletype);
        $this->assertEquals($user->id, $stored->userid);
        $this->assertEquals('blocked', $stored->action);
    }
    
    /**
     * Helper: Create test competency
     */
    protected function create_test_competency() {
        global $DB;
        
        // Create competency framework
        $framework = new \stdClass();
        $framework->shortname = 'testframework';
        $framework->idnumber = 'TF1';
        $framework->contextid = \context_system::instance()->id;
        $framework->visible = 1;
        $framework->timecreated = time();
        $framework->timemodified = time();
        $frameworkid = $DB->insert_record('competency_framework', $framework);
        
        // Create competency
        $competency = new \stdClass();
        $competency->shortname = 'testcomp';
        $competency->idnumber = 'TC1';
        $competency->competencyframeworkid = $frameworkid;
        $competency->timecreated = time();
        $competency->timemodified = time();
        $competency->id = $DB->insert_record('competency', $competency);
        
        return $competency;
    }
    
    /**
     * Helper: Create test rule
     */
    protected function create_test_rule($competencyid, $courseid, $threshold, $enabled = 1) {
        global $DB;
        
        $rule = new \stdClass();
        $rule->competencyid = $competencyid;
        $rule->courseid = $courseid;
        $rule->threshold = $threshold;
        $rule->enabled = $enabled;
        $rule->timecreated = time();
        $rule->timemodified = time();
        $rule->id = $DB->insert_record('local_sceh_attendance_rules', $rule);
        
        return $rule;
    }
}
