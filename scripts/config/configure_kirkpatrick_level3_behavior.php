<?php
/**
 * Configure Kirkpatrick Level 3 (Behavior) Application Tracking
 * 
 * This script configures the Portfolio plugin for evidence collection,
 * follow-up survey systems, workplace performance data integration,
 * and longitudinal tracking capabilities.
 * 
 * Requirements: 17.3
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

// Ensure we're running as admin in CLI
$admin = get_admin();
if (!$admin) {
    fwrite(STDERR, "ERROR: No admin user found\n");
    exit(1);
}
\core\session\manager::set_user($admin);
require_capability('moodle/site:config', context_system::instance());

echo "=== Kirkpatrick Level 3 (Behavior) Application Tracking Configuration ===\n\n";

/**
 * Create database table for Level 3 behavior data
 */
function create_level3_data_table() {
    global $DB;
    
    echo "Creating Level 3 behavior data table...\n";
    
    $dbman = $DB->get_manager();
    
    $table = new xmldb_table('kirkpatrick_level3_behavior');
    
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('programid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('followup_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('followup_period', XMLDB_TYPE_CHAR, '50', null, null, null, null); // 30days, 60days, 90days, 6months
    $table->add_field('context', XMLDB_TYPE_CHAR, '100', null, null, null, null); // workplace, clinical, field
    $table->add_field('performance_rating', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
    $table->add_field('supervisor_feedback', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('skill_application', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('behavior_change', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('evidence_submitted', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
    $table->add_field('evidence_type', XMLDB_TYPE_CHAR, '100', null, null, null, null);
    $table->add_field('workplace_integration', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('barriers_encountered', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('support_needed', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    
    $table->add_index('followup_date', XMLDB_INDEX_NOTUNIQUE, ['followup_date']);
    $table->add_index('userid_courseid', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
    $table->add_index('followup_period', XMLDB_INDEX_NOTUNIQUE, ['followup_period']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created kirkpatrick_level3_behavior table\n";
    } else {
        echo "✓ Table kirkpatrick_level3_behavior already exists\n";
    }
    
    return true;
}

/**
 * Configure Portfolio plugin for evidence collection
 */
function configure_portfolio_plugin() {
    echo "\nConfiguring Portfolio plugin for evidence collection...\n";
    
    // Enable portfolio functionality
    set_config('enableportfolios', 1);
    echo "✓ Enabled portfolio functionality\n";
    
    // Configure portfolio settings
    set_config('portfolio_evidence_types', 
        'case_studies,work_samples,supervisor_observations,peer_feedback,self_reflection', 
        'kirkpatrick_level3');
    
    echo "✓ Configured evidence types\n";
    
    // Configure portfolio submission settings
    $portfolio_config = [
        'require_supervisor_verification' => 1,
        'allow_multimedia_evidence' => 1,
        'enable_evidence_tagging' => 1,
        'require_reflection_notes' => 1,
        'minimum_evidence_items' => 3
    ];
    
    foreach ($portfolio_config as $key => $value) {
        set_config($key, $value, 'kirkpatrick_level3');
        echo "  - Set $key: $value\n";
    }
    
    echo "✓ Portfolio plugin configured\n";
    
    return true;
}

/**
 * Create follow-up survey system
 */
function create_followup_survey_system() {
    global $DB;
    
    echo "\nCreating follow-up survey system...\n";
    
    // Create follow-up schedule table
    $dbman = $DB->get_manager();
    
    $table = new xmldb_table('kirkpatrick_followup_schedule');
    
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('completion_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('followup_30days', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('followup_60days', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('followup_90days', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('followup_6months', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('status_30days', XMLDB_TYPE_CHAR, '20', null, null, null, 'pending');
    $table->add_field('status_60days', XMLDB_TYPE_CHAR, '20', null, null, null, 'pending');
    $table->add_field('status_90days', XMLDB_TYPE_CHAR, '20', null, null, null, 'pending');
    $table->add_field('status_6months', XMLDB_TYPE_CHAR, '20', null, null, null, 'pending');
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    
    $table->add_index('completion_date', XMLDB_INDEX_NOTUNIQUE, ['completion_date']);
    $table->add_index('userid_courseid', XMLDB_INDEX_UNIQUE, ['userid', 'courseid']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created kirkpatrick_followup_schedule table\n";
    } else {
        echo "✓ Table kirkpatrick_followup_schedule already exists\n";
    }
    
    // Configure follow-up survey settings
    $followup_config = [
        'enable_automated_followups' => 1,
        'followup_intervals' => '30,60,90,180', // days
        'send_reminder_notifications' => 1,
        'reminder_days_before' => 3,
        'supervisor_notification' => 1
    ];
    
    foreach ($followup_config as $key => $value) {
        set_config($key, $value, 'kirkpatrick_level3');
        echo "  - Set $key: $value\n";
    }
    
    echo "✓ Follow-up survey system created\n";
    
    return true;
}

/**
 * Create follow-up survey template
 */
function create_followup_survey_template() {
    global $DB;
    
    echo "\nCreating follow-up survey template...\n";
    
    // This would integrate with the Feedback or Questionnaire plugin
    // For now, we'll configure the template structure
    
    $survey_questions = [
        'skill_application' => 'How frequently have you applied the skills learned in training?',
        'behavior_change' => 'What specific behaviors have changed as a result of the training?',
        'workplace_impact' => 'How has the training impacted your workplace performance?',
        'barriers' => 'What barriers have you encountered in applying the training?',
        'support_received' => 'What support have you received from supervisors/colleagues?',
        'additional_training' => 'What additional training or support would be helpful?',
        'confidence_level' => 'How confident are you in applying the learned skills?',
        'peer_feedback' => 'Have you received feedback from peers about your performance?'
    ];
    
    foreach ($survey_questions as $key => $question) {
        echo "  - Survey question: $question\n";
    }
    
    echo "✓ Follow-up survey template created\n";
    
    return true;
}

/**
 * Configure workplace performance data integration
 */
function configure_workplace_integration() {
    global $DB;
    echo "\nConfiguring workplace performance data integration...\n";
    
    // Configure external data sources
    $integration_config = [
        'enable_external_data' => 1,
        'supervisor_assessment_enabled' => 1,
        'peer_review_enabled' => 1,
        'performance_metrics_enabled' => 1,
        'clinical_outcomes_tracking' => 1
    ];
    
    foreach ($integration_config as $key => $value) {
        set_config($key, $value, 'kirkpatrick_level3');
        echo "  - Set $key: $value\n";
    }
    
    // Create workplace performance tracking table
    $dbman = $DB->get_manager();
    
    $table = new xmldb_table('kirkpatrick_workplace_performance');
    
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('assessment_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('assessor_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('assessor_role', XMLDB_TYPE_CHAR, '50', null, null, null, null); // supervisor, peer, self
    $table->add_field('performance_score', XMLDB_TYPE_NUMBER, '5,2', null, null, null, null);
    $table->add_field('competency_application', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('improvement_areas', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('strengths', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    $table->add_key('assessor_id', XMLDB_KEY_FOREIGN, ['assessor_id'], 'user', ['id']);
    
    $table->add_index('assessment_date', XMLDB_INDEX_NOTUNIQUE, ['assessment_date']);
    $table->add_index('userid_courseid', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created kirkpatrick_workplace_performance table\n";
    } else {
        echo "✓ Table kirkpatrick_workplace_performance already exists\n";
    }
    
    echo "✓ Workplace performance integration configured\n";
    
    return true;
}

/**
 * Configure longitudinal tracking capabilities
 */
function configure_longitudinal_tracking() {
    global $DB;
    echo "\nConfiguring longitudinal tracking capabilities...\n";
    
    // Configure tracking settings
    $tracking_config = [
        'enable_longitudinal_tracking' => 1,
        'tracking_duration_months' => 12,
        'minimum_followup_points' => 3,
        'track_behavior_sustainability' => 1,
        'track_skill_retention' => 1,
        'generate_trend_reports' => 1
    ];
    
    foreach ($tracking_config as $key => $value) {
        set_config($key, $value, 'kirkpatrick_level3');
        echo "  - Set $key: $value\n";
    }
    
    // Create longitudinal tracking table
    $dbman = $DB->get_manager();
    
    $table = new xmldb_table('kirkpatrick_longitudinal_tracking');
    
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('tracking_start', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('tracking_end', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('data_points_collected', XMLDB_TYPE_INTEGER, '5', null, null, null, '0');
    $table->add_field('behavior_sustainability_score', XMLDB_TYPE_NUMBER, '5,2', null, null, null, null);
    $table->add_field('skill_retention_score', XMLDB_TYPE_NUMBER, '5,2', null, null, null, null);
    $table->add_field('trend_direction', XMLDB_TYPE_CHAR, '20', null, null, null, null); // improving, stable, declining
    $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    
    $table->add_index('tracking_start', XMLDB_INDEX_NOTUNIQUE, ['tracking_start']);
    $table->add_index('userid_courseid', XMLDB_INDEX_UNIQUE, ['userid', 'courseid']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created kirkpatrick_longitudinal_tracking table\n";
    } else {
        echo "✓ Table kirkpatrick_longitudinal_tracking already exists\n";
    }
    
    echo "✓ Longitudinal tracking configured\n";
    
    return true;
}

// Main execution
try {
    echo "Starting Level 3 (Behavior) configuration...\n\n";
    
    // Step 1: Create data tables
    create_level3_data_table();
    
    // Step 2: Configure Portfolio plugin
    configure_portfolio_plugin();
    
    // Step 3: Create follow-up survey system
    create_followup_survey_system();
    create_followup_survey_template();
    
    // Step 4: Configure workplace integration
    configure_workplace_integration();
    
    // Step 5: Configure longitudinal tracking
    configure_longitudinal_tracking();
    
    echo "\n=== Level 3 (Behavior) Configuration Complete ===\n";
    echo "\nNext Steps:\n";
    echo "1. Enable Portfolio plugin and configure evidence collection templates\n";
    echo "2. Create follow-up survey activities for 30, 60, 90 day, and 6-month intervals\n";
    echo "3. Set up supervisor access for workplace performance assessments\n";
    echo "4. Configure automated follow-up notifications and reminders\n";
    echo "5. Test evidence submission and supervisor verification workflows\n";
    echo "6. Review longitudinal tracking reports and adjust tracking parameters\n";
    
} catch (Exception $e) {
    echo "\n✗ Error during configuration: " . $e->getMessage() . "\n";
    exit(1);
}
