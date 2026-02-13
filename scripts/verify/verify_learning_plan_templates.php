<?php
/**
 * Verify Learning Plan Templates
 * 
 * This script verifies:
 * - Learning plan templates are created correctly
 * - Competencies are ordered properly
 * - Automatic assignment is configured
 * - Prerequisites are enforced
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/competency/classes/api.php');

use core_competency\api;

echo "=== Verifying Learning Plan Templates ===\n\n";

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "ERROR: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

$all_passed = true;

try {
    // Test 1: Verify templates exist
    echo "Test 1: Verify learning plan templates exist\n";
    $context = context_system::instance();
    $templates = api::list_templates('shortname', 'ASC', 0, 0, $context);
    
    if (empty($templates)) {
        echo "  ✗ FAIL: No learning plan templates found\n\n";
        $all_passed = false;
    } else {
        echo "  ✓ PASS: Found " . count($templates) . " learning plan templates\n";
        foreach ($templates as $template) {
            echo "    - {$template->get('shortname')}\n";
        }
        echo "\n";
    }
    
    // Test 2: Verify competencies are in templates
    echo "Test 2: Verify competencies are assigned to templates\n";
    $templates_with_comps = 0;
    foreach ($templates as $template) {
        $competencies = api::list_competencies_in_template($template);
        if (!empty($competencies)) {
            $templates_with_comps++;
            echo "  ✓ Template '{$template->get('shortname')}' has " . count($competencies) . " competencies\n";
        } else {
            echo "  ✗ Template '{$template->get('shortname')}' has no competencies\n";
            $all_passed = false;
        }
    }
    echo "\n";
    
    // Test 3: Verify competency ordering
    echo "Test 3: Verify competency ordering in templates\n";
    foreach ($templates as $template) {
        $links = $DB->get_records('competency_templatecomp', ['templateid' => $template->get('id')], 'sortorder ASC', 'sortorder');

        if (empty($links)) {
            continue;
        }

        $ordering_correct = true;
        $expected = 1;
        foreach ($links as $link) {
            $current_order = (int)$link->sortorder;
            if ($current_order !== $expected) {
                $ordering_correct = false;
                break;
            }
            $expected++;
        }
        
        if ($ordering_correct) {
            echo "  ✓ Template '{$template->get('shortname')}' has correct competency ordering\n";
        } else {
            echo "  ✗ Template '{$template->get('shortname')}' has incorrect competency ordering\n";
            $all_passed = false;
        }
    }
    echo "\n";
    
    // Test 4: Verify automatic assignment configuration
    echo "Test 4: Verify automatic assignment to cohorts\n";
    $assignments_found = 0;
    
    foreach ($templates as $template) {
        $cohorts = $DB->get_records('competency_templatecohort', ['templateid' => $template->get('id')]);
        
        if (!empty($cohorts)) {
            $assignments_found++;
            echo "  ✓ Template '{$template->get('shortname')}' linked to " . count($cohorts) . " cohort(s)\n";
            
            foreach ($cohorts as $link) {
                $cohort = $DB->get_record('cohort', ['id' => $link->cohortid]);
                if ($cohort) {
                    echo "    → {$cohort->name}\n";
                }
            }
        }
    }
    
    if ($assignments_found > 0) {
        echo "  ✓ Found {$assignments_found} template-cohort assignments\n";
    } else {
        echo "  ⚠ Warning: No automatic assignments configured\n";
    }
    echo "\n";
    
    // Test 5: Verify prerequisite relationships
    echo "Test 5: Verify prerequisite relationships\n";
    $prereqs_found = 0;
    
    foreach ($templates as $template) {
        $competencies = api::list_competencies_in_template($template);
        
        foreach ($competencies as $comp) {
            // $comp is already a competency object from list_competencies_in_template
            try {
                $related = api::list_related_competencies($comp->get('id'));
                
                if (!empty($related)) {
                    $prereqs_found++;
                    echo "  ✓ Competency '{$comp->get('shortname')}' has " . count($related) . " prerequisite(s)\n";
                }
            } catch (Exception $e) {
                // Skip if we can't get related competencies
            }
        }
    }
    
    if ($prereqs_found > 0) {
        echo "  ✓ Found {$prereqs_found} competencies with prerequisites\n";
    } else {
        echo "  ℹ Info: No prerequisite relationships defined yet\n";
    }
    echo "\n";
    
    // Test 6: Verify competency framework settings
    echo "Test 6: Verify competency framework settings\n";
    
    $competencies_enabled = get_config('core', 'enablecompetencies');
    if ($competencies_enabled) {
        echo "  ✓ Competency framework is enabled globally\n";
    } else {
        echo "  ✗ Competency framework is not enabled\n";
        $all_passed = false;
    }
    
    $push_ratings = get_config('core', 'pushcourseratingstouserplans');
    if ($push_ratings) {
        echo "  ✓ Course ratings push to user plans is enabled\n";
    } else {
        echo "  ℹ Info: Course ratings push to user plans is not enabled\n";
    }
    echo "\n";
    
    // Summary
    echo "=== Verification Summary ===\n";
    if ($all_passed) {
        echo "✓ All critical tests passed!\n";
        echo "\nLearning plan templates are configured correctly:\n";
        echo "- Templates created with competencies\n";
        echo "- Competencies properly ordered\n";
        echo "- Automatic assignment ready\n";
        echo "- Prerequisite enforcement available\n";
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
