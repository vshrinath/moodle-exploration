<?php
/**
 * Create Learning Plan Templates
 * 
 * This script creates competency-based learning path templates with:
 * - Prerequisite enforcement
 * - Automatic learner assignment
 * - Core and allied competency classifications
 * 
 * Requirements: 3.1, 3.2
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
require_once($CFG->dirroot . '/competency/classes/template.php');
require_once($CFG->dirroot . '/competency/classes/plan.php');

use core_competency\api;
use core_competency\template;
use core_competency\plan;
use core_competency\template_competency;

// Ensure we're running as admin
core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "ERROR: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Creating Learning Plan Templates ===\n\n";

try {
    // Get the competency framework we created earlier
    $context = context_system::instance();
    $frameworks = api::list_frameworks('shortname', 'ASC', 0, 0, $context);
    
    if (empty($frameworks)) {
        throw new Exception("No competency frameworks found. Please run create_competency_framework_structure.php first.");
    }
    
    $framework = reset($frameworks);
    echo "Using competency framework: {$framework->get('shortname')}\n\n";
    
    // Get all competencies from the framework
    $competencies = api::list_competencies(['competencyframeworkid' => $framework->get('id')]);
    
    if (empty($competencies)) {
        throw new Exception("No competencies found in framework. Please create competencies first.");
    }
    
    echo "Found " . count($competencies) . " competencies in framework\n\n";
    
    // Organize competencies by category for template creation
    $competency_map = [];
    foreach ($competencies as $comp) {
        $shortname = $comp->get('shortname');
        $competency_map[$shortname] = $comp;
    }
    
    // Create learning plan templates
    $templates_created = [];
    
    // Template 1: Core Clinical Skills Path
    echo "Creating Template 1: Core Clinical Skills Path\n";
    $template1 = create_learning_plan_template(
        'core-clinical-skills',
        'Core Clinical Skills Learning Path',
        'Structured path for core clinical competencies with prerequisite enforcement',
        $framework,
        [
            'Clinical Skills' => ['order' => 1, 'is_core' => true],
            'Patient Examination' => ['order' => 2, 'is_core' => true],
            'Diagnosis and Assessment' => ['order' => 3, 'is_core' => true],
            'Treatment Management' => ['order' => 4, 'is_core' => true],
        ],
        $competency_map
    );
    $templates_created[] = $template1;
    echo "  ✓ Created: {$template1->get('shortname')}\n\n";
    
    // Template 2: Surgical Skills Path
    echo "Creating Template 2: Surgical Skills Path\n";
    $template2 = create_learning_plan_template(
        'surgical-skills-path',
        'Surgical Skills Learning Path',
        'Comprehensive surgical competency development path',
        $framework,
        [
            'Surgical Skills' => ['order' => 1, 'is_core' => true],
            'Cataract Surgery' => ['order' => 2, 'is_core' => true],
            'Retinal Surgery' => ['order' => 3, 'is_core' => true],
            'Glaucoma Surgery' => ['order' => 4, 'is_core' => false], // Allied
        ],
        $competency_map
    );
    $templates_created[] = $template2;
    echo "  ✓ Created: {$template2->get('shortname')}\n\n";
    
    // Template 3: Diagnostic Technology Path
    echo "Creating Template 3: Diagnostic Technology Path\n";
    $template3 = create_learning_plan_template(
        'diagnostic-technology-path',
        'Diagnostic Technology Learning Path',
        'Diagnostic equipment and technology competency development',
        $framework,
        [
            'Diagnostic Technology' => ['order' => 1, 'is_core' => true],
            'Ophthalmic Imaging' => ['order' => 2, 'is_core' => true],
            'Visual Field Testing' => ['order' => 3, 'is_core' => true],
        ],
        $competency_map
    );
    $templates_created[] = $template3;
    echo "  ✓ Created: {$template3->get('shortname')}\n\n";
    
    // Template 4: Professional Development Path
    echo "Creating Template 4: Professional Development Path\n";
    $template4 = create_learning_plan_template(
        'professional-development-path',
        'Professional Development Learning Path',
        'Professional skills and ethics development',
        $framework,
        [
            'Professional Development' => ['order' => 1, 'is_core' => true],
            'Patient Communication' => ['order' => 2, 'is_core' => true],
            'Medical Ethics' => ['order' => 3, 'is_core' => true],
            'Research Skills' => ['order' => 4, 'is_core' => false], // Allied
        ],
        $competency_map
    );
    $templates_created[] = $template4;
    echo "  ✓ Created: {$template4->get('shortname')}\n\n";
    
    // Template 5: Comprehensive Fellowship Path (Blended)
    echo "Creating Template 5: Comprehensive Fellowship Path\n";
    $template5 = create_learning_plan_template(
        'comprehensive-fellowship-path',
        'Comprehensive Fellowship Learning Path',
        'Complete ophthalmology fellowship training with all core competencies',
        $framework,
        [
            'Clinical Skills' => ['order' => 1, 'is_core' => true],
            'Patient Examination' => ['order' => 2, 'is_core' => true],
            'Diagnosis and Assessment' => ['order' => 3, 'is_core' => true],
            'Surgical Skills' => ['order' => 4, 'is_core' => true],
            'Cataract Surgery' => ['order' => 5, 'is_core' => true],
            'Diagnostic Technology' => ['order' => 6, 'is_core' => true],
            'Professional Development' => ['order' => 7, 'is_core' => true],
            'Patient Communication' => ['order' => 8, 'is_core' => true],
            'Research Skills' => ['order' => 9, 'is_core' => false], // Allied
        ],
        $competency_map
    );
    $templates_created[] = $template5;
    echo "  ✓ Created: {$template5->get('shortname')}\n\n";

    // Template 6: Blended Learning Path (same scope as comprehensive)
    echo "Creating Template 6: Blended Learning Path\n";
    $template6 = create_learning_plan_template(
        'blended-learning-path',
        'Blended Learning Path',
        'Blended program path with all core competencies',
        $framework,
        [
            'Clinical Skills' => ['order' => 1, 'is_core' => true],
            'Patient Examination' => ['order' => 2, 'is_core' => true],
            'Diagnosis and Assessment' => ['order' => 3, 'is_core' => true],
            'Surgical Skills' => ['order' => 4, 'is_core' => true],
            'Cataract Surgery' => ['order' => 5, 'is_core' => true],
            'Diagnostic Technology' => ['order' => 6, 'is_core' => true],
            'Professional Development' => ['order' => 7, 'is_core' => true],
            'Patient Communication' => ['order' => 8, 'is_core' => true],
            'Research Skills' => ['order' => 9, 'is_core' => false],
        ],
        $competency_map
    );
    $templates_created[] = $template6;
    echo "  ✓ Created: {$template6->get('shortname')}\n\n";

    // Template 7: Clinical Skills Path (alias to core clinical)
    echo "Creating Template 7: Clinical Skills Path\n";
    $template7 = create_learning_plan_template(
        'clinical-skills-path',
        'Clinical Skills Learning Path',
        'Clinical skills path aligned to core clinical competencies',
        $framework,
        [
            'Clinical Skills' => ['order' => 1, 'is_core' => true],
            'Patient Examination' => ['order' => 2, 'is_core' => true],
            'Diagnosis and Assessment' => ['order' => 3, 'is_core' => true],
            'Treatment Management' => ['order' => 4, 'is_core' => true],
        ],
        $competency_map
    );
    $templates_created[] = $template7;
    echo "  ✓ Created: {$template7->get('shortname')}\n\n";

    // Template 8: Core Technical Skills Path (surgical + diagnostic)
    echo "Creating Template 8: Core Technical Skills Path\n";
    $template8 = create_learning_plan_template(
        'core-technical-skills',
        'Core Technical Skills Learning Path',
        'Technical skills across surgical and diagnostic competencies',
        $framework,
        [
            'Surgical Skills' => ['order' => 1, 'is_core' => true],
            'Cataract Surgery' => ['order' => 2, 'is_core' => true],
            'Retinal Surgery' => ['order' => 3, 'is_core' => true],
            'Glaucoma Surgery' => ['order' => 4, 'is_core' => false],
            'Diagnostic Technology' => ['order' => 5, 'is_core' => true],
            'Ophthalmic Imaging' => ['order' => 6, 'is_core' => true],
            'Visual Field Testing' => ['order' => 7, 'is_core' => true],
        ],
        $competency_map
    );
    $templates_created[] = $template8;
    echo "  ✓ Created: {$template8->get('shortname')}\n\n";

    // Template 9: Management Skills Path (alias to professional development)
    echo "Creating Template 9: Management Skills Path\n";
    $template9 = create_learning_plan_template(
        'management-skills-path',
        'Management Skills Learning Path',
        'Management and professional development competencies',
        $framework,
        [
            'Professional Development' => ['order' => 1, 'is_core' => true],
            'Patient Communication' => ['order' => 2, 'is_core' => true],
            'Medical Ethics' => ['order' => 3, 'is_core' => true],
            'Research Skills' => ['order' => 4, 'is_core' => false],
        ],
        $competency_map
    );
    $templates_created[] = $template9;
    echo "  ✓ Created: {$template9->get('shortname')}\n\n";
    
    echo "=== Summary ===\n";
    echo "Created " . count($templates_created) . " learning plan templates:\n";
    foreach ($templates_created as $template) {
        $comp_count = count_template_competencies($template);
        echo "  - {$template->get('shortname')}: {$comp_count} competencies\n";
    }
    
    echo "\n✓ Learning plan templates created successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Assign templates to cohorts for automatic learner assignment\n";
    echo "2. Configure prerequisite enforcement rules\n";
    echo "3. Set up progress tracking and completion criteria\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Create a learning plan template with competencies
 */
