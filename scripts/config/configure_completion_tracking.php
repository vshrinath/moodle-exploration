<?php
/**
 * Enable completion tracking site-wide and set smart defaults on all courses.
 *
 * What this script does:
 *   1. Enables the site-level completion tracking toggle (enablecompletion).
 *   2. Enables completion tracking on every course that has it disabled.
 *   3. Sets default activity completion rules per activity type:
 *        - quiz     → "Receive a grade" (automatic)
 *        - resource  → "View the activity" (automatic)
 *        - url       → "View the activity" (automatic)
 *        - assign    → "Submit for grading" (automatic)
 *        - All others → "Student can manually mark as done"
 *   4. Updates existing activities that have no completion rule set.
 *
 * Safe to run multiple times (idempotent).
 *
 * Usage:
 *   php configure_completion_tracking.php                    # Dry run (shows what would change)
 *   php configure_completion_tracking.php --apply            # Apply changes
 *   php configure_completion_tracking.php --apply --verbose  # Apply with detail
 *
 * @package    scripts
 */

define('CLI_SCRIPT', true);

// Find Moodle config.
$configlocations = [
    __DIR__ . '/../../config.php',                        // Local dev.
    '/var/www/html/config.php',                           // Docker container.
    '/bitnami/moodle/config.php',                         // Bitnami container.
];

$configfound = false;
foreach ($configlocations as $configpath) {
    if (file_exists($configpath)) {
        require_once($configpath);
        $configfound = true;
        break;
    }
}

if (!$configfound) {
    echo "ERROR: Could not find Moodle config.php\n";
    exit(1);
}

require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/completionlib.php');

// Parse CLI options.
list($options, $unrecognised) = cli_get_params([
    'apply' => false,
    'verbose' => false,
    'help' => false,
], [
    'a' => 'apply',
    'v' => 'verbose',
    'h' => 'help',
]);

if ($options['help']) {
    echo "Enable completion tracking site-wide and set smart defaults.\n\n";
    echo "Usage: php configure_completion_tracking.php [--apply] [--verbose]\n\n";
    echo "  --apply    Apply changes (default is dry run)\n";
    echo "  --verbose  Show detailed output\n";
    exit(0);
}

$dryrun = !$options['apply'];
$verbose = $options['verbose'];
$prefix = $dryrun ? '[DRY RUN] ' : '';

echo "=== Completion Tracking Configuration ===\n";
echo $dryrun ? "Mode: DRY RUN (use --apply to make changes)\n\n" : "Mode: APPLYING CHANGES\n\n";

// ─── Step 1: Site-level toggle ───────────────────────────────────

echo "--- Step 1: Site-level completion tracking ---\n";
$currentvalue = get_config('', 'enablecompletion');
if ($currentvalue) {
    echo "  ✓ Already enabled.\n";
} else {
    echo "  {$prefix}Enabling site-level completion tracking.\n";
    if (!$dryrun) {
        set_config('enablecompletion', 1);
    }
}

// ─── Step 2: Per-course enablement ───────────────────────────────

echo "\n--- Step 2: Enable completion on all courses ---\n";
$courses = $DB->get_records_select('course', 'id > 1 AND enablecompletion = 0');
$coursecount = count($courses);
echo "  Found {$coursecount} course(s) with completion disabled.\n";

foreach ($courses as $course) {
    echo "  {$prefix}Enabling on: {$course->fullname} (id={$course->id})\n";
    if (!$dryrun) {
        $DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);
    }
}

// ─── Step 3: Default completion rules per activity type ──────────

// Define smart defaults by module type.
// COMPLETION_TRACKING_NONE = 0, COMPLETION_TRACKING_MANUAL = 1, COMPLETION_TRACKING_AUTOMATIC = 2
$defaults = [
    'quiz' => [
        'completion' => 2,          // Automatic.
        'completionusegrade' => 1,  // Receive a grade.
        'completionview' => 0,
    ],
    'resource' => [
        'completion' => 2,          // Automatic.
        'completionview' => 1,      // Must view.
        'completionusegrade' => 0,
    ],
    'url' => [
        'completion' => 2,          // Automatic.
        'completionview' => 1,      // Must view.
        'completionusegrade' => 0,
    ],
    'assign' => [
        'completion' => 2,          // Automatic.
        'completionsubmit' => 1,    // Must submit.
        'completionview' => 0,
        'completionusegrade' => 0,
    ],
    // Fallback for all other types.
    '_default' => [
        'completion' => 1,          // Manual (student marks done).
    ],
];

echo "\n--- Step 3: Update existing activities with no completion rule ---\n";
$allcourses = $DB->get_records_select('course', 'id > 1', null, '', 'id, fullname');
$updated = 0;
$skipped = 0;

foreach ($allcourses as $course) {
    // Get all course modules with no completion tracking (completion = 0).
    $cms = $DB->get_records_sql(
        "SELECT cm.id, cm.module, cm.instance, cm.completion, m.name AS modname
           FROM {course_modules} cm
           JOIN {modules} m ON m.id = cm.module
          WHERE cm.course = :courseid
            AND cm.completion = 0
            AND cm.deletioninprogress = 0",
        ['courseid' => $course->id]
    );

    if (empty($cms)) {
        continue;
    }

    if ($verbose) {
        echo "\n  Course: {$course->fullname} (id={$course->id}) — " . count($cms) . " untracked activities\n";
    }

    foreach ($cms as $cm) {
        $rule = isset($defaults[$cm->modname]) ? $defaults[$cm->modname] : $defaults['_default'];

        if ($verbose) {
            $rulename = $rule['completion'] == 2 ? 'automatic' : 'manual';
            echo "    {$prefix}{$cm->modname} (cmid={$cm->id}) → {$rulename}\n";
        }

        if (!$dryrun) {
            $updatedata = ['id' => $cm->id];
            foreach ($rule as $field => $value) {
                $updatedata[$field] = $value;
            }
            $DB->update_record('course_modules', (object) $updatedata);
        }
        $updated++;
    }
}

echo "  {$prefix}Updated {$updated} activities. Skipped {$skipped}.\n";

// ─── Step 4: Rebuild course caches ───────────────────────────────

if (!$dryrun && $updated > 0) {
    echo "\n--- Step 4: Rebuilding course caches ---\n";
    foreach ($allcourses as $course) {
        rebuild_course_cache($course->id, true);
    }
    echo "  ✓ Course caches rebuilt.\n";
}

// ─── Summary ─────────────────────────────────────────────────────

echo "\n=== Summary ===\n";
echo "  Site-level toggle: " . ($currentvalue ? 'already on' : ($dryrun ? 'would enable' : 'enabled')) . "\n";
echo "  Courses updated: {$coursecount}\n";
echo "  Activities updated: {$updated}\n";

if ($dryrun) {
    echo "\nThis was a dry run. Use --apply to make changes.\n";
} else {
    echo "\n✓ Completion tracking is now active.\n";
}
