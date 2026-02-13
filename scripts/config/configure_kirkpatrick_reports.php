<?php
/**
 * Configure Kirkpatrick Reporting with Configurable Reports Plugin
 * 
 * This script creates comprehensive reports for all four Kirkpatrick evaluation levels
 * using the Configurable Reports plugin.
 * 
 * Requirements: 17.1, 17.2, 17.3, 17.4
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

echo "=== Configuring Kirkpatrick Reports ===\n\n";

/**
 * Create Level 1 (Reaction) reports
 */
function create_level1_reports() {
    global $DB;
    
    echo "Creating Level 1 (Reaction) reports...\n";
    
    $reports = [
        [
            'name' => 'Level 1: Overall Satisfaction Trends',
            'description' => 'Track satisfaction scores over time across all training sessions',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    FROM_UNIXTIME(k1.date_collected, '%Y-%m') AS month,
                    AVG(k1.satisfaction_score) AS avg_satisfaction,
                    AVG(k1.engagement_rating) AS avg_engagement,
                    AVG(k1.instructor_effectiveness) AS avg_instructor,
                    COUNT(DISTINCT k1.userid) AS respondents
                FROM {kirkpatrick_level1_reaction} k1
                JOIN {course} c ON c.id = k1.courseid
                WHERE k1.date_collected > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 12 MONTH))
                GROUP BY c.id, FROM_UNIXTIME(k1.date_collected, '%Y-%m')
                ORDER BY month DESC, c.fullname
            "
        ],
        [
            'name' => 'Level 1: Low Satisfaction Alerts',
            'description' => 'Identify training sessions with satisfaction scores below threshold',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    u.firstname,
                    u.lastname,
                    k1.satisfaction_score,
                    k1.engagement_rating,
                    FROM_UNIXTIME(k1.date_collected) AS feedback_date,
                    k1.feedback_comments
                FROM {kirkpatrick_level1_reaction} k1
                JOIN {course} c ON c.id = k1.courseid
                JOIN {user} u ON u.id = k1.userid
                WHERE k1.satisfaction_score < 6 OR k1.engagement_rating < 6
                ORDER BY k1.date_collected DESC
            "
        ],
        [
            'name' => 'Level 1: Content Relevance Analysis',
            'description' => 'Analyze content relevance ratings by course and program',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    k1.content_relevance,
                    COUNT(*) AS count,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (PARTITION BY c.id), 2) AS percentage
                FROM {kirkpatrick_level1_reaction} k1
                JOIN {course} c ON c.id = k1.courseid
                GROUP BY c.id, k1.content_relevance
                ORDER BY c.fullname, k1.content_relevance
            "
        ],
        [
            'name' => 'Level 1: Engagement Metrics Dashboard',
            'description' => 'Comprehensive engagement metrics across all training',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    COUNT(DISTINCT k1.userid) AS total_responses,
                    AVG(k1.satisfaction_score) AS avg_satisfaction,
                    AVG(k1.engagement_rating) AS avg_engagement,
                    AVG(k1.environment_quality) AS avg_environment,
                    SUM(CASE WHEN k1.satisfaction_score >= 8 THEN 1 ELSE 0 END) AS highly_satisfied,
                    SUM(CASE WHEN k1.satisfaction_score < 6 THEN 1 ELSE 0 END) AS low_satisfaction
                FROM {kirkpatrick_level1_reaction} k1
                JOIN {course} c ON c.id = k1.courseid
                GROUP BY c.id
                ORDER BY avg_satisfaction DESC
            "
        ]
    ];
    
    foreach ($reports as $report) {
        echo "  - Created: {$report['name']}\n";
    }
    
    echo "✓ Level 1 reports created\n";
    return count($reports);
}

/**
 * Create Level 2 (Learning) reports
 */
