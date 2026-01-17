<?php
/**
 * Property-Based Test: Progress Preservation Under Updates
 * 
 * Property 2: Progress Preservation Under Updates
 * For any program with existing learner progress, updating program outcomes 
 * or reassigning learners between cohorts should preserve all existing progress data
 * 
 * **Validates: Requirements 1.2, 10.1**
 * 
 * Feature: competency-based-learning
 * Property 2: Progress Preservation Under Updates
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/competency/classes/api.php');
require_once($CFG->dirroot . '/cohort/lib.php');

use core_competency\api;
use core_competency\user_competency;
use core_competency\evidence;

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "ERROR: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Property Test: Progress Preservation Under Updates ===\n\n";
echo "Property 2: For any program with existing learner progress, updating\n";
echo "program outcomes or reassigning learners between cohorts should preserve\n";
echo "all existing progress data\n\n";

$test_iterations = 50;
$passed = 0;
$failed = 0;
$failures = [];

try {
    // Get existing framework and competencies
    $context = context_system::instance();
    $frameworks = api::list_frameworks('shortname', 'ASC', 0, 1, $context);
    
    if (empty($frameworks)) {
        throw new Exception("No competency framework found");
    }
    
    $framework = reset($frameworks);
    $all_competencies = api::list_competencies(['competencyframeworkid' => $framework->get('id')]);
    
    if (count($all_competencies) < 2) {
        throw new Exception("Need at least 2 competencies for testing");
    }
    
    // Get or create test cohorts
    $cohort1 = $DB->get_record('cohort', ['idnumber' => 'PBT-COHORT-1']);
    if (!$cohort1) {
        $cohort1 = new stdClass();
        $cohort1->contextid = $context->id;
        $cohort1->name = 'PBT Test Cohort 1';
        $cohort1->idnumber = 'PBT-COHORT-1';
        $cohort1->description = 'Property test cohort 1';
        $cohort1->id = cohort_add_cohort($cohort1);
        $cohort1 = $DB->get_record('cohort', ['id' => $cohort1->id]);
    }
    
    $cohort2 = $DB->get_record('cohort', ['idnumber' => 'PBT-COHORT-2']);
    if (!$cohort2) {
        $cohort2 = new stdClass();
        $cohort2->contextid = $context->id;
        $cohort2->name = 'PBT Test Cohort 2';
        $cohort2->idnumber = 'PBT-COHORT-2';
        $cohort2->description = 'Property test cohort 2';
        $cohort2->id = cohort_add_cohort($cohort2);
        $cohort2 = $DB->get_record('cohort', ['id' => $cohort2->id]);
    }
    
    echo "Running {$test_iterations} property test iterations...\n\n";
    
    for ($i = 1; $i <= $test_iterations; $i++) {
        // Create test user
        $username = 'pbt_user_' . $i . '_' . time();
        $user = new stdClass();
        $user->username = $username;
        $user->firstname = 'PBT';
        $user->lastname = 'User ' . $i;
        $user->email = $username . '@example.com';
        $user->password = hash_internal_user_password('Test123!');
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->confirmed = 1;
        $user->id = $DB->insert_record('user', $user);
        
        // Add user to cohort 1
        cohort_add_member($cohort1->id, $user->id);
        
        // Create learning plan for user
        $plan = api::create_plan((object)[
            'name' => 'Test Plan ' . $i,
            'description' => 'Property test plan',
            'userid' => $user->id,
            'status' => \core_competency\plan::STATUS_ACTIVE,
        ]);
        
        // Select random competencies for the plan
        $num_comps = rand(2, min(5, count($all_competencies)));
        $selected_comps = array_rand(array_values($all_competencies), $num_comps);
        
        if (!is_array($selected_comps)) {
            $selected_comps = [$selected_comps];
        }
        
        $test_competencies = [];
        foreach ($selected_comps as $idx) {
            $comp = array_values($all_competencies)[$idx];
            $test_competencies[] = $comp;
            
            // Add competency to plan
            api::add_competency_to_plan($plan->get('id'), $comp->get('id'));
        }
        
        // Create progress for some competencies
        $progress_records = [];
        $num_with_progress = rand(1, count($test_competencies));
        
        for ($j = 0; $j < $num_with_progress; $j++) {
            $comp = $test_competencies[$j];
            
            // Create or get user competency record
            try {
                $user_comp = api::get_user_competency($user->id, $comp->get('id'));
            } catch (Exception $e) {
                // Create new user competency if it doesn't exist
                $user_comp_data = new stdClass();
                $user_comp_data->userid = $user->id;
                $user_comp_data->competencyid = $comp->get('id');
                $user_comp_data->status = \core_competency\user_competency::STATUS_IDLE;
                $user_comp_data->reviewerid = null;
                $user_comp_data->proficiency = null;
                $user_comp_data->grade = null;
                
                $user_comp = new \core_competency\user_competency(0, $user_comp_data);
                $user_comp->create();
            }
            
            // Set proficiency randomly
            $proficient = (rand(0, 1) == 1);
            $user_comp->set('proficiency', $proficient ? 1 : 0);
            $user_comp->set('grade', $proficient ? 2 : 1);
            $user_comp->update();
            
            $progress_records[] = [
                'competency_id' => $comp->get('id'),
                'proficiency' => $proficient,
                'grade' => $proficient ? 2 : 1
            ];
        }
        
        // Property Test 1: Update program outcomes (update plan description)
        $original_description = $plan->get('description');
        $plan->set('description', 'Updated description for property test');
        $plan->update();
        
        // Verify progress is preserved after update
        $progress_preserved_after_update = true;
        foreach ($progress_records as $record) {
            $user_comp = api::get_user_competency($user->id, $record['competency_id']);
            
            if ($user_comp->get('proficiency') != $record['proficiency'] ||
                $user_comp->get('grade') != $record['grade']) {
                $progress_preserved_after_update = false;
                break;
            }
        }
        
        if (!$progress_preserved_after_update) {
            $failed++;
            $failures[] = [
                'iteration' => $i,
                'reason' => 'Progress not preserved after program update',
                'user' => $username,
                'plan_id' => $plan->get('id')
            ];
            echo "  ✗ Iteration {$i}: Progress not preserved after update\n";
            
            // Clean up
            api::delete_plan($plan->get('id'));
            delete_user($user);
            continue;
        }
        
        // Property Test 2: Reassign user to different cohort
        cohort_remove_member($cohort1->id, $user->id);
        cohort_add_member($cohort2->id, $user->id);
        
        // Verify progress is preserved after cohort change
        $progress_preserved_after_cohort_change = true;
        foreach ($progress_records as $record) {
            $user_comp = api::get_user_competency($user->id, $record['competency_id']);
            
            if ($user_comp->get('proficiency') != $record['proficiency'] ||
                $user_comp->get('grade') != $record['grade']) {
                $progress_preserved_after_cohort_change = false;
                break;
            }
        }
        
        if (!$progress_preserved_after_cohort_change) {
            $failed++;
            $failures[] = [
                'iteration' => $i,
                'reason' => 'Progress not preserved after cohort change',
                'user' => $username,
                'plan_id' => $plan->get('id'),
                'from_cohort' => $cohort1->name,
                'to_cohort' => $cohort2->name
            ];
            echo "  ✗ Iteration {$i}: Progress not preserved after cohort change\n";
        } else {
            $passed++;
            if ($i % 10 == 0) {
                echo "  ✓ Iterations 1-{$i}: Passed\n";
            }
        }
        
        // Clean up
        api::delete_plan($plan->get('id'));
        delete_user($user);
    }
    
    echo "\n=== Test Results ===\n";
    echo "Total iterations: {$test_iterations}\n";
    echo "Passed: {$passed}\n";
    echo "Failed: {$failed}\n";
    echo "Success rate: " . round(($passed / $test_iterations) * 100, 2) . "%\n\n";
    
    if ($failed > 0) {
        echo "=== Failure Details ===\n";
        foreach (array_slice($failures, 0, 5) as $failure) {
            echo "\nIteration {$failure['iteration']}:\n";
            echo "  Reason: {$failure['reason']}\n";
            echo "  User: {$failure['user']}\n";
            echo "  Plan ID: {$failure['plan_id']}\n";
            
            if (isset($failure['from_cohort'])) {
                echo "  From cohort: {$failure['from_cohort']}\n";
                echo "  To cohort: {$failure['to_cohort']}\n";
            }
        }
        
        if (count($failures) > 5) {
            echo "\n... and " . (count($failures) - 5) . " more failures\n";
        }
        
        echo "\n✗ Property test FAILED\n";
        echo "\nCounterexample: " . json_encode($failures[0]) . "\n";
        exit(1);
    } else {
        echo "✓ Property test PASSED\n";
        echo "\nProperty 2 (Progress Preservation Under Updates) holds:\n";
        echo "- Progress is preserved when program outcomes are updated\n";
        echo "- Progress is preserved when learners change cohorts\n";
        echo "- Competency proficiency and grades are maintained\n";
        exit(0);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
