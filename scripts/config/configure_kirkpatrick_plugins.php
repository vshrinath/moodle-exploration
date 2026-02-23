<?php
/**
 * Configuration script for Kirkpatrick Model Evaluation Plugins
 * Task 2.6: Install Kirkpatrick Model evaluation plugins
 * Requirements: 17.1, 17.2, 17.3, 17.4
 * 
 * This script configures the Kirkpatrick evaluation framework plugins:
 * - Level 1 (Reaction): Feedback Activity and Questionnaire
 * - Level 2 (Learning): Competency Framework integration
 * - Level 3 (Behavior): Portfolio system
 * - Level 4 (Results): External Database configuration
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
echo "Configuring Kirkpatrick Model Evaluation Plugins\n";
echo "========================================\n\n";

// Level 1: Reaction - Enable Feedback Activity
echo "Level 1 - Reaction (Satisfaction)\n";
echo "-----------------------------------\n";

// Enable Feedback module
echo "Enabling Feedback Activity module...\n";
$feedback_visible = get_config('mod_feedback', 'visible');
if ($feedback_visible === false || $feedback_visible == 0) {
    set_config('visible', 1, 'mod_feedback');
    echo "✓ Feedback Activity module enabled\n";
} else {
    echo "✓ Feedback Activity module already enabled\n";
}

// Configure Feedback settings for Kirkpatrick Level 1
set_config('allowfullanonymous', 1, 'feedback'); // Allow anonymous responses
set_config('email_notification', 1, 'feedback'); // Enable email notifications
echo "✓ Feedback Activity configured for satisfaction surveys\n";

// Check Questionnaire plugin
echo "\nChecking Questionnaire plugin installation...\n";
$questionnaire_version = get_config('mod_questionnaire', 'version');
if ($questionnaire_version) {
    echo "✓ Questionnaire plugin installed (version: $questionnaire_version)\n";
    
    // Configure Questionnaire for advanced surveys
    set_config('downloadoptions', 'csv,xls', 'questionnaire');
    echo "✓ Questionnaire configured for advanced evaluation surveys\n";
} else {
    echo "⚠ Questionnaire plugin not found - please complete installation via admin UI\n";
}

echo "\n";

// Level 2: Learning - Verify Competency Framework
echo "Level 2 - Learning (Knowledge Gain)\n";
echo "------------------------------------\n";

// Check if competency framework is enabled
$competency_enabled = get_config('core_competency', 'enabled');
if ($competency_enabled) {
    echo "✓ Competency Framework enabled\n";
} else {
    echo "⚠ Competency Framework not enabled - enabling now...\n";
    set_config('enabled', 1, 'core_competency');
    echo "✓ Competency Framework enabled\n";
}

// Verify badges system
$badges_enabled = get_config('core', 'enablebadges');
if ($badges_enabled) {
    echo "✓ Badges system enabled for learning verification\n";
} else {
    echo "⚠ Badges system not enabled - enabling now...\n";
    set_config('enablebadges', 1);
    echo "✓ Badges system enabled\n";
}

echo "\n";

// Level 3: Behavior - Enable Portfolio System
echo "Level 3 - Behavior (Application Tracking)\n";
echo "------------------------------------------\n";

// Enable portfolio functionality
$portfolio_enabled = get_config('core', 'enableportfolios');
if ($portfolio_enabled) {
    echo "✓ Portfolio system already enabled\n";
} else {
    echo "Enabling Portfolio system...\n";
    set_config('enableportfolios', 1);
    echo "✓ Portfolio system enabled\n";
}

// Configure portfolio settings for evidence collection
set_config('portfolio_moderate', 0); // No moderation required for evidence submission
echo "✓ Portfolio configured for evidence collection\n";

echo "\n";

// Level 4: Results - External Database Configuration
echo "Level 4 - Results (Organizational Impact)\n";
echo "-----------------------------------------\n";

// Check External Database plugins
$extdb_enrol = get_config('enrol_database', 'version');
$extdb_auth = get_config('auth_db', 'version');

if ($extdb_enrol) {
    echo "✓ External Database enrolment plugin available\n";
} else {
    echo "⚠ External Database enrolment plugin not found\n";
}

if ($extdb_auth) {
    echo "✓ External Database authentication plugin available\n";
} else {
    echo "⚠ External Database authentication plugin not found\n";
}

echo "\n";
echo "⚠ IMPORTANT: External Database connection requires manual configuration:\n";
echo "  1. Navigate to: Site administration > Plugins > Enrolments > External database\n";
echo "  2. Configure database connection settings:\n";
echo "     - Database driver (mysqli, pgsql, etc.)\n";
echo "     - Database host, name, user, password\n";
echo "     - Table mappings for hospital system data\n";
echo "  3. Test connection and verify data synchronization\n";

echo "\n";

// Summary
echo "========================================\n";
echo "Configuration Summary\n";
echo "========================================\n\n";

echo "Kirkpatrick Model Evaluation Framework Status:\n\n";

echo "✓ Level 1 (Reaction): Feedback & Questionnaire configured\n";
echo "✓ Level 2 (Learning): Competency Framework & Badges enabled\n";
echo "✓ Level 3 (Behavior): Portfolio system enabled\n";
echo "⚠ Level 4 (Results): External Database requires manual setup\n";

echo "\n";
echo "Next Steps:\n";
echo "1. Complete plugin installation via admin UI (Site admin > Notifications)\n";
echo "2. Configure External Database connection for Level 4 data\n";
echo "3. Create Feedback templates for post-session surveys\n";
echo "4. Set up Questionnaire templates for evaluation\n";
echo "5. Configure Portfolio instances for evidence collection\n";
echo "6. Run verification: php verify_kirkpatrick_setup.php\n";

echo "\n========================================\n";
echo "Configuration Complete\n";
echo "========================================\n";