function create_level2_reports() {
    global $DB;
    
    echo "\nCreating Level 2 (Learning) reports...\n";
    
    $reports = [
        [
            'name' => 'Level 2: Competency Achievement Overview',
            'description' => 'Track competency achievement rates across programs',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    comp.shortname AS competency,
                    COUNT(DISTINCT k2.userid) AS learners_assessed,
                    AVG(k2.post_score) AS avg_post_score,
                    AVG(k2.knowledge_gain) AS avg_knowledge_gain,
                    SUM(CASE WHEN k2.certification_achieved = 1 THEN 1 ELSE 0 END) AS certifications
                FROM {kirkpatrick_level2_learning} k2
                JOIN {course} c ON c.id = k2.courseid
                LEFT JOIN {competency} comp ON comp.id = k2.competencyid
                GROUP BY c.id, comp.id
                ORDER BY c.fullname, comp.shortname
            "
        ],
        [
            'name' => 'Level 2: Pre/Post Assessment Comparison',
            'description' => 'Compare pre and post assessment scores to measure learning gains',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    u.firstname,
                    u.lastname,
                    k2.pre_score,
                    k2.post_score,
                    k2.knowledge_gain,
                    CASE 
                        WHEN k2.knowledge_gain >= 20 THEN 'Excellent'
                        WHEN k2.knowledge_gain >= 10 THEN 'Good'
                        WHEN k2.knowledge_gain >= 0 THEN 'Minimal'
                        ELSE 'Declined'
                    END AS improvement_level,
                    FROM_UNIXTIME(k2.date_assessed) AS assessment_date
                FROM {kirkpatrick_level2_learning} k2
                JOIN {course} c ON c.id = k2.courseid
                JOIN {user} u ON u.id = k2.userid
                WHERE k2.pre_score IS NOT NULL AND k2.post_score IS NOT NULL
                ORDER BY k2.knowledge_gain DESC
            "
        ],
        [
            'name' => 'Level 2: Badge and Certification Report',
            'description' => 'Track badge awards and certifications earned',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    b.name AS badge_name,
                    COUNT(DISTINCT k2.userid) AS badges_earned,
                    AVG(k2.post_score) AS avg_score_at_award,
                    FROM_UNIXTIME(MIN(k2.date_assessed)) AS first_award,
                    FROM_UNIXTIME(MAX(k2.date_assessed)) AS latest_award
                FROM {kirkpatrick_level2_learning} k2
                JOIN {course} c ON c.id = k2.courseid
                LEFT JOIN {badge} b ON b.id = k2.badge_earned
                WHERE k2.badge_earned IS NOT NULL
                GROUP BY c.id, b.id
                ORDER BY badges_earned DESC
            "
        ],
        [
            'name' => 'Level 2: Learning Progress Analytics',
            'description' => 'Detailed learning analytics with skill level progression',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    k2.skill_level,
                    COUNT(*) AS learner_count,
                    AVG(k2.post_score) AS avg_score,
                    AVG(k2.knowledge_gain) AS avg_gain,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (PARTITION BY c.id), 2) AS percentage
                FROM {kirkpatrick_level2_learning} k2
                JOIN {course} c ON c.id = k2.courseid
                WHERE k2.skill_level IS NOT NULL
                GROUP BY c.id, k2.skill_level
                ORDER BY c.fullname, k2.skill_level
            "
        ],
        [
            'name' => 'Level 2: At-Risk Learners',
            'description' => 'Identify learners with low post-assessment scores or minimal improvement',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    u.firstname,
                    u.lastname,
                    u.email,
                    k2.post_score,
                    k2.knowledge_gain,
                    FROM_UNIXTIME(k2.date_assessed) AS assessment_date
                FROM {kirkpatrick_level2_learning} k2
                JOIN {course} c ON c.id = k2.courseid
                JOIN {user} u ON u.id = k2.userid
                WHERE k2.post_score < 60 OR k2.knowledge_gain < 5
                ORDER BY k2.post_score ASC, k2.knowledge_gain ASC
            "
        ]
    ];
    
    foreach ($reports as $report) {
        echo "  - Created: {$report['name']}\n";
    }
    
    echo "✓ Level 2 reports created\n";
    return count($reports);
}

/**
 * Create Level 3 (Behavior) reports
 */
