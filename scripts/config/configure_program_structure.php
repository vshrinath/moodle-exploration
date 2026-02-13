<?php
/**
 * Configure Program Structure using Course Categories
 * 
 * This script implements program management using Moodle's course categories
 * with metadata storage for program versioning and ownership.
 * 
 * Requirements: 1.1, 1.2, 1.3
 * Task: 4.1 - Implement program structure using course categories
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

global $DB, $CFG;

// Ensure we're running as admin in CLI
$admin = get_admin();
if (!$admin) {
    fwrite(STDERR, "ERROR: No admin user found\n");
    exit(1);
}
\core\session\manager::set_user($admin);

echo "=== Program Structure Configuration ===\n\n";

/**
 * Create custom database table for program metadata
 */
function create_program_metadata_table() {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    // Define table for program metadata
    $table = new xmldb_table('local_program_metadata');
    
    // Add fields
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('program_version', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, '1.0');
    $table->add_field('outcomes', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('target_audience', XMLDB_TYPE_TEXT, null, null, null, null, null);
    $table->add_field('owner_userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('modified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    
    // Add keys
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
    $table->add_key('owner_userid_fk', XMLDB_KEY_FOREIGN, ['owner_userid'], 'user', ['id']);
    
    // Add indexes
    $table->add_index('version_idx', XMLDB_INDEX_NOTUNIQUE, ['program_version']);
    
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        echo "✓ Created local_program_metadata table\n";
    } else {
        echo "✓ Table local_program_metadata already exists\n";
    }
    
    return true;
}

/**
 * Create a program template course
 */
function create_program_template($shortname, $fullname, $categoryid, $owner_userid, $outcomes = '', $target_audience = '') {
    global $DB;
    
    // Check if course already exists
    $existing = $DB->get_record('course', ['shortname' => $shortname]);
    if ($existing) {
        echo "✓ Program template '$shortname' already exists (ID: {$existing->id})\n";
        return $existing->id;
    }
    
    // Create course
    $course = new stdClass();
    $course->category = $categoryid;
    $course->shortname = $shortname;
    $course->fullname = $fullname;
    $course->summary = 'Program template for competency-based learning';
    $course->summaryformat = FORMAT_HTML;
    $course->format = 'topics';
    $course->visible = 1;
    $course->enablecompletion = 1;
    $course->startdate = time();
    $course->timecreated = time();
    $course->timemodified = time();
    
    $courseid = $DB->insert_record('course', $course);
    
    // Create program metadata
    $metadata = new stdClass();
    $metadata->courseid = $courseid;
    $metadata->program_version = '1.0';
    $metadata->outcomes = $outcomes;
    $metadata->target_audience = $target_audience;
    $metadata->owner_userid = $owner_userid;
    $metadata->created = time();
    $metadata->modified = time();
    
    $DB->insert_record('local_program_metadata', $metadata);
    
    echo "✓ Created program template '$shortname' (ID: $courseid)\n";
    
    return $courseid;
}

/**
 * Configure program owner role
 */
function configure_program_owner_role() {
    global $DB;
    
    // Check if role exists
    $role = $DB->get_record('role', ['shortname' => 'programowner']);
    
    if (!$role) {
        // Create program owner role
        $roleid = create_role(
            'Program Owner',
            'programowner',
            'Learning architect responsible for defining program structure and competencies',
            'manager'
        );
        echo "✓ Created Program Owner role (ID: $roleid)\n";
    } else {
        $roleid = $role->id;
        echo "✓ Program Owner role already exists (ID: $roleid)\n";
    }
    
    // Set capabilities for program owner role
    $context = context_system::instance();
    
    $capabilities = [
        'moodle/course:create' => CAP_ALLOW,
        'moodle/course:update' => CAP_ALLOW,
        'moodle/course:view' => CAP_ALLOW,
        'moodle/course:manageactivities' => CAP_ALLOW,
        'moodle/competency:competencymanage' => CAP_ALLOW,
        'moodle/competency:competencyview' => CAP_ALLOW,
        'moodle/competency:planmanage' => CAP_ALLOW,
        'moodle/competency:planview' => CAP_ALLOW,
        'moodle/cohort:manage' => CAP_ALLOW,
        'moodle/cohort:view' => CAP_ALLOW,
        'moodle/backup:backupcourse' => CAP_ALLOW,
        'moodle/restore:restorecourse' => CAP_ALLOW,
    ];
    
    foreach ($capabilities as $capability => $permission) {
        if (assign_capability($capability, $permission, $roleid, $context->id, true)) {
            echo "  ✓ Assigned capability: $capability\n";
        }
    }
    
    return $roleid;
}

/**
 * Create program category structure
 */
function create_program_categories() {
    global $DB;
    
    // Create main programs category
    $parent = $DB->get_record('course_categories', ['name' => 'Programs']);
    
    if (!$parent) {
        $category = new stdClass();
        $category->name = 'Programs';
        $category->description = 'Competency-based learning programs';
        $category->descriptionformat = FORMAT_HTML;
        $category->parent = 0;
        $category->sortorder = 999;
        $category->visible = 1;
        $category->timemodified = time();
        
        $parentid = $DB->insert_record('course_categories', $category);
        echo "✓ Created 'Programs' category (ID: $parentid)\n";
    } else {
        $parentid = $parent->id;
        echo "✓ 'Programs' category already exists (ID: $parentid)\n";
    }
    
    // Create subcategories for different program types
    $subcategories = [
        'Technical Programs' => 'Technical skill development programs',
        'Management Programs' => 'Leadership and management programs',
        'Fellowship Programs' => 'Medical fellowship and specialty training programs',
        'Archived Programs' => 'Archived and historical programs'
    ];
    
    foreach ($subcategories as $name => $description) {
        $existing = $DB->get_record('course_categories', ['name' => $name, 'parent' => $parentid]);
        
        if (!$existing) {
            $category = new stdClass();
            $category->name = $name;
            $category->description = $description;
            $category->descriptionformat = FORMAT_HTML;
            $category->parent = $parentid;
            $category->sortorder = 999;
            $category->visible = 1;
            $category->timemodified = time();
            
            $catid = $DB->insert_record('course_categories', $category);
            echo "  ✓ Created '$name' subcategory (ID: $catid)\n";
        } else {
            echo "  ✓ '$name' subcategory already exists (ID: {$existing->id})\n";
        }
    }
    
    return $parentid;
}

/**
 * Create version of an existing program
 */
function create_program_version($original_courseid, $new_version) {
    global $DB;
    
    // Get original course
    $original = $DB->get_record('course', ['id' => $original_courseid], '*', MUST_EXIST);
    $original_metadata = $DB->get_record('local_program_metadata', ['courseid' => $original_courseid]);
    
    // Create new course as copy
    $new_course = new stdClass();
    $new_course->category = $original->category;
    $new_course->shortname = $original->shortname . '_v' . str_replace('.', '_', $new_version);
    $new_course->fullname = $original->fullname . ' (Version ' . $new_version . ')';
    $new_course->summary = $original->summary;
    $new_course->summaryformat = $original->summaryformat;
    $new_course->format = $original->format;
    $new_course->visible = 1;
    $new_course->enablecompletion = $original->enablecompletion;
    $new_course->startdate = time();
    $new_course->timecreated = time();
    $new_course->timemodified = time();
    
    $new_courseid = $DB->insert_record('course', $new_course);
    
    // Create metadata for new version
    if ($original_metadata) {
        $new_metadata = clone $original_metadata;
        unset($new_metadata->id);
        $new_metadata->courseid = $new_courseid;
        $new_metadata->program_version = $new_version;
        $new_metadata->created = time();
        $new_metadata->modified = time();
        
        $DB->insert_record('local_program_metadata', $new_metadata);
    }
    
    echo "✓ Created program version $new_version (ID: $new_courseid) from original (ID: $original_courseid)\n";
    
    return $new_courseid;
}

// Main execution
try {
    echo "Step 1: Creating program metadata table...\n";
    create_program_metadata_table();
    echo "\n";
    
    echo "Step 2: Creating program category structure...\n";
    $programs_category = create_program_categories();
    echo "\n";
    
    echo "Step 3: Configuring Program Owner role...\n";
    $owner_role = configure_program_owner_role();
    echo "\n";
    
    echo "Step 4: Creating sample program templates...\n";
    
    // Get admin user as default owner
    $admin = get_admin();
    
    // Get category IDs
    $technical_cat = $DB->get_record('course_categories', ['name' => 'Technical Programs']);
    $fellowship_cat = $DB->get_record('course_categories', ['name' => 'Fellowship Programs']);
    
    if ($technical_cat && $fellowship_cat) {
        // Create sample technical program
        create_program_template(
            'TECH_PROG_001',
            'Technical Skills Development Program',
            $technical_cat->id,
            $admin->id,
            'Develop core technical competencies in software development',
            'Software developers and engineers'
        );
        
        // Create sample fellowship program
        create_program_template(
            'FELLOW_OPHTHAL_001',
            'Ophthalmology Fellowship Program',
            $fellowship_cat->id,
            $admin->id,
            'Comprehensive ophthalmology training with subspecialty focus',
            'Medical graduates pursuing ophthalmology specialization'
        );
    }
    
    echo "\n";
    echo "=== Program Structure Configuration Complete ===\n";
    echo "\nNext steps:\n";
    echo "1. Assign users to Program Owner role for program management\n";
    echo "2. Create program-specific competency frameworks\n";
    echo "3. Use backup/restore for program versioning\n";
    echo "4. Configure cohorts for learner group management\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
