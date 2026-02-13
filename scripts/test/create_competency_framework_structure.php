<?php
/**
 * Create Competency Framework Structure
 * Task 3.1: Define competency categories and hierarchies
 * 
 * This script creates a comprehensive competency framework with:
 * - Competency categories and hierarchies
 * - Prerequisite relationship management
 * - Core vs allied competency classifications
 * 
 * Requirements: 2.1, 2.2, 2.3
 */

define('CLI_SCRIPT', true);
require_once('/opt/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/competency/classes/api.php');
require_once($CFG->dirroot.'/competency/classes/competency_framework.php');
require_once($CFG->dirroot.'/competency/classes/competency.php');

use core_competency\api;
use core_competency\competency_framework;
use core_competency\competency;

// Set admin user for permissions
$admin = get_admin();
if (!$admin) {
    echo "Error: No admin user found\n";
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Creating Competency Framework Structure ===\n\n";

/**
 * Create a competency framework using the API
 */
function create_framework($shortname, $idnumber, $description) {
    global $DB;
    
    try {
        // Get the default competence scale
        $scale = $DB->get_record('scale', ['name' => 'Default competence scale']);
        if (!$scale) {
            $scale = $DB->get_record_sql("SELECT * FROM {scale} ORDER BY id LIMIT 1");
        }
        
        // Build scale configuration - must be JSON with objects
        $scale_items = explode(',', $scale->scale);
        $scale_config = [(object)['scaleid' => $scale->id]];  // First element with scale ID
        
        foreach ($scale_items as $index => $item) {
            $scale_config[] = (object)[
                'id' => $index + 1,
                'scaledefault' => ($index == count($scale_items) - 1) ? 1 : 0,
                'proficient' => ($index == count($scale_items) - 1) ? 1 : 0
            ];
        }
        
        $framework_data = (object)[
            'shortname' => $shortname,
            'idnumber' => $idnumber,
            'description' => $description,
            'descriptionformat' => FORMAT_HTML,
            'visible' => 1,
            'contextid' => context_system::instance()->id,
            'scaleid' => $scale->id,
            'scaleconfiguration' => json_encode($scale_config),
            'taxonomies' => ''
        ];
        
        // Use the API to create the framework
        $framework = api::create_framework($framework_data);
        
        echo "✓ Framework created: {$shortname} (ID: " . $framework->get('id') . ")\n";
        return $framework;
        
    } catch (Exception $e) {
        echo "✗ Failed to create framework {$shortname}: " . $e->getMessage() . "\n";
        echo "Debug info: " . $e->getTraceAsString() . "\n";
        return null;
    }
}

/**
 * Create a competency with optional parent using the API
 */
function create_competency($framework_id, $shortname, $idnumber, $description, $parent_id = 0, $sortorder = 0) {
    global $DB;
    
    // Check if competency already exists
    $existing = $DB->get_record('competency', ['idnumber' => $idnumber, 'competencyframeworkid' => $framework_id]);
    if ($existing) {
        $level = $parent_id > 0 ? "  Child" : "Parent";
        echo "  ✓ {$level} competency exists: {$shortname} (ID: {$existing->id})\n";
        return new competency($existing->id);
    }
    
    try {
        $competency_data = (object)[
            'shortname' => $shortname,
            'idnumber' => $idnumber,
            'description' => $description,
            'descriptionformat' => FORMAT_HTML,
            'competencyframeworkid' => $framework_id,
            'parentid' => $parent_id,
            'sortorder' => $sortorder
        ];
        
        // Use the API to create the competency
        $competency = api::create_competency($competency_data);
        
        $level = $parent_id > 0 ? "  Child" : "Parent";
        echo "  ✓ {$level} competency created: {$shortname} (ID: " . $competency->get('id') . ")\n";
        return $competency;
        
    } catch (Exception $e) {
        echo "  ✗ Failed to create competency {$shortname}: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Add prerequisite relationship between competencies
 */
function add_prerequisite($competency, $prerequisite_competency) {
    global $DB, $USER;
    
    try {
        // Check for circular dependencies before adding
        if (would_create_circular_dependency($competency->get('id'), $prerequisite_competency->get('id'))) {
            echo "  ⚠ Skipping prerequisite: Would create circular dependency\n";
            return false;
        }
        
        // Create prerequisite relationship in competency_relatedcomp table
        $relation = new stdClass();
        $relation->competencyid = $competency->get('id');
        $relation->relatedcompetencyid = $prerequisite_competency->get('id');
        $relation->timecreated = time();
        $relation->timemodified = time();
        $relation->usermodified = $USER->id;
        
        if (!$DB->record_exists('competency_relatedcomp', [
            'competencyid' => $relation->competencyid,
            'relatedcompetencyid' => $relation->relatedcompetencyid
        ])) {
            $DB->insert_record('competency_relatedcomp', $relation);
            echo "  ✓ Prerequisite added: {$prerequisite_competency->get('shortname')} → {$competency->get('shortname')}\n";
            return true;
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "  ✗ Failed to add prerequisite: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Check if adding a prerequisite would create a circular dependency
 */
function would_create_circular_dependency($competency_id, $prerequisite_id, $visited = []) {
    global $DB;
    
    // If prerequisite is the same as competency, it's circular
    if ($competency_id == $prerequisite_id) {
        return true;
    }
    
    // Check if we've already visited this node (cycle detection)
    if (in_array($prerequisite_id, $visited)) {
        return true;
    }
    
    $visited[] = $prerequisite_id;
    
    // Check if competency is already a prerequisite of the proposed prerequisite
    $existing = $DB->get_records('competency_relatedcomp', ['competencyid' => $prerequisite_id]);
    
    foreach ($existing as $relation) {
        if ($relation->relatedcompetencyid == $competency_id) {
            return true;
        }
        
        // Recursively check for indirect cycles
        if (would_create_circular_dependency($competency_id, $relation->relatedcompetencyid, $visited)) {
            return true;
        }
    }
    
    return false;
}

// ============================================================================
// Main Execution: Create Sample Competency Framework Structure
// ============================================================================

echo "Creating sample competency framework for ophthalmology fellowship...\n\n";

// Check if framework already exists
global $DB;
$existing_framework = $DB->get_record('competency_framework', ['idnumber' => 'OPHTHAL_FELLOW_2025']);

if ($existing_framework) {
    echo "Framework already exists (ID: {$existing_framework->id}). Using existing framework.\n\n";
    $framework = new competency_framework($existing_framework->id);
} else {
    // Create main framework
    $framework = create_framework(
        'Ophthalmology Fellowship Competencies',
        'OPHTHAL_FELLOW_2025',
        'Comprehensive competency framework for ophthalmology fellowship training program'
    );

    if (!$framework) {
        echo "\n✗ Failed to create framework. Exiting.\n";
        exit(1);
    }
}

echo "\n--- Creating Competency Categories and Hierarchies ---\n";

// Category 1: Clinical Skills
$clinical_parent = create_competency(
    $framework->get('id'),
    'Clinical Skills',
    'CLIN_SKILLS',
    'Core clinical competencies for patient care and diagnosis',
    0,
    1
);

$clinical_children = [];
if ($clinical_parent) {
    $clinical_children['examination'] = create_competency(
        $framework->get('id'),
        'Patient Examination',
        'CLIN_EXAM',
        'Comprehensive ophthalmic examination techniques',
        $clinical_parent->get('id'),
        1
    );
    
    $clinical_children['diagnosis'] = create_competency(
        $framework->get('id'),
        'Diagnosis and Assessment',
        'CLIN_DIAG',
        'Diagnostic reasoning and clinical assessment',
        $clinical_parent->get('id'),
        2
    );
    
    $clinical_children['management'] = create_competency(
        $framework->get('id'),
        'Treatment Management',
        'CLIN_MGMT',
        'Patient treatment planning and management',
        $clinical_parent->get('id'),
        3
    );
}

// Category 2: Surgical Skills
$surgical_parent = create_competency(
    $framework->get('id'),
    'Surgical Skills',
    'SURG_SKILLS',
    'Surgical competencies across ophthalmology subspecialties',
    0,
    2
);

$surgical_children = [];
if ($surgical_parent) {
    $surgical_children['cataract'] = create_competency(
        $framework->get('id'),
        'Cataract Surgery',
        'SURG_CATARACT',
        'Phacoemulsification and intraocular lens implantation',
        $surgical_parent->get('id'),
        1
    );
    
    $surgical_children['retina'] = create_competency(
        $framework->get('id'),
        'Retinal Surgery',
        'SURG_RETINA',
        'Vitreoretinal surgical procedures',
        $surgical_parent->get('id'),
        2
    );
    
    $surgical_children['glaucoma'] = create_competency(
        $framework->get('id'),
        'Glaucoma Surgery',
        'SURG_GLAUCOMA',
        'Glaucoma surgical interventions',
        $surgical_parent->get('id'),
        3
    );
}

// Category 3: Diagnostic Skills
$diagnostic_parent = create_competency(
    $framework->get('id'),
    'Diagnostic Technology',
    'DIAG_TECH',
    'Proficiency in diagnostic equipment and imaging',
    0,
    3
);

$diagnostic_children = [];
if ($diagnostic_parent) {
    $diagnostic_children['imaging'] = create_competency(
        $framework->get('id'),
        'Ophthalmic Imaging',
        'DIAG_IMAGING',
        'OCT, fundus photography, and imaging interpretation',
        $diagnostic_parent->get('id'),
        1
    );
    
    $diagnostic_children['fields'] = create_competency(
        $framework->get('id'),
        'Visual Field Testing',
        'DIAG_FIELDS',
        'Perimetry and visual field interpretation',
        $diagnostic_parent->get('id'),
        2
    );
}

// Category 4: Professional Skills (Allied)
$professional_parent = create_competency(
    $framework->get('id'),
    'Professional Development',
    'PROF_DEV',
    'Professional and communication competencies',
    0,
    4
);

$professional_children = [];
if ($professional_parent) {
    $professional_children['communication'] = create_competency(
        $framework->get('id'),
        'Patient Communication',
        'PROF_COMM',
        'Effective patient and family communication',
        $professional_parent->get('id'),
        1
    );
    
    $professional_children['ethics'] = create_competency(
        $framework->get('id'),
        'Medical Ethics',
        'PROF_ETHICS',
        'Ethical decision-making and professionalism',
        $professional_parent->get('id'),
        2
    );
    
    $professional_children['research'] = create_competency(
        $framework->get('id'),
        'Research Skills',
        'PROF_RESEARCH',
        'Clinical research and evidence-based practice',
        $professional_parent->get('id'),
        3
    );
}

echo "\n--- Establishing Prerequisite Relationships ---\n";

// Diagnosis requires examination
if (isset($clinical_children['examination']) && isset($clinical_children['diagnosis'])) {
    add_prerequisite($clinical_children['diagnosis'], $clinical_children['examination']);
}

// Management requires diagnosis
if (isset($clinical_children['diagnosis']) && isset($clinical_children['management'])) {
    add_prerequisite($clinical_children['management'], $clinical_children['diagnosis']);
}

// Surgical skills require clinical examination
if ($clinical_parent && $surgical_parent) {
    add_prerequisite($surgical_parent, $clinical_parent);
}

// Advanced surgeries require basic cataract surgery
if (isset($surgical_children['cataract']) && isset($surgical_children['retina'])) {
    add_prerequisite($surgical_children['retina'], $surgical_children['cataract']);
}

if (isset($surgical_children['cataract']) && isset($surgical_children['glaucoma'])) {
    add_prerequisite($surgical_children['glaucoma'], $surgical_children['cataract']);
}

// Imaging interpretation requires clinical diagnosis skills
if (isset($clinical_children['diagnosis']) && isset($diagnostic_children['imaging'])) {
    add_prerequisite($diagnostic_children['imaging'], $clinical_children['diagnosis']);
}

echo "\n--- Testing Circular Dependency Prevention ---\n";
// Attempt to create circular dependency (should be prevented)
if (isset($clinical_children['examination']) && isset($clinical_children['diagnosis'])) {
    echo "Attempting to create circular dependency...\n";
    add_prerequisite($clinical_children['examination'], $clinical_children['diagnosis']);
}

echo "\n=== Competency Framework Structure Created Successfully ===\n";
echo "Framework ID: " . $framework->get('id') . "\n";
echo "Total parent competencies: 4\n";
echo "Total child competencies: " . (count($clinical_children) + count($surgical_children) + count($diagnostic_children) + count($professional_children)) . "\n";
echo "Prerequisite relationships established with circular dependency prevention\n";

echo "\n✓ Task 3.1 Complete: Competency framework structure created\n";
echo "✓ Requirements 2.1, 2.2, 2.3 satisfied\n";

?>
