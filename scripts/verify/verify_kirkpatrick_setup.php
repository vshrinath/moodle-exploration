<?php
/**
 * Verification script for Kirkpatrick Model Evaluation Plugins
 * Task 2.6: Install Kirkpatrick Model evaluation plugins
 * Requirements: 17.1, 17.2, 17.3, 17.4
 * 
 * This script verifies the installation and configuration of all
 * Kirkpatrick evaluation framework components.
 */

define('CLI_SCRIPT', true);
// Detect Moodle config
if (!defined('MOODLE_INTERNAL')) {
    $config_paths = [
        '/var/www/html/public/config.php',
        '/bitnami/moodle/config.php',
        dirname(__DIR__, 2) . '/moodle-core/public/config.php',
        dirname(__DIR__, 1) . '/config.php',
        __DIR__ . '/config.php'
    ];
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            break;
        }
    }
}

require_once($CFG->libdir.'/adminlib.php');

echo "========================================\n";
echo "Kirkpatrick Model Evaluation Framework\n";
echo "Installation Verification\n";
echo "========================================\n\n";

$all_checks_passed = true;

// Level 1: Reaction - Feedback and Questionnaire
echo "Level 1 - Reaction (Satisfaction Measurement)\n";
echo "----------------------------------------------\n";

// Check Feedback Activity module
$feedback_path = $CFG->dirroot . '/mod/feedback';
$feedback_enabled = get_config('mod_feedback', 'visible');

if (file_exists($feedback_path)) {
    echo "✓ Feedback Activity module installed\n";
    if ($feedback_enabled) {
        echo "✓ Feedback Activity module enabled\n";
    } else {
        echo "✗ Feedback Activity module not enabled\n";
        $all_checks_passed = false;
    }
} else {
    echo "✗ Feedback Activity module not found\n";
    $all_checks_passed = false;
}

// Check Questionnaire plugin
$questionnaire_path = $CFG->dirroot . '/mod/questionnaire';
$questionnaire_version = get_config('mod_questionnaire', 'version');

if (file_exists($questionnaire_path)) {
    echo "✓ Questionnaire plugin installed\n";
    if ($questionnaire_version) {
        echo "✓ Questionnaire plugin configured (version: $questionnaire_version)\n";
    } else {
        echo "⚠ Questionnaire plugin needs database upgrade\n";
    }
} else {
    echo "✗ Questionnaire plugin not found\n";
    $all_checks_passed = false;
}

// Check feedback configuration
$feedback_anonymous = get_config('feedback', 'allowfullanonymous');
if ($feedback_anonymous) {
    echo "✓ Anonymous feedback enabled for honest responses\n";
} else {
    echo "⚠ Anonymous feedback not configured\n";
}

echo "\n";

// Level 2: Learning - Competency Framework and Assessments
echo "Level 2 - Learning (Knowledge Gain Measurement)\n";
echo "------------------------------------------------\n";

// Check Competency Framework
$competency_enabled = get_config('core_competency', 'enabled');
if ($competency_enabled) {
    echo "✓ Competency Framework enabled\n";
} else {
    echo "✗ Competency Framework not enabled\n";
    $all_checks_passed = false;
}

// Check Badges system
$badges_enabled = get_config('core', 'enablebadges');
if ($badges_enabled) {
    echo "✓ Badges system enabled for achievement verification\n";
} else {
    echo "✗ Badges system not enabled\n";
    $all_checks_passed = false;
}

// Check Quiz module
$quiz_path = $CFG->dirroot . '/mod/quiz';
if (file_exists($quiz_path)) {
    echo "✓ Quiz module available for assessments\n";
} else {
    echo "✗ Quiz module not found\n";
    $all_checks_passed = false;
}

// Check Assignment module
$assign_path = $CFG->dirroot . '/mod/assign';
if (file_exists($assign_path)) {
    echo "✓ Assignment module available for skill demonstrations\n";
} else {
    echo "✗ Assignment module not found\n";
    $all_checks_passed = false;
}

echo "\n";

// Level 3: Behavior - Portfolio System
echo "Level 3 - Behavior (Application Tracking)\n";
echo "------------------------------------------\n";

// Check Portfolio system
$portfolio_enabled = get_config('core', 'enableportfolios');
$portfolio_path = $CFG->dirroot . '/portfolio';

