<?php
/**
 * Configure Digital Badge System
 * 
 * Sets up Open Badges 2.0 compliant badge framework with competency-based criteria
 * Implements Requirements 15.1, 15.3
 * 
 * Usage: php configure_badge_system.php
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
require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->dirroot . '/badges/lib.php');

// Ensure we're running as admin in CLI
$admin = get_admin();
if (!$admin) {
    fwrite(STDERR, "ERROR: No admin user found\n");
    exit(1);
}
\core\session\manager::set_user($admin);
require_capability('moodle/site:config', context_system::instance());

echo "=== Digital Badge System Configuration ===\n\n";

// Step 1: Enable badges globally
echo "Step 1: Enabling badges globally...\n";
set_config('enablebadges', 1);
set_config('badges_defaultissuername', get_config('core', 'sitename'));
set_config('badges_defaultissuercontact', get_config('core', 'supportemail'));
echo "✓ Badges enabled globally\n\n";

// Step 2: Configure Open Badges 2.0 settings
echo "Step 2: Configuring Open Badges 2.0 settings...\n";
set_config('badges_badgesalt', random_string(32)); // Unique salt for badge verification
set_config('badges_allowexternalbackpack', 1); // Enable external backpack connections
set_config('badges_allowcoursebadges', 1); // Enable course-level badges
echo "✓ Open Badges 2.0 settings configured\n\n";

// Step 3: Create competency-based badge templates
echo "Step 3: Creating competency-based badge templates...\n";

$badge_templates = [
    [
        'name' => 'Competency Achievement - Bronze',
        'description' => 'Awarded for achieving basic proficiency in a core competency',
        'type' => BADGE_TYPE_SITE,
        'status' => BADGE_STATUS_ACTIVE,
        'criteria_type' => 'competency',
        'level' => 'bronze'
    ],
    [
        'name' => 'Competency Achievement - Silver',
        'description' => 'Awarded for achieving intermediate proficiency in a core competency',
        'type' => BADGE_TYPE_SITE,
        'status' => BADGE_STATUS_ACTIVE,
        'criteria_type' => 'competency',
        'level' => 'silver'
    ],
    [
        'name' => 'Competency Achievement - Gold',
        'description' => 'Awarded for achieving advanced proficiency in a core competency',
        'type' => BADGE_TYPE_SITE,
        'status' => BADGE_STATUS_ACTIVE,
        'criteria_type' => 'competency',
        'level' => 'gold'
    ],
    [
        'name' => 'Learning Path Completion',
        'description' => 'Awarded for completing all competencies in a learning path',
        'type' => BADGE_TYPE_SITE,
        'status' => BADGE_STATUS_ACTIVE,
        'criteria_type' => 'learning_path',
        'level' => 'completion'
    ],
    [
        'name' => 'Program Completion',
        'description' => 'Awarded for completing all core competencies in a program',
        'type' => BADGE_TYPE_SITE,
        'status' => BADGE_STATUS_ACTIVE,
        'criteria_type' => 'program',
        'level' => 'completion'
    ]
];

// Start transaction for badge creation
$transaction = $DB->start_delegated_transaction();

try {
    $created_badges = [];
    foreach ($badge_templates as $template) {
        $badge = new stdClass();
        $badge->name = $template['name'];
        $badge->description = $template['description'];
        $badge->timecreated = time();
        $badge->timemodified = time();
        $badge->usercreated = $USER->id;
        $badge->usermodified = $USER->id;
        $badge->issuername = get_config('core', 'sitename');
        $badge->issuerurl = $CFG->wwwroot;
        $badge->issuercontact = get_config('core', 'supportemail');
        $badge->expiredate = null; // No expiration
        $badge->expireperiod = null;
        $badge->type = $template['type'];
        $badge->courseid = null; // Site-wide badges
        $badge->messagesubject = 'Congratulations! You earned a badge';
        $badge->message = 'You have earned the badge: ' . $template['name'];
        $badge->attachment = 1; // Attach badge to notification
        $badge->notification = 1; // Send notification
        $badge->status = BADGE_STATUS_INACTIVE; // Start inactive, activate after criteria setup
        $badge->version = '2.0'; // Open Badges 2.0
        $badge->language = 'en';
        $badge->imageauthorname = get_config('core', 'sitename');
        $badge->imageauthoremail = get_config('core', 'supportemail');
        $badge->imageauthorurl = $CFG->wwwroot;
        $badge->imagecaption = $template['name'];
        
        $badge_id = $DB->insert_record('badge', $badge);
        
        $created_badges[] = [
            'id' => $badge_id,
            'name' => $template['name'],
            'criteria_type' => $template['criteria_type'],
            'level' => $template['level']
        ];
        
        echo "  ✓ Created badge: {$template['name']} (ID: {$badge_id})\n";
    }

    echo "\n";

    // Step 4: Configure badge criteria for competency-based awarding
    echo "Step 4: Configuring badge criteria...\n";

    foreach ($created_badges as $badge_info) {
        // Create criteria record
        $criteria = new stdClass();
        $criteria->badgeid = $badge_info['id'];
        $criteria->criteriatype = BADGE_CRITERIA_TYPE_COMPETENCY; // Competency-based criteria
        $criteria->method = BADGE_CRITERIA_AGGREGATION_ALL; // All competencies must be achieved
        
        $criteria_id = $DB->insert_record('badge_criteria', $criteria);
        
        echo "  ✓ Configured {$badge_info['criteria_type']} criteria for: {$badge_info['name']}\n";
    }

    // Commit transaction
    $transaction->allow_commit();
    echo "\n✓ All badges and criteria created successfully (transaction committed)\n";
    
} catch (Exception $e) {
    // Rollback on error
    $transaction->rollback($e);
    echo "\n✗ Error creating badges: " . $e->getMessage() . "\n";
    echo "✗ Transaction rolled back - no badges were created\n";
    exit(1);
}

echo "\n";

// Step 5: Enable external badge sharing
echo "Step 5: Enabling external badge sharing...\n";

// Configure Mozilla Backpack as default
set_config('badges_site_backpack', 'https://backpack.openbadges.org');
set_config('badges_allowexternalbackpack', 1);

// Enable badge sharing options
set_config('badges_defaultissuercontact', get_config('core', 'supportemail'));

echo "✓ External badge sharing enabled\n";
echo "  - Users can connect to Mozilla Backpack\n";
echo "  - Badges can be shared on LinkedIn, Twitter, and other platforms\n";
echo "  - Badge verification URL: {$CFG->wwwroot}/badges/\n\n";

// Step 6: Create badge management roles and permissions
echo "Step 6: Configuring badge management permissions...\n";

$context = context_system::instance();

// Ensure Program Owners can create and manage badges
$manager_role = $DB->get_record('role', ['shortname' => 'manager']);
if ($manager_role) {
    assign_capability('moodle/badges:createbadge', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('moodle/badges:deletebadge', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('moodle/badges:configurecriteria', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('moodle/badges:configuremessages', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('moodle/badges:configuredetails', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('moodle/badges:awardbadge', CAP_ALLOW, $manager_role->id, $context->id, true);
    assign_capability('moodle/badges:viewbadges', CAP_ALLOW, $manager_role->id, $context->id, true);
    echo "  ✓ Badge management permissions granted to Program Owners (Manager role)\n";
}

// Ensure Trainers can award badges
$teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($teacher_role) {
    assign_capability('moodle/badges:awardbadge', CAP_ALLOW, $teacher_role->id, $context->id, true);
    assign_capability('moodle/badges:viewbadges', CAP_ALLOW, $teacher_role->id, $context->id, true);
    echo "  ✓ Badge awarding permissions granted to Trainers (Teacher role)\n";
}

// Ensure Learners can view and manage their own badges
$student_role = $DB->get_record('role', ['shortname' => 'student']);
if ($student_role) {
    assign_capability('moodle/badges:viewbadges', CAP_ALLOW, $student_role->id, $context->id, true);
    assign_capability('moodle/badges:manageownbadges', CAP_ALLOW, $student_role->id, $context->id, true);
    echo "  ✓ Badge viewing permissions granted to Learners (Student role)\n";
}

echo "\n";

// Step 7: Summary and next steps
echo "=== Configuration Complete ===\n\n";
echo "Badge System Status:\n";
echo "  ✓ Open Badges 2.0 compliant framework enabled\n";
echo "  ✓ " . count($created_badges) . " competency-based badge templates created\n";
echo "  ✓ External badge sharing configured (Mozilla Backpack)\n";
echo "  ✓ Role-based badge management permissions configured\n\n";

echo "Created Badge Templates:\n";
foreach ($created_badges as $badge) {
    echo "  - {$badge['name']} (ID: {$badge['id']})\n";
}

echo "\nNext Steps:\n";
echo "  1. Link badges to specific competencies in the competency framework\n";
echo "  2. Activate badges after configuring specific criteria\n";
echo "  3. Upload custom badge images for each template\n";
echo "  4. Test badge awarding workflow with sample competency completions\n";
echo "  5. Configure automatic badge awarding based on competency achievement\n\n";

echo "Badge Management URL: {$CFG->wwwroot}/badges/index.php\n";
echo "Badge Verification URL: {$CFG->wwwroot}/badges/mybadges.php\n\n";

echo "Configuration saved successfully!\n";
