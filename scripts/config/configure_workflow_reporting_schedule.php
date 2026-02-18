<?php
/**
 * Configure WF-12 reporting schedule baseline.
 *
 * Idempotent setup for report scheduling:
 * - Ensures custom reports are enabled
 * - Ensures report audience exists for explicit recipient users
 * - Ensures schedule exists (or updates existing schedule) for chosen report
 * - Optional immediate execution for validation
 *
 * Usage examples:
 *   php scripts/config/configure_workflow_reporting_schedule.php --mode=verify --report-id=1 --report-recipient-usernames=ops.admin
 *   php scripts/config/configure_workflow_reporting_schedule.php --mode=apply --report-id=1 --report-recipient-usernames=ops.admin,ops.owner
 *   php scripts/config/configure_workflow_reporting_schedule.php --mode=apply --report-id=1 --report-recipient-usernames=ops.admin --run-now
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/reportbuilder/lib.php');

use core_reportbuilder\local\helpers\schedule as schedule_helper;
use core_reportbuilder\local\helpers\report as report_helper;
use core_reportbuilder\local\models\audience as audience_model;
use core_reportbuilder\local\models\schedule as schedule_model;
use core_reportbuilder\reportbuilder\audience\manual as manual_audience;
use core_reportbuilder\reportbuilder\schedule\message as message_schedule;
use core_reportbuilder\task\send_schedule as send_schedule_task;

global $DB;

init_cli_admin('moodle/site:config');

$mode = 'verify';
$reportid = 0;
$recipients = [];
$schedulename = 'WF12 Operational Summary';
$runnow = false;

foreach ($argv as $arg) {
    if (strpos($arg, '--mode=') === 0) {
        $mode = trim(substr($arg, 7));
    } else if (strpos($arg, '--report-id=') === 0) {
        $reportid = (int) trim(substr($arg, 12));
    } else if (strpos($arg, '--report-recipient-usernames=') === 0) {
        $raw = trim(substr($arg, 29));
        if ($raw !== '') {
            $recipients = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
    } else if (strpos($arg, '--schedule-name=') === 0) {
        $schedulename = trim(substr($arg, 16));
    } else if ($arg === '--run-now') {
        $runnow = true;
    }
}

if (!in_array($mode, ['verify', 'apply'], true)) {
    fwrite(STDERR, "ERROR: Invalid mode '{$mode}'. Use --mode=verify|apply.\n");
    exit(1);
}

if ($reportid <= 0) {
    fwrite(STDERR, "ERROR: --report-id is required and must be > 0.\n");
    exit(1);
}

if (empty($recipients)) {
    fwrite(STDERR, "ERROR: --report-recipient-usernames is required.\n");
    exit(1);
}

if ($mode === 'verify' && $runnow) {
    fwrite(STDERR, "ERROR: --run-now requires --mode=apply.\n");
    exit(1);
}

echo "=== WF-12 Reporting Schedule Baseline ===\n";
echo "MODE\t{$mode}\n";
echo "REPORT_ID\t{$reportid}\n";
echo "SCHEDULE_NAME\t{$schedulename}\n";
echo "RUN_NOW\t" . ($runnow ? 'true' : 'false') . "\n";

$report = $DB->get_record('reportbuilder_report', ['id' => $reportid], 'id,name,source', IGNORE_MISSING);
if (!$report) {
    fwrite(STDERR, "ERROR: Report not found for id={$reportid}.\n");
    exit(1);
}
echo "REPORT_FOUND\tID={$report->id}\tSOURCE={$report->source}\n";

$reportcolcount = (int) $DB->count_records('reportbuilder_column', ['reportid' => $reportid]);
if ($reportcolcount === 0) {
    $wf12reportname = 'WF12 Scheduled Users Report';
    $wf12source = 'core_user\\reportbuilder\\datasource\\users';
    $existingwf12report = $DB->get_record('reportbuilder_report', [
        'name' => $wf12reportname,
        'source' => $wf12source,
    ], 'id,name,source', IGNORE_MISSING);

    if ($existingwf12report) {
        $report = $existingwf12report;
        $reportid = (int) $report->id;
        echo "REPORT_SWITCH\tUsing existing WF12 custom report ID={$reportid}\n";
    } else if ($mode === 'apply') {
        $created = report_helper::create_report((object) [
            'name' => $wf12reportname,
            'source' => $wf12source,
        ], true);
        $reportid = (int) $created->get('id');
        $report = $DB->get_record('reportbuilder_report', ['id' => $reportid], 'id,name,source', MUST_EXIST);
        echo "REPORT_CREATED\tID={$reportid}\tSOURCE={$wf12source}\n";
    } else {
        echo "VERIFY_ONLY\tSelected report has no custom columns; apply mode will create '{$wf12reportname}'\n";
    }
}

$recipientusers = [];
foreach ($recipients as $username) {
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], 'id,username,email', IGNORE_MISSING);
    if (!$user) {
        fwrite(STDERR, "ERROR: Recipient user not found: {$username}\n");
        exit(1);
    }
    $recipientusers[] = $user;
}

$recipientids = array_map(static function(stdClass $u): int {
    return (int) $u->id;
}, $recipientusers);
sort($recipientids);
echo "RECIPIENTS\t" . implode(',', array_map(static function(stdClass $u): string {
    return $u->username;
}, $recipientusers)) . "\n";

if ($mode === 'apply') {
    set_config('enablecustomreports', 1);
    echo "SET_CONFIG\tenablecustomreports=1\n";
} else {
    echo "VERIFY_ONLY\tWould set enablecustomreports=1\n";
}

$manualaudienceclass = 'core_reportbuilder\\reportbuilder\\audience\\manual';
$audienceid = 0;

$audiences = audience_model::get_records(['reportid' => $reportid], 'id');
foreach ($audiences as $audience) {
    if ($audience->get('classname') !== $manualaudienceclass) {
        continue;
    }
    $cfg = json_decode((string) $audience->get('configdata'), true) ?: [];
    $users = array_map('intval', $cfg['users'] ?? []);
    sort($users);
    if ($users === $recipientids) {
        $audienceid = (int) $audience->get('id');
        break;
    }
}

if ($audienceid <= 0) {
    if ($mode === 'apply') {
        $instance = manual_audience::create($reportid, ['users' => $recipientids]);
        $audienceid = (int) $instance->get_persistent()->get('id');
        echo "AUDIENCE_CREATED\tID={$audienceid}\tCLASS=manual\n";
    } else {
        echo "VERIFY_ONLY\tWould create manual audience for selected recipients\n";
    }
} else {
    echo "AUDIENCE_EXISTS\tID={$audienceid}\n";
}

$schedule = $DB->get_record('reportbuilder_schedule', ['reportid' => $reportid, 'name' => $schedulename], '*', IGNORE_MISSING);
$scheduleid = $schedule ? (int) $schedule->id : 0;

$configdata = [
    'subject' => 'Workflow Report: ' . $schedulename,
    'message' => [
        'text' => 'Automated workflow report generated by WF-12 baseline configuration.',
        'format' => FORMAT_PLAIN,
    ],
    'reportempty' => message_schedule::REPORT_EMPTY_SEND_WITHOUT,
];

$scheduledata = (object) [
    'reportid' => $reportid,
    'name' => $schedulename,
    'enabled' => 1,
    'audiences' => json_encode([$audienceid]),
    'classname' => message_schedule::class,
    'configdata' => json_encode($configdata),
    'format' => 'csv',
    'userviewas' => schedule_model::REPORT_VIEWAS_CREATOR,
    'timescheduled' => time() + 300,
    'recurrence' => schedule_model::RECURRENCE_WEEKLY,
];

if ($scheduleid > 0) {
    if ($mode === 'apply') {
        $scheduledata->id = $scheduleid;
        schedule_helper::update_schedule($scheduledata);
        echo "SCHEDULE_UPDATED\tID={$scheduleid}\n";
    } else {
        echo "VERIFY_ONLY\tWould update existing schedule id={$scheduleid}\n";
    }
} else {
    if ($mode === 'apply') {
        if ($audienceid <= 0) {
            fwrite(STDERR, "ERROR: Cannot create schedule without audience in apply mode.\n");
            exit(1);
        }
        $created = message_schedule::create($scheduledata)->get_persistent();
        $scheduleid = (int) $created->get('id');
        echo "SCHEDULE_CREATED\tID={$scheduleid}\n";
    } else {
        echo "VERIFY_ONLY\tWould create schedule '{$schedulename}'\n";
    }
}

if ($mode === 'apply' && $runnow) {
    if ($scheduleid <= 0) {
        fwrite(STDERR, "ERROR: Cannot run schedule immediately, schedule id is missing.\n");
        exit(1);
    }

    $task = new send_schedule_task();
    $task->set_custom_data([
        'reportid' => $reportid,
        'scheduleid' => $scheduleid,
    ]);
    try {
        $task->execute();
    } catch (Throwable $e) {
        fwrite(STDERR, "ERROR: Schedule execution failed: " . $e->getMessage() . "\n");
        exit(1);
    }

    $sentat = (int) $DB->get_field('reportbuilder_schedule', 'timelastsent', ['id' => $scheduleid], IGNORE_MISSING);
    echo "SCHEDULE_EXECUTED\tID={$scheduleid}\tTIMELASTSENT={$sentat}\n";

    $recipientcount = count($recipientids);
    $since = time() - 300;
    [$insql, $params] = $DB->get_in_or_equal($recipientids, SQL_PARAMS_NAMED, 'uid');
    $params['since'] = $since;
    $sentnotifications = (int) $DB->count_records_sql(
        "SELECT COUNT(n.id)
           FROM {notifications} n
          WHERE n.useridto {$insql}
            AND n.timecreated >= :since",
        $params
    );
    echo "NOTIFICATION_RECENT_COUNT\t{$sentnotifications}\tEXPECTED_MIN={$recipientcount}\n";
}

echo "DONE\tWF-12 reporting schedule baseline complete\n";
