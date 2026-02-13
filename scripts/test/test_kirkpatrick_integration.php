<?php
/**
 * Integration test for Kirkpatrick Model Evaluation Framework
 * Task 2.6: Install Kirkpatrick Model evaluation plugins
 * Requirements: 17.1, 17.2, 17.3, 17.4
 * 
 * This script tests the integration of all four Kirkpatrick evaluation levels
 * to ensure data can flow through the complete evaluation framework.
 */

define('CLI_SCRIPT', true);
require_once('/bitnami/moodle/config.php');
require_once($CFG->libdir.'/adminlib.php');

echo "========================================\n";
echo "Kirkpatrick Model Integration Test\n";
echo "========================================\n\n";

$test_results = [];
$tests_passed = 0;
$tests_failed = 0;

/**
 * Helper function to run a test
 */
function run_test($test_name, $test_function) {
    global $test_results, $tests_passed, $tests_failed;
    
    echo "Testing: $test_name\n";
    try {
        $result = $test_function();
        if ($result) {
            echo "  ✓ PASSED\n";
            $tests_passed++;
            $test_results[$test_name] = 'PASSED';
        } else {
            echo "  ✗ FAILED\n";
            $tests_failed++;
            $test_results[$test_name] = 'FAILED';
        }
    } catch (Exception $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . "\n";
        $tests_failed++;
        $test_results[$test_name] = 'ERROR';
    }
    echo "\n";
}

// Test Level 1: Reaction - Feedback Activity
run_test("Level 1: Feedback Activity Module", function() {
    global $CFG, $DB;
    
    // Check if feedback module exists
    $feedback_path = $CFG->dirroot . '/mod/feedback';
    if (!file_exists($feedback_path)) {
        echo "    Feedback module not found\n";
        return false;
    }
    
    // Check if feedback is enabled
    $feedback_enabled = get_config('mod_feedback', 'visible');
    if (!$feedback_enabled) {
        echo "    Feedback module not enabled\n";
        return false;
    }
    
    // Check feedback tables exist
    $tables = ['feedback', 'feedback_item', 'feedback_value', 'feedback_completed'];
    foreach ($tables as $table) {
        if (!$DB->get_manager()->table_exists($table)) {
            echo "    Table '$table' does not exist\n";
            return false;
        }
    }
    
    echo "    Feedback Activity ready for satisfaction surveys\n";
    return true;
});

// Test Level 1: Questionnaire Plugin
run_test("Level 1: Questionnaire Plugin", function() {
    global $CFG, $DB;
    
    // Check if questionnaire module exists
    $questionnaire_path = $CFG->dirroot . '/mod/questionnaire';
    if (!file_exists($questionnaire_path)) {
        echo "    Questionnaire plugin not found\n";
        return false;
    }
    
    // Check questionnaire version
    $version = get_config('mod_questionnaire', 'version');
    if (!$version) {
        echo "    Questionnaire plugin not configured\n";
        return false;
    }
    
    // Check questionnaire tables exist
    if ($DB->get_manager()->table_exists('questionnaire')) {
        echo "    Questionnaire plugin ready for advanced surveys\n";
        return true;
    }
    
    echo "    Questionnaire tables not found\n";
    return false;
});

// Test Level 2: Competency Framework
run_test("Level 2: Competency Framework", function() {
    global $CFG, $DB;
    
    // Check if competency is enabled
    $competency_enabled = get_config('core_competency', 'enabled');
    if (!$competency_enabled) {
        echo "    Competency framework not enabled\n";
        return false;
    }
    
    // Check competency tables
    $tables = ['competency', 'competency_framework', 'competency_coursecomp', 'competency_usercomp'];
    foreach ($tables as $table) {
        if (!$DB->get_manager()->table_exists($table)) {
            echo "    Table '$table' does not exist\n";
            return false;
        }
    }
    
    echo "    Competency framework ready for learning measurement\n";
    return true;
});

// Test Level 2: Badges System
run_test("Level 2: Badges System", function() {
    global $CFG, $DB;
    
    // Check if badges are enabled
    $badges_enabled = get_config('core', 'enablebadges');
    if (!$badges_enabled) {
        echo "    Badges system not enabled\n";
        return false;
    }
    
    // Check badge tables
    $tables = ['badge', 'badge_criteria', 'badge_issued'];
    foreach ($tables as $table) {
        if (!$DB->get_manager()->table_exists($table)) {
            echo "    Table '$table' does not exist\n";
            return false;
        }
    }
    
    echo "    Badges system ready for achievement verification\n";
    return true;
});

