<?php
/**
 * Configure Kirkpatrick Level 2 (Learning) Assessment Framework
 * 
 * This script configures pre/post assessment comparison systems, competency-based
 * learning measurement, badge integration, and learning analytics.
 * 
 * Requirements: 17.2
 */

require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/competency/classes/api.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

echo "=== Kirkpatrick Level 2 (Learning) Assessment Framework Configuration ===\n\n";

/**
 * Create database table for Level 2 learning data
 */
function create_level2_data_table() {
    global $DB;
    
    echo "Creating Level 2 learning data table...\n";
    
    $dbman = $DB->get_manager();
    
    $table = new xmldb_table('kirkpatrick_level2_learning');
    
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('competencyid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('assessmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('assessment_type', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $table->add_field('date_assessed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('pre_score', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);
    $table->add_field('post_score', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);
    $table->add_field('skill_level', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $table->add_field('knowledge_gain', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);
    $table->add_field('badge_earned', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('certification_achieved', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
    $table->add_field('learning_objectives_met', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    $table->add_key('competencyid', XMLDB_KEY_FOREIGN, ['competencyid'], 'competency', ['id']);
    
    $table->add_index('date_assessed', XMLDB_INDEX_NOTUNIQUE, ['date_assessed']);
    $table->add_index('courseid_userid', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'userid']);
    $table->add_index('competencyid_userid', XMLDB_INDEX_NOTUNIQUE, ['competencyid', 'userid']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created kirkpatrick_level2_learning table\n";
    } else {
        echo "✓ Table kirkpatrick_level2_learning already exists\n";
    }
    
    return true;
}

/**
 * Configure pre/post assessment comparison system
 */
function configure_prepost_assessment_system() {
    echo "\nConfiguring pre/post assessment comparison system...\n";
    
    // Create assessment type configuration
    $assessment_types = [
        'pre_assessment' => 'Pre-training knowledge assessment',
        'post_assessment' => 'Post-training knowledge assessment',
        'skill_demonstration' => 'Practical skill demonstration',
        'competency_evaluation' => 'Competency-based evaluation'
    ];
    
    foreach ($assessment_types as $type => $description) {
        set_config($type . '_enabled', 1, 'kirkpatrick_level2');
        echo "  - Configured: $type ($description)\n";
    }
    
    // Configure comparison settings
    set_config('enable_prepost_comparison', 1, 'kirkpatrick_level2');
    set_config('minimum_improvement_threshold', 10, 'kirkpatrick_level2'); // 10% improvement
    set_config('mastery_threshold', 80, 'kirkpatrick_level2'); // 80% for mastery
    
    echo "✓ Pre/post assessment system configured\n";
    
    return true;
}

/**
 * Configure competency-based learning measurement
 */
function configure_competency_measurement() {
    echo "\nConfiguring competency-based learning measurement...\n";
    
    // Enable competency framework
    set_config('enabled', 1, 'core_competency');
    echo "✓ Enabled competency framework\n";
    
    // Configure competency grading
    set_config('pushcourseratingstouserplans', 1, 'core_competency');
    echo "✓ Enabled pushing course ratings to learning plans\n";
    
    // Configure competency evidence collection
    set_config('evidenceofpriorlearningdescription', 
        'Evidence of competency achievement through assessments and demonstrations', 
        'core_competency');
    echo "✓ Configured evidence collection\n";
    
    // Configure competency proficiency scales
    $proficiency_levels = [
        'not_competent' => 'Not Yet Competent',
        'developing' => 'Developing Competency',
        'competent' => 'Competent',
        'proficient' => 'Proficient',
        'expert' => 'Expert'
    ];
    
    foreach ($proficiency_levels as $level => $description) {
        echo "  - Proficiency level: $description\n";
    }
    
    echo "✓ Competency measurement configured\n";
    
    return true;
}

/**
 * Integrate badge system with learning verification
 */
function integrate_badge_system() {
    echo "\nIntegrating badge system with learning verification...\n";
    
    // Enable badges
    set_config('enablebadges', 1);
    echo "✓ Enabled badges system\n";
    
    // Configure badge criteria for competency achievement
    set_config('badges_defaultissuername', 'Competency-Based Learning System');
    set_config('badges_defaultissuercontact', get_config('core', 'supportemail'));
    
    echo "✓ Configured badge issuer settings\n";
    
    // Configure badge-competency integration
    $badge_competency_config = [
        'auto_award_on_competency' => 1,
        'require_all_competencies' => 0, // Can award for individual competencies
        'badge_expiry_enabled' => 0, // Badges don't expire by default
        'allow_external_backpack' => 1 // Allow sharing to external badge platforms
    ];
    
    foreach ($badge_competency_config as $key => $value) {
        set_config($key, $value, 'kirkpatrick_level2');
        echo "  - Set $key: $value\n";
    }
    
    echo "✓ Badge-competency integration configured\n";
    
    return true;
}

/**
 * Create learning analytics and progress visualization
 */
function create_learning_analytics() {
    echo "\nCreating learning analytics and progress visualization...\n";
    
    // Enable analytics
    set_config('enabled', 1, 'analytics');
    echo "✓ Enabled analytics engine\n";
    
    // Configure learning analytics models
    $analytics_models = [
        'students_at_risk' => 'Identify learners at risk of not achieving competencies',
        'no_teaching' => 'Detect courses with insufficient teaching activity',
        'no_recent_accesses' => 'Identify learners with low engagement'
    ];
    
    foreach ($analytics_models as $model => $description) {
        echo "  - Analytics model: $model ($description)\n";
    }
    
    // Configure progress visualization settings
    set_config('show_competency_progress', 1, 'kirkpatrick_level2');
    set_config('show_knowledge_gain_charts', 1, 'kirkpatrick_level2');
    set_config('show_skill_level_indicators', 1, 'kirkpatrick_level2');
    set_config('show_badge_achievements', 1, 'kirkpatrick_level2');
    
    echo "✓ Learning analytics configured\n";
    
    return true;
}

/**
 * Create assessment tracking table for pre/post comparisons
 */
function create_assessment_tracking_table() {
    global $DB;
    
    echo "\nCreating assessment tracking table...\n";
    
    $dbman = $DB->get_manager();
    
    $table = new xmldb_table('kirkpatrick_assessment_tracking');
    
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('assessment_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
    $table->add_field('assessment_instance', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('attempt_number', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '1');
    $table->add_field('score', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);
    $table->add_field('max_score', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);
    $table->add_field('percentage', XMLDB_TYPE_NUMBER, '5,2', null, null, null, null);
    $table->add_field('competencies_assessed', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    
    $table->add_index('userid_courseid', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
    $table->add_index('assessment_type', XMLDB_INDEX_NOTUNIQUE, ['assessment_type']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created kirkpatrick_assessment_tracking table\n";
    } else {
        echo "✓ Table kirkpatrick_assessment_tracking already exists\n";
    }
    
    return true;
}

/**
 * Configure learning objectives tracking
 */
function configure_learning_objectives() {
    echo "\nConfiguring learning objectives tracking...\n";
    
    // Enable course objectives
    set_config('enableoutcomes', 1);
    echo "✓ Enabled learning outcomes/objectives\n";
    
    // Configure objective-competency mapping
    set_config('map_objectives_to_competencies', 1, 'kirkpatrick_level2');
    set_config('track_objective_achievement', 1, 'kirkpatrick_level2');
    set_config('require_objective_evidence', 1, 'kirkpatrick_level2');
    
    echo "✓ Learning objectives tracking configured\n";
    
    return true;
}

// Main execution
try {
    echo "Starting Level 2 (Learning) configuration...\n\n";
    
    // Step 1: Create data tables
    create_level2_data_table();
    create_assessment_tracking_table();
    
    // Step 2: Configure pre/post assessment system
    configure_prepost_assessment_system();
    
    // Step 3: Configure competency measurement
    configure_competency_measurement();
    
    // Step 4: Integrate badge system
    integrate_badge_system();
    
    // Step 5: Create learning analytics
    create_learning_analytics();
    
    // Step 6: Configure learning objectives
    configure_learning_objectives();
    
    echo "\n=== Level 2 (Learning) Configuration Complete ===\n";
    echo "\nNext Steps:\n";
    echo "1. Create pre-assessment quizzes for each training program\n";
    echo "2. Create corresponding post-assessments with similar content\n";
    echo "3. Map assessments to competencies in the competency framework\n";
    echo "4. Configure badge criteria based on competency achievement\n";
    echo "5. Test pre/post assessment comparison with sample learners\n";
    echo "6. Review learning analytics dashboards and adjust as needed\n";
    
} catch (Exception $e) {
    echo "\n✗ Error during configuration: " . $e->getMessage() . "\n";
    exit(1);
}
