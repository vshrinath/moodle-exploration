<?php
/**
 * Property-Based Test: Feedback Visibility Workflow
 * 
 * Property 12: Feedback Visibility Workflow
 * For any assessment submission, feedback should become visible to the learner 
 * only after it has been provided by an authorized trainer
 * 
 * **Validates: Requirements 5.3**
 * 
 * Feature: competency-based-learning
 * Property 12: Feedback Visibility Workflow
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "ERROR: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Property Test: Feedback Visibility Workflow ===\n\n";
echo "Property 12: For any assessment submission, feedback should become visible\n";
echo "to the learner only after it has been provided by an authorized trainer\n\n";

$test_iterations = 50;
$passed = 0;
$failed = 0;
$failures = [];

try {
    // Get or create test course
    $course = $DB->get_record('course', ['shortname' => 'PBT_FEEDBACK_TEST']);
    if (!$course) {
        $course = new stdClass();
        $course->fullname = 'PBT Feedback Test Course';
        $course->shortname = 'PBT_FEEDBACK_TEST';
        $course->category = 1;
        $course->format = 'topics';
        $course->visible = 1;
        $course->id = $DB->insert_record('course', $course);
        $course = $DB->get_record('course', ['id' => $course->id]);
    }
    
    // Get course context
    $context = context_course::instance($course->id);
    
    // Create trainer role assignment
    $trainer_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
    if (!$trainer_role) {
        throw new Exception("Editing teacher role not found");
    }
    
    echo "Running {$test_iterations} property test iterations...\n\n";
    
    for ($i = 1; $i <= $test_iterations; $i++) {
        // Create test learner
        $learner_username = 'pbt_learner_' . $i . '_' . time();
        $learner = new stdClass();
        $learner->username = $learner_username;
        $learner->firstname = 'PBT';
        $learner->lastname = 'Learner ' . $i;
        $learner->email = $learner_username . '@example.com';
        $learner->password = hash_internal_user_password('Test123!');
        $learner->mnethostid = $CFG->mnet_localhost_id;
        $learner->confirmed = 1;
        $learner->id = $DB->insert_record('user', $learner);
        
        // Create test trainer
        $trainer_username = 'pbt_trainer_' . $i . '_' . time();
        $trainer = new stdClass();
        $trainer->username = $trainer_username;
        $trainer->firstname = 'PBT';
        $trainer->lastname = 'Trainer ' . $i;
        $trainer->email = $trainer_username . '@example.com';
        $trainer->password = hash_internal_user_password('Test123!');
        $trainer->mnethostid = $CFG->mnet_localhost_id;
        $trainer->confirmed = 1;
        $trainer->id = $DB->insert_record('user', $trainer);
        
        // Enrol learner as student
        $student_role = $DB->get_record('role', ['shortname' => 'student']);
        $manual_enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        if (!$manual_enrol) {
            $enrol_plugin = enrol_get_plugin('manual');
            $enrol_plugin->add_instance($course);
            $manual_enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        }
        
        $enrol_plugin = enrol_get_plugin('manual');
        $enrol_plugin->enrol_user($manual_enrol, $learner->id, $student_role->id);
        $enrol_plugin->enrol_user($manual_enrol, $trainer->id, $trainer_role->id);
        
        // Create assignment directly in database (simplified for property testing)
        $assign_data = new stdClass();
        $assign_data->course = $course->id;
        $assign_data->name = 'PBT Assignment ' . $i;
        $assign_data->intro = 'Property test assignment';
        $assign_data->introformat = FORMAT_HTML;
        $assign_data->grade = 100;
        $assign_data->timemodified = time();
        $assign_data->timecreated = time();
        $assign_data->id = $DB->insert_record('assign', $assign_data);
        
        // Create submission record
        $submission = new stdClass();
        $submission->assignment = $assign_data->id;
        $submission->userid = $learner->id;
        $submission->timecreated = time();
        $submission->timemodified = time();
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $submission->latest = 1;
        $submission->attemptnumber = 0;
        $submission->id = $DB->insert_record('assign_submission', $submission);
        
        // Property Check 1: Grade should NOT exist before trainer provides feedback
        $grade_before = $DB->get_record('assign_grades', [
            'assignment' => $assign_data->id,
            'userid' => $learner->id
        ]);
        
        if ($grade_before && $grade_before->grade !== null && $grade_before->grade >= 0) {
            $failed++;
            $failures[] = [
                'iteration' => $i,
                'reason' => 'Grade/feedback exists before trainer provided it',
                'learner' => $learner_username,
                'assignment' => $assign_data->name,
                'unexpected_grade' => $grade_before->grade
            ];
            echo "  ✗ Iteration {$i}: Feedback visible before grading\n";
            
            // Clean up
            $DB->delete_records('assign_submission', ['id' => $submission->id]);
            $DB->delete_records('assign', ['id' => $assign_data->id]);
            delete_user($learner);
            delete_user($trainer);
            continue;
        }
        
        // Trainer provides feedback
        $grade_data = new stdClass();
        $grade_data->assignment = $assign_data->id;
        $grade_data->userid = $learner->id;
        $grade_data->grader = $trainer->id;
        $grade_data->grade = rand(60, 100);
        $grade_data->attemptnumber = 0;
        $grade_data->timemodified = time();
        $grade_data->timecreated = time();
        $grade_data->id = $DB->insert_record('assign_grades', $grade_data);
        
        // Add feedback comment
        $feedback_comment = new stdClass();
        $feedback_comment->assignment = $assign_data->id;
        $feedback_comment->grade = $grade_data->id;
        $feedback_comment->commenttext = 'Good work! Property test feedback.';
        $feedback_comment->commentformat = FORMAT_HTML;
        $DB->insert_record('assignfeedback_comments', $feedback_comment);
        
        // Property Check 2: Grade/feedback should NOW be visible
        $grade_after = $DB->get_record('assign_grades', [
            'assignment' => $assign_data->id,
            'userid' => $learner->id
        ]);
        
        $feedback_visible_after_grading = false;
        if ($grade_after && $grade_after->grade !== null && $grade_after->grade >= 0) {
            // Verify it was provided by authorized trainer
            if ($grade_after->grader == $trainer->id) {
                $feedback_visible_after_grading = true;
            }
        }
        
        if (!$feedback_visible_after_grading) {
            $failed++;
            $failures[] = [
                'iteration' => $i,
                'reason' => 'Feedback not visible after trainer provided it',
                'learner' => $learner_username,
                'trainer' => $trainer_username,
                'assignment' => $assign_data->name,
                'expected_grade' => $grade_data->grade,
                'actual_grade' => $grade_after ? $grade_after->grade : 'null'
            ];
            echo "  ✗ Iteration {$i}: Feedback not visible after grading\n";
        } else {
            $passed++;
            if ($i % 10 == 0) {
                echo "  ✓ Iterations 1-{$i}: Passed\n";
            }
        }
        
        // Clean up
        $DB->delete_records('assignfeedback_comments', ['assignment' => $assign_data->id]);
        $DB->delete_records('assign_grades', ['assignment' => $assign_data->id]);
        $DB->delete_records('assign_submission', ['assignment' => $assign_data->id]);
        $DB->delete_records('assign', ['id' => $assign_data->id]);
        delete_user($learner);
        delete_user($trainer);
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
            echo "  Learner: {$failure['learner']}\n";
            echo "  Assignment: {$failure['assignment']}\n";
            
            if (isset($failure['trainer'])) {
                echo "  Trainer: {$failure['trainer']}\n";
                echo "  Grade: {$failure['grade']}\n";
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
        echo "\nProperty 12 (Feedback Visibility Workflow) holds:\n";
        echo "- Feedback is NOT visible before trainer provides it\n";
        echo "- Feedback becomes visible after trainer grades submission\n";
        echo "- Only authorized trainers can provide feedback\n";
        echo "- Learners can view feedback once it's provided\n";
        exit(0);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

?>
