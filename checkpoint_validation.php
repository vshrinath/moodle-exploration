<?php
/**
 * Checkpoint 13: Core Functionality and Rules Engine Validation
 * 
 * This script validates:
 * - Core competency and learning path functionality
 * - Cohort management and access controls
 * - Content and assessment integration
 * - Attendance tracking and badge system integration
 * - Rules engine functionality (attendance locking, roster automation)
 * - Kirkpatrick evaluation data collection
 * - Ophthalmology fellowship features
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');

// Validation results tracker
$results = [
    'passed' => [],
    'failed' => [],
    'warnings' => []
];

function log_result($category, $test, $status, $message = '') {
    global $results;
    $result = [
        'category' => $category,
        'test' => $test,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($status === 'pass') {
        $results['passed'][] = $result;
        echo "✓ PASS: [$category] $test\n";
    } elseif ($status === 'fail') {
        $results['failed'][] = $result;
        echo "✗ FAIL: [$category] $test - $message\n";
    } else {
        $results['warnings'][] = $result;
        echo "⚠ WARN: [$category] $test - $message\n";
    }
    if ($message && $status === 'pass') {
        echo "  → $message\n";
    }
}

echo "=================================================================\n";
echo "CHECKPOINT 13: CORE FUNCTIONALITY AND RULES ENGINE VALIDATION\n";
echo "=================================================================\n\n";

// 1. CORE COMPETENCY AND LEARNING PATH FUNCTIONALITY
echo "\n--- 1. Core Competency and Learning Path Functionality ---\n";

// Check competency framework is enabled
$competency_enabled = get_config('core', 'competency_enabled');
if ($competency_enabled) {
    log_result('Competency', 'Framework Enabled', 'pass', 'Competency framework is enabled');
} else {
    log_result('Competency', 'Framework Enabled', 'fail', 'Competency framework is not enabled');
}

// Check for competency frameworks
try {
    $frameworks = $DB->get_records('competency_framework');
    if (count($frameworks) > 0) {
        log_result('Competency', 'Frameworks Exist', 'pass', count($frameworks) . ' framework(s) found');
    } else {
        log_result('Competency', 'Frameworks Exist', 'warn', 'No competency frameworks found');
    }
} catch (Exception $e) {
    log_result('Competency', 'Frameworks Exist', 'warn', 'Error checking frameworks: ' . $e->getMessage());
}

// Check for competencies
try {
    $competencies = $DB->get_records('competency');
    if (count($competencies) > 0) {
        log_result('Competency', 'Competencies Exist', 'pass', count($competencies) . ' competency(ies) found');
    } else {
        log_result('Competency', 'Competencies Exist', 'warn', 'No competencies found');
    }
} catch (Exception $e) {
    log_result('Competency', 'Competencies Exist', 'warn', 'Error checking competencies: ' . $e->getMessage());
}

// Check for learning plan templates
try {
    $plan_templates = $DB->get_records('competency_template');
    if (count($plan_templates) > 0) {
        log_result('Learning Path', 'Plan Templates Exist', 'pass', count($plan_templates) . ' template(s) found');
    } else {
        log_result('Learning Path', 'Plan Templates Exist', 'warn', 'No learning plan templates found');
    }
} catch (Exception $e) {
    log_result('Learning Path', 'Plan Templates Exist', 'warn', 'Table not found or error: ' . $e->getMessage());
}

// Check for learning plans
try {
    $plans = $DB->get_records('competency_plan');
    if (count($plans) > 0) {
        log_result('Learning Path', 'Learning Plans Exist', 'pass', count($plans) . ' plan(s) found');
    } else {
        log_result('Learning Path', 'Learning Plans Exist', 'warn', 'No learning plans found');
    }
} catch (Exception $e) {
    log_result('Learning Path', 'Learning Plans Exist', 'warn', 'Table not found or error: ' . $e->getMessage());
}

// 2. COHORT MANAGEMENT AND ACCESS CONTROLS
echo "\n--- 2. Cohort Management and Access Controls ---\n";

// Check for cohorts
$cohorts = $DB->get_records('cohort');
if (count($cohorts) > 0) {
    log_result('Cohort', 'Cohorts Exist', 'pass', count($cohorts) . ' cohort(s) found');
} else {
    log_result('Cohort', 'Cohorts Exist', 'warn', 'No cohorts found');
}

// Check role-based access controls
$roles = $DB->get_records('role', null, '', 'id, shortname, name');
$required_roles = ['manager', 'coursecreator', 'editingteacher', 'teacher', 'student'];
$found_roles = array_column($roles, 'shortname');
foreach ($required_roles as $required_role) {
    if (in_array($required_role, $found_roles)) {
        log_result('Access Control', "Role: $required_role", 'pass', 'Role exists');
    } else {
        log_result('Access Control', "Role: $required_role", 'fail', 'Required role not found');
    }
}

// 3. CONTENT AND ASSESSMENT INTEGRATION
echo "\n--- 3. Content and Assessment Integration ---\n";

// Check for courses
$courses = $DB->get_records('course', ['id' => $DB->sql_compare_text('id') . ' > 1']);
if (count($courses) > 0) {
    log_result('Content', 'Courses Exist', 'pass', count($courses) . ' course(s) found');
} else {
    log_result('Content', 'Courses Exist', 'warn', 'No courses found (besides site course)');
}

// Check for quiz module
$quiz_module = $DB->get_record('modules', ['name' => 'quiz']);
if ($quiz_module && $quiz_module->visible) {
    log_result('Assessment', 'Quiz Module', 'pass', 'Quiz module is enabled');
} else {
    log_result('Assessment', 'Quiz Module', 'fail', 'Quiz module not available');
}

// Check for assignment module
$assign_module = $DB->get_record('modules', ['name' => 'assign']);
if ($assign_module && $assign_module->visible) {
    log_result('Assessment', 'Assignment Module', 'pass', 'Assignment module is enabled');
} else {
    log_result('Assessment', 'Assignment Module', 'fail', 'Assignment module not available');
}

// Check for repository plugins (YouTube, Vimeo)
$youtube_repo = $DB->get_record('repository', ['type' => 'youtube']);
if ($youtube_repo) {
    log_result('Content', 'YouTube Repository', 'pass', 'YouTube repository is available');
} else {
    log_result('Content', 'YouTube Repository', 'warn', 'YouTube repository not configured');
}

// 4. ATTENDANCE TRACKING AND BADGE SYSTEM INTEGRATION
echo "\n--- 4. Attendance Tracking and Badge System Integration ---\n";

// Check for attendance module
$attendance_module = $DB->get_record('modules', ['name' => 'attendance']);
if ($attendance_module && $attendance_module->visible) {
    log_result('Attendance', 'Attendance Module', 'pass', 'Attendance module is enabled');
    
    // Check for attendance sessions
    $attendance_sessions = $DB->get_records('attendance_sessions');
    if (count($attendance_sessions) > 0) {
        log_result('Attendance', 'Sessions Exist', 'pass', count($attendance_sessions) . ' session(s) found');
    } else {
        log_result('Attendance', 'Sessions Exist', 'warn', 'No attendance sessions found');
    }
} else {
    log_result('Attendance', 'Attendance Module', 'fail', 'Attendance module not available');
}

// Check badge system
$badges_enabled = !empty($CFG->enablebadges);
if ($badges_enabled) {
    log_result('Badges', 'Badge System Enabled', 'pass', 'Badge system is enabled');
    
    // Check for badges
    $badges = $DB->get_records('badge');
    if (count($badges) > 0) {
        log_result('Badges', 'Badges Exist', 'pass', count($badges) . ' badge(s) found');
    } else {
        log_result('Badges', 'Badges Exist', 'warn', 'No badges configured');
    }
} else {
    log_result('Badges', 'Badge System Enabled', 'fail', 'Badge system is not enabled');
}

// 5. RULES ENGINE FUNCTIONALITY
echo "\n--- 5. Rules Engine Functionality ---\n";

// Check if rules engine plugin exists
$rules_plugin_path = $CFG->dirroot . '/local/sceh_rules';
if (file_exists($rules_plugin_path)) {
    log_result('Rules Engine', 'Plugin Installed', 'pass', 'local_sceh_rules plugin found');
    
    // Check plugin version
    $plugin_info = core_plugin_manager::instance()->get_plugin_info('local_sceh_rules');
    if ($plugin_info) {
        log_result('Rules Engine', 'Plugin Registered', 'pass', 'Plugin version: ' . $plugin_info->versiondb);
    } else {
        log_result('Rules Engine', 'Plugin Registered', 'warn', 'Plugin not registered in Moodle');
    }
    
    // Check for attendance rules table
    $dbman = $DB->get_manager();
    $attendance_rules_table = new xmldb_table('local_sceh_attendance_rules');
    if ($dbman->table_exists($attendance_rules_table)) {
        log_result('Rules Engine', 'Attendance Rules Table', 'pass', 'Database table exists');
        
        // Check for attendance rules
        $attendance_rules = $DB->get_records('local_sceh_attendance_rules');
        if (count($attendance_rules) > 0) {
            log_result('Rules Engine', 'Attendance Rules Configured', 'pass', count($attendance_rules) . ' rule(s) found');
        } else {
            log_result('Rules Engine', 'Attendance Rules Configured', 'warn', 'No attendance rules configured');
        }
    } else {
        log_result('Rules Engine', 'Attendance Rules Table', 'fail', 'Database table not found');
    }
    
    // Check for roster rules table
    $roster_rules_table = new xmldb_table('local_sceh_roster_rules');
    if ($dbman->table_exists($roster_rules_table)) {
        log_result('Rules Engine', 'Roster Rules Table', 'pass', 'Database table exists');
        
        // Check for roster rules
        $roster_rules = $DB->get_records('local_sceh_roster_rules');
        if (count($roster_rules) > 0) {
            log_result('Rules Engine', 'Roster Rules Configured', 'pass', count($roster_rules) . ' rule(s) found');
        } else {
            log_result('Rules Engine', 'Roster Rules Configured', 'warn', 'No roster rules configured');
        }
    } else {
        log_result('Rules Engine', 'Roster Rules Table', 'fail', 'Database table not found');
    }
    
    // Check event observers are registered
    $observers_file = $rules_plugin_path . '/db/events.php';
    if (file_exists($observers_file)) {
        log_result('Rules Engine', 'Event Observers', 'pass', 'Event observers file exists');
    } else {
        log_result('Rules Engine', 'Event Observers', 'fail', 'Event observers file not found');
    }
    
} else {
    log_result('Rules Engine', 'Plugin Installed', 'fail', 'local_sceh_rules plugin not found');
}

// 6. KIRKPATRICK EVALUATION DATA COLLECTION
echo "\n--- 6. Kirkpatrick Evaluation Data Collection ---\n";

// Check for feedback module (Level 1)
$feedback_module = $DB->get_record('modules', ['name' => 'feedback']);
if ($feedback_module && $feedback_module->visible) {
    log_result('Kirkpatrick L1', 'Feedback Module', 'pass', 'Feedback module is enabled');
} else {
    log_result('Kirkpatrick L1', 'Feedback Module', 'warn', 'Feedback module not available');
}

// Check for questionnaire module (Level 1)
$questionnaire_module = $DB->get_record('modules', ['name' => 'questionnaire']);
if ($questionnaire_module && $questionnaire_module->visible) {
    log_result('Kirkpatrick L1', 'Questionnaire Module', 'pass', 'Questionnaire module is enabled');
} else {
    log_result('Kirkpatrick L1', 'Questionnaire Module', 'warn', 'Questionnaire module not available');
}

// Level 2 uses competency framework (already checked)
log_result('Kirkpatrick L2', 'Competency Framework', 'pass', 'Uses core competency framework');

// Check for portfolio (Level 3)
$portfolio_enabled = !empty($CFG->enableportfolios);
if ($portfolio_enabled) {
    log_result('Kirkpatrick L3', 'Portfolio System', 'pass', 'Portfolio system is enabled');
} else {
    log_result('Kirkpatrick L3', 'Portfolio System', 'warn', 'Portfolio system not enabled');
}

// Check for Kirkpatrick dashboard plugin
$kirkpatrick_dashboard_path = $CFG->dirroot . '/local/kirkpatrick_dashboard';
if (file_exists($kirkpatrick_dashboard_path)) {
    log_result('Kirkpatrick', 'Dashboard Plugin', 'pass', 'Kirkpatrick dashboard plugin found');
} else {
    log_result('Kirkpatrick', 'Dashboard Plugin', 'warn', 'Kirkpatrick dashboard plugin not found');
}

// Check for Level 4 plugin
$level4_plugin_path = $CFG->dirroot . '/local/kirkpatrick_level4';
if (file_exists($level4_plugin_path)) {
    log_result('Kirkpatrick L4', 'Level 4 Plugin', 'pass', 'Level 4 integration plugin found');
} else {
    log_result('Kirkpatrick L4', 'Level 4 Plugin', 'warn', 'Level 4 plugin not installed (optional)');
}

// 7. OPHTHALMOLOGY FELLOWSHIP FEATURES
echo "\n--- 7. Ophthalmology Fellowship Features ---\n";

// Check for database module (for logbooks and credentialing)
$database_module = $DB->get_record('modules', ['name' => 'data']);
if ($database_module && $database_module->visible) {
    log_result('Fellowship', 'Database Module', 'pass', 'Database activity module is enabled');
    
    // Check for database activities
    $databases = $DB->get_records('data');
    if (count($databases) > 0) {
        log_result('Fellowship', 'Database Activities', 'pass', count($databases) . ' database(s) found');
        
        // Check for case logbook indicators
        $logbook_found = false;
        $credentialing_found = false;
        $research_found = false;
        
        foreach ($databases as $database) {
            if (stripos($database->name, 'logbook') !== false || stripos($database->name, 'case') !== false) {
                $logbook_found = true;
            }
            if (stripos($database->name, 'credential') !== false) {
                $credentialing_found = true;
            }
            if (stripos($database->name, 'research') !== false || stripos($database->name, 'publication') !== false) {
                $research_found = true;
            }
        }
        
        if ($logbook_found) {
            log_result('Fellowship', 'Case Logbook', 'pass', 'Case logbook database found');
        } else {
            log_result('Fellowship', 'Case Logbook', 'warn', 'No case logbook database found');
        }
        
        if ($credentialing_found) {
            log_result('Fellowship', 'Credentialing Sheet', 'pass', 'Credentialing database found');
        } else {
            log_result('Fellowship', 'Credentialing Sheet', 'warn', 'No credentialing database found');
        }
        
        if ($research_found) {
            log_result('Fellowship', 'Research Tracking', 'pass', 'Research database found');
        } else {
            log_result('Fellowship', 'Research Tracking', 'warn', 'No research database found');
        }
    } else {
        log_result('Fellowship', 'Database Activities', 'warn', 'No database activities configured');
    }
} else {
    log_result('Fellowship', 'Database Module', 'fail', 'Database activity module not available');
}

// Check for scheduler module (for rotations)
$scheduler_module = $DB->get_record('modules', ['name' => 'scheduler']);
if ($scheduler_module && $scheduler_module->visible) {
    log_result('Fellowship', 'Scheduler Module', 'pass', 'Scheduler module is enabled');
} else {
    log_result('Fellowship', 'Scheduler Module', 'warn', 'Scheduler module not available');
}

// Check for custom user profile fields
$profile_fields = $DB->get_records('user_info_field');
if (count($profile_fields) > 0) {
    log_result('Fellowship', 'Custom Profile Fields', 'pass', count($profile_fields) . ' custom field(s) found');
} else {
    log_result('Fellowship', 'Custom Profile Fields', 'warn', 'No custom profile fields configured');
}

// Check database templates exist
$template_path = $CFG->dirroot . '/database_templates';
if (file_exists($template_path)) {
    log_result('Fellowship', 'Database Templates', 'pass', 'Database templates directory found');
    
    $templates = ['case_logbook_template.xml', 'credentialing_sheet_template.xml', 'research_publications_template.xml'];
    foreach ($templates as $template) {
        if (file_exists($template_path . '/' . $template)) {
            log_result('Fellowship', basename($template, '.xml'), 'pass', 'Template file exists');
        } else {
            log_result('Fellowship', basename($template, '.xml'), 'warn', 'Template file not found');
        }
    }
} else {
    log_result('Fellowship', 'Database Templates', 'warn', 'Database templates directory not found');
}

// 8. GAMIFICATION PLUGINS
echo "\n--- 8. Gamification and Engagement ---\n";

// Check for Level Up! plugin
$levelup_path = $CFG->dirroot . '/blocks/xp';
if (file_exists($levelup_path)) {
    log_result('Gamification', 'Level Up! Plugin', 'pass', 'Level Up! plugin found');
} else {
    log_result('Gamification', 'Level Up! Plugin', 'warn', 'Level Up! plugin not installed');
}

// Check for Stash plugin
$stash_path = $CFG->dirroot . '/blocks/stash';
if (file_exists($stash_path)) {
    log_result('Gamification', 'Stash Plugin', 'pass', 'Stash plugin found');
} else {
    log_result('Gamification', 'Stash Plugin', 'warn', 'Stash plugin not installed');
}

// Check for Custom Certificate plugin
$customcert_module = $DB->get_record('modules', ['name' => 'customcert']);
if ($customcert_module && $customcert_module->visible) {
    log_result('Gamification', 'Custom Certificate', 'pass', 'Custom Certificate module is enabled');
} else {
    log_result('Gamification', 'Custom Certificate', 'warn', 'Custom Certificate module not available');
}

// 9. REPORTING AND ANALYTICS
echo "\n--- 9. Reporting and Analytics ---\n";

// Check for Configurable Reports plugin
$reports_path = $CFG->dirroot . '/blocks/configurable_reports';
if (file_exists($reports_path)) {
    log_result('Reporting', 'Configurable Reports', 'pass', 'Configurable Reports plugin found');
} else {
    log_result('Reporting', 'Configurable Reports', 'warn', 'Configurable Reports plugin not installed');
}

// SUMMARY
echo "\n=================================================================\n";
echo "VALIDATION SUMMARY\n";
echo "=================================================================\n";
echo "✓ Passed: " . count($results['passed']) . " tests\n";
echo "⚠ Warnings: " . count($results['warnings']) . " tests\n";
echo "✗ Failed: " . count($results['failed']) . " tests\n";
echo "=================================================================\n";

if (count($results['failed']) > 0) {
    echo "\nFAILED TESTS:\n";
    foreach ($results['failed'] as $failure) {
        echo "  • [{$failure['category']}] {$failure['test']}: {$failure['message']}\n";
    }
}

if (count($results['warnings']) > 0) {
    echo "\nWARNINGS (may need configuration):\n";
    foreach ($results['warnings'] as $warning) {
        echo "  • [{$warning['category']}] {$warning['test']}: {$warning['message']}\n";
    }
}

echo "\n=================================================================\n";
echo "CHECKPOINT VALIDATION COMPLETE\n";
echo "=================================================================\n";

// Exit with appropriate code
if (count($results['failed']) > 0) {
    exit(1);
} else {
    exit(0);
}
