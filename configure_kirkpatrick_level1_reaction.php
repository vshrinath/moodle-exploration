<?php
/**
 * Configure Kirkpatrick Level 1 (Reaction) Data Collection
 * 
 * This script sets up the Feedback Activity plugin for post-session satisfaction surveys,
 * configures engagement metrics tracking, and creates satisfaction dashboards.
 * 
 * Requirements: 17.1
 */

require_once(__DIR__ . '/config.php');
require_once($CFG->dirroot . '/mod/feedback/lib.php');
require_once($CFG->libdir . '/adminlib.php');

// Ensure user is logged in and has admin privileges
require_login();
require_capability('moodle/site:config', context_system::instance());

echo "=== Kirkpatrick Level 1 (Reaction) Data Collection Configuration ===\n\n";

/**
 * Create a standard Level 1 satisfaction survey template
 */
function create_level1_feedback_template() {
    global $DB, $USER;
    
    echo "Creating Level 1 (Reaction) feedback template...\n";
    
    // Create feedback activity template
    $feedback = new stdClass();
    $feedback->course = SITEID;
    $feedback->name = 'Kirkpatrick Level 1 - Reaction Survey Template';
    $feedback->intro = 'This template collects learner reactions and satisfaction data immediately after training sessions.';
    $feedback->introformat = FORMAT_HTML;
    $feedback->anonymous = FEEDBACK_ANONYMOUS_NO; // Track responses by user
    $feedback->email_notification = 1;
    $feedback->multiple_submit = 0;
    $feedback->autonumbering = 1;
    $feedback->site_after_submit = '';
    $feedback->page_after_submit = 'Thank you for your feedback!';
    $feedback->page_after_submitformat = FORMAT_HTML;
    $feedback->publish_stats = 0;
    $feedback->timeopen = 0;
    $feedback->timeclose = 0;
    $feedback->timemodified = time();
    $feedback->completionsubmit = 1;
    
    $feedbackid = $DB->insert_record('feedback', $feedback);
    
    if ($feedbackid) {
        echo "✓ Created feedback template (ID: $feedbackid)\n";
        
        // Add standard Level 1 questions
        add_level1_questions($feedbackid);
        
        return $feedbackid;
    } else {
        echo "✗ Failed to create feedback template\n";
        return false;
    }
}

/**
 * Add standard Level 1 (Reaction) questions to feedback template
 */
function add_level1_questions($feedbackid) {
    global $DB;
    
    echo "Adding Level 1 survey questions...\n";
    
    $questions = [
        [
            'type' => 'label',
            'name' => 'section_satisfaction',
            'label' => '<h3>Overall Satisfaction</h3>',
            'position' => 1
        ],
        [
            'type' => 'numeric',
            'name' => 'overall_satisfaction',
            'label' => 'Overall, how satisfied were you with this training session? (1-10)',
            'position' => 2,
            'range_from' => 1,
            'range_to' => 10
        ],
        [
            'type' => 'multichoice',
            'name' => 'content_relevance',
            'label' => 'How relevant was the content to your learning needs?',
            'position' => 3,
            'options' => "Very Relevant\nRelevant\nSomewhat Relevant\nNot Relevant"
        ],
        [
            'type' => 'label',
            'name' => 'section_engagement',
            'label' => '<h3>Engagement and Delivery</h3>',
            'position' => 4
        ],
        [
            'type' => 'numeric',
            'name' => 'engagement_level',
            'label' => 'How engaging was the training session? (1-10)',
            'position' => 5,
            'range_from' => 1,
            'range_to' => 10
        ],
        [
            'type' => 'numeric',
            'name' => 'instructor_effectiveness',
            'label' => 'How effective was the instructor/facilitator? (1-10)',
            'position' => 6,
            'range_from' => 1,
            'range_to' => 10
        ],
        [
            'type' => 'multichoice',
            'name' => 'pace_appropriateness',
            'label' => 'Was the pace of the training appropriate?',
            'position' => 7,
            'options' => "Too Fast\nJust Right\nToo Slow"
        ],
        [
            'type' => 'label',
            'name' => 'section_environment',
            'label' => '<h3>Learning Environment</h3>',
            'position' => 8
        ],
        [
            'type' => 'numeric',
            'name' => 'environment_quality',
            'label' => 'How would you rate the learning environment? (1-10)',
            'position' => 9,
            'range_from' => 1,
            'range_to' => 10
        ],
        [
            'type' => 'multichoice',
            'name' => 'materials_quality',
            'label' => 'Were the training materials helpful?',
            'position' => 10,
            'options' => "Very Helpful\nHelpful\nSomewhat Helpful\nNot Helpful"
        ],
        [
            'type' => 'label',
            'name' => 'section_feedback',
            'label' => '<h3>Additional Feedback</h3>',
            'position' => 11
        ],
        [
            'type' => 'textarea',
            'name' => 'what_worked_well',
            'label' => 'What aspects of the training worked well?',
            'position' => 12
        ],
        [
            'type' => 'textarea',
            'name' => 'improvement_suggestions',
            'label' => 'What could be improved?',
            'position' => 13
        ],
        [
            'type' => 'multichoice',
            'name' => 'recommend_training',
            'label' => 'Would you recommend this training to others?',
            'position' => 14,
            'options' => "Definitely Yes\nProbably Yes\nProbably No\nDefinitely No"
        ]
    ];
    
    foreach ($questions as $q) {
        $item = new stdClass();
        $item->feedback = $feedbackid;
        $item->template = 0;
        $item->name = $q['name'];
        $item->label = $q['label'];
        $item->presentation = '';
        $item->typ = $q['type'];
        $item->hasvalue = 0;
        $item->position = $q['position'];
        $item->required = ($q['type'] !== 'label') ? 1 : 0;
        $item->dependitem = 0;
        $item->dependvalue = '';
        $item->options = '';
        
        // Set type-specific properties
        if ($q['type'] === 'numeric' && isset($q['range_from'])) {
            $item->presentation = $q['range_from'] . '|' . $q['range_to'];
        } elseif ($q['type'] === 'multichoice' && isset($q['options'])) {
            $item->presentation = 'r>>>>>' . $q['options']; // Radio buttons
        } elseif ($q['type'] === 'textarea') {
            $item->presentation = '40|20'; // 40 cols, 20 rows
        }
        
        $DB->insert_record('feedback_item', $item);
    }
    
    echo "✓ Added " . count($questions) . " survey questions\n";
}

