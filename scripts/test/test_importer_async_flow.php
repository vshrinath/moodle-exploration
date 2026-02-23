<?php
/**
 * Regression test for Local SCEH Importer Adhoc Task flow.
 * 
 * Verifies:
 * 1. Job record creation and initial state.
 * 2. Adhoc task queueing.
 * 3. Execution via task manager.
 * 4. Post-execution job state (completed/failed).
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB, $USER;

echo "=== Testing SCEH Importer Async Flow ===\n";

try {
    // 1. Setup Mock User and Course.
    $admin = get_admin();
    \core\session\manager::set_user($admin);
    
    $course = $DB->get_record('course', ['id' => 2]); // Use Site Course or a known test course.
    if (!$course) {
        // Fallback to create one if necessary, but assume id=1 or 2 exists in dev.
        $course = $DB->get_record('course', ['id' => 1]); 
    }

    echo "Target Course: {$course->fullname}\n";

    // 2. Create Mock Job.
    $manifest = [
        'sections' => [['idnumber' => 'TEST-SEC-1', 'name' => 'Test Section']],
        'activities' => [
            [
                'idnumber' => 'TEST-ACT-1',
                'title' => 'Async Test URL',
                'type' => 'url',
                'section_idnumber' => 'TEST-SEC-1',
                'url' => 'http://example.com'
            ]
        ]
    ];

    $job = new stdClass();
    $job->userid = $admin->id;
    $job->courseid = $course->id;
    $job->status = 'queued';
    $job->manifest = json_encode($manifest);
    $job->timecreated = time();
    $job->timemodified = time();
    $jobid = $DB->insert_record('local_sceh_importer_job', $job);

    echo "Created Job ID: {$jobid}\n";

    // 3. Queue Adhoc Task.
    // Clear old tasks first to avoid ambiguity in test.
    $DB->delete_records('task_adhoc', ['classname' => '\local_sceh_importer\task\import_package_task']);
    $DB->delete_records('task_adhoc', ['classname' => 'local_sceh_importer\task\import_package_task']);

    $task = new \local_sceh_importer\task\import_package_task();
    $task->set_custom_data(['jobid' => $jobid]);
    $task->set_userid($admin->id);
    \core\task\manager::queue_adhoc_task($task, true);
    
    echo "Task Queued for Job ID: {$jobid}\n";

    // 4. Run the Task Immediately.
    echo "Executing Task via Manager...\n";
    $targetclassname = '\local_sceh_importer\task\import_package_task';
    $tasks = $DB->get_records('task_adhoc', ['classname' => $targetclassname]);
    
    if (!$tasks) {
        $targetclassname = 'local_sceh_importer\task\import_package_task';
        $tasks = $DB->get_records('task_adhoc', ['classname' => $targetclassname]);
    }

    $found = false;
    foreach ($tasks as $t) {
        $data = json_decode($t->customdata);
        if ($data && isset($data->jobid) && (int)$data->jobid === (int)$jobid) {
            echo "Executing task ID: {$t->id} for Job ID: {$jobid}\n";
            $realadhoc = \core\task\manager::get_adhoc_task($t->id);
            if ($realadhoc) {
                // We wrap in try-catch to see errors during execution.
                try {
                    $realadhoc->execute();
                    \core\task\manager::adhoc_task_complete($realadhoc);
                    echo "Task execution manual call finished.\n";
                } catch (Exception $e) {
                    echo "TASK EXECUTION EXCEPTION: " . $e->getMessage() . "\n";
                    echo $e->getTraceAsString() . "\n";
                }
                $found = true;
                break;
            }
        }
    }

    if (!$found) {
        echo "Task NOT found in queue for Job ID: {$jobid}\n";
    }

    // 5. Verify Job Record.
    $finaljob = $DB->get_record('local_sceh_importer_job', ['id' => $jobid]);
    echo "Final Job Status: {$finaljob->status}\n";
    
    if ($finaljob->status === 'completed') {
        echo "PASS: Job marked as completed.\n";
    } else {
        echo "FAIL: Job status is {$finaljob->status}. Error: {$finaljob->error}\n";
    }

    // 6. Cleanup (Optional).
    // $DB->delete_records('local_sceh_importer_job', ['id' => $jobid]);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
