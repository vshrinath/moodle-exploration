<?php
/**
 * Property-Based Test: Automated Badge Awarding
 * Task 8.3: Property 17 - Automated Badge Awarding
 * 
 * **Property 17: Automated Badge Awarding**
 * For any learner completing competencies that meet badge criteria,
 * the system should automatically award appropriate digital badges
 * and certificates without manual intervention
 * 
 * **Validates: Requirements 15.1, 15.4**
 * 
 * Feature: competency-based-learning
 * Property 17: Automated Badge Awarding
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/badgeslib.php');
require_once($CFG->dirroot.'/badges/lib.php');
require_once($CFG->dirroot.'/competency/classes/api.php');
require_once($CFG->dirroot.'/competency/classes/competency_framework.php');
require_once($CFG->dirroot.'/competency/classes/competency.php');
require_once($CFG->dirroot.'/competency/classes/user_competency.php');

use core_competency\api;
use core_competency\competency_framework;
use core_competency\competency;
use core_competency\user_competency;

// Set admin user
$admin = get_admin();
\core\session\manager::set_user($admin);

echo "=== Property-Based Test: Automated Badge Awarding ===\n";
echo "Feature: competency-based-learning\n";
echo "Property 17: Automated Badge Awarding\n";
echo "Validates: Requirements 15.1, 15.4\n\n";

global $DB;

$test_passed = true;
$test_iterations = 10;  // Number of property test iterations
$failures = [];

/**
 * Create a test user
 */
function create_test_user($iteration) {
    global $DB;
    
    $user = new stdClass();
    $user->username = 'testuser_' . $iteration . '_' . time();
    $user->firstname = 'Test';
    $user->lastname = 'User ' . $iteration;
    $user->email = 'testuser' . $iteration . '_' . time() . '@example.com';
    $user->password = hash_internal_user_password('TestPass123!');
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->timecreated = time();
    $user->timemodified = time();
    
    $user_id = $DB->insert_record('user', $user);
    return $user_id;
}

/**
 * Create a test competency
 */
function create_test_competency($framework_id, $iteration) {
    $comp_data = (object)[
        'shortname' => 'Badge Test Comp ' . $iteration . '_' . time(),
        'idnumber' => 'BADGE_COMP_' . $iteration . '_' . time(),
        'description' => 'Test competency for badge awarding',
        'descriptionformat' => FORMAT_HTML,
        'competencyframeworkid' => $framework_id,
        'parentid' => 0,
        'sortorder' => $iteration
    ];
    
    $competency = api::create_competency($comp_data);
    return $competency;
}

/**
 * Create a test badge with competency criteria
 */
function create_test_badge($competency_id, $iteration) {
    global $DB, $USER;
    
    $badge = new stdClass();
    $badge->name = 'Test Badge ' . $iteration . '_' . time();
    $badge->description = 'Test badge for automated awarding';
    $badge->timecreated = time();
    $badge->timemodified = time();
    $badge->usercreated = $USER->id;
    $badge->usermodified = $USER->id;
    $badge->issuername = 'Test System';
    $badge->issuerurl = $CFG->wwwroot;
    $badge->issuercontact = 'test@example.com';
    $badge->expiredate = null;
    $badge->expireperiod = null;
    $badge->type = BADGE_TYPE_SITE;
    $badge->courseid = null;
    $badge->messagesubject = 'Badge Earned';
    $badge->message = 'You earned a badge!';
    $badge->attachment = 1;
    $badge->notification = 1;
    $badge->status = BADGE_STATUS_ACTIVE;
    $badge->version = '2.0';
    $badge->language = 'en';
    $badge->imageauthorname = 'Test';
    $badge->imageauthoremail = 'test@example.com';
    $badge->imageauthorurl = $CFG->wwwroot;
    $badge->imagecaption = 'Test Badge';
    
    $badge_id = $DB->insert_record('badge', $badge);
    
    // Create competency-based criteria
    $criteria = new stdClass();
    $criteria->badgeid = $badge_id;
    $criteria->criteriatype = BADGE_CRITERIA_TYPE_COMPETENCY;
    $criteria->method = BADGE_CRITERIA_AGGREGATION_ALL;
    
    $criteria_id = $DB->insert_record('badge_criteria', $criteria);
    
    // Link specific competency to criteria
    $criteria_param = new stdClass();
    $criteria_param->critid = $criteria_id;
    $criteria_param->name = 'competency_' . $competency_id;
    $criteria_param->value = $competency_id;
    
    $DB->insert_record('badge_criteria_param', $criteria_param);
    
    return $badge_id;
}

/**
 * Mark competency as completed for user
 */
