<?php
/**
 * Unified Kirkpatrick Dashboard - Main page
 *
 * @package    local_kirkpatrick_dashboard
 * @copyright  2025 Competency-Based Learning System
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();
require_capability('local/kirkpatrick_dashboard:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/kirkpatrick_dashboard/index.php'));
$PAGE->set_title(get_string('dashboardtitle', 'local_kirkpatrick_dashboard'));
$PAGE->set_heading(get_string('dashboardheading', 'local_kirkpatrick_dashboard'));
$PAGE->set_pagelayout('admin');

// Add JavaScript for interactive features
$PAGE->requires->js_call_amd('local_kirkpatrick_dashboard/dashboard', 'init');

echo $OUTPUT->header();

// Dashboard filters
echo html_writer::start_div('kirkpatrick-dashboard-filters');
echo html_writer::tag('h3', get_string('filters', 'local_kirkpatrick_dashboard'));

// Program filter.
$programs = $DB->get_records_select_menu(
    'course',
    'id <> :sitecourseid AND category > 0',
    ['sitecourseid' => SITEID],
    'fullname',
    'id, fullname'
);
echo html_writer::label(get_string('selectprogram', 'local_kirkpatrick_dashboard'), 'program-filter');
echo html_writer::select($programs, 'program', '', ['' => get_string('allprograms', 'local_kirkpatrick_dashboard')], 
    ['id' => 'program-filter', 'class' => 'form-control']);

// Time period filter
$periods = [
    '30' => get_string('last30days', 'local_kirkpatrick_dashboard'),
    '90' => get_string('last90days', 'local_kirkpatrick_dashboard'),
    '180' => get_string('last6months', 'local_kirkpatrick_dashboard'),
    '365' => get_string('lastyear', 'local_kirkpatrick_dashboard'),
    'all' => get_string('alltime', 'local_kirkpatrick_dashboard')
];
echo html_writer::label(get_string('timeperiod', 'local_kirkpatrick_dashboard'), 'period-filter');
echo html_writer::select($periods, 'period', '90', [], 
    ['id' => 'period-filter', 'class' => 'form-control']);

echo html_writer::end_div();

// Level 1: Reaction
echo html_writer::start_div('kirkpatrick-level level-1');
echo html_writer::tag('h2', get_string('level1reaction', 'local_kirkpatrick_dashboard'));

echo html_writer::start_div('level-metrics row');

// Satisfaction score
$avg_satisfaction = $DB->get_field_sql(
    "SELECT AVG(satisfaction_score) FROM {kirkpatrick_level1_reaction}"
);
$avg_satisfaction = $avg_satisfaction !== false ? (float)$avg_satisfaction : 0.0;
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('avgsatisfaction', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', number_format($avg_satisfaction, 1) . '/10', ['class' => 'metric-value']);
echo html_writer::end_div();

// Engagement rating
$avg_engagement = $DB->get_field_sql(
    "SELECT AVG(engagement_rating) FROM {kirkpatrick_level1_reaction}"
);
$avg_engagement = $avg_engagement !== false ? (float)$avg_engagement : 0.0;
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('avgengagement', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', number_format($avg_engagement, 1) . '/10', ['class' => 'metric-value']);
echo html_writer::end_div();

// Response count
$response_count = $DB->count_records('kirkpatrick_level1_reaction');
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('totalresponses', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', $response_count, ['class' => 'metric-value']);
echo html_writer::end_div();

// Low satisfaction alerts
$low_satisfaction = $DB->count_records_select('kirkpatrick_level1_reaction', 'satisfaction_score < 6');
echo html_writer::start_div('metric-card col-md-3 alert-card');
echo html_writer::tag('h4', get_string('lowsatisfaction', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', $low_satisfaction, ['class' => 'metric-value']);
echo html_writer::end_div();

echo html_writer::end_div(); // level-metrics

// Satisfaction trend chart placeholder
echo html_writer::div('', 'chart-container', ['id' => 'level1-trend-chart']);

echo html_writer::end_div(); // level-1

// Level 2: Learning
echo html_writer::start_div('kirkpatrick-level level-2');
echo html_writer::tag('h2', get_string('level2learning', 'local_kirkpatrick_dashboard'));

echo html_writer::start_div('level-metrics row');

// Average knowledge gain
$avg_knowledge_gain = $DB->get_field_sql(
    "SELECT AVG(knowledge_gain) FROM {kirkpatrick_level2_learning}"
);
$avg_knowledge_gain = $avg_knowledge_gain !== false ? (float)$avg_knowledge_gain : 0.0;
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('avgknowledgegain', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', number_format($avg_knowledge_gain, 1) . '%', ['class' => 'metric-value']);
echo html_writer::end_div();

// Competencies achieved
$competencies_achieved = $DB->count_records('kirkpatrick_level2_learning', ['certification_achieved' => 1]);
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('competenciesachieved', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', $competencies_achieved, ['class' => 'metric-value']);
echo html_writer::end_div();

// Badges earned
$badges_earned = $DB->count_records_select('kirkpatrick_level2_learning', 'badge_earned IS NOT NULL');
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('badgesearned', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', $badges_earned, ['class' => 'metric-value']);
echo html_writer::end_div();

// At-risk learners
$at_risk = $DB->count_records_select('kirkpatrick_level2_learning', 'post_score < 60 OR knowledge_gain < 5');
echo html_writer::start_div('metric-card col-md-3 alert-card');
echo html_writer::tag('h4', get_string('atrisklearners', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', $at_risk, ['class' => 'metric-value']);
echo html_writer::end_div();

echo html_writer::end_div(); // level-metrics

// Learning progress chart placeholder
echo html_writer::div('', 'chart-container', ['id' => 'level2-progress-chart']);

echo html_writer::end_div(); // level-2

// Level 3: Behavior
echo html_writer::start_div('kirkpatrick-level level-3');
echo html_writer::tag('h2', get_string('level3behavior', 'local_kirkpatrick_dashboard'));

echo html_writer::start_div('level-metrics row');

// Average performance rating
$avg_performance = $DB->get_field_sql(
    "SELECT AVG(performance_rating) FROM {kirkpatrick_level3_behavior}"
);
$avg_performance = $avg_performance !== false ? (float)$avg_performance : 0.0;
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('avgperformance', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', number_format($avg_performance, 1) . '/10', ['class' => 'metric-value']);
echo html_writer::end_div();

// Behavior tracking count
$behavior_tracked = $DB->count_records('kirkpatrick_level3_behavior');
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('behaviortracked', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', $behavior_tracked, ['class' => 'metric-value']);
echo html_writer::end_div();

// Evidence submitted
$evidence_submitted = $DB->count_records('kirkpatrick_level3_behavior', ['evidence_submitted' => 1]);
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('evidencesubmitted', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', $evidence_submitted, ['class' => 'metric-value']);
echo html_writer::end_div();

// Follow-up completion rate
$followup_scheduled = $DB->count_records('kirkpatrick_followup_schedule');
$followup_completed = $DB->count_records('kirkpatrick_followup_schedule', ['status_30days' => 'completed']);
$completion_rate = ($followup_scheduled > 0) ? (($followup_completed / $followup_scheduled) * 100) : 0.0;
echo html_writer::start_div('metric-card col-md-3');
echo html_writer::tag('h4', get_string('followupcompletion', 'local_kirkpatrick_dashboard'));
echo html_writer::tag('div', number_format($completion_rate, 1) . '%', ['class' => 'metric-value']);
echo html_writer::end_div();

echo html_writer::end_div(); // level-metrics

// Behavior sustainability chart placeholder
echo html_writer::div('', 'chart-container', ['id' => 'level3-sustainability-chart']);

echo html_writer::end_div(); // level-3

// Level 4: Results
echo html_writer::start_div('kirkpatrick-level level-4');
echo html_writer::tag('h2', get_string('level4results', 'local_kirkpatrick_dashboard'));

echo html_writer::start_div('level-metrics row');

// Check if Level 4 plugin is installed
$level4_installed = $DB->record_exists('config_plugins', ['plugin' => 'local_kirkpatrick_level4']);

if ($level4_installed) {
    // Total cost savings
    $total_savings = $DB->get_field_sql(
        "SELECT SUM(cost_savings) FROM {kirkpatrick_level4_results}"
    );
    $total_savings = $total_savings !== false ? (float)$total_savings : 0.0;
    echo html_writer::start_div('metric-card col-md-3');
    echo html_writer::tag('h4', get_string('totalcostsavings', 'local_kirkpatrick_dashboard'));
    echo html_writer::tag('div', '$' . number_format($total_savings, 0), ['class' => 'metric-value']);
    echo html_writer::end_div();
    
    // Average ROI
    $avg_roi = $DB->get_field_sql(
        "SELECT AVG(roi_calculation) FROM {kirkpatrick_level4_results}"
    );
    $avg_roi = $avg_roi !== false ? (float)$avg_roi : 0.0;
    echo html_writer::start_div('metric-card col-md-3');
    echo html_writer::tag('h4', get_string('avgroi', 'local_kirkpatrick_dashboard'));
    echo html_writer::tag('div', number_format($avg_roi, 1) . '%', ['class' => 'metric-value']);
    echo html_writer::end_div();
    
    // Productivity improvement
    $avg_productivity = $DB->get_field_sql(
        "SELECT AVG(productivity_improvement) FROM {kirkpatrick_level4_results}"
    );
    $avg_productivity = $avg_productivity !== false ? (float)$avg_productivity : 0.0;
    echo html_writer::start_div('metric-card col-md-3');
    echo html_writer::tag('h4', get_string('avgproductivity', 'local_kirkpatrick_dashboard'));
    echo html_writer::tag('div', number_format($avg_productivity, 1) . '%', ['class' => 'metric-value']);
    echo html_writer::end_div();
    
    // Programs measured
    $programs_measured = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT programid) FROM {kirkpatrick_level4_results}"
    );
    echo html_writer::start_div('metric-card col-md-3');
    echo html_writer::tag('h4', get_string('programsmeasured', 'local_kirkpatrick_dashboard'));
    echo html_writer::tag('div', $programs_measured, ['class' => 'metric-value']);
    echo html_writer::end_div();
    
    // ROI chart placeholder
    echo html_writer::div('', 'chart-container', ['id' => 'level4-roi-chart']);
} else {
    echo html_writer::div(
        get_string('level4notinstalled', 'local_kirkpatrick_dashboard'),
        'alert alert-info'
    );
}

echo html_writer::end_div(); // level-metrics
echo html_writer::end_div(); // level-4

// Integrated view
echo html_writer::start_div('kirkpatrick-integrated');
echo html_writer::tag('h2', get_string('integratedview', 'local_kirkpatrick_dashboard'));

// Evaluation funnel
echo html_writer::div('', 'chart-container', ['id' => 'evaluation-funnel-chart']);

// Export options
echo html_writer::start_div('export-options');
echo html_writer::tag('h3', get_string('exportdata', 'local_kirkpatrick_dashboard'));
echo html_writer::link(
    new moodle_url('/local/kirkpatrick_dashboard/export.php', ['format' => 'pdf']),
    get_string('exportpdf', 'local_kirkpatrick_dashboard'),
    ['class' => 'btn btn-primary']
);
echo ' ';
echo html_writer::link(
    new moodle_url('/local/kirkpatrick_dashboard/export.php', ['format' => 'excel']),
    get_string('exportexcel', 'local_kirkpatrick_dashboard'),
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();

echo html_writer::end_div(); // kirkpatrick-integrated

echo $OUTPUT->footer();
