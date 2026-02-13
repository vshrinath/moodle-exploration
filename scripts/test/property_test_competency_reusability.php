<?php
/**
 * Property-Based Test: Competency Reusability
 * Task 3.2: Property 4 - Competency Reusability
 * 
 * **Property 4: Competency Reusability**
 * For any competency created in the framework, it should be referenceable 
 * by multiple programs without duplication
 * 
 * **Validates: Requirements 2.1**
 * 
 * Feature: competency-based-learning
 * Property 4: Competency Reusability
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/competency/classes/api.php');
require_once($CFG->dirroot.'/competency/classes/competency_framework.php');
require_once($CFG->dirroot.'/competency/classes/competency.php');

use core_competency\api;
use core_competency\competency_framework;
use core_competency\competency;

// Set admin user
$admin = get_admin();
\core\session\manager::set_user($admin);

echo "=== Property-Based Test: Competency Reusability ===\n";
echo "Feature: competency-based-learning\n";
echo "Property 4: Competency Reusability\n";
echo "Validates: Requirements 2.1\n\n";

global $DB;

$test_passed = true;
$test_iterations = 10;  // Number of property test iterations
$failures = [];

/**
 * Generate random competency data
 */
function generate_competency_data($framework_id, $iteration) {
    return (object)[
        'shortname' => 'Test Competency ' . $iteration . '_' . time(),
        'idnumber' => 'TEST_COMP_' . $iteration . '_' . time(),
        'description' => 'Test competency for reusability testing',
        'descriptionformat' => FORMAT_HTML,
        'competencyframeworkid' => $framework_id,
        'parentid' => 0,
        'sortorder' => $iteration
    ];
}

/**
 * Create a test course
 */
function create_test_course($iteration) {
    global $DB;
    
    $course_data = new stdClass();
    $course_data->fullname = 'Test Course ' . $iteration . '_' . time();
    $course_data->shortname = 'TC' . $iteration . '_' . time();
    $course_data->category = 1;  // Miscellaneous category
    $course_data->timecreated = time();
    $course_data->timemodified = time();
    
    $course_id = $DB->insert_record('course', $course_data);
    return $course_id;
}

/**
 * Link competency to course
 */
function link_competency_to_course($competency_id, $course_id) {
    try {
        api::add_competency_to_course($course_id, $competency_id);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Property Test: Competency can be referenced by multiple courses without duplication
 */
function test_competency_reusability($iteration) {
    global $DB;
    
    echo "Iteration {$iteration}: ";
    
    try {
        // Get the test framework
        $framework = $DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);
        if (!$framework) {
            echo "✗ Framework not found\n";
            return false;
        }
        
        // Create a competency
        $comp_data = generate_competency_data($framework->id, $iteration);
        $competency = api::create_competency($comp_data);
        $competency_id = $competency->get('id');
        
        // Create multiple courses
        $num_courses = rand(2, 5);
        $course_ids = [];
        for ($i = 0; $i < $num_courses; $i++) {
            $course_ids[] = create_test_course($iteration . '_' . $i);
        }
        
        // Link the same competency to all courses
        $link_count = 0;
        foreach ($course_ids as $course_id) {
            if (link_competency_to_course($competency_id, $course_id)) {
                $link_count++;
            }
        }
        
        // Verify: Competency should be linked to all courses
        if ($link_count != $num_courses) {
            echo "✗ Failed to link competency to all courses ({$link_count}/{$num_courses})\n";
            return false;
        }
        
        // Verify: Only ONE competency record exists (no duplication)
        $competency_count = $DB->count_records('competency', ['id' => $competency_id]);
        if ($competency_count != 1) {
            echo "✗ Competency duplicated ({$competency_count} records)\n";
            return false;
        }
        
        // Verify: Multiple course-competency links exist
        $link_records = $DB->count_records('competency_coursecomp', ['competencyid' => $competency_id]);
        if ($link_records != $num_courses) {
            echo "✗ Incorrect number of course links ({$link_records}/{$num_courses})\n";
            return false;
        }
        
        // Verify: All courses reference the SAME competency ID
        $course_comps = $DB->get_records('competency_coursecomp', ['competencyid' => $competency_id]);
        $unique_comp_ids = array_unique(array_column(array_values($course_comps), 'competencyid'));
        if (count($unique_comp_ids) != 1) {
            echo "✗ Multiple competency IDs found (should be 1)\n";
            return false;
        }
        
        // Cleanup
        foreach ($course_ids as $course_id) {
            $DB->delete_records('competency_coursecomp', ['courseid' => $course_id]);
            $DB->delete_records('course', ['id' => $course_id]);
        }
        api::delete_competency($competency_id);
        
        echo "✓ PASS (competency reused across {$num_courses} courses)\n";
        return true;
        
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run property tests
echo "Running {$test_iterations} property test iterations...\n\n";

for ($i = 1; $i <= $test_iterations; $i++) {
    if (!test_competency_reusability($i)) {
        $test_passed = false;
        $failures[] = $i;
    }
    
    // Small delay to avoid timestamp collisions
    usleep(100000);  // 0.1 second
}

// Summary
echo "\n=== Property Test Results ===\n";
echo "Total iterations: {$test_iterations}\n";
echo "Passed: " . ($test_iterations - count($failures)) . "\n";
echo "Failed: " . count($failures) . "\n";

if ($test_passed) {
    echo "\n✓ PROPERTY TEST PASSED\n";
    echo "✓ Property 4 (Competency Reusability) holds across all test cases\n";
    echo "✓ Requirement 2.1 validated\n";
    exit(0);
} else {
    echo "\n✗ PROPERTY TEST FAILED\n";
    echo "Failed iterations: " . implode(', ', $failures) . "\n";
    echo "✗ Property 4 (Competency Reusability) violated\n";
    exit(1);
}

?>