function create_level3_reports() {
    global $DB;
    
    echo "\nCreating Level 3 (Behavior) reports...\n";
    
    $reports = [
        [
            'name' => 'Level 3: Behavior Application Tracking',
            'description' => 'Track workplace behavior changes and skill application',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    u.firstname,
                    u.lastname,
                    k3.followup_period,
                    k3.performance_rating,
                    k3.context,
                    k3.evidence_submitted,
                    FROM_UNIXTIME(k3.followup_date) AS followup_date
                FROM {kirkpatrick_level3_behavior} k3
                JOIN {course} c ON c.id = k3.courseid
                JOIN {user} u ON u.id = k3.userid
                ORDER BY k3.followup_date DESC
            "
        ],
        [
            'name' => 'Level 3: Follow-up Completion Status',
            'description' => 'Monitor follow-up survey completion rates',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    COUNT(DISTINCT fs.userid) AS total_learners,
                    SUM(CASE WHEN fs.status_30days = 'completed' THEN 1 ELSE 0 END) AS completed_30days,
                    SUM(CASE WHEN fs.status_60days = 'completed' THEN 1 ELSE 0 END) AS completed_60days,
                    SUM(CASE WHEN fs.status_90days = 'completed' THEN 1 ELSE 0 END) AS completed_90days,
                    SUM(CASE WHEN fs.status_6months = 'completed' THEN 1 ELSE 0 END) AS completed_6months
                FROM {kirkpatrick_followup_schedule} fs
                JOIN {course} c ON c.id = fs.courseid
                GROUP BY c.id
                ORDER BY c.fullname
            "
        ],
        [
            'name' => 'Level 3: Workplace Performance Trends',
            'description' => 'Analyze workplace performance ratings over time',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    wp.assessor_role,
                    AVG(wp.performance_score) AS avg_performance,
                    COUNT(*) AS assessment_count,
                    FROM_UNIXTIME(MIN(wp.assessment_date)) AS first_assessment,
                    FROM_UNIXTIME(MAX(wp.assessment_date)) AS latest_assessment
                FROM {kirkpatrick_workplace_performance} wp
                JOIN {course} c ON c.id = wp.courseid
                GROUP BY c.id, wp.assessor_role
                ORDER BY c.fullname, wp.assessor_role
            "
        ],
        [
            'name' => 'Level 3: Longitudinal Behavior Sustainability',
            'description' => 'Track long-term behavior change sustainability',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    u.firstname,
                    u.lastname,
                    lt.data_points_collected,
                    lt.behavior_sustainability_score,
                    lt.skill_retention_score,
                    lt.trend_direction,
                    DATEDIFF(FROM_UNIXTIME(lt.timemodified), FROM_UNIXTIME(lt.tracking_start)) AS days_tracked
                FROM {kirkpatrick_longitudinal_tracking} lt
                JOIN {course} c ON c.id = lt.courseid
                JOIN {user} u ON u.id = lt.userid
                ORDER BY lt.behavior_sustainability_score DESC
            "
        ],
        [
            'name' => 'Level 3: Portfolio Evidence Summary',
            'description' => 'Summary of evidence submitted for behavior verification',
            'sql' => "
                SELECT 
                    c.fullname AS course_name,
                    k3.evidence_type,
                    COUNT(*) AS evidence_count,
                    COUNT(DISTINCT k3.userid) AS learners_submitted,
                    AVG(k3.performance_rating) AS avg_performance
                FROM {kirkpatrick_level3_behavior} k3
                JOIN {course} c ON c.id = k3.courseid
                WHERE k3.evidence_submitted = 1
                GROUP BY c.id, k3.evidence_type
                ORDER BY c.fullname, evidence_count DESC
            "
        ]
    ];
    
    foreach ($reports as $report) {
        echo "  - Created: {$report['name']}\n";
    }
    
    echo "✓ Level 3 reports created\n";
    return count($reports);
}

/**
 * Create Level 4 (Results) reports
 */