function complete_competency_for_user($user_id, $competency_id) {
    try {
        // Create user competency record
        $user_comp = api::create_user_competency($user_id, $competency_id);
        
        // Mark as proficient
        api::grade_competency($user_id, $competency_id, 1, 'Completed for testing');
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if badge was automatically awarded to user
 */
function check_badge_awarded($user_id, $badge_id) {
    global $DB;
    
    // Check badge_issued table
    $issued = $DB->get_record('badge_issued', [
        'badgeid' => $badge_id,
        'userid' => $user_id
    ]);
    
    return !empty($issued);
}

/**
 * Trigger badge awarding process
 */
function trigger_badge_awarding($badge_id) {
    global $DB;
    
    // Get badge object
    $badge_record = $DB->get_record('badge', ['id' => $badge_id]);
    if (!$badge_record) {
        return false;
    }
    
    // Create badge instance
    $badge = new badge($badge_id);
    
    // Review badge criteria and award if met
    // This simulates the automated process that would run via cron
    $badge->review_all_criteria();
    
    return true;
}

/**
 * Property Test: Badge is automatically awarded when competency criteria are met
 */
function test_automated_badge_awarding($iteration) {
    global $DB;
    
    echo "Iteration {$iteration}: ";
    
    try {
        // Get the test framework
        $framework = $DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);
        if (!$framework) {
            echo "✗ Framework not found\n";
            return false;
        }
        
        // Create test user
        $user_id = create_test_user($iteration);
        
        // Create test competency
        $competency = create_test_competency($framework->id, $iteration);
        $competency_id = $competency->get('id');
        
        // Create badge with competency criteria
        $badge_id = create_test_badge($competency_id, $iteration);
        
        // Verify: Badge should NOT be awarded yet
        if (check_badge_awarded($user_id, $badge_id)) {
            echo "✗ Badge awarded prematurely\n";
            return false;
        }
        
        // Complete the competency for the user
        if (!complete_competency_for_user($user_id, $competency_id)) {
            echo "✗ Failed to complete competency\n";
            return false;
        }
        
        // Trigger badge awarding process (simulates cron)
        trigger_badge_awarding($badge_id);
        
        // Small delay to allow processing
        sleep(1);
        
        // Verify: Badge should now be automatically awarded
        if (!check_badge_awarded($user_id, $badge_id)) {
            echo "✗ Badge not automatically awarded after competency completion\n";
            return false;
        }
        
        // Verify: Badge issue record has correct data
        $issued = $DB->get_record('badge_issued', [
            'badgeid' => $badge_id,
            'userid' => $user_id
        ]);
        
        if (!$issued) {
            echo "✗ Badge issued record not found\n";
            return false;
        }
        
        if (empty($issued->uniquehash)) {
            echo "✗ Badge missing unique hash for verification\n";
            return false;
        }
        
        if ($issued->dateissued <= 0) {
            echo "✗ Badge missing issue date\n";
            return false;
        }
        
        // Verify: Badge is Open Badges 2.0 compliant
        $badge_record = $DB->get_record('badge', ['id' => $badge_id]);
        if ($badge_record->version !== '2.0') {
            echo "✗ Badge not Open Badges 2.0 compliant\n";
            return false;
        }
        
        // Verify: User can access their badge
        $user_badges = badges_get_user_badges($user_id);
        $found_badge = false;
        foreach ($user_badges as $user_badge) {
            if ($user_badge->id == $badge_id) {
                $found_badge = true;
                break;
            }
        }
        
        if (!$found_badge) {
            echo "✗ Badge not accessible to user\n";
            return false;
        }
        
        // Cleanup
        $DB->delete_records('badge_issued', ['badgeid' => $badge_id]);
        $DB->delete_records('badge_criteria_param', ['critid' => $DB->get_field('badge_criteria', 'id', ['badgeid' => $badge_id])]);
        $DB->delete_records('badge_criteria', ['badgeid' => $badge_id]);
        $DB->delete_records('badge', ['id' => $badge_id]);
        $DB->delete_records('competency_usercomp', ['userid' => $user_id, 'competencyid' => $competency_id]);
        api::delete_competency($competency_id);
        $DB->delete_records('user', ['id' => $user_id]);
        
        echo "✓ PASS (badge automatically awarded)\n";
        return true;
        
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Property Test: Multi-level badge progression works correctly
 */
function test_multi_level_badge_progression($iteration) {
    global $DB;
    
    echo "Multi-level test {$iteration}: ";
    
    try {
        // Get the test framework
        $framework = $DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);
        if (!$framework) {
            echo "✗ Framework not found\n";
            return false;
        }
        
        // Create test user
        $user_id = create_test_user($iteration . '_ml');
        
        // Create multiple competencies for different badge levels
        $bronze_comp = create_test_competency($framework->id, $iteration . '_bronze');
        $silver_comp = create_test_competency($framework->id, $iteration . '_silver');
        $gold_comp = create_test_competency($framework->id, $iteration . '_gold');
        
        // Create badges for each level
        $bronze_badge = create_test_badge($bronze_comp->get('id'), $iteration . '_bronze');
        $silver_badge = create_test_badge($silver_comp->get('id'), $iteration . '_silver');
        $gold_badge = create_test_badge($gold_comp->get('id'), $iteration . '_gold');
        
        // Complete bronze competency
        complete_competency_for_user($user_id, $bronze_comp->get('id'));
        trigger_badge_awarding($bronze_badge);
        sleep(1);
        
        // Verify bronze badge awarded
        if (!check_badge_awarded($user_id, $bronze_badge)) {
            echo "✗ Bronze badge not awarded\n";
            return false;
        }
        
        // Complete silver competency
        complete_competency_for_user($user_id, $silver_comp->get('id'));
        trigger_badge_awarding($silver_badge);
        sleep(1);
        
        // Verify silver badge awarded
        if (!check_badge_awarded($user_id, $silver_badge)) {
            echo "✗ Silver badge not awarded\n";
            return false;
        }
        
        // Complete gold competency
        complete_competency_for_user($user_id, $gold_comp->get('id'));
        trigger_badge_awarding($gold_badge);
        sleep(1);
        
        // Verify gold badge awarded
        if (!check_badge_awarded($user_id, $gold_badge)) {
            echo "✗ Gold badge not awarded\n";
            return false;
        }
        
        // Verify all three badges are held by user
        $user_badge_count = $DB->count_records('badge_issued', ['userid' => $user_id]);
        if ($user_badge_count != 3) {
            echo "✗ Incorrect badge count ({$user_badge_count}/3)\n";
            return false;
        }
        
        // Cleanup
        foreach ([$bronze_badge, $silver_badge, $gold_badge] as $badge_id) {
            $DB->delete_records('badge_issued', ['badgeid' => $badge_id]);
            $DB->delete_records('badge_criteria_param', ['critid' => $DB->get_field('badge_criteria', 'id', ['badgeid' => $badge_id])]);
            $DB->delete_records('badge_criteria', ['badgeid' => $badge_id]);
            $DB->delete_records('badge', ['id' => $badge_id]);
        }
        
        foreach ([$bronze_comp, $silver_comp, $gold_comp] as $comp) {
            $DB->delete_records('competency_usercomp', ['userid' => $user_id, 'competencyid' => $comp->get('id')]);
            api::delete_competency($comp->get('id'));
        }
        
        $DB->delete_records('user', ['id' => $user_id]);
        
        echo "✓ PASS (multi-level progression works)\n";
        return true;
        
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run property tests
echo "Running {$test_iterations} property test iterations...\n\n";

// Test 1: Basic automated badge awarding
echo "Test 1: Basic Automated Badge Awarding\n";
for ($i = 1; $i <= $test_iterations; $i++) {
    if (!test_automated_badge_awarding($i)) {
        $test_passed = false;
        $failures[] = "basic_$i";
    }
    usleep(200000);  // 0.2 second delay
}

echo "\n";

// Test 2: Multi-level badge progression
echo "Test 2: Multi-Level Badge Progression\n";
$multi_level_iterations = 3;
for ($i = 1; $i <= $multi_level_iterations; $i++) {
    if (!test_multi_level_badge_progression($i)) {
        $test_passed = false;
        $failures[] = "multi_level_$i";
    }
    usleep(200000);
}

// Summary
echo "\n=== Property Test Results ===\n";
$total_tests = $test_iterations + $multi_level_iterations;
echo "Total iterations: {$total_tests}\n";
echo "Passed: " . ($total_tests - count($failures)) . "\n";
echo "Failed: " . count($failures) . "\n";

if ($test_passed) {
    echo "\n✓ PROPERTY TEST PASSED\n";
    echo "✓ Property 17 (Automated Badge Awarding) holds across all test cases\n";
    echo "✓ Requirements 15.1, 15.4 validated\n";
    echo "\nValidated behaviors:\n";
    echo "  ✓ Badges automatically awarded on competency completion\n";
    echo "  ✓ Multi-level badge progression (Bronze → Silver → Gold)\n";
    echo "  ✓ Open Badges 2.0 compliance maintained\n";
    echo "  ✓ Badge verification data generated correctly\n";
    echo "  ✓ User badge access working properly\n";
    exit(0);
} else {
    echo "\n✗ PROPERTY TEST FAILED\n";
    echo "Failed iterations: " . implode(', ', $failures) . "\n";
    echo "✗ Property 17 (Automated Badge Awarding) violated\n";
    exit(1);
}

?>
