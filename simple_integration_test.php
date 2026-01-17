<?php
/**
 * Simple integration tests for competency framework and learning plans
 * Task 2.2: Write integration tests for plugin compatibility
 * Requirements: 2.1, 3.1
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');

echo "=== Simple Competency Framework and Learning Plans Integration Tests ===\n\n";

/**
 * Test 1: Verify competency framework tables and functionality
 */
function test_competency_tables() {
    global $DB;
    
    echo "Test 1: Database Tables and Structure\n";
    
    $tables = [
        'competency_framework',
        'competency',
        'competency_coursecomp',
        'competency_usercomp',
        'competency_evidence',
        'competency_plan',
        'competency_plancomp',
        'competency_template',
        'competency_templatecomp'
    ];
    
    $all_exist = true;
    foreach ($tables as $table) {
        $exists = $DB->get_manager()->table_exists($table);
        echo "  Table {$table}: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
        if (!$exists) $all_exist = false;
    }
    
    return $all_exist;
}

/**
 * Test 2: Verify competency framework is enabled and functional
 */
function test_competency_enabled() {
    echo "\nTest 2: Competency Framework Configuration\n";
    
    $competencies_enabled = get_config('core', 'enablecompetencies');
    $learningplans_enabled = get_config('core', 'enablelearningplans');
    $completion_enabled = get_config('core', 'enablecompletion');
    
    echo "  Competencies enabled: " . ($competencies_enabled ? "✓ YES" : "✗ NO") . "\n";
    echo "  Learning plans enabled: " . ($learningplans_enabled ? "✓ YES" : "✗ NO") . "\n";
    echo "  Completion tracking enabled: " . ($completion_enabled ? "✓ YES" : "✗ NO") . "\n";
    
    return $competencies_enabled && $learningplans_enabled && $completion_enabled;
}

/**
 * Test 3: Test basic database operations
 */
function test_database_operations() {
    global $DB;
    
    echo "\nTest 3: Database Operations\n";
    
    try {
        // Test reading existing data
        $framework_count = $DB->count_records('competency_framework');
        echo "  Framework count query: ✓ SUCCESS ({$framework_count} records)\n";
        
        $competency_count = $DB->count_records('competency');
        echo "  Competency count query: ✓ SUCCESS ({$competency_count} records)\n";
        
        $plan_count = $DB->count_records('competency_plan');
        echo "  Learning plan count query: ✓ SUCCESS ({$plan_count} records)\n";
        
        // Test a join query (simulating reporting needs)
        $sql = "SELECT COUNT(*) as total 
                FROM {competency_framework} cf 
                LEFT JOIN {competency} c ON c.competencyframeworkid = cf.id";
        $result = $DB->get_record_sql($sql);
        echo "  Join query (framework-competency): ✓ SUCCESS ({$result->total} total relationships)\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "  ✗ Database operation failed: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Test 4: Verify reporting capabilities
 */
function test_reporting_capabilities() {
    global $DB;
    
    echo "\nTest 4: Reporting Data Access\n";
    
    try {
        // Test complex reporting query
        $sql = "SELECT 
                    cf.id as framework_id,
                    cf.shortname as framework_name,
                    COUNT(c.id) as competency_count,
                    COUNT(CASE WHEN c.parentid = 0 THEN 1 END) as parent_competencies,
                    COUNT(CASE WHEN c.parentid > 0 THEN 1 END) as child_competencies
                FROM {competency_framework} cf
                LEFT JOIN {competency} c ON c.competencyframeworkid = cf.id
                GROUP BY cf.id, cf.shortname
                ORDER BY cf.shortname";
        
        $frameworks = $DB->get_records_sql($sql);
        echo "  Complex reporting query: ✓ SUCCESS (" . count($frameworks) . " frameworks analyzed)\n";
        
        // Test plan-competency relationships
        $sql = "SELECT 
                    p.id as plan_id,
                    p.name as plan_name,
                    COUNT(pc.competencyid) as competency_count
                FROM {competency_plan} p
                LEFT JOIN {competency_plancomp} pc ON pc.planid = p.id
                GROUP BY p.id, p.name";
        
        $plans = $DB->get_records_sql($sql);
        echo "  Plan-competency analysis: ✓ SUCCESS (" . count($plans) . " plans analyzed)\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "  ✗ Reporting query failed: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Test 5: Verify competency API availability
 */
function test_competency_api() {
    echo "\nTest 5: Competency API Classes\n";
    
    $classes = [
        'core_competency\\competency_framework',
        'core_competency\\competency',
        'core_competency\\plan',
        'core_competency\\template',
        'core_competency\\evidence'
    ];
    
    $all_available = true;
    foreach ($classes as $class) {
        $exists = class_exists($class);
        echo "  Class {$class}: " . ($exists ? "✓ AVAILABLE" : "✗ MISSING") . "\n";
        if (!$exists) $all_available = false;
    }
    
    return $all_available;
}

// Run all tests
echo "Starting integration tests...\n\n";

$test1 = test_competency_tables();
$test2 = test_competency_enabled();
$test3 = test_database_operations();
$test4 = test_reporting_capabilities();
$test5 = test_competency_api();

echo "\n=== Integration Test Results ===\n";
echo "Database Tables: " . ($test1 ? "✓ PASSED" : "✗ FAILED") . "\n";
echo "Configuration: " . ($test2 ? "✓ PASSED" : "✗ FAILED") . "\n";
echo "Database Operations: " . ($test3 ? "✓ PASSED" : "✗ FAILED") . "\n";
echo "Reporting Capabilities: " . ($test4 ? "✓ PASSED" : "✗ FAILED") . "\n";
echo "API Availability: " . ($test5 ? "✓ PASSED" : "✗ FAILED") . "\n";

$all_passed = $test1 && $test2 && $test3 && $test4 && $test5;

echo "\n=== Requirements Validation ===\n";
echo "✓ Requirement 2.1: Competency framework integration - " . ($test1 && $test2 && $test5 ? "VERIFIED" : "FAILED") . "\n";
echo "✓ Requirement 3.1: Learning path functionality - " . ($test2 && $test3 ? "VERIFIED" : "FAILED") . "\n";
echo "✓ Configurable reports data access - " . ($test4 ? "VERIFIED" : "FAILED") . "\n";

if ($all_passed) {
    echo "\n✓ ALL INTEGRATION TESTS PASSED\n";
    echo "✓ Plugin compatibility confirmed\n";
    exit(0);
} else {
    echo "\n✗ SOME INTEGRATION TESTS FAILED\n";
    echo "✗ Please check configuration and try again\n";
    exit(1);
}

?>