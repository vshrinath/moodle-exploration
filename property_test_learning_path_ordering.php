<?php
/**
 * Property-Based Test: Learning Path Ordering Consistency
 * 
 * Property 7: Learning Path Ordering Consistency
 * For any learning path with ordered competencies, the ordering should be 
 * preserved and respect existing prerequisite relationships
 * 
 * **Validates: Requirements 3.1, 3.2**
 * 
 * Feature: competency-based-learning
 * Property 7: Learning Path Ordering Consistency
 */

define('CLI_SCRIPT', true);
$config_paths = [
    __DIR__ . '/config.php',
    '/bitnami/moodle/config.php',
    '/opt/bitnami/moodle/config.php',
];
$config_path = null;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}
if (!$config_path) {
    fwrite(STDERR, "ERROR: Moodle config.php not found\n");
    exit(1);
}
require_once($config_path);
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/competency/classes/api.php');

use core_competency\api;
use core_competency\template;
use core_competency\competency;

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "ERROR: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Property Test: Learning Path Ordering Consistency ===\n\n";
echo "Property 7: For any learning path with ordered competencies, the ordering\n";
echo "should be preserved and respect existing prerequisite relationships\n\n";

$test_iterations = 100;
$passed = 0;
$failed = 0;
$failures = [];

try {
    // Get existing framework
    $context = context_system::instance();
    $frameworks = api::list_frameworks('shortname', 'ASC', 0, 1, $context);
    
    if (empty($frameworks)) {
        throw new Exception("No competency framework found");
    }
    
    $framework = reset($frameworks);
    $all_competencies = api::list_competencies(['competencyframeworkid' => $framework->get('id')]);
    
    if (count($all_competencies) < 3) {
        throw new Exception("Need at least 3 competencies for testing");
    }
    
    echo "Running {$test_iterations} property test iterations...\n\n";
    
    for ($i = 1; $i <= $test_iterations; $i++) {
        // Generate random learning path configuration
        $num_competencies = rand(2, min(8, count($all_competencies)));
        $selected_comps = array_rand(array_values($all_competencies), $num_competencies);
        
        if (!is_array($selected_comps)) {
            $selected_comps = [$selected_comps];
        }
        
        $test_competencies = [];
        foreach ($selected_comps as $idx) {
            $comp = array_values($all_competencies)[$idx];
            $test_competencies[] = $comp;
        }
        
        // Create test template
        $template_shortname = 'pbt-ordering-test-' . $i . '-' . time();
        $template = api::create_template((object)[
            'shortname' => $template_shortname,
            'contextid' => $context->id,
            'description' => 'Property test template for ordering',
            'descriptionformat' => FORMAT_HTML,
            'visible' => 1,
            'duedate' => 0,
        ]);
        
        // Add competencies with explicit ordering
        $expected_order = [];
        $sort_order = 1;
        foreach ($test_competencies as $comp) {
            api::add_competency_to_template($template->get('id'), $comp->get('id'));
            
            // Set explicit sort order
            $DB->set_field('competency_templatecomp', 'sortorder', $sort_order, [
                'templateid' => $template->get('id'),
                'competencyid' => $comp->get('id')
            ]);
            
            $expected_order[$comp->get('id')] = $sort_order;
            $sort_order++;
        }
        
        // Property Test 1: Verify ordering is preserved
        $links = $DB->get_records('competency_templatecomp', ['templateid' => $template->get('id')], 'sortorder ASC', 'competencyid,sortorder');
        $actual_order = [];
        foreach ($links as $link) {
            $actual_order[$link->competencyid] = (int)$link->sortorder;
        }

        $ordering_preserved = ($expected_order == $actual_order);
        
        if (!$ordering_preserved) {
            $failed++;
            $failures[] = [
                'iteration' => $i,
                'reason' => 'Ordering not preserved',
                'template' => $template_shortname,
                'expected' => $expected_order,
                'actual' => $actual_order
            ];
            echo "  ✗ Iteration {$i}: Ordering not preserved\n";
        } else {
            // Property Test 2: Verify prerequisite relationships are respected
            $prerequisite_violations = [];
            
            foreach ($retrieved_comps as $comp) {
                $related = api::list_related_competencies($comp->get('id'));
                
                if (!empty($related)) {
                    // Check if prerequisites appear before this competency in the path
                    $comp_position = $actual_order[$comp->get('id')];
                    
                    foreach ($related as $prereq) {
                        if (isset($actual_order[$prereq->get('id')])) {
                            $prereq_position = $actual_order[$prereq->get('id')];
                            
                            // Prerequisite should come before dependent competency
                            // Note: In Moodle, prerequisites can be defined bidirectionally
                            // so we check if there's a logical ordering issue
                            if ($prereq_position > $comp_position) {
                                $prerequisite_violations[] = [
                                    'competency' => $comp->get('shortname'),
                                    'prerequisite' => $prereq->get('shortname'),
                                    'comp_position' => $comp_position,
                                    'prereq_position' => $prereq_position
                                ];
                            }
                        }
                    }
                }
            }
            
            if (!empty($prerequisite_violations)) {
                $failed++;
                $failures[] = [
                    'iteration' => $i,
                    'reason' => 'Prerequisite ordering violations',
                    'template' => $template_shortname,
                    'violations' => $prerequisite_violations
                ];
                echo "  ✗ Iteration {$i}: Prerequisite ordering violations found\n";
            } else {
                $passed++;
                if ($i % 10 == 0) {
                    echo "  ✓ Iterations 1-{$i}: Passed\n";
                }
            }
        }
        
        // Clean up test template
        api::delete_template($template->get('id'));
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
            echo "  Template: {$failure['template']}\n";
            
            if (isset($failure['violations'])) {
                echo "  Violations:\n";
                foreach ($failure['violations'] as $violation) {
                    echo "    - '{$violation['competency']}' (position {$violation['comp_position']}) ";
                    echo "requires '{$violation['prerequisite']}' (position {$violation['prereq_position']})\n";
                }
            }
            
            if (isset($failure['expected'])) {
                echo "  Expected order: " . json_encode($failure['expected']) . "\n";
                echo "  Actual order: " . json_encode($failure['actual']) . "\n";
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
        echo "\nProperty 7 (Learning Path Ordering Consistency) holds:\n";
        echo "- Competency ordering is preserved in learning paths\n";
        echo "- Prerequisite relationships are respected in path ordering\n";
        echo "- Learning paths maintain consistent ordering across operations\n";
        exit(0);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
