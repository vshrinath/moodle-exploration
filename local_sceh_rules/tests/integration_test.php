<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Integration tests for rules engine with Moodle plugins
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules;

use local_sceh_rules\rules\attendance_rule;
use local_sceh_rules\rules\roster_rule;

defined('MOODLE_INTERNAL') || die();

/**
 * Integration tests for rules engine
 *
 * @group local_sceh_rules
 */
class integration_test extends \advanced_testcase {
    
    /**
     * Test rules engine integrates with competency framework
     */
    public function test_integration_with_competency_framework() {
        global $DB;
        
        $this->resetAfterTest(true);
        $this->setAdminUser();
        
        // Create competency framework
        $framework = $this->create_competency_framework();
        $competency = $this->create_competency($framework->id);
        
        // Verify competency exists
        $this->assertTrue($DB->record_exists('competency', ['id' => $competency->id]));
        
        // Create rule referencing this competency
        $course = $this->getDataGenerator()->create_course();
        $rule = $this->create_attendance_rule($competency->id, $course->id);
        
        // Verify rule references competency correctly
        $stored = $DB->get_record('local_sceh_attendance_rules', ['id' => $rule->id]);
        $this->assertEquals($competency->id, $stored->competencyid);
    }
    
    /**
     * Test rules engine with multiple plugins
     */
    public function test_multiple_plugin_integration() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        // Create test data
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_competency_framework();
        $competency1 = $this->create_competency($framework->id);
        $competency2 = $this->create_competency($framework->id);
        
        // Create attendance rule
        $attendanceRule = $this->create_attendance_rule($competency1->id, $course->id);
        
        // Create roster rule
        $rosterRule = $this->create_roster_rule('morning', $competency2->id);
        
        // Verify both rules exist
        $this->assertTrue($DB->record_exists('local_sceh_attendance_rules', ['id' => $attendanceRule->id]));
        $this->assertTrue($DB->record_exists('local_sceh_roster_rules', ['id' => $rosterRule->id]));
        
        // Verify rules can be retrieved
        $attendanceEvaluator = new attendance_rule();
        $rosterEvaluator = new roster_rule();
        
        $this->assertNotEmpty($attendanceEvaluator->get_active_rules());
        $this->assertNotEmpty($rosterEvaluator->get_active_rules());
    }
    
    /**
     * Test audit trail across different rule types
     */
    public function test_audit_trail_integration() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $framework = $this->create_competency_framework();
        $competency = $this->create_competency($framework->id);
        
        // Create rules
        $attendanceRule = $this->create_attendance_rule($competency->id, $course->id);
        $rosterRule = $this->create_roster_rule('morning', $competency->id);
        
        // Create audit entries for both rule types
        $audit1 = new \stdClass();
        $audit1->ruletype = 'attendance';
        $audit1->ruleid = $attendanceRule->id;
        $audit1->userid = $user->id;
        $audit1->action = 'blocked';
        $audit1->details = json_encode(['test' => 'data']);
        $audit1->timecreated = time();
        $DB->insert_record('local_sceh_rules_audit', $audit1);
        
        $audit2 = new \stdClass();
        $audit2->ruletype = 'roster';
        $audit2->ruleid = $rosterRule->id;
        $audit2->userid = $user->id;
        $audit2->action = 'evidence_awarded';
        $audit2->details = json_encode(['test' => 'data']);
        $audit2->timecreated = time();
        $DB->insert_record('local_sceh_rules_audit', $audit2);
        
        // Verify audit trail
        $auditRecords = $DB->get_records('local_sceh_rules_audit', ['userid' => $user->id]);
        $this->assertCount(2, $auditRecords);
        
        // Verify different rule types are logged
        $types = array_column(array_values($auditRecords), 'ruletype');
        $this->assertContains('attendance', $types);
        $this->assertContains('roster', $types);
    }
    
    /**
     * Test configuration settings integration
     */
    public function test_configuration_integration() {
        $this->resetAfterTest(true);
        
        // Test enabling/disabling rules
        set_config('attendance_rules_enabled', 1, 'local_sceh_rules');
        set_config('roster_rules_enabled', 1, 'local_sceh_rules');
        
        $this->assertEquals(1, get_config('local_sceh_rules', 'attendance_rules_enabled'));
        $this->assertEquals(1, get_config('local_sceh_rules', 'roster_rules_enabled'));
        
        // Test disabling
        set_config('attendance_rules_enabled', 0, 'local_sceh_rules');
        $this->assertEquals(0, get_config('local_sceh_rules', 'attendance_rules_enabled'));
    }
    
    /**
     * Test database schema integrity
     */
    public function test_database_schema_integrity() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        // Verify all required tables exist
        $dbman = $DB->get_manager();
        
        $this->assertTrue($dbman->table_exists('local_sceh_attendance_rules'));
        $this->assertTrue($dbman->table_exists('local_sceh_roster_rules'));
        $this->assertTrue($dbman->table_exists('local_sceh_rules_audit'));
    }
    
    // Helper methods
    
    protected function create_competency_framework() {
        global $DB;
        
        static $counter = 0;
        $counter++;
        
        $framework = new \stdClass();
        $framework->shortname = 'framework' . $counter;
        $framework->idnumber = 'FW' . $counter;
        $framework->contextid = \context_system::instance()->id;
        $framework->visible = 1;
        $framework->timecreated = time();
        $framework->timemodified = time();
        $framework->id = $DB->insert_record('competency_framework', $framework);
        
        return $framework;
    }
    
    protected function create_competency($frameworkid) {
        global $DB;
        
        static $counter = 0;
        $counter++;
        
        $competency = new \stdClass();
        $competency->shortname = 'comp' . $counter;
        $competency->idnumber = 'C' . $counter;
        $competency->competencyframeworkid = $frameworkid;
        $competency->timecreated = time();
        $competency->timemodified = time();
        $competency->id = $DB->insert_record('competency', $competency);
        
        return $competency;
    }
    
    protected function create_attendance_rule($competencyid, $courseid, $threshold = 75.0) {
        global $DB;
        
        $rule = new \stdClass();
        $rule->competencyid = $competencyid;
        $rule->courseid = $courseid;
        $rule->threshold = $threshold;
        $rule->enabled = 1;
        $rule->timecreated = time();
        $rule->timemodified = time();
        $rule->id = $DB->insert_record('local_sceh_attendance_rules', $rule);
        
        return $rule;
    }
    
    protected function create_roster_rule($rostertype, $competencyid) {
        global $DB;
        
        $rule = new \stdClass();
        $rule->rostertype = $rostertype;
        $rule->competencyid = $competencyid;
        $rule->evidencedesc = "Completed $rostertype roster";
        $rule->enabled = 1;
        $rule->timecreated = time();
        $rule->timemodified = time();
        $rule->id = $DB->insert_record('local_sceh_roster_rules', $rule);
        
        return $rule;
    }
}
