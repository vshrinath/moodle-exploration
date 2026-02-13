<?php
/**
 * Moodle Setup Verification Script
 * Verifies that all required components for the competency-based learning system are properly configured
 */

define('CLI_SCRIPT', true);
require_once('/bitnami/moodle/config.php');
require_once($CFG->libdir.'/clilib.php');

echo "=== Moodle Competency-Based Learning System Setup Verification ===\n\n";

// Check Moodle version
echo "Moodle Version: " . $CFG->release . "\n";
echo "Database: " . $CFG->dbtype . " on " . $CFG->dbhost . "\n\n";

// Core competency framework checks
echo "=== Core Competency Framework ===\n";
$competencies_enabled = get_config('core', 'enablecompetencies');
echo "Competencies enabled: " . ($competencies_enabled ? 'YES' : 'NO') . "\n";

$learning_plans_enabled = get_config('core', 'enablelearningplans');
echo "Learning plans enabled: " . ($learning_plans_enabled ? 'YES' : 'NO') . "\n";

// Check if competency tables exist
$dbman = $DB->get_manager();
$competency_tables = [
    'competency_framework',
    'competency',
    'competency_coursecomp',
    'competency_usercomp',
    'competency_evidence',
    'competency_plan',
    'competency_plancomp',
    'competency_template',
    'competency_templatecomp'
];

echo "\nCompetency database tables:\n";
foreach ($competency_tables as $table) {
    $exists = $dbman->table_exists($table);
    echo "  $table: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}

// Check other required features
echo "\n=== Required Features ===\n";
$badges_enabled = get_config('core', 'enablebadges');
echo "Badges enabled: " . ($badges_enabled ? 'YES' : 'NO') . "\n";

$completion_enabled = get_config('core', 'enablecompletion');
echo "Completion tracking enabled: " . ($completion_enabled ? 'YES' : 'NO') . "\n";

$availability_enabled = get_config('core', 'enableavailability');
echo "Conditional activities enabled: " . ($availability_enabled ? 'YES' : 'NO') . "\n";

$cohorts_enabled = get_config('core', 'enablecohorts');
echo "Cohorts enabled: " . ($cohorts_enabled ? 'YES' : 'NO') . "\n";

// Check for required plugins
echo "\n=== Plugin Availability ===\n";
$required_plugins = [
    'mod_quiz' => 'Quiz module',
    'mod_assign' => 'Assignment module',
    'mod_feedback' => 'Feedback module',
    'mod_questionnaire' => 'Questionnaire module',
    'mod_data' => 'Database activity module',
    'mod_scheduler' => 'Scheduler module',
    'repository_youtube' => 'YouTube repository',
    'repository_vimeo' => 'Vimeo repository',
    'block_configurable_reports' => 'Configurable Reports',
    'mod_customcert' => 'Custom Certificate',
    'mod_attendance' => 'Attendance',
    'block_xp' => 'Level Up! gamification',
    'block_stash' => 'Stash plugin',
    'core_portfolio' => 'Portfolio module'
];

foreach ($required_plugins as $plugin => $name) {
    if ($plugin === 'core_portfolio') {
        $enabled = get_config('core', 'enableportfolios');
        echo "  $name: " . ($enabled ? 'ENABLED' : 'DISABLED') . "\n";
        continue;
    }
    $plugin_info = core_plugin_manager::instance()->get_plugin_info($plugin);
    if ($plugin_info) {
        echo "  $name: INSTALLED (version " . $plugin_info->versiondb . ")\n";
    } else {
        echo "  $name: NOT INSTALLED\n";
    }
}

// Check capabilities
echo "\n=== Competency Capabilities ===\n";
$context = context_system::instance();
$capabilities = [
    'moodle/competency:competencymanage',
    'moodle/competency:competencyview',
    'moodle/competency:planmanage',
    'moodle/competency:planview'
];

foreach ($capabilities as $capability) {
    $exists = get_capability_info($capability);
    echo "  $capability: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}

// Summary
echo "\n=== Setup Summary ===\n";
$all_good = $competencies_enabled && $learning_plans_enabled && $badges_enabled && 
           $completion_enabled && $availability_enabled && $cohorts_enabled;

if ($all_good) {
    echo "✓ Core competency framework is properly configured!\n";
    echo "✓ All required core features are enabled.\n";
    echo "✓ Ready for competency-based learning implementation.\n";
} else {
    echo "⚠ Some required features may not be properly configured.\n";
    echo "Please review the settings above.\n";
}

echo "\nNext steps:\n";
echo "1. Install additional required plugins (see Plugin Availability section)\n";
echo "2. Configure competency frameworks\n";
echo "3. Set up learning plan templates\n";
echo "4. Configure cohorts and user roles\n";

echo "\n=== Verification Complete ===\n";
?>
