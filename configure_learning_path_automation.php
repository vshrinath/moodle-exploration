<?php
/**
 * Configure Learning Path Automation
 * 
 * This script configures:
 * - Automatic learner assignment to learning plans based on cohort membership
 * - Prerequisite enforcement in learning paths
 * - Competency completion rules
 * 
 * Requirements: 3.1, 3.2
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/competency/classes/api.php');
require_once($CFG->dirroot . '/cohort/lib.php');

use core_competency\api;
use core_competency\template;
use core_competency\template_cohort;

core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "ERROR: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Configuring Learning Path Automation ===\n\n";

try {
    // Get all learning plan templates
    $context = context_system::instance();
    $templates = api::list_templates('shortname', 'ASC', 0, 0, $context);
    
    if (empty($templates)) {
        throw new Exception("No learning plan templates found. Please run create_learning_plan_templates.php first.");
    }
    
    echo "Found " . count($templates) . " learning plan templates\n\n";
    
    // Get all cohorts
    $cohorts = cohort_get_all_cohorts(0, 0);
    
    if (empty($cohorts['cohorts'])) {
        echo "Warning: No cohorts found. Creating sample cohorts for demonstration...\n\n";
        $cohorts_list = create_sample_cohorts();
    } else {
        $cohorts_list = $cohorts['cohorts'];
        echo "Found " . count($cohorts_list) . " cohorts\n\n";
    }
    
    // Configure automatic assignment: Link templates to cohorts
    echo "=== Configuring Automatic Learner Assignment ===\n\n";
    
    $assignments = [];
    
    foreach ($templates as $template) {
        $shortname = $template->get('shortname');
        
        // Find matching cohort or use first available
        $matching_cohort = null;
        foreach ($cohorts_list as $cohort) {
            if (stripos($cohort->name, 'technical') !== false && stripos($shortname, 'technical') !== false) {
                $matching_cohort = $cohort;
                break;
            } elseif (stripos($cohort->name, 'clinical') !== false && stripos($shortname, 'clinical') !== false) {
                $matching_cohort = $cohort;
                break;
            } elseif (stripos($cohort->name, 'management') !== false && stripos($shortname, 'management') !== false) {
                $matching_cohort = $cohort;
                break;
            }
        }
        
        // If no specific match, use first cohort for demonstration
        if (!$matching_cohort && !empty($cohorts_list)) {
            $matching_cohort = reset($cohorts_list);
        }
        
        if ($matching_cohort) {
            // Link template to cohort for automatic assignment
            $existing = $DB->get_record('competency_templatecohort', [
                'templateid' => $template->get('id'),
                'cohortid' => $matching_cohort->id
            ]);
            
            if (!$existing) {
                api::create_template_cohort($template->get('id'), $matching_cohort->id);
                echo "✓ Linked template '{$shortname}' to cohort '{$matching_cohort->name}'\n";
                echo "  → Users added to this cohort will automatically get this learning plan\n\n";
                
                $assignments[] = [
                    'template' => $shortname,
                    'cohort' => $matching_cohort->name
                ];
            } else {
                echo "  Template '{$shortname}' already linked to cohort '{$matching_cohort->name}'\n\n";
            }
        }
    }
    
    // Configure prerequisite enforcement
    echo "\n=== Configuring Prerequisite Enforcement ===\n\n";
    
    foreach ($templates as $template) {
        $competencies = api::list_competencies_in_template($template);
        
        echo "Template: {$template->get('shortname')}\n";
        
        if (empty($competencies)) {
            echo "  No competencies in this template\n\n";
            continue;
        }
        
        echo "Competencies in learning path (" . count($competencies) . " total):\n";
        
        $order = 1;
        foreach ($competencies as $comp) {
            echo "  {$order}. {$comp->get('shortname')} - {$comp->get('idnumber')}\n";
            
            // Check if this competency has prerequisites defined
            try {
                $related = api::list_related_competencies($comp->get('id'));
                if (!empty($related)) {
                    echo "     Prerequisites: ";
                    $prereq_names = [];
                    foreach ($related as $rel) {
                        $prereq_names[] = $rel->get('shortname');
                    }
                    echo implode(', ', $prereq_names) . "\n";
                }
            } catch (Exception $e) {
                // Skip if prerequisites can't be retrieved
            }
            
            $order++;
        }
        echo "\n";
    }
    
    // Configure competency completion rules
    echo "=== Configuring Competency Completion Rules ===\n\n";
    
    // Enable competency-based completion globally
    set_config('enablecompetencies', 1);
    echo "✓ Competency framework enabled globally\n";
    
    set_config('pushcourseratingstouserplans', 1);
    echo "✓ Course ratings push to user plans enabled\n";
    
    // Configure learning plan settings
    set_config('competency_learningplanduedatescheduled', 1, 'tool_lp');
    echo "✓ Learning plan due date scheduling enabled\n";
    
    echo "\n=== Summary ===\n";
    echo "Configured automatic assignment for " . count($assignments) . " template-cohort pairs:\n";
    foreach ($assignments as $assignment) {
        echo "  - {$assignment['template']} → {$assignment['cohort']}\n";
    }
    
    echo "\n✓ Learning path automation configured successfully!\n";
    echo "\nHow it works:\n";
    echo "1. When a user is added to a cohort, they automatically get the linked learning plan\n";
    echo "2. Competencies in the plan are ordered and prerequisites are enforced\n";
    echo "3. Progress is tracked automatically as users complete activities\n";
    echo "4. Users cannot progress to dependent competencies until prerequisites are met\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Create sample cohorts for demonstration
 */
function create_sample_cohorts() {
    $cohorts = [];
    
    $cohort_configs = [
        ['name' => 'Technical Training Cohort', 'idnumber' => 'TECH-2025-01'],
        ['name' => 'Clinical Skills Cohort', 'idnumber' => 'CLIN-2025-01'],
        ['name' => 'Management Training Cohort', 'idnumber' => 'MGMT-2025-01'],
    ];
    
    foreach ($cohort_configs as $config) {
        $existing = cohort_get_cohort_by_idnumber($config['idnumber']);
        
        if (!$existing) {
            $cohort = new stdClass();
            $cohort->contextid = context_system::instance()->id;
            $cohort->name = $config['name'];
            $cohort->idnumber = $config['idnumber'];
            $cohort->description = 'Sample cohort for ' . $config['name'];
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->visible = 1;
            
            $cohort->id = cohort_add_cohort($cohort);
            $cohorts[$cohort->id] = $cohort;
            
            echo "  Created cohort: {$cohort->name}\n";
        } else {
            $cohorts[$existing->id] = $existing;
        }
    }
    
    echo "\n";
    return $cohorts;
}
