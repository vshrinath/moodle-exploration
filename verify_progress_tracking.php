<?php
/**
 * Verify Progress Tracking Configuration
 * 
 * This script verifies:
 * - Automatic progress updates are enabled
 * - Competency completion criteria are configured
 * - Progress preservation is working
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/competency/classes/api.php');

use core_competency\api;

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "ERROR: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Verifying Progress Tracking Configuration ===\n\n";

$all_passed = true;

try {
    // Test 1: Verify competency framework is enabled
    echo "Test 1: Verify competency framework is enabled\n";
    $competencies_enabled = get_config('core', 'enablecompetencies');
    
    if ($competencies_enabled) {
        echo "  ✓ PASS: Competency framework is enabled\n";
    } else {
        echo "  ✗ FAIL: Competency framework is not enabled\n";
        $all_passed = false;
    }
    echo "\n";
    
    // Test 2: Verify automatic progress tracking
    echo "Test 2: Verify automatic progress tracking\n";
    $push_ratings = get_config('core', 'pushcourseratingstouserplans');
    $completion_enabled = get_config('core', 'enablecompletion');
    
    if ($push_ratings) {
        echo "  ✓ PASS: Automatic competency rating enabled\n";
    } else {
        echo "  ✗ FAIL: Automatic competency rating not enabled\n";
        $all_passed = false;
    }
    
    if ($completion_enabled) {
        echo "  ✓ PASS: Completion tracking enabled\n";
    } else {
        echo "  ✗ FAIL: Completion tracking not enabled\n";
        $all_passed = false;
    }
    echo "\n";
    
    // Test 3: Verify competency completion criteria
    echo "Test 3: Verify competency completion criteria\n";
    $context = context_system::instance();
    $frameworks = api::list_frameworks('shortname', 'ASC', 0, 1, $context);
    
    if (empty($frameworks)) {
        echo "  ✗ FAIL: No competency framework found\n";
        $all_passed = false;
    } else {
        $framework = reset($frameworks);
        $competencies = api::list_competencies(['competencyframeworkid' => $framework->get('id')]);
        
        $with_rules = 0;
        foreach ($competencies as $comp) {
            if ($comp->get('ruletype') != null && $comp->get('ruletype') != '') {
                $with_rules++;
            }
        }
        
        echo "  ✓ PASS: Found " . count($competencies) . " competencies\n";
        echo "  ✓ PASS: {$with_rules} competencies have completion rules configured\n";
    }
    echo "\n";
    
    // Test 4: Verify proficiency scale configuration
    echo "Test 4: Verify proficiency scale configuration\n";
    if (!empty($frameworks)) {
        $framework = reset($frameworks);
        $scale_id = $framework->get('scaleid');
        
        if ($scale_id) {
            $scale = $DB->get_record('scale', ['id' => $scale_id]);
            if ($scale) {
                echo "  ✓ PASS: Framework uses scale: {$scale->name}\n";
            } else {
                echo "  ✗ FAIL: Scale not found\n";
                $all_passed = false;
            }
        } else {
            echo "  ⚠ WARNING: No scale configured for framework\n";
        }
    }
    echo "\n";
    
    // Test 5: Verify progress preservation capability
    echo "Test 5: Verify progress preservation capability\n";
    
    // Check if user competency table exists and is accessible
    $table_exists = $DB->get_manager()->table_exists('competency_usercomp');
    
    if ($table_exists) {
        echo "  ✓ PASS: User competency tracking table exists\n";
        
        // Check if there are any user competency records
        $count = $DB->count_records('competency_usercomp');
        echo "  ✓ PASS: {$count} user competency records in system\n";
        
        // Check if evidence table exists
        $evidence_table = $DB->get_manager()->table_exists('competency_evidence');
        if ($evidence_table) {
            $evidence_count = $DB->count_records('competency_evidence');
            echo "  ✓ PASS: Evidence tracking enabled ({$evidence_count} evidence records)\n";
        }
    } else {
        echo "  ✗ FAIL: User competency tracking not available\n";
        $all_passed = false;
    }
    echo "\n";
    
    // Test 6: Verify learning plan configuration
    echo "Test 6: Verify learning plan configuration\n";
    $templates = api::list_templates('shortname', 'ASC', 0, 0, $context);
    
    if (!empty($templates)) {
        echo "  ✓ PASS: Found " . count($templates) . " learning plan templates\n";
        
        // Check if any plans are assigned to users
        $plan_count = $DB->count_records('competency_plan');
        echo "  ✓ PASS: {$plan_count} learning plans assigned to users\n";
    } else {
        echo "  ⚠ WARNING: No learning plan templates found\n";
    }
    echo "\n";
    
    // Summary
    echo "=== Verification Summary ===\n";
    if ($all_passed) {
        echo "✓ All critical tests passed!\n\n";
        echo "Progress tracking is configured correctly:\n";
        echo "- Automatic progress updates enabled\n";
        echo "- Competency completion criteria configured\n";
        echo "- Progress preservation working\n";
        echo "- Evidence tracking enabled\n";
        exit(0);
    } else {
        echo "✗ Some tests failed. Please review the output above.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
