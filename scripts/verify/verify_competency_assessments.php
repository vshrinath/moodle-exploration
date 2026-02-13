<?php
/**
 * Verify Competency-Mapped Assessments Configuration
 * Task 6.1: Verify quiz and assignment modules with competency mapping
 * 
 * This script verifies:
 * - Quiz and assignment modules are properly configured
 * - Rubric-based assessment is available
 * - Immediate feedback mechanisms are working
 * - Competency mapping capabilities are functional
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');

// Set admin user
$admin = get_admin();
\core\session\manager::set_user($admin);

echo "=== Verifying Competency-Mapped Assessments ===\n\n";

global $DB;

$all_checks_passed = true;

// Check 1: Quiz module configuration
echo "1. Quiz Module Configuration:\n";
$quiz_enabled = $DB->record_exists('modules', ['name' => 'quiz', 'visible' => 1]);
echo "   " . ($quiz_enabled ? "✓" : "✗") . " Quiz module enabled\n";
$all_checks_passed = $all_checks_passed && $quiz_enabled;

$quiz_feedback = get_config('quiz', 'reviewcorrectness');
echo "   " . ($quiz_feedback ? "✓" : "✗") . " Immediate feedback configured\n";
$all_checks_passed = $all_checks_passed && $quiz_feedback;

// Check 2: Assignment module configuration
echo "\n2. Assignment Module Configuration:\n";
$assign_enabled = $DB->record_exists('modules', ['name' => 'assign', 'visible' => 1]);
echo "   " . ($assign_enabled ? "✓" : "✗") . " Assignment module enabled\n";
$all_checks_passed = $all_checks_passed && $assign_enabled;

$assign_feedback = get_config('assign', 'feedback_comments_default');
echo "   " . ($assign_feedback ? "✓" : "✗") . " Feedback plugins configured\n";
$all_checks_passed = $all_checks_passed && $assign_feedback;

// Check 3: Rubric availability
echo "\n3. Rubric-Based Assessment:\n";
$rubric_path = $CFG->dirroot . '/grade/grading/form/rubric';
$rubric_available = file_exists($rubric_path);
echo "   " . ($rubric_available ? "✓" : "✗") . " Rubric grading method available\n";
$all_checks_passed = $all_checks_passed && $rubric_available;

// Check 4: Competency mapping capabilities
echo "\n4. Competency Mapping Capabilities:\n";
$comp_grade_cap = $DB->record_exists('capabilities', ['name' => 'moodle/competency:competencygrade']);
echo "   " . ($comp_grade_cap ? "✓" : "✗") . " Competency grading capability\n";
$all_checks_passed = $all_checks_passed && $comp_grade_cap;

$comp_manage_cap = $DB->record_exists('capabilities', ['name' => 'moodle/competency:competencymanage']);
echo "   " . ($comp_manage_cap ? "✓" : "✗") . " Competency management capability\n";
$all_checks_passed = $all_checks_passed && $comp_manage_cap;

// Check 5: Completion tracking
echo "\n5. Activity Completion:\n";
$completion_enabled = get_config('core', 'enablecompletion');
echo "   " . ($completion_enabled ? "✓" : "✗") . " Activity completion enabled\n";
$all_checks_passed = $all_checks_passed && $completion_enabled;

// Check 6: Evidence generation
echo "\n6. Evidence Generation:\n";
$push_ratings = get_config('core_competency', 'pushcourseratingstouserplans');
echo "   " . ($push_ratings ? "✓" : "✗") . " Automatic evidence from assessments\n";
$all_checks_passed = $all_checks_passed && $push_ratings;

// Check 7: Database tables
echo "\n7. Required Database Tables:\n";
$tables = ['quiz', 'assign', 'competency', 'competency_coursecomp'];
foreach ($tables as $table) {
    $exists = $DB->get_manager()->table_exists($table);
    echo "   " . ($exists ? "✓" : "✗") . " Table {$table}\n";
    $all_checks_passed = $all_checks_passed && $exists;
}

// Summary
echo "\n=== Verification Summary ===\n";
if ($all_checks_passed) {
    echo "✓ All checks passed\n";
    echo "✓ Competency-mapped assessments are properly configured\n";
    echo "✓ System is ready for creating competency-based assessments\n";
} else {
    echo "✗ Some checks failed\n";
    echo "Please run configure_competency_assessments.php to fix issues\n";
}

exit($all_checks_passed ? 0 : 1);

?>