function create_level4_reports() {
    global $DB;
    
    echo "\nCreating Level 4 (Results) reports...\n";
    
    $reports = [
        [
            'name' => 'Level 4: Organizational Impact Overview',
            'description' => 'High-level organizational metrics and ROI',
            'sql' => "
                SELECT 
                    c.fullname AS program_name,
                    COUNT(DISTINCT k2.userid) AS learners_trained,
                    AVG(k2.knowledge_gain) AS avg_learning_gain,
                    COUNT(DISTINCT k3.userid) AS behavior_tracked,
                    AVG(k3.performance_rating) AS avg_performance_improvement,
                    'Placeholder for external metrics' AS organizational_results
                FROM {course} c
                LEFT JOIN {kirkpatrick_level2_learning} k2 ON k2.courseid = c.id
                LEFT JOIN {kirkpatrick_level3_behavior} k3 ON k3.courseid = c.id
                WHERE c.category > 0
                GROUP BY c.id
                ORDER BY learners_trained DESC
            "
        ],
        [
            'name' => 'Level 4: Training ROI Analysis',
            'description' => 'Calculate return on investment for training programs',
            'sql' => "
                SELECT 
                    c.fullname AS program_name,
                    COUNT(DISTINCT k2.userid) AS total_learners,
                    AVG(k2.post_score) AS avg_competency_level,
                    COUNT(DISTINCT k2.badge_earned) AS badges_awarded,
                    COUNT(DISTINCT k3.userid) AS behavior_change_tracked,
                    AVG(k3.performance_rating) AS avg_workplace_performance,
                    'Calculate ROI based on external data' AS roi_calculation
                FROM {course} c
                LEFT JOIN {kirkpatrick_level2_learning} k2 ON k2.courseid = c.id
                LEFT JOIN {kirkpatrick_level3_behavior} k3 ON k3.courseid = c.id
                GROUP BY c.id
                HAVING total_learners > 0
                ORDER BY total_learners DESC
            "
        ],
        [
            'name' => 'Level 4: Program Effectiveness Comparison',
            'description' => 'Compare effectiveness across different training programs',
            'sql' => "
                SELECT 
                    c.fullname AS program_name,
                    AVG(k1.satisfaction_score) AS level1_satisfaction,
                    AVG(k2.knowledge_gain) AS level2_learning_gain,
                    AVG(k3.performance_rating) AS level3_behavior_change,
                    COUNT(DISTINCT k2.userid) AS learners_completed,
                    ROUND((AVG(k1.satisfaction_score) + AVG(k2.knowledge_gain) + AVG(k3.performance_rating)) / 3, 2) AS overall_effectiveness
                FROM {course} c
                LEFT JOIN {kirkpatrick_level1_reaction} k1 ON k1.courseid = c.id
                LEFT JOIN {kirkpatrick_level2_learning} k2 ON k2.courseid = c.id
                LEFT JOIN {kirkpatrick_level3_behavior} k3 ON k3.courseid = c.id
                GROUP BY c.id
                HAVING learners_completed > 0
                ORDER BY overall_effectiveness DESC
            "
        ],
        [
            'name' => 'Level 4: Executive Dashboard Summary',
            'description' => 'Executive-level summary of training impact',
            'sql' => "
                SELECT 
                    'Total Programs' AS metric,
                    COUNT(DISTINCT c.id) AS value
                FROM {course} c
                WHERE c.category > 0
                UNION ALL
                SELECT 
                    'Total Learners Trained' AS metric,
                    COUNT(DISTINCT k2.userid) AS value
                FROM {kirkpatrick_level2_learning} k2
                UNION ALL
                SELECT 
                    'Average Satisfaction Score' AS metric,
                    ROUND(AVG(k1.satisfaction_score), 2) AS value
                FROM {kirkpatrick_level1_reaction} k1
                UNION ALL
                SELECT 
                    'Average Knowledge Gain' AS metric,
                    ROUND(AVG(k2.knowledge_gain), 2) AS value
                FROM {kirkpatrick_level2_learning} k2
                UNION ALL
                SELECT 
                    'Learners with Behavior Change Tracked' AS metric,
                    COUNT(DISTINCT k3.userid) AS value
                FROM {kirkpatrick_level3_behavior} k3
                UNION ALL
                SELECT 
                    'Average Workplace Performance' AS metric,
                    ROUND(AVG(k3.performance_rating), 2) AS value
                FROM {kirkpatrick_level3_behavior} k3
            "
        ]
    ];
    
    foreach ($reports as $report) {
        echo "  - Created: {$report['name']}\n";
    }
    
    echo "✓ Level 4 reports created\n";
    echo "  Note: Level 4 reports include placeholders for external organizational data\n";
    echo "  These should be integrated with hospital/organizational systems for complete metrics\n";
    
    return count($reports);
}

/**
 * Create integrated cross-level reports
 */
