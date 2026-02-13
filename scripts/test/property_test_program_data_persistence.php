<?php
/**
 * Property-Based Test: Program Data Persistence
 * 
 * Property 1: For any program creation with valid name, description, audience, 
 * and owner data, storing and retrieving the program should return equivalent data.
 * 
 * Validates: Requirements 1.1
 * Feature: competency-based-learning
 * 
 * This test uses property-based testing to verify that program data is correctly
 * persisted and retrieved from the database across many random inputs.
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB, $CFG;

echo "=== Property Test: Program Data Persistence ===\n";
echo "Property 1: Program data should persist correctly across store/retrieve operations\n";
echo "Validates: Requirements 1.1\n\n";

// Test configuration
$TEST_ITERATIONS = 100;
$failures = [];
$successes = 0;

/**
 * Generate random program data
 */
function generate_random_program_data($iteration) {
    global $DB;
    
    $admin = get_admin();
    
    // Get a random category
    $categories = $DB->get_records('course_categories', null, '', 'id');
    $category_ids = array_keys($categories);
    $random_category = $category_ids[array_rand($category_ids)];
    
    // Generate random but valid data
    $shortname = 'PROP_TEST_' . $iteration . '_' . substr(md5(random_bytes(10)), 0, 8);
    $fullname = 'Property Test Program ' . $iteration . ' - ' . bin2hex(random_bytes(5));
    $outcomes = 'Test outcomes: ' . bin2hex(random_bytes(20));
    $target_audience = 'Test audience: ' . bin2hex(random_bytes(15));
    $version = '1.' . rand(0, 99);
    
    return [
        'shortname' => $shortname,
        'fullname' => $fullname,
        'categoryid' => $random_category,
        'owner_userid' => $admin->id,
        'outcomes' => $outcomes,
        'target_audience' => $target_audience,
        'version' => $version
    ];
}

/**
 * Create program with metadata
 */
function create_program_with_metadata($data) {
    global $DB;
    
    // Create course
    $course = new stdClass();
    $course->category = $data['categoryid'];
    $course->shortname = $data['shortname'];
    $course->fullname = $data['fullname'];
    $course->summary = 'Property test program';
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
    $metadata->program_version = $data['version'];
    $metadata->outcomes = $data['outcomes'];
    $metadata->target_audience = $data['target_audience'];
    $metadata->owner_userid = $data['owner_userid'];
    $metadata->created = time();
    $metadata->modified = time();
    
    $metadataid = $DB->insert_record('local_program_metadata', $metadata);
    
    return ['courseid' => $courseid, 'metadataid' => $metadataid];
}

/**
 * Retrieve and verify program data
 */
function retrieve_and_verify_program($courseid, $original_data) {
    global $DB;
    
    // Retrieve course
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        return ['success' => false, 'error' => 'Course not found'];
    }
    
    // Retrieve metadata
    $metadata = $DB->get_record('local_program_metadata', ['courseid' => $courseid]);
    if (!$metadata) {
        return ['success' => false, 'error' => 'Metadata not found'];
    }
    
    // Verify all fields match
    $errors = [];
    
    if ($course->shortname !== $original_data['shortname']) {
        $errors[] = "Shortname mismatch: expected '{$original_data['shortname']}', got '{$course->shortname}'";
    }
    
    if ($course->fullname !== $original_data['fullname']) {
        $errors[] = "Fullname mismatch: expected '{$original_data['fullname']}', got '{$course->fullname}'";
    }
    
    if ($course->category != $original_data['categoryid']) {
        $errors[] = "Category mismatch: expected '{$original_data['categoryid']}', got '{$course->category}'";
    }
    
    if ($metadata->program_version !== $original_data['version']) {
        $errors[] = "Version mismatch: expected '{$original_data['version']}', got '{$metadata->program_version}'";
    }
    
    if ($metadata->outcomes !== $original_data['outcomes']) {
        $errors[] = "Outcomes mismatch";
    }
    
    if ($metadata->target_audience !== $original_data['target_audience']) {
        $errors[] = "Target audience mismatch";
    }
    
    if ($metadata->owner_userid != $original_data['owner_userid']) {
        $errors[] = "Owner mismatch: expected '{$original_data['owner_userid']}', got '{$metadata->owner_userid}'";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return ['success' => true];
}

/**
 * Cleanup test program
 */
function cleanup_program($courseid) {
    global $DB;
    
    // Delete metadata
    $DB->delete_records('local_program_metadata', ['courseid' => $courseid]);
    
    // Delete course
    $DB->delete_records('course', ['id' => $courseid]);
}

// Run property-based tests
echo "Running $TEST_ITERATIONS iterations...\n\n";

for ($i = 1; $i <= $TEST_ITERATIONS; $i++) {
    try {
        // Generate random program data
        $program_data = generate_random_program_data($i);
        
        // Create program
        $ids = create_program_with_metadata($program_data);
        
        // Retrieve and verify
        $result = retrieve_and_verify_program($ids['courseid'], $program_data);
        
        if ($result['success']) {
            $successes++;
            if ($i % 10 == 0) {
                echo "✓ Iteration $i: PASS\n";
            }
        } else {
            $failures[] = [
                'iteration' => $i,
                'data' => $program_data,
                'error' => $result['error'] ?? implode(', ', $result['errors'] ?? [])
            ];
            echo "✗ Iteration $i: FAIL - {$result['error']}\n";
        }
        
        // Cleanup
        cleanup_program($ids['courseid']);
        
    } catch (Exception $e) {
        $failures[] = [
            'iteration' => $i,
            'data' => $program_data ?? null,
            'error' => $e->getMessage()
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
    echo "\n✓ Property holds: Program data persistence is correct across all test cases\n";
    echo "✓ Requirements 1.1 validated successfully\n";
    exit(0);
} else {
    echo "\n✗ Property violated: Program data persistence failed in some cases\n";
    echo "\nFailure details:\n";
    
    // Show first 5 failures
    $show_count = min(5, count($failures));
    for ($i = 0; $i < $show_count; $i++) {
        $failure = $failures[$i];
        echo "\nIteration {$failure['iteration']}:\n";
        echo "  Error: {$failure['error']}\n";
        if ($failure['data']) {
            echo "  Data: shortname={$failure['data']['shortname']}, version={$failure['data']['version']}\n";
        }
    }
    
    if (count($failures) > 5) {
        echo "\n... and " . (count($failures) - 5) . " more failures\n";
    }
    
    exit(1);
}