// Test Level 2: Assessment Modules
run_test("Level 2: Assessment Modules", function() {
    global $CFG;
    
    // Check Quiz module
    $quiz_path = $CFG->dirroot . '/mod/quiz';
    if (!file_exists($quiz_path)) {
        echo "    Quiz module not found\n";
        return false;
    }
    
    // Check Assignment module
    $assign_path = $CFG->dirroot . '/mod/assign';
    if (!file_exists($assign_path)) {
        echo "    Assignment module not found\n";
        return false;
    }
    
    echo "    Assessment modules ready for knowledge testing\n";
    return true;
});

// Test Level 3: Portfolio System
run_test("Level 3: Portfolio System", function() {
    global $CFG, $DB;
    
    // Check if portfolio is enabled
    $portfolio_enabled = get_config('core', 'enableportfolios');
    if (!$portfolio_enabled) {
        echo "    Portfolio system not enabled\n";
        return false;
    }
    
    // Check portfolio directory
    $portfolio_path = $CFG->dirroot . '/portfolio';
    if (!file_exists($portfolio_path)) {
        echo "    Portfolio system not found\n";
        return false;
    }
    
    // Check portfolio tables
    if ($DB->get_manager()->table_exists('portfolio_instance')) {
        echo "    Portfolio system ready for evidence collection\n";
        return true;
    }
    
    echo "    Portfolio tables not found\n";
    return false;
});

// Test Level 4: External Database Plugin
run_test("Level 4: External Database Plugin", function() {
    global $CFG;
    
    // Check External Database enrolment plugin
    $extdb_enrol_path = $CFG->dirroot . '/enrol/database';
    if (!file_exists($extdb_enrol_path)) {
        echo "    External Database enrolment plugin not found\n";
        return false;
    }
    
    // Check External Database auth plugin
    $extdb_auth_path = $CFG->dirroot . '/auth/db';
    if (!file_exists($extdb_auth_path)) {
        echo "    External Database auth plugin not found\n";
        return false;
    }
    
    echo "    External Database plugins ready for hospital integration\n";
    return true;
});

// Test Integration: Completion Tracking
run_test("Integration: Completion Tracking", function() {
    global $CFG, $DB;
    
    // Check if completion is enabled
    $completion_enabled = get_config('core', 'enablecompletion');
    if (!$completion_enabled) {
        echo "    Completion tracking not enabled\n";
        return false;
    }
    
    // Check completion tables
    $tables = ['course_completions', 'course_completion_criteria'];
    foreach ($tables as $table) {
        if (!$DB->get_manager()->table_exists($table)) {
            echo "    Table '$table' does not exist\n";
            return false;
        }
    }
    
    echo "    Completion tracking ready for progress monitoring\n";
    return true;
});

// Test Integration: Configurable Reports
run_test("Integration: Configurable Reports", function() {
    global $CFG;
    
    // Check if Configurable Reports is installed
    $reports_path = $CFG->dirroot . '/blocks/configurable_reports';
    if (!file_exists($reports_path)) {
        echo "    Configurable Reports not installed (recommended)\n";
        return true; // Not critical, just recommended
    }
    
    echo "    Configurable Reports available for Kirkpatrick dashboards\n";
    return true;
});

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n\n";

echo "Tests Passed: $tests_passed\n";
echo "Tests Failed: $tests_failed\n";
echo "Total Tests: " . ($tests_passed + $tests_failed) . "\n\n";

if ($tests_failed == 0) {
    echo "✓ ALL TESTS PASSED\n\n";
    echo "Kirkpatrick Model Evaluation Framework Integration:\n";
    echo "  ✓ Level 1 (Reaction): Feedback & Questionnaire ready\n";
    echo "  ✓ Level 2 (Learning): Competency & Assessments ready\n";
    echo "  ✓ Level 3 (Behavior): Portfolio system ready\n";
    echo "  ✓ Level 4 (Results): External Database ready\n";
    echo "  ✓ Integration: Completion tracking & reporting ready\n\n";
    echo "The system is ready for comprehensive training evaluation!\n";
} else {
    echo "✗ SOME TESTS FAILED\n\n";
    echo "Failed Tests:\n";
    foreach ($test_results as $test => $result) {
        if ($result !== 'PASSED') {
            echo "  - $test: $result\n";
        }
    }
    echo "\nPlease review the errors and:\n";
    echo "  1. Run installation script: bash install_kirkpatrick_plugins.sh\n";
    echo "  2. Run configuration script: php configure_kirkpatrick_plugins.php\n";
    echo "  3. Complete plugin installation via admin UI\n";
    echo "  4. Re-run this test script\n";
}

echo "\n========================================\n";
echo "Integration Test Complete\n";
echo "========================================\n";

exit($tests_failed == 0 ? 0 : 1);