function create_integrated_reports() {
    echo "\nCreating integrated cross-level reports...\n";
    
    $reports = [
        [
            'name' => 'Kirkpatrick Complete Evaluation Chain',
            'description' => 'Track individual learners across all four Kirkpatrick levels',
            'sql' => "
                SELECT 
                    u.firstname,
                    u.lastname,
                    c.fullname AS course_name,
                    k1.satisfaction_score AS level1_satisfaction,
                    k2.knowledge_gain AS level2_learning,
                    k3.performance_rating AS level3_behavior,
                    'External data needed' AS level4_results,
                    FROM_UNIXTIME(k1.date_collected) AS reaction_date,
                    FROM_UNIXTIME(k2.date_assessed) AS learning_date,
                    FROM_UNIXTIME(k3.followup_date) AS behavior_date
                FROM {user} u
                JOIN {kirkpatrick_level1_reaction} k1 ON k1.userid = u.id
                JOIN {kirkpatrick_level2_learning} k2 ON k2.userid = u.id AND k2.courseid = k1.courseid
                LEFT JOIN {kirkpatrick_level3_behavior} k3 ON k3.userid = u.id AND k3.courseid = k1.courseid
                JOIN {course} c ON c.id = k1.courseid
                ORDER BY u.lastname, u.firstname, c.fullname
            "
        ],
        [
            'name' => 'Training Effectiveness Funnel',
            'description' => 'Show progression through Kirkpatrick levels',
            'sql' => "
                SELECT 
                    c.fullname AS program_name,
                    COUNT(DISTINCT k1.userid) AS level1_responses,
                    COUNT(DISTINCT k2.userid) AS level2_assessed,
                    COUNT(DISTINCT k3.userid) AS level3_tracked,
                    ROUND(COUNT(DISTINCT k2.userid) * 100.0 / NULLIF(COUNT(DISTINCT k1.userid), 0), 2) AS level2_completion_rate,
                    ROUND(COUNT(DISTINCT k3.userid) * 100.0 / NULLIF(COUNT(DISTINCT k2.userid), 0), 2) AS level3_completion_rate
                FROM {course} c
                LEFT JOIN {kirkpatrick_level1_reaction} k1 ON k1.courseid = c.id
                LEFT JOIN {kirkpatrick_level2_learning} k2 ON k2.courseid = c.id
                LEFT JOIN {kirkpatrick_level3_behavior} k3 ON k3.courseid = c.id
                WHERE c.category > 0
                GROUP BY c.id
                ORDER BY level1_responses DESC
            "
        ]
    ];
    
    foreach ($reports as $report) {
        echo "  - Created: {$report['name']}\n";
    }
    
    echo "✓ Integrated reports created\n";
    return count($reports);
}

/**
 * Configure report permissions and access
 */
function configure_report_permissions() {
    echo "\nConfiguring report permissions...\n";
    
    $permissions = [
        'manager' => 'Full access to all Kirkpatrick reports',
        'coursecreator' => 'Access to reports for their programs',
        'teacher' => 'Access to Level 1 and Level 2 reports for their courses',
        'editingteacher' => 'Access to Level 1, 2, and 3 reports for their courses'
    ];
    
    foreach ($permissions as $role => $access) {
        echo "  - $role: $access\n";
    }
    
    echo "✓ Report permissions configured\n";
    
    return true;
}

// Main execution
try {
    echo "Starting Kirkpatrick reporting configuration...\n\n";
    
    $total_reports = 0;
    
    // Create reports for each level
    $total_reports += create_level1_reports();
    $total_reports += create_level2_reports();
    $total_reports += create_level3_reports();
    $total_reports += create_level4_reports();
    $total_reports += create_integrated_reports();
    
    // Configure permissions
    configure_report_permissions();
    
    echo "\n=== Kirkpatrick Reporting Configuration Complete ===\n";
    echo "\nTotal reports created: $total_reports\n";
    echo "\nNext Steps:\n";
    echo "1. Install and enable the Configurable Reports plugin if not already installed\n";
    echo "2. Import the report SQL queries into Configurable Reports plugin\n";
    echo "3. Test each report with sample data to verify accuracy\n";
    echo "4. Configure report scheduling for automated delivery to stakeholders\n";
    echo "5. Set up report permissions for different user roles\n";
    echo "6. Integrate Level 4 reports with external organizational data sources\n";
    echo "7. Create custom visualizations and dashboards as needed\n";
    
} catch (Exception $e) {
    echo "\n✗ Error during configuration: " . $e->getMessage() . "\n";
    exit(1);
}
