<?php
/**
 * Master test runner for SCEH Moodle Regression Suite.
 * This script executes all key integration and property tests and returns a single exit code.
 */

define('CLI_SCRIPT', true);

// Detect Moodle config
$config_paths = [
    '/var/www/html/public/config.php',
    '/bitnami/moodle/config.php',
    dirname(__DIR__, 2) . '/moodle-core/public/config.php',
    dirname(__DIR__, 1) . '/config.php'
];

$moodle_config = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $moodle_config = $path;
        break;
    }
}

if (!$moodle_config) {
    echo "Error: Moodle config.php not found.\n";
    exit(1);
}

require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    ['help' => false, 'stop-on-error' => false],
    ['h' => 'help']
);

if ($options['help']) {
    echo "Master Test Runner\n";
    echo "Usage: php scripts/test/run_all_tests.php\n";
    exit(0);
}

$tests = [
    'Importer Async Flow' => 'scripts/test/test_importer_async_flow.php',
    'Allied Health Quiz Workflow' => 'scripts/test/test_allied_health_quiz_workflow.php',
    'Kirkpatrick Integration' => 'scripts/test/test_kirkpatrick_integration.php',
    'Competency Integration' => 'scripts/test/test_competency_integration.php',
    'Circular Dependency Prevention' => 'scripts/test/property_test_circular_dependency_prevention.php',
    'Version Isolation' => 'scripts/test/property_test_version_isolation.php',
    'Role Based Access Control' => 'scripts/test/property_test_role_based_access_control.php'
];

echo "=== Starting SCEH Moodle Regression Suite ===\n";
$failed = [];
$passed = 0;

foreach ($tests as $name => $path) {
    echo "\n[TEST] {$name} ({$path})...\n";
    $fullpath = $CFG->dirroot . '/' . ltrim($path, '/');
    
    if (!file_exists($fullpath)) {
         // Fallback for paths relative to script
         $fullpath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    if (!file_exists($fullpath)) {
        echo "✗ SKIPPED: File not found at {$fullpath}\n";
        $failed[] = "{$name} (File not found)";
        continue;
    }

    // Execute the script and capture output/exit status
    $command = "php {$fullpath}";
    $output = [];
    $retval = 0;
    exec($command, $output, $retval);

    if ($retval === 0) {
        echo "✓ PASSED\n";
        $passed++;
    } else {
        echo "✗ FAILED (Exit Code: {$retval})\n";
        echo "--- Output Start ---\n";
        echo implode("\n", array_slice($output, -10)); // Show last 10 lines
        echo "\n--- Output End ---\n";
        $failed[] = $name;
        
        if ($options['stop-on-error']) {
            break;
        }
    }
}

echo "\n" . str_repeat('=', 40) . "\n";
echo "Suite Summary:\n";
echo "Passed: {$passed} / " . count($tests) . "\n";

if ($failed) {
    echo "Failed Tests:\n";
    foreach ($failed as $f) {
        echo " - {$f}\n";
    }
    echo str_repeat('=', 40) . "\n";
    exit(1);
}

echo "ALL TESTS PASSED\n";
echo str_repeat('=', 40) . "\n";
exit(0);
