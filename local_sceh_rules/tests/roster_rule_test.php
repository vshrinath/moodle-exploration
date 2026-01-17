<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Unit tests for roster rule evaluator
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules;

use local_sceh_rules\rules\roster_rule;

defined('MOODLE_INTERNAL') || die();

/**
 * Test roster rule evaluation
 *
 * @group local_sceh_rules
 */
class roster_rule_test extends \advanced_testcase {
    
    /**
     * Test roster rule creation
     */
    public function test_create_roster_rule() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $competency = $this->create_test_competency();
        
        // Create roster rule
        $rule = new \stdClass();
        $rule->rostertype = 'morning';
        $rule->competencyid = $competency->id;
        $rule->evidencedesc = 'Completed morning class roster';
        $rule->enabled = 1;
        $rule->timecreated = time();
        $rule->timemodified = time();
        
        $ruleid = $DB->insert_record('local_sceh_roster_rules', $rule);
        
        $this->assertNotEmpty($ruleid);
        
        // Verify rule was created
        $stored = $DB->get_record('local_sceh_roster_rules', ['id' => $ruleid]);
        $this->assertEquals('morning', $stored->rostertype);
        $this->assertEquals($competency->id, $stored->competencyid);
        $this->assertEquals('Completed morning class roster', $stored->evidencedesc);
    }
    
    /**
     * Test getting rules for roster type
     */
    public function test_get_rules_for_roster_type() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $competency1 = $this->create_test_competency();
        $competency2 = $this->create_test_competency();
        
        // Create rules for different roster types
        $rule1 = $this->create_test_roster_rule('morning', $competency1->id);
        $rule2 = $this->create_test_roster_rule('night', $competency2->id);
        $rule3 = $this->create_test_roster_rule('morning', $competency2->id);
        
        $evaluator = new roster_rule();
        $morningRules = $evaluator->get_rules_for_roster_type('morning');
        
        // Should return 2 morning rules
        $this->assertCount(2, $morningRules);
        
        $nightRules = $evaluator->get_rules_for_roster_type('night');
        $this->assertCount(1, $nightRules);
    }
    
    /**
     * Test all roster types are supported
     */
    public function test_all_roster_types_supported() {
        $this->resetAfterTest(true);
        
        $competency = $this->create_test_competency();
        
        $rostertypes = ['morning', 'night', 'training', 'satellite', 'posting'];
        
        foreach ($rostertypes as $type) {
            $rule = $this->create_test_roster_rule($type, $competency->id);
            $this->assertEquals($type, $rule->rostertype);
        }
    }
    
    /**
     * Test getting active rules
     */
    public function test_get_active_rules() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $competency = $this->create_test_competency();
        
        // Create enabled rule
        $rule1 = $this->create_test_roster_rule('morning', $competency->id, 1);
        
        // Create disabled rule
        $rule2 = $this->create_test_roster_rule('night', $competency->id, 0);
        
        $evaluator = new roster_rule();
        $active = $evaluator->get_active_rules();
        
        // Should only return enabled rule
        $this->assertCount(1, $active);
        $this->assertEquals($rule1->id, reset($active)->id);
    }
    
    /**
     * Test audit logging for roster rules
     */
    public function test_roster_audit_logging() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $user = $this->getDataGenerator()->create_user();
        $competency = $this->create_test_competency();
        $rule = $this->create_test_roster_rule('morning', $competency->id);
        
        // Create audit entry
        $audit = new \stdClass();
        $audit->ruletype = 'roster';
        $audit->ruleid = $rule->id;
        $audit->userid = $user->id;
        $audit->action = 'evidence_awarded';
        $audit->details = json_encode([
            'competencyid' => $competency->id,
            'rostertype' => 'morning'
        ]);
        $audit->timecreated = time();
        
        $auditid = $DB->insert_record('local_sceh_rules_audit', $audit);
        
        $this->assertNotEmpty($auditid);
        
        // Verify audit record
        $stored = $DB->get_record('local_sceh_rules_audit', ['id' => $auditid]);
        $this->assertEquals('roster', $stored->ruletype);
        $this->assertEquals($user->id, $stored->userid);
        $this->assertEquals('evidence_awarded', $stored->action);
    }
    
    /**
     * Test unique constraint on roster type and competency
     */
    public function test_unique_roster_competency_constraint() {
        global $DB;
        
        $this->resetAfterTest(true);
        
        $competency = $this->create_test_competency();
        
        // Create first rule
        $rule1 = $this->create_test_roster_rule('morning', $competency->id);
        
        // Try to create duplicate - should fail due to unique index
        $this->expectException(\dml_write_exception::class);
        $rule2 = $this->create_test_roster_rule('morning', $competency->id);
    }
    
    /**
     * Helper: Create test competency
     */
    protected function create_test_competency() {
        global $DB;
        
        static $counter = 0;
        $counter++;
        
        // Create competency framework
        $framework = new \stdClass();
        $framework->shortname = 'testframework' . $counter;
        $framework->idnumber = 'TF' . $counter;
        $framework->contextid = \context_system::instance()->id;
        $framework->visible = 1;
        $framework->timecreated = time();
        $framework->timemodified = time();
        $frameworkid = $DB->insert_record('competency_framework', $framework);
        
        // Create competency
        $competency = new \stdClass();
        $competency->shortname = 'testcomp' . $counter;
        $competency->idnumber = 'TC' . $counter;
        $competency->competencyframeworkid = $frameworkid;
        $competency->timecreated = time();
        $competency->timemodified = time();
        $competency->id = $DB->insert_record('competency', $competency);
        
        return $competency;
    }
    
    /**
     * Helper: Create test roster rule
     */
    protected function create_test_roster_rule($rostertype, $competencyid, $enabled = 1) {
        global $DB;
        
        $rule = new \stdClass();
        $rule->rostertype = $rostertype;
        $rule->competencyid = $competencyid;
        $rule->evidencedesc = "Completed $rostertype roster";
        $rule->enabled = $enabled;
        $rule->timecreated = time();
        $rule->timemodified = time();
        $rule->id = $DB->insert_record('local_sceh_roster_rules', $rule);
        
        return $rule;
    }
}
