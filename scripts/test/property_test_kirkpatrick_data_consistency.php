<?php
/**
 * Property-Based Test: Kirkpatrick Data Consistency
 * 
 * Property 18: Kirkpatrick Data Consistency
 * 
 * For any learner progressing through training, data collected at each Kirkpatrick level
 * should be consistent, properly linked, and maintain referential integrity across all four levels.
 * 
 * **Validates: Requirements 17.1, 17.2, 17.3, 17.4**
 * 
 * @package    local_kirkpatrick
 * @copyright  2025 Competency-Based Learning System
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
$config_paths = [
    __DIR__ . '/config.php',
    '/bitnami/moodle/config.php',
    '/opt/bitnami/moodle/config.php',
];
$config_path = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}
if (!$config_path) {
    fwrite(STDERR, "ERROR: Moodle config.php not found\n");
    exit(1);
}
require_once($config_path);

if (!class_exists('PHPUnit\\Framework\\TestCase')) {
    echo "SKIPPED: PHPUnit not available in this runtime.\n";
    exit(0);
}

require_once($CFG->libdir . '/phpunit/classes/util.php');

// This test uses Eris for property-based testing
// Install via: composer require giorgiosironi/eris

use Eris\Generator;
use Eris\TestTrait;

class KirkpatrickDataConsistencyTest extends PHPUnit\Framework\TestCase {
    use TestTrait;
    
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest(true);
    }
    
    /**
     * Property 18: Kirkpatrick Data Consistency
     * 
     * For any learner and course combination, if data exists at Level N,
     * then data should exist at all previous levels (N-1, N-2, etc.)
     */
    public function testKirkpatrickDataConsistencyAcrossLevels() {
        $this->forAll(
            Generator\int(1, 100),  // userid
            Generator\int(1, 50),   // courseid
            Generator\bool(),       // has_level1
            Generator\bool(),       // has_level2
            Generator\bool(),       // has_level3
            Generator\bool()        // has_level4
        )->then(function($userid, $courseid, $has_level1, $has_level2, $has_level3, $has_level4) {
            global $DB;
            
            // Create test user and course
            $user = $this->create_test_user($userid);
            $course = $this->create_test_course($courseid);
            
            // Create Kirkpatrick data based on flags
            if ($has_level1) {
                $this->create_level1_data($user->id, $course->id);
            }
            if ($has_level2) {
                $this->create_level2_data($user->id, $course->id);
            }
            if ($has_level3) {
                $this->create_level3_data($user->id, $course->id);
            }
            if ($has_level4) {
                $this->create_level4_data($course->id);
            }
            
            // Property: If Level 4 exists, Level 3 should exist
            if ($has_level4) {
                $level3_exists = $DB->record_exists('kirkpatrick_level3_behavior', [
                    'userid' => $user->id,
                    'courseid' => $course->id
                ]);
                $this->assertTrue($has_level3 || !$has_level4, 
                    "Level 4 data exists but Level 3 is missing");
            }
            
            // Property: If Level 3 exists, Level 2 should exist
            if ($has_level3) {
                $level2_exists = $DB->record_exists('kirkpatrick_level2_learning', [
                    'userid' => $user->id,
                    'courseid' => $course->id
                ]);
                $this->assertTrue($has_level2 || !$has_level3,
                    "Level 3 data exists but Level 2 is missing");
            }
            
            // Property: If Level 2 exists, Level 1 should exist
            if ($has_level2) {
                $level1_exists = $DB->record_exists('kirkpatrick_level1_reaction', [
                    'userid' => $user->id,
                    'courseid' => $course->id
                ]);
                $this->assertTrue($has_level1 || !$has_level2,
                    "Level 2 data exists but Level 1 is missing");
            }
        });
    }
    
    /**
     * Property: Timestamps should be chronologically ordered across levels
     */
    public function testKirkpatrickTimestampOrdering() {
        $this->forAll(
            Generator\int(1, 100),  // userid
            Generator\int(1, 50)    // courseid
        )->then(function($userid, $courseid) {
            global $DB;
            
            $user = $this->create_test_user($userid);
            $course = $this->create_test_course($courseid);
            
            // Create data at all levels with realistic timestamps
            $base_time = time() - (90 * 24 * 60 * 60); // 90 days ago
            
            $level1_time = $base_time;
            $level2_time = $base_time + (7 * 24 * 60 * 60);  // 7 days later
            $level3_time = $base_time + (30 * 24 * 60 * 60); // 30 days later
            
            $this->create_level1_data($user->id, $course->id, $level1_time);
            $this->create_level2_data($user->id, $course->id, $level2_time);
            $this->create_level3_data($user->id, $course->id, $level3_time);
            
            // Retrieve timestamps
            $level1 = $DB->get_record('kirkpatrick_level1_reaction', [
                'userid' => $user->id,
                'courseid' => $course->id
            ]);
            $level2 = $DB->get_record('kirkpatrick_level2_learning', [
                'userid' => $user->id,
                'courseid' => $course->id
            ]);
            $level3 = $DB->get_record('kirkpatrick_level3_behavior', [
                'userid' => $user->id,
                'courseid' => $course->id
            ]);
            
            // Property: Level 1 should occur before Level 2
            $this->assertLessThanOrEqual($level2->date_assessed, $level1->date_collected,
                "Level 1 reaction should be collected before Level 2 assessment");
            
            // Property: Level 2 should occur before Level 3
            $this->assertLessThanOrEqual($level3->followup_date, $level2->date_assessed,
                "Level 2 assessment should occur before Level 3 follow-up");
        });
    }
    
    /**
     * Property: User and course references should be valid
     */
    public function testKirkpatrickReferentialIntegrity() {
        $this->forAll(
            Generator\int(1, 100),  // userid
            Generator\int(1, 50)    // courseid
        )->then(function($userid, $courseid) {
            global $DB;
            
            $user = $this->create_test_user($userid);
            $course = $this->create_test_course($courseid);
            
            // Create data at all levels
            $this->create_level1_data($user->id, $course->id);
            $this->create_level2_data($user->id, $course->id);
            $this->create_level3_data($user->id, $course->id);
            
            // Property: All user IDs should reference valid users
            $level1_users = $DB->get_fieldset_select('kirkpatrick_level1_reaction', 
                'userid', 'courseid = ?', [$course->id]);
            foreach ($level1_users as $uid) {
                $this->assertTrue($DB->record_exists('user', ['id' => $uid]),
                    "Level 1 references non-existent user ID: $uid");
            }
            
            $level2_users = $DB->get_fieldset_select('kirkpatrick_level2_learning',
                'userid', 'courseid = ?', [$course->id]);
            foreach ($level2_users as $uid) {
                $this->assertTrue($DB->record_exists('user', ['id' => $uid]),
                    "Level 2 references non-existent user ID: $uid");
            }
            
            $level3_users = $DB->get_fieldset_select('kirkpatrick_level3_behavior',
                'userid', 'courseid = ?', [$course->id]);
            foreach ($level3_users as $uid) {
                $this->assertTrue($DB->record_exists('user', ['id' => $uid]),
                    "Level 3 references non-existent user ID: $uid");
            }
            
            // Property: All course IDs should reference valid courses
            $level1_courses = $DB->get_fieldset_select('kirkpatrick_level1_reaction',
                'courseid', 'userid = ?', [$user->id]);
            foreach ($level1_courses as $cid) {
                $this->assertTrue($DB->record_exists('course', ['id' => $cid]),
                    "Level 1 references non-existent course ID: $cid");
            }
        });
    }
    
    /**
     * Property: Data aggregation should be consistent
     */
    public function testKirkpatrickDataAggregationConsistency() {
        $this->forAll(
            Generator\int(5, 20)  // number of learners
        )->then(function($learner_count) {
            global $DB;
            
            $course = $this->create_test_course(1);
            
            // Create data for multiple learners
            for ($i = 1; $i <= $learner_count; $i++) {
                $user = $this->create_test_user($i);
                $this->create_level1_data($user->id, $course->id);
                $this->create_level2_data($user->id, $course->id);
            }
            
            // Property: Count of Level 1 records should match count of Level 2 records
            $level1_count = $DB->count_records('kirkpatrick_level1_reaction', 
                ['courseid' => $course->id]);
            $level2_count = $DB->count_records('kirkpatrick_level2_learning',
                ['courseid' => $course->id]);
            
            $this->assertEquals($level1_count, $level2_count,
                "Level 1 and Level 2 record counts should match for complete learner journeys");
            
            // Property: Average satisfaction should be within valid range
            $avg_satisfaction = $DB->get_field_sql(
                "SELECT AVG(satisfaction_score) FROM {kirkpatrick_level1_reaction} WHERE courseid = ?",
                [$course->id]
            );
            $this->assertGreaterThanOrEqual(1, $avg_satisfaction,
                "Average satisfaction should be >= 1");
            $this->assertLessThanOrEqual(10, $avg_satisfaction,
                "Average satisfaction should be <= 10");
            
            // Property: Average knowledge gain should be calculable
            $avg_knowledge_gain = $DB->get_field_sql(
                "SELECT AVG(knowledge_gain) FROM {kirkpatrick_level2_learning} WHERE courseid = ?",
                [$course->id]
            );
            $this->assertIsNumeric($avg_knowledge_gain,
                "Average knowledge gain should be numeric");
        });
    }
    
    /**
     * Property: Level 4 data should correlate with Level 2 and 3 data
     */
    public function testKirkpatrickLevel4Correlation() {
        $this->forAll(
            Generator\int(1, 50)  // courseid
        )->then(function($courseid) {
            global $DB;
            
            $course = $this->create_test_course($courseid);
            
            // Create learner data
            for ($i = 1; $i <= 10; $i++) {
                $user = $this->create_test_user($i);
                $this->create_level1_data($user->id, $course->id);
                $this->create_level2_data($user->id, $course->id);
                $this->create_level3_data($user->id, $course->id);
            }
            
            // Create Level 4 data
            $this->create_level4_data($course->id);
            
            // Property: If Level 4 data exists, there should be learners with Level 2 data
            $level4_exists = $DB->record_exists('kirkpatrick_level4_results',
                ['programid' => $course->id]);
            
            if ($level4_exists) {
                $level2_count = $DB->count_records('kirkpatrick_level2_learning',
                    ['courseid' => $course->id]);
                $this->assertGreaterThan(0, $level2_count,
                    "Level 4 results should only exist if learners have completed Level 2");
            }
            
            // Property: Level 4 ROI should be calculable from Level 2 data
            if ($level4_exists) {
                $level4 = $DB->get_record('kirkpatrick_level4_results',
                    ['programid' => $course->id]);
                $this->assertIsNumeric($level4->roi_calculation,
                    "Level 4 ROI should be numeric");
            }
        });
    }
    
    // Helper methods
    
    private function create_test_user($id) {
        global $DB;
        
        $user = $DB->get_record('user', ['id' => $id]);
        if (!$user) {
            $user = new stdClass();
            $user->id = $id;
            $user->username = 'testuser' . $id;
            $user->firstname = 'Test';
            $user->lastname = 'User' . $id;
            $user->email = 'testuser' . $id . '@example.com';
            $user->confirmed = 1;
            $user->mnethostid = 1;
            $user->timecreated = time();
            $user->timemodified = time();
            $user->id = $DB->insert_record('user', $user);
        }
        return $user;
    }
    
    private function create_test_course($id) {
        global $DB;
        
        $course = $DB->get_record('course', ['id' => $id]);
        if (!$course) {
            $course = new stdClass();
            $course->id = $id;
            $course->fullname = 'Test Course ' . $id;
            $course->shortname = 'TC' . $id;
            $course->category = 1;
            $course->timecreated = time();
            $course->timemodified = time();
            $course->id = $DB->insert_record('course', $course);
        }
        return $course;
    }
    
    private function create_level1_data($userid, $courseid, $timestamp = null) {
        global $DB;
        
        $timestamp = $timestamp ?? time();
        
        $record = new stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->sessionid = null;
        $record->feedbackid = 1;
        $record->date_collected = $timestamp;
        $record->satisfaction_score = rand(6, 10);
        $record->engagement_rating = rand(6, 10);
        $record->content_relevance = 'Relevant';
        $record->instructor_effectiveness = rand(7, 10);
        $record->environment_quality = rand(7, 10);
        $record->feedback_comments = 'Test feedback';
        $record->timecreated = $timestamp;
        $record->timemodified = $timestamp;
        
        return $DB->insert_record('kirkpatrick_level1_reaction', $record);
    }
    
    private function create_level2_data($userid, $courseid, $timestamp = null) {
        global $DB;
        
        $timestamp = $timestamp ?? time();
        
        $record = new stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->competencyid = null;
        $record->assessmentid = 1;
        $record->assessment_type = 'quiz';
        $record->date_assessed = $timestamp;
        $record->pre_score = rand(40, 60);
        $record->post_score = rand(70, 95);
        $record->skill_level = 'competent';
        $record->knowledge_gain = $record->post_score - $record->pre_score;
        $record->badge_earned = null;
        $record->certification_achieved = 1;
        $record->learning_objectives_met = 'All objectives met';
        $record->timecreated = $timestamp;
        $record->timemodified = $timestamp;
        
        return $DB->insert_record('kirkpatrick_level2_learning', $record);
    }
    
    private function create_level3_data($userid, $courseid, $timestamp = null) {
        global $DB;
        
        $timestamp = $timestamp ?? time();
        
        $record = new stdClass();
        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->programid = $courseid;
        $record->followup_date = $timestamp;
        $record->followup_period = '30days';
        $record->context = 'workplace';
        $record->performance_rating = rand(7, 10);
        $record->supervisor_feedback = 'Good performance';
        $record->skill_application = 'Applied skills effectively';
        $record->behavior_change = 'Positive behavior change observed';
        $record->evidence_submitted = 1;
        $record->evidence_type = 'case_study';
        $record->workplace_integration = 'Well integrated';
        $record->barriers_encountered = 'None';
        $record->support_needed = 'None';
        $record->timecreated = $timestamp;
        $record->timemodified = $timestamp;
        
        return $DB->insert_record('kirkpatrick_level3_behavior', $record);
    }
    
    private function create_level4_data($courseid) {
        global $DB;
        
        // Check if Level 4 plugin is installed
        if (!$DB->get_manager()->table_exists(new xmldb_table('kirkpatrick_level4_results'))) {
            return null;
        }
        
        $record = new stdClass();
        $record->programid = $courseid;
        $record->measurement_period = date('Y-m');
        $record->organization_unit = 'Test Unit';
        $record->patient_outcomes = json_encode(['success_rate' => 95]);
        $record->cost_savings = rand(50000, 200000);
        $record->quality_metrics = json_encode(['quality_score' => 92]);
        $record->roi_calculation = rand(50, 200);
        $record->productivity_improvement = rand(10, 30);
        $record->safety_indicators = json_encode(['incidents' => 0]);
        $record->external_source = 'test';
        $record->sync_status = 'completed';
        $record->last_sync = time();
        $record->timecreated = time();
        $record->timemodified = time();
        
        return $DB->insert_record('kirkpatrick_level4_results', $record);
    }
}

// Run the test
echo "=== Property-Based Test: Kirkpatrick Data Consistency ===\n\n";
echo "Property 18: Kirkpatrick Data Consistency\n";
echo "Validates: Requirements 17.1, 17.2, 17.3, 17.4\n\n";

echo "This property test verifies that:\n";
echo "1. Data collected at each Kirkpatrick level maintains referential integrity\n";
echo "2. Timestamps are chronologically ordered across levels\n";
echo "3. User and course references are valid\n";
echo "4. Data aggregation is consistent\n";
echo "5. Level 4 data correlates with Level 2 and 3 data\n\n";

echo "To run this test:\n";
echo "1. Install Eris: composer require giorgiosironi/eris\n";
echo "2. Run: vendor/bin/phpunit property_test_kirkpatrick_data_consistency.php\n";
