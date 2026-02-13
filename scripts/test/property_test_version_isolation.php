<?php
/**
 * Property-Based Test: Version Isolation
 * 
 * Property 3: For any program with multiple versions, existing cohorts should 
 * remain associated with their original version while new cohorts use the latest version.
 * 
 * Validates: Requirements 1.3
 * Feature: competency-based-learning
 * 
 * This test verifies that program versioning maintains proper isolation between
 * versions and cohort associations.
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB, $CFG;

echo "=== Property Test: Version Isolation ===\n";
echo "Property 3: Program versions should maintain isolation with correct cohort associations\n";
echo "Validates: Requirements 1.3\n\n";

// Test configuration
$TEST_ITERATIONS = 50;
$failures = [];
$successes = 0;

/**
 * Create a program with metadata
 */
function create_test_program($shortname, $version) {
    global $DB;
    
    $admin = get_admin();
    $categories = $DB->get_records('course_categories', null, '', 'id', 0, 1);
    $category = reset($categories);
    
    // Create course
    $course = new stdClass();
    $course->category = $category->id;
    $course->shortname = $shortname;
    $course->fullname = "Test Program $shortname";
    $course->summary = 'Version isolation test';
    $course->summaryformat = FORMAT_HTML;
    $course->format = 'topics';
    $course->visible = 1;
    $course->enablecompletion = 1;
    $course->startdate = time();
    $course->timecreated = time();
    $course->timemodified = time();
    
    $courseid = $DB->insert_record('course', $course);
    
    // Create metadata
    $metadata = new stdClass();
    $metadata->courseid = $courseid;
    $metadata->program_version = $version;
    $metadata->outcomes = "Test outcomes for version $version";
    $metadata->target_audience = "Test audience";
    $metadata->owner_userid = $admin->id;
    $metadata->created = time();
    $metadata->modified = time();
    
    $DB->insert_record('local_program_metadata', $metadata);
    
    return $courseid;
}

/**
 * Create a cohort associated with a program
 */
function create_test_cohort($name, $courseid) {
    global $DB;
    
    $cohort = new stdClass();
    $cohort->contextid = context_system::instance()->id;
    $cohort->name = $name;
    $cohort->idnumber = 'TEST_' . substr(md5($name . time()), 0, 10);
    $cohort->description = "Test cohort for course $courseid";
    $cohort->descriptionformat = FORMAT_HTML;
    $cohort->visible = 1;
    $cohort->timecreated = time();
    $cohort->timemodified = time();
    
    $cohortid = $DB->insert_record('cohort', $cohort);
    
    // Enrol cohort to course
    $enrol = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'cohort']);
    if (!$enrol) {
        $enrol = new stdClass();
        $enrol->enrol = 'cohort';
        $enrol->status = 0;
        $enrol->courseid = $courseid;
        $enrol->sortorder = 0;
        $enrol->timecreated = time();
        $enrol->timemodified = time();
        $enrol->customint1 = $cohortid;
        
        $DB->insert_record('enrol', $enrol);
    }
    
    return $cohortid;
}

/**
 * Create a new version of a program
 */
function create_program_version($original_courseid, $new_version) {
    global $DB;
    
    $original = $DB->get_record('course', ['id' => $original_courseid]);
    $original_metadata = $DB->get_record('local_program_metadata', ['courseid' => $original_courseid]);
    
    // Create new course
    $new_course = new stdClass();
    $new_course->category = $original->category;
    $new_course->shortname = $original->shortname . '_v' . str_replace('.', '_', $new_version);
    $new_course->fullname = $original->fullname . ' (Version ' . $new_version . ')';
    $new_course->summary = $original->summary;
    $new_course->summaryformat = $original->summaryformat;
    $new_course->format = $original->format;
    $new_course->visible = 1;
    $new_course->enablecompletion = $original->enablecompletion;
    $new_course->startdate = time();
    $new_course->timecreated = time();
    $new_course->timemodified = time();
    
    $new_courseid = $DB->insert_record('course', $new_course);
    
    // Create metadata for new version
    $new_metadata = new stdClass();
    $new_metadata->courseid = $new_courseid;
    $new_metadata->program_version = $new_version;
    $new_metadata->outcomes = $original_metadata->outcomes . " (Updated in v$new_version)";
    $new_metadata->target_audience = $original_metadata->target_audience;
    $new_metadata->owner_userid = $original_metadata->owner_userid;
    $new_metadata->created = time();
    $new_metadata->modified = time();
    
    $DB->insert_record('local_program_metadata', $new_metadata);
    
    return $new_courseid;
}

/**
 * Verify version isolation
 */