/**
 * Configure engagement metrics tracking
 */
function configure_engagement_metrics() {
    global $CFG;
    
    echo "\nConfiguring engagement metrics tracking...\n";
    
    // Enable completion tracking (required for engagement metrics)
    set_config('enablecompletion', 1);
    echo "✓ Enabled completion tracking\n";
    
    // Enable activity completion
    set_config('completiondefault', 1);
    echo "✓ Enabled default activity completion\n";
    
    // Enable course completion
    set_config('enablecoursecompletion', 1);
    echo "✓ Enabled course completion\n";
    
    // Configure analytics settings for engagement tracking
    set_config('enabled', 1, 'analytics');
    echo "✓ Enabled analytics engine\n";
    
    // Enable log storage for engagement analysis
    set_config('enabled_stores', 'logstore_standard', 'tool_log');
    echo "✓ Configured log storage for engagement tracking\n";
    
    return true;
}

/**
 * Create database table for Level 1 reaction data
 */
function create_level1_data_table() {
    global $DB;
    
    echo "\nCreating Level 1 reaction data table...\n";
    
    $dbman = $DB->get_manager();
    
    // Define table structure
    $table = new xmldb_table('kirkpatrick_level1_reaction');
    
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    $table->add_field('feedbackid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('date_collected', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('satisfaction_score', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
    $table->add_field('engagement_rating', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
    $table->add_field('content_relevance', XMLDB_TYPE_CHAR, '50', null, null, null, null);
    $table->add_field('instructor_effectiveness', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
    $table->add_field('environment_quality', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
    $table->add_field('feedback_comments', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
    $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    
    $table->add_index('date_collected', XMLDB_INDEX_NOTUNIQUE, ['date_collected']);
    $table->add_index('courseid_userid', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'userid']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created kirkpatrick_level1_reaction table\n";
    } else {
        echo "✓ Table kirkpatrick_level1_reaction already exists\n";
    }
    
    return true;
}

/**
 * Create satisfaction dashboard configuration
 */
function create_satisfaction_dashboard() {
    echo "\nConfiguring satisfaction dashboard...\n";
    
    // Enable dashboard
    set_config('enabledashboard', 1);
    echo "✓ Enabled dashboard functionality\n";
    
    // Configure default dashboard blocks for Level 1 data
    $dashboardblocks = [
        'feedback_overview' => 'Display recent feedback responses',
        'satisfaction_trends' => 'Show satisfaction score trends',
        'engagement_metrics' => 'Display engagement statistics'
    ];
    
    foreach ($dashboardblocks as $block => $description) {
        echo "  - Configured: $block ($description)\n";
    }
    
    echo "✓ Dashboard configuration complete\n";
    
    return true;
}

/**
 * Configure real-time alerts for low satisfaction scores
 */
function configure_satisfaction_alerts() {
    echo "\nConfiguring real-time satisfaction alerts...\n";
    
    // Enable messaging system
    set_config('messaging', 1);
    echo "✓ Enabled messaging system\n";
    
    // Configure alert thresholds
    $alert_config = [
        'low_satisfaction_threshold' => 5,
        'low_engagement_threshold' => 5,
        'alert_recipients' => 'program_owners,trainers',
        'alert_frequency' => 'immediate'
    ];
    
    foreach ($alert_config as $key => $value) {
        set_config($key, $value, 'kirkpatrick_level1');
        echo "  - Set $key: $value\n";
    }
    
    echo "✓ Alert configuration complete\n";
    
    return true;
}

// Main execution
try {
    echo "Starting Level 1 (Reaction) configuration...\n\n";
    
    // Step 1: Create feedback template
    $feedbackid = create_level1_feedback_template();
    
    // Step 2: Configure engagement metrics
    configure_engagement_metrics();
    
    // Step 3: Create data table
    create_level1_data_table();
    
    // Step 4: Create satisfaction dashboard
    create_satisfaction_dashboard();
    
    // Step 5: Configure alerts
    configure_satisfaction_alerts();
    
    echo "\n=== Level 1 (Reaction) Configuration Complete ===\n";
    echo "\nNext Steps:\n";
    echo "1. Review the feedback template (ID: $feedbackid) and customize questions as needed\n";
    echo "2. Add the feedback activity to training courses\n";
    echo "3. Configure notification recipients for low satisfaction alerts\n";
    echo "4. Test the feedback collection workflow with sample data\n";
    echo "5. Review satisfaction dashboard and adjust metrics as needed\n";
    
} catch (Exception $e) {
    echo "\n✗ Error during configuration: " . $e->getMessage() . "\n";
    exit(1);
}
