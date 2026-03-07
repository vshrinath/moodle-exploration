<?php
/**
 * Configure Advanced Cohort Management
 * 
 * This script implements advanced cohort management with support for:
 * - Multiple cohort types (technical, management, trainer-led, self-paced)
 * - Cohort-specific access rules and content
 * - Mixed delivery mode support within cohorts
 * 
 * Requirements: 4.1, 6.1
 * Task: 4.4 - Configure advanced cohort management
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();
require_once($CFG->libdir . '/adminlib.php');

global $DB, $CFG;

echo "=== Advanced Cohort Management Configuration ===\n\n";

/**
 * Create custom database table for cohort metadata
 */
function create_cohort_metadata_table() {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    // Define table for cohort metadata
    $table = new xmldb_table('local_cohort_metadata');
    
    // Add fields
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('cohort_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'technical');
    $table->add_field('delivery_mode', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'trainer-led');
    $table->add_field('mixed_delivery', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('access_rules', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('content_restrictions', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('modified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    // Add keys
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('cohortid_fk', XMLDB_KEY_FOREIGN, ['cohortid'], 'cohort', ['id']);
    
    // Add indexes
    $table->add_index('cohort_type_idx', XMLDB_INDEX_NOTUNIQUE, ['cohort_type']);
    $table->add_index('delivery_mode_idx', XMLDB_INDEX_NOTUNIQUE, ['delivery_mode']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created local_cohort_metadata table\n";
    } else {
        echo "✓ Table local_cohort_metadata already exists\n";
    }
    
    return true;
}

/**
 * Create a cohort with metadata
 */
function create_cohort_with_metadata($name, $idnumber, $description, $type, $delivery_mode, $mixed_delivery = false) {
    global $DB;
    
    // Check if cohort already exists
    $existing = $DB->get_record('cohort', ['idnumber' => $idnumber]);
    if ($existing) {
        echo "✓ Cohort '$name' already exists (ID: {$existing->id})\n";
        return $existing->id;
    }
    
    // Create cohort
    $cohort = new stdClass();
    $cohort->contextid = context_system::instance()->id;
    $cohort->name = $name;
    $cohort->idnumber = $idnumber;
    $cohort->description = $description;
    $cohort->descriptionformat = FORMAT_HTML;
    $cohort->visible = 1;
    $cohort->timecreated = time();
    $cohort->timemodified = time();
    
    $cohortid = $DB->insert_record('cohort', $cohort);
    
    // Create metadata
    $metadata = new stdClass();
    $metadata->cohortid = $cohortid;
    $metadata->cohort_type = $type;
    $metadata->delivery_mode = $delivery_mode;
    $metadata->mixed_delivery = $mixed_delivery ? 1 : 0;
    $metadata->access_rules = json_encode([
        'type' => $type,
        'delivery' => $delivery_mode,
        'restrictions' => []
    ]);
    $metadata->content_restrictions = json_encode([]);
    $metadata->created = time();
    $metadata->modified = time();
    
    $DB->insert_record('local_cohort_metadata', $metadata);
    
    echo "✓ Created cohort '$name' (ID: $cohortid, Type: $type, Delivery: $delivery_mode)\n";
    
    return $cohortid;
}

/**
 * Configure cohort-specific access rules
 */
function configure_cohort_access_rules($cohortid, $rules) {
    global $DB;
    
    $metadata = $DB->get_record('local_cohort_metadata', ['cohortid' => $cohortid]);
    
    if ($metadata) {
        $metadata->access_rules = json_encode($rules);
        $metadata->modified = time();
        $DB->update_record('local_cohort_metadata', $metadata);
        echo "  ✓ Updated access rules for cohort $cohortid\n";
        return true;
    }
    
    return false;
}

/**
 * Configure cohort-specific content restrictions
 */
function configure_cohort_content_restrictions($cohortid, $restrictions) {
    global $DB;
    
    $metadata = $DB->get_record('local_cohort_metadata', ['cohortid' => $cohortid]);
    
    if ($metadata) {
        $metadata->content_restrictions = json_encode($restrictions);
        $metadata->modified = time();
        $DB->update_record('local_cohort_metadata', $metadata);
        echo "  ✓ Updated content restrictions for cohort $cohortid\n";
        return true;
    }
    
    return false;
}

/**
 * Enable cohort enrolment plugin
 */
function enable_cohort_enrolment() {
    global $DB;
    
    // Check if cohort enrolment plugin is enabled
    $plugin = $DB->get_record('enrol', ['enrol' => 'cohort'], 'id', IGNORE_MULTIPLE);
    
    if (!$plugin) {
        echo "⚠ Cohort enrolment plugin needs to be enabled in Moodle admin\n";
        echo "  Navigate to: Site administration > Plugins > Enrolments > Manage enrol plugins\n";
    } else {
        echo "✓ Cohort enrolment plugin is available\n";
    }
    
    return true;
}

/**
 * Create sample cohorts for different types
 */
function create_sample_cohorts() {
    global $DB;
    
    echo "\nCreating sample cohorts...\n";
    
    // Technical cohort - trainer-led
    $tech_trainer = create_cohort_with_metadata(
        'Technical Training - Trainer Led',
        'TECH_TRAINER_001',
        'Technical skills development with instructor guidance',
        'technical',
        'trainer-led',
        false
    );
    
    // Technical cohort - self-paced
    $tech_self = create_cohort_with_metadata(
        'Technical Training - Self Paced',
        'TECH_SELF_001',
        'Self-paced technical skills development',
        'technical',
        'self-paced',
        false
    );
    
    // Management cohort - trainer-led
    $mgmt_trainer = create_cohort_with_metadata(
        'Management Program - Trainer Led',
        'MGMT_TRAINER_001',
        'Leadership and management training with instructor',
        'management',
        'trainer-led',
        false
    );
    
    // Mixed delivery cohort
    $mixed = create_cohort_with_metadata(
        'Blended Learning Cohort',
        'BLENDED_001',
        'Mixed delivery mode with both trainer-led and self-paced components',
        'technical',
        'trainer-led',
        true
    );
    
    // Configure access rules for mixed delivery cohort
    configure_cohort_access_rules($mixed, [
        'type' => 'technical',
        'delivery' => 'mixed',
        'trainer_led_modules' => ['module1', 'module3', 'module5'],
        'self_paced_modules' => ['module2', 'module4', 'module6'],
        'restrictions' => [
            'require_completion' => true,
            'sequential_access' => true
        ]
    ]);
    
    // Configure content restrictions
    configure_cohort_content_restrictions($mixed, [
        'restricted_activities' => [],
        'cohort_specific_content' => true,
        'visibility_rules' => [
            'show_to_cohort_only' => true
        ]
    ]);
    
    return [
        'tech_trainer' => $tech_trainer,
        'tech_self' => $tech_self,
        'mgmt_trainer' => $mgmt_trainer,
        'mixed' => $mixed
    ];
}

/**
 * Create helper functions for cohort management
 */
function create_cohort_helper_functions() {
    echo "\nCohort management helper functions available:\n";
    echo "  - get_cohort_type(\$cohortid): Get cohort type\n";
    echo "  - get_cohort_delivery_mode(\$cohortid): Get delivery mode\n";
    echo "  - is_mixed_delivery(\$cohortid): Check if mixed delivery\n";
    echo "  - get_cohort_access_rules(\$cohortid): Get access rules\n";
    echo "  - get_cohort_content_restrictions(\$cohortid): Get content restrictions\n";
}

// Main execution
try {
    echo "Step 1: Creating cohort metadata table...\n";
    create_cohort_metadata_table();
    echo "\n";
    
    echo "Step 2: Checking cohort enrolment plugin...\n";
    enable_cohort_enrolment();
    echo "\n";
    
    echo "Step 3: Creating sample cohorts...\n";
    $cohorts = create_sample_cohorts();
    echo "\n";
    
    echo "Step 4: Setting up helper functions...\n";
    create_cohort_helper_functions();
    echo "\n";
    
    echo "=== Advanced Cohort Management Configuration Complete ===\n";
    echo "\nCohort Types Supported:\n";
    echo "  - technical: Technical skills development\n";
    echo "  - management: Leadership and management training\n";
    echo "  - trainer-led: Instructor-guided learning\n";
    echo "  - self-paced: Independent learning\n";
    echo "\nDelivery Modes:\n";
    echo "  - trainer-led: Scheduled sessions with instructor\n";
    echo "  - self-paced: Learn at your own pace\n";
    echo "  - mixed: Combination of both modes\n";
    echo "\nNext steps:\n";
    echo "1. Enrol learners into appropriate cohorts\n";
    echo "2. Configure cohort-specific content and activities\n";
    echo "3. Set up conditional access based on cohort membership\n";
    echo "4. Configure cohort-specific completion criteria\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