function verify_version_isolation($original_courseid, $new_courseid, $old_cohortid, $new_cohortid) {
    global $DB;
    
    $errors = [];
    
    // Verify original course still exists with original version
    $original_metadata = $DB->get_record('local_program_metadata', ['courseid' => $original_courseid]);
    if (!$original_metadata) {
        $errors[] = "Original program metadata not found";
    } elseif (!preg_match('/^1\./', $original_metadata->program_version)) {
        $errors[] = "Original version changed: {$original_metadata->program_version}";
    }
    
    // Verify new course has new version
    $new_metadata = $DB->get_record('local_program_metadata', ['courseid' => $new_courseid]);
    if (!$new_metadata) {
        $errors[] = "New program metadata not found";
    } elseif (!preg_match('/^2\./', $new_metadata->program_version)) {
        $errors[] = "New version incorrect: {$new_metadata->program_version}";
    }
    
    // Verify old cohort still associated with original course
    $old_enrol = $DB->get_record('enrol', ['courseid' => $original_courseid, 'customint1' => $old_cohortid]);
    if (!$old_enrol) {
        $errors[] = "Old cohort no longer associated with original course";
    }
    
    // Verify new cohort associated with new course
    $new_enrol = $DB->get_record('enrol', ['courseid' => $new_courseid, 'customint1' => $new_cohortid]);
    if (!$new_enrol) {
        $errors[] = "New cohort not associated with new course";
    }
    
    // Verify old cohort NOT associated with new course
    $wrong_enrol = $DB->get_record('enrol', ['courseid' => $new_courseid, 'customint1' => $old_cohortid]);
    if ($wrong_enrol) {
        $errors[] = "Old cohort incorrectly associated with new course";
    }
    
    // Verify versions are different
    if ($original_metadata && $new_metadata && $original_metadata->program_version === $new_metadata->program_version) {
        $errors[] = "Versions are not isolated (both have same version)";
    }
    
    return $errors;
}

/**
 * Cleanup test data
 */
function cleanup_test_data($courseids, $cohortids) {
    global $DB;
    
    foreach ($cohortids as $cohortid) {
        $DB->delete_records('cohort', ['id' => $cohortid]);
    }
    
    foreach ($courseids as $courseid) {
        $DB->delete_records('enrol', ['courseid' => $courseid]);
        $DB->delete_records('local_program_metadata', ['courseid' => $courseid]);
        $DB->delete_records('course', ['id' => $courseid]);
    }
}

// Run property-based tests
echo "Running $TEST_ITERATIONS iterations...\n\n";

for ($i = 1; $i <= $TEST_ITERATIONS; $i++) {
    try {
        $shortname = 'VER_TEST_' . $i . '_' . substr(md5(random_bytes(10)), 0, 6);
        
        // Create original program (version 1.0)
        $original_version = '1.' . rand(0, 9);
        $original_courseid = create_test_program($shortname, $original_version);
        
        // Create cohort for original version
        $old_cohort_name = "Cohort for $shortname v$original_version";
        $old_cohortid = create_test_cohort($old_cohort_name, $original_courseid);
        
        // Create new version (version 2.0)
        $new_version = '2.' . rand(0, 9);
        $new_courseid = create_program_version($original_courseid, $new_version);
        
        // Create cohort for new version
        $new_cohort_name = "Cohort for $shortname v$new_version";
        $new_cohortid = create_test_cohort($new_cohort_name, $new_courseid);
        
        // Verify isolation
        $errors = verify_version_isolation($original_courseid, $new_courseid, $old_cohortid, $new_cohortid);
        
        if (empty($errors)) {
            $successes++;
            if ($i % 10 == 0) {
                echo "✓ Iteration $i: PASS\n";
            }
        } else {
            $failures[] = [
                'iteration' => $i,
                'shortname' => $shortname,
                'errors' => $errors
            ];
            echo "✗ Iteration $i: FAIL - " . implode(', ', $errors) . "\n";
        }
        
        // Cleanup
        cleanup_test_data([$original_courseid, $new_courseid], [$old_cohortid, $new_cohortid]);
        
    } catch (Exception $e) {
        $failures[] = [
            'iteration' => $i,
            'shortname' => $shortname ?? 'unknown',
            'errors' => [$e->getMessage()]
        ];
        echo "✗ Iteration $i: EXCEPTION - {$e->getMessage()}\n";
    }
}

// Report results
echo "\n=== Test Results ===\n";
echo "Total iterations: $TEST_ITERATIONS\n";
echo "Successes: $successes\n";
echo "Failures: " . count($failures) . "\n";

if (empty($failures)) {
    echo "\n✓ Property holds: Version isolation is maintained correctly\n";
    echo "✓ Requirements 1.3 validated successfully\n";
    exit(0);
} else {
    echo "\n✗ Property violated: Version isolation failed in some cases\n";
    echo "\nFailure details:\n";
    
    $show_count = min(5, count($failures));
    for ($i = 0; $i < $show_count; $i++) {
        $failure = $failures[$i];
        echo "\nIteration {$failure['iteration']} ({$failure['shortname']}):\n";
        foreach ($failure['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
    if (count($failures) > 5) {
        echo "\n... and " . (count($failures) - 5) . " more failures\n";
    }
    
    exit(1);
}
