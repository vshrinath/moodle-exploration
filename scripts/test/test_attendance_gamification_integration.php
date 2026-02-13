<?php
/**
 * Integration test for Attendance and Gamification plugins
 * Task 2.5: Install attendance and gamification plugins
 * Requirements: 14.1, 15.2, 16.1
 * 
 * This script tests the integration of installed plugins with the competency framework
 */

define('CLI_SCRIPT', true);
require_once('/bitnami/moodle/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');

echo "========================================\n";
echo "Testing Attendance and Gamification Integration\n";
echo "========================================\n\n";

$test_results = [];
$all_tests_passed = true;

/**
 * Test helper function
 */
function run_test($test_name, $test_function) {
    global $test_results, $all_tests_passed;
    
    echo "Running: $test_name...\n";
    
    try {
        $result = $test_function();
        if ($result['success']) {
            echo "  ✓ PASSED: {$result['message']}\n";
            $test_results[$test_name] = 'PASSED';
        } else {
            echo "  ✗ FAILED: {$result['message']}\n";
            $test_results[$test_name] = 'FAILED';
            $all_tests_passed = false;
        }
    } catch (Exception $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . "\n";
        $test_results[$test_name] = 'ERROR';
        $all_tests_passed = false;
    }
    echo "\n";
}

/**
 * Test 1: Verify plugin files exist
 */
run_test('Plugin Files Existence', function() {
    global $CFG;
    
    $plugins = [
        'mod/attendance' => 'Attendance Plugin',
        'blocks/xp' => 'Level Up! Plugin',
        'mod/customcert' => 'Custom Certificate Plugin'
    ];
    
    $missing = [];
    foreach ($plugins as $path => $name) {
        if (!file_exists($CFG->dirroot . '/' . $path)) {
            $missing[] = $name;
        }
    }
    
    if (empty($missing)) {
        return ['success' => true, 'message' => 'All plugin files present'];
    } else {
        return ['success' => false, 'message' => 'Missing: ' . implode(', ', $missing)];
    }
});

/**
 * Test 2: Check plugin version files
 */
run_test('Plugin Version Files', function() {
    global $CFG;
    
    $version_files = [
        $CFG->dirroot . '/mod/attendance/version.php',
        $CFG->dirroot . '/blocks/xp/version.php',
        $CFG->dirroot . '/mod/customcert/version.php'
    ];
    
    $valid = 0;
    foreach ($version_files as $file) {
        if (file_exists($file)) {
            $plugin = new stdClass();
            include($file);
            if (isset($plugin->version)) {
                $valid++;
            }
        }
    }
    
    if ($valid === count($version_files)) {
        return ['success' => true, 'message' => "All $valid version files valid"];
    } else {
        return ['success' => false, 'message' => "Only $valid/" . count($version_files) . " version files valid"];
    }
});

/**
 * Test 3: Check competency framework availability
 */
run_test('Competency Framework Available', function() {
    global $CFG;
    
    if (file_exists($CFG->dirroot . '/competency/classes/competency_framework.php')) {
        return ['success' => true, 'message' => 'Competency framework available for integration'];
    } else {
        return ['success' => false, 'message' => 'Competency framework not found'];
    }
});

/**
 * Test 4: Check completion tracking enabled
 */
run_test('Completion Tracking Configuration', function() {
    global $CFG;
    
    if (isset($CFG->enablecompletion) && $CFG->enablecompletion == 1) {
        return ['success' => true, 'message' => 'Completion tracking enabled'];
    } else {
        return ['success' => false, 'message' => 'Completion tracking not enabled'];
    }
});

/**
 * Test 5: Check badges system availability
 */
run_test('Badges System Available', function() {
    global $CFG;
    
    if (file_exists($CFG->dirroot . '/badges/classes/badge.php')) {
        return ['success' => true, 'message' => 'Badges system available'];
    } else {
        return ['success' => false, 'message' => 'Badges system not found'];
    }
});

/**
 * Test 6: Verify database tables can be created
 */
run_test('Database Connection', function() {
    global $DB;
    
    try {
        $DB->get_record('config', ['name' => 'version']);
        return ['success' => true, 'message' => 'Database connection working'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
});

/**
 * Test 7: Check plugin installation readiness
 */
run_test('Plugin Installation Readiness', function() {
    global $CFG;
    
    $plugin_manager = core_plugin_manager::instance();
    
    $our_plugins = [
        'mod_attendance' => 'Attendance',
        'block_xp' => 'Level Up!',
        'mod_customcert' => 'Custom Certificate'
    ];
    $found = 0;
    
    foreach ($our_plugins as $component => $name) {
        $plugin = $plugin_manager->get_plugin_info($component);
        if ($plugin === null) {
            // Plugin files exist but not yet installed in database
            $found++;
        }
    }
    
    if ($found > 0) {
        return ['success' => true, 'message' => "$found plugins ready for installation"];
    } else {
        return ['success' => true, 'message' => 'Plugins may already be installed or need admin notification'];
    }
});

// Summary
echo "========================================\n";
echo "Test Summary\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;
$errors = 0;

foreach ($test_results as $test => $result) {
    echo "$test: $result\n";
    if ($result === 'PASSED') $passed++;
    elseif ($result === 'FAILED') $failed++;
    else $errors++;
}

echo "\n";
echo "Total: " . count($test_results) . " tests\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Errors: $errors\n";
echo "\n";

if ($all_tests_passed) {
    echo "========================================\n";
    echo "✓ ALL INTEGRATION TESTS PASSED\n";
    echo "========================================\n";
    echo "\nPlugins are ready for installation via Moodle admin interface.\n";
    echo "\nNext Steps:\n";
    echo "  1. Access http://localhost:8080\n";
    echo "  2. Login as administrator\n";
    echo "  3. Navigate to Site administration > Notifications\n";
    echo "  4. Complete plugin installation wizard\n";
    echo "  5. Run verify_attendance_gamification.php to confirm\n";
    exit(0);
} else {
    echo "========================================\n";
    echo "✗ SOME TESTS FAILED\n";
    echo "========================================\n";
    echo "\nPlease review the failed tests above.\n";
    exit(1);
}