if (file_exists($portfolio_path)) {
    echo "✓ Portfolio system installed\n";
    if ($portfolio_enabled) {
        echo "✓ Portfolio system enabled\n";
    } else {
        echo "✗ Portfolio system not enabled\n";
        $all_checks_passed = false;
    }
} else {
    echo "✗ Portfolio system not found\n";
    $all_checks_passed = false;
}

// Check for portfolio plugins
$portfolio_plugins = core_component::get_plugin_list('portfolio');
if (!empty($portfolio_plugins)) {
    echo "✓ Portfolio plugins available: " . count($portfolio_plugins) . " plugin(s)\n";
} else {
    echo "⚠ No portfolio plugins configured\n";
}

echo "\n";

// Level 4: Results - External Database Integration
echo "Level 4 - Results (Organizational Impact)\n";
echo "------------------------------------------\n";

// Check External Database enrolment plugin
$extdb_enrol_path = $CFG->dirroot . '/enrol/database';
$extdb_enrol_enabled = get_config('enrol_database', 'version');

if (file_exists($extdb_enrol_path)) {
    echo "✓ External Database enrolment plugin installed\n";
    if ($extdb_enrol_enabled) {
        echo "✓ External Database enrolment plugin available\n";
    }
} else {
    echo "✗ External Database enrolment plugin not found\n";
    $all_checks_passed = false;
}

// Check External Database authentication plugin
$extdb_auth_path = $CFG->dirroot . '/auth/db';
$extdb_auth_enabled = get_config('auth_db', 'version');

if (file_exists($extdb_auth_path)) {
    echo "✓ External Database authentication plugin installed\n";
    if ($extdb_auth_enabled) {
        echo "✓ External Database authentication plugin available\n";
    }
} else {
    echo "✗ External Database authentication plugin not found\n";
    $all_checks_passed = false;
}

// Check if external database is configured
$extdb_host = get_config('enrol_database', 'dbhost');
if ($extdb_host) {
    echo "✓ External Database connection configured\n";
} else {
    echo "⚠ External Database connection not configured (manual setup required)\n";
}

echo "\n";

// Additional Integration Checks
echo "Integration Checks\n";
echo "------------------\n";

// Check Configurable Reports for Kirkpatrick analytics
$reports_path = $CFG->dirroot . '/blocks/configurable_reports';
if (file_exists($reports_path)) {
    echo "✓ Configurable Reports plugin available for analytics\n";
} else {
    echo "⚠ Configurable Reports plugin not found (recommended for dashboards)\n";
}

// Check completion tracking
$completion_enabled = get_config('core', 'enablecompletion');
if ($completion_enabled) {
    echo "✓ Completion tracking enabled\n";
} else {
    echo "⚠ Completion tracking not enabled\n";
}

echo "\n";

// Summary
echo "========================================\n";
echo "Verification Summary\n";
echo "========================================\n\n";

if ($all_checks_passed) {
    echo "✓ ALL CRITICAL CHECKS PASSED\n\n";
    echo "Kirkpatrick Model Evaluation Framework is ready:\n";
    echo "  ✓ Level 1 (Reaction): Feedback & Questionnaire\n";
    echo "  ✓ Level 2 (Learning): Competency & Assessments\n";
    echo "  ✓ Level 3 (Behavior): Portfolio System\n";
    echo "  ✓ Level 4 (Results): External Database\n";
} else {
    echo "✗ SOME CHECKS FAILED\n\n";
    echo "Please review the errors above and:\n";
    echo "  1. Complete plugin installation via admin UI\n";
    echo "  2. Run configuration script: php configure_kirkpatrick_plugins.php\n";
    echo "  3. Re-run this verification script\n";
}

echo "\n";
echo "Next Steps:\n";
echo "1. Configure External Database connection for hospital data\n";
echo "2. Create Feedback templates for post-session surveys\n";
echo "3. Set up Questionnaire templates for evaluation\n";
echo "4. Configure Portfolio instances for evidence collection\n";
echo "5. Create Configurable Reports for Kirkpatrick dashboards\n";
echo "6. Test end-to-end evaluation workflow\n";

echo "\n========================================\n";
echo "Verification Complete\n";
echo "========================================\n";

exit($all_checks_passed ? 0 : 1);