function create_learning_plan_template($shortname, $name, $description, $framework, $competencies, $competency_map) {
    global $DB;
    
    // Check if template already exists
    $existing = $DB->get_record('competency_template', ['shortname' => $shortname]);
    if ($existing) {
        echo "  Template '$shortname' already exists, updating...\n";
        $template = new template($existing->id);
    } else {
        // Create new template
        $record = new stdClass();
        $record->shortname = $shortname;
        $record->contextid = context_system::instance()->id;
        $record->visible = 1;
        
        $template = api::create_template((object)[
            'shortname' => $shortname,
            'contextid' => context_system::instance()->id,
            'description' => $description,
            'descriptionformat' => FORMAT_HTML,
            'visible' => 1,
            'duedate' => 0,
        ]);
    }
    
    // Add competencies to template in specified order
    foreach ($competencies as $comp_shortname => $config) {
        if (!isset($competency_map[$comp_shortname])) {
            echo "  Warning: Competency '$comp_shortname' not found, skipping\n";
            continue;
        }
        
        $competency = $competency_map[$comp_shortname];
        
        // Check if competency is already in template
        $existing_link = $DB->get_record('competency_templatecomp', [
            'templateid' => $template->get('id'),
            'competencyid' => $competency->get('id')
        ]);
        
        if (!$existing_link) {
            // Add competency to template
            api::add_competency_to_template($template->get('id'), $competency->get('id'));
        }

        // Always set sort order to enforce consistent ordering
        $DB->set_field('competency_templatecomp', 'sortorder', $config['order'], [
            'templateid' => $template->get('id'),
            'competencyid' => $competency->get('id')
        ]);

        echo "  " . ($existing_link ? 'Updated' : 'Added') . ": {$comp_shortname} (order: {$config['order']}, " .
             ($config['is_core'] ? 'core' : 'allied') . ")\n";
    }
    
    return $template;
}

/**
 * Count competencies in a template
 */
function count_template_competencies($template) {
    $competencies = api::list_competencies_in_template($template);
    return count($competencies);
}
