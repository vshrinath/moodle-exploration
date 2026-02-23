<?php
/**
 * Automated workflow test: Allied Health foundational (phase 2).
 *
 * Scope:
 * - Creates/updates course from day folders in test_content
 * - Adds day resources (content, lesson plan, roleplay)
 * - Creates day quizzes and imports questions from CSV into quiz bank
 * - Validates cohort-sync learner enrollment
 * - Validates trainer release flow and learner visibility
 *
 * Usage:
 *   php scripts/test/test_allied_health_quiz_workflow.php
 *   php scripts/test/test_allied_health_quiz_workflow.php --category-idnumber=allied-health
 *   php scripts/test/test_allied_health_quiz_workflow.php --content-root="/var/www/html/public/test_content/Allied Health Program"
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../lib/config_helper.php');
require_moodle_config();

require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/lib/resourcelib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/classes/quiz_settings.php');
require_once($CFG->dirroot . '/mod/quiz/classes/grade_calculator.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/question/format/gift/format.php');
require_once($CFG->libdir . '/accesslib.php');

$CFG->noemailever = true;

global $DB;

$defaultcontentroot = $CFG->dirroot . '/test_content/Allied Health Program';
if (!is_dir($defaultcontentroot) && is_dir('/tmp/allied-health-content')) {
    $defaultcontentroot = '/tmp/allied-health-content';
}

$options = getopt('', [
    'category-idnumber::',
    'course-shortname::',
    'course-fullname::',
    'content-root::',
    'program-owner::',
    'trainer::',
    'learner::',
    'cohort-idnumber::',
]);

$categoryidnumber = trim((string)($options['category-idnumber'] ?? 'allied-health'));
$courseshortname = trim((string)($options['course-shortname'] ?? ('AHW-FOUND-AUTO-' . date('Ymd-Hi'))));
$coursefullname = trim((string)($options['course-fullname'] ?? ('Allied Health - Foundational (Automation) ' . date('Y-m-d H:i'))));
$contentroot = trim((string)($options['content-root'] ?? $defaultcontentroot));
$programownerusername = trim((string)($options['program-owner'] ?? 'mock.programowner'));
$trainerusername = trim((string)($options['trainer'] ?? 'mock.trainer'));
$learnerusername = trim((string)($options['learner'] ?? 'mock.learner'));
$cohortidnumber = trim((string)($options['cohort-idnumber'] ?? 'mock-allied-2026'));

$results = [];

function log_check(array &$results, string $id, bool $pass, string $detail): void {
    $results[] = ['id' => $id, 'pass' => $pass, 'detail' => $detail];
    echo ($pass ? "PASS" : "FAIL") . "\t{$id}\t{$detail}\n";
}

function fail_exit(array $results, string $message): void {
    fwrite(STDERR, "ERROR\t{$message}\n");
    $failed = array_filter($results, static function(array $r): bool {
        return !$r['pass'];
    });
    exit(empty($failed) ? 1 : 2);
}

function set_script_user(stdClass $user): void {
    \core\session\manager::set_user($user);
}

function get_user_or_throw(string $username): stdClass {
    global $DB;
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], '*', IGNORE_MISSING);
    if (!$user) {
        throw new RuntimeException("Missing user {$username}");
    }
    return $user;
}

function find_allied_health_category(string $idnumber): ?stdClass {
    global $DB;
    $category = $DB->get_record('course_categories', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
    if ($category) {
        return $category;
    }
    return $DB->get_record('course_categories', ['name' => 'Allied Health Workers'], '*', IGNORE_MISSING);
}

function list_visible_dirs(string $path): array {
    if (!is_dir($path)) {
        return [];
    }
    $entries = scandir($path);
    if ($entries === false) {
        return [];
    }

    $dirs = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry[0] === '.') {
            continue;
        }
        $full = $path . '/' . $entry;
        if (is_dir($full)) {
            $dirs[] = ['name' => $entry, 'path' => $full];
        }
    }
    return $dirs;
}

function discover_day_folders(string $contentroot): array {
    $level1 = list_visible_dirs($contentroot);
    if (empty($level1)) {
        return [];
    }

    $days = [];
    foreach ($level1 as $entry) {
        if (is_dir($entry['path'] . '/quiz')) {
            $days[] = $entry;
        }
    }
    if (!empty($days)) {
        return $days;
    }

    if (count($level1) === 1) {
        $level2 = list_visible_dirs($level1[0]['path']);
        foreach ($level2 as $entry) {
            if (is_dir($entry['path'] . '/quiz')) {
                $days[] = $entry;
            }
        }
    }

    return $days;
}

function first_file_in_dir(string $dir): ?string {
    if (!is_dir($dir)) {
        return null;
    }
    $files = scandir($dir);
    if ($files === false) {
        return null;
    }
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file[0] === '.') {
            continue;
        }
        $full = $dir . '/' . $file;
        if (is_file($full)) {
            return $full;
        }
    }
    return null;
}

function get_first_csv_from_quiz_dir(string $daypath): ?string {
    $quizdir = $daypath . '/quiz';
    if (!is_dir($quizdir)) {
        return null;
    }
    $files = scandir($quizdir);
    if ($files === false) {
        return null;
    }
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file[0] === '.') {
            continue;
        }
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'csv') {
            return $quizdir . '/' . $file;
        }
    }
    return null;
}

function parse_csv_rows_assoc(string $csvpath): array {
    $handle = fopen($csvpath, 'rb');
    if (!$handle) {
        return [];
    }

    $headers = [];
    $rows = [];
    $line = 0;
    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        if ($line === 1) {
            $headers = array_map(static function($h): string {
                return strtolower(trim(ltrim((string)$h, "\xEF\xBB\xBF")));
            }, $data);
            continue;
        }
        if (empty(array_filter($data, static function($value): bool {
            return trim((string)$value) !== '';
        }))) {
            continue;
        }

        $row = [];
        foreach ($headers as $idx => $header) {
            if ($header === '') {
                continue;
            }
            $row[$header] = trim((string)($data[$idx] ?? ''));
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}

function ensure_course_for_program_owner(stdClass $category, stdClass $programowner, string $shortname, string $fullname): stdClass {
    global $DB;

    $existing = $DB->get_record('course', ['shortname' => $shortname], '*', IGNORE_MISSING);
    if ($existing) {
        return $existing;
    }

    set_script_user($programowner);
    require_capability('moodle/course:create', context_coursecat::instance((int)$category->id));

    return create_course((object)[
        'category' => (int)$category->id,
        'fullname' => $fullname,
        'shortname' => $shortname,
        'idnumber' => 'AUTO-' . $shortname,
        'summary' => 'Automated Allied Health workflow test course',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'visible' => 0,
        'enablecompletion' => 1,
        'startdate' => time(),
    ]);
}

function ensure_section_name(stdClass $course, int $sectionnum, string $name): void {
    global $DB;
    course_create_sections_if_missing($course, [$sectionnum]);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum], '*', MUST_EXIST);
    if (trim((string)$section->name) !== $name) {
        $section->name = $name;
        $DB->update_record('course_sections', $section);
    }
}

function slugify(string $value): string {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($value));
    $slug = trim((string)$slug, '-');
    return $slug !== '' ? $slug : 'item';
}

function find_existing_cm_by_idnumber_and_module(int $courseid, string $cmidnumber, string $modname): ?stdClass {
    global $DB;
    $sql = "SELECT cm.id, cm.instance
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module
             WHERE cm.course = :courseid
               AND cm.idnumber = :idnumber
               AND m.name = :modname";
    return $DB->get_record_sql($sql, ['courseid' => $courseid, 'idnumber' => $cmidnumber, 'modname' => $modname]) ?: null;
}

function build_quiz_moduleinfo(stdClass $course, int $section, string $quizname, string $cmidnumber, string $intro): stdClass {
    global $DB;

    $moduleinfo = new stdClass();
    $moduleinfo->course = (int)$course->id;
    $moduleinfo->modulename = 'quiz';
    $moduleinfo->name = $quizname;
    $moduleinfo->intro = $intro;
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->section = $section;
    $moduleinfo->visible = 0;
    $moduleinfo->visibleoncoursepage = 1;
    $moduleinfo->groupmode = 0;
    $moduleinfo->groupingid = 0;
    $moduleinfo->completion = 0;
    $moduleinfo->cmidnumber = $cmidnumber;
    $moduleinfo->timeopen = 0;
    $moduleinfo->timeclose = 0;
    $moduleinfo->preferredbehaviour = 'deferredfeedback';
    $moduleinfo->attempts = 0;
    $moduleinfo->attemptonlast = 0;
    $moduleinfo->grademethod = QUIZ_GRADEHIGHEST;
    $moduleinfo->decimalpoints = 2;
    $moduleinfo->questiondecimalpoints = -1;
    $moduleinfo->attemptduring = 1;
    $moduleinfo->correctnessduring = 1;
    $moduleinfo->maxmarksduring = 1;
    $moduleinfo->marksduring = 1;
    $moduleinfo->specificfeedbackduring = 1;
    $moduleinfo->generalfeedbackduring = 1;
    $moduleinfo->rightanswerduring = 1;
    $moduleinfo->overallfeedbackduring = 0;
    $moduleinfo->attemptimmediately = 1;
    $moduleinfo->correctnessimmediately = 1;
    $moduleinfo->maxmarksimmediately = 1;
    $moduleinfo->marksimmediately = 1;
    $moduleinfo->specificfeedbackimmediately = 1;
    $moduleinfo->generalfeedbackimmediately = 1;
    $moduleinfo->rightanswerimmediately = 1;
    $moduleinfo->overallfeedbackimmediately = 1;
    $moduleinfo->attemptopen = 1;
    $moduleinfo->correctnessopen = 1;
    $moduleinfo->maxmarksopen = 1;
    $moduleinfo->marksopen = 1;
    $moduleinfo->specificfeedbackopen = 1;
    $moduleinfo->generalfeedbackopen = 1;
    $moduleinfo->rightansweropen = 1;
    $moduleinfo->overallfeedbackopen = 1;
    $moduleinfo->attemptclosed = 1;
    $moduleinfo->correctnessclosed = 1;
    $moduleinfo->maxmarksclosed = 1;
    $moduleinfo->marksclosed = 1;
    $moduleinfo->specificfeedbackclosed = 1;
    $moduleinfo->generalfeedbackclosed = 1;
    $moduleinfo->rightanswerclosed = 1;
    $moduleinfo->overallfeedbackclosed = 1;
    $moduleinfo->questionsperpage = 1;
    $moduleinfo->shuffleanswers = 1;
    $moduleinfo->sumgrades = 0;
    $moduleinfo->grade = 100;
    $moduleinfo->timelimit = 0;
    $moduleinfo->overduehandling = 'autosubmit';
    $moduleinfo->graceperiod = 86400;
    $moduleinfo->quizpassword = '';
    $moduleinfo->subnet = '';
    $moduleinfo->browsersecurity = '';
    $moduleinfo->delay1 = 0;
    $moduleinfo->delay2 = 0;
    $moduleinfo->showuserpicture = 0;
    $moduleinfo->showblocks = 0;
    $moduleinfo->navmethod = QUIZ_NAVMETHOD_FREE;
    $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST);

    return $moduleinfo;
}

function ensure_hidden_quiz_for_day(stdClass $course, stdClass $programowner, int $sectionnum, string $dayname, string $quizcsv, int $csvrows): array {
    global $DB;

    $cmidnumber = 'AHW-DAY-QUIZ-' . slugify($dayname);
    $existing = find_existing_cm_by_idnumber_and_module((int)$course->id, $cmidnumber, 'quiz');
    if ($existing) {
        set_coursemodule_visible((int)$existing->id, 0);
        return ['cmid' => (int)$existing->id, 'quizid' => (int)$existing->instance];
    }

    set_script_user($programowner);
    require_capability('mod/quiz:addinstance', context_course::instance((int)$course->id));

    $intro = 'Source CSV: ' . basename($quizcsv) . ' | Parsed rows: ' . $csvrows;
    $moduleinfo = build_quiz_moduleinfo($course, $sectionnum, $dayname . ' Quiz', $cmidnumber, $intro);
    $created = add_moduleinfo($moduleinfo, $course, null);

    $cmid = (int)$created->coursemodule;
    $quizid = (int)$created->instance;
    set_coursemodule_visible($cmid, 0);

    $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
    if (trim((string)$quiz->name) !== ($dayname . ' Quiz')) {
        $quiz->name = $dayname . ' Quiz';
        $DB->update_record('quiz', $quiz);
    }

    return ['cmid' => $cmid, 'quizid' => $quizid];
}

function build_resource_moduleinfo_from_file(stdClass $course, int $userid, int $sectionnum, string $name, string $cmidnumber, string $sourcefile): stdClass {
    global $DB;

    if (!is_readable($sourcefile)) {
        throw new RuntimeException("Unreadable source file: {$sourcefile}");
    }

    $moduleinfo = new stdClass();
    $moduleinfo->course = (int)$course->id;
    $moduleinfo->modulename = 'resource';
    $moduleinfo->name = $name;
    $moduleinfo->intro = '';
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->section = $sectionnum;
    $moduleinfo->visible = 0;
    $moduleinfo->visibleoncoursepage = 1;
    $moduleinfo->groupmode = 0;
    $moduleinfo->groupingid = 0;
    $moduleinfo->completion = 0;
    $moduleinfo->cmidnumber = $cmidnumber;
    $moduleinfo->display = RESOURCELIB_DISPLAY_AUTO;
    $moduleinfo->printintro = 1;
    $moduleinfo->showsize = 1;
    $moduleinfo->showtype = 1;

    $draftitemid = file_get_unused_draft_itemid();
    $usercontext = context_user::instance($userid);
    $filerecord = [
        'component' => 'user',
        'filearea' => 'draft',
        'contextid' => $usercontext->id,
        'itemid' => $draftitemid,
        'filename' => basename($sourcefile),
        'filepath' => '/',
    ];
    $fs = get_file_storage();
    $fs->create_file_from_pathname($filerecord, $sourcefile);

    $moduleinfo->files = $draftitemid;
    $moduleinfo->uploaded = 1;
    $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);

    return $moduleinfo;
}

function ensure_hidden_resource_for_day(stdClass $course, stdClass $programowner, int $sectionnum, string $dayname, string $bucket, string $sourcefile): int {
    $cmidnumber = 'AHW-' . strtoupper($bucket) . '-' . slugify($dayname);
    $existing = find_existing_cm_by_idnumber_and_module((int)$course->id, $cmidnumber, 'resource');
    if ($existing) {
        set_coursemodule_visible((int)$existing->id, 0);
        return (int)$existing->id;
    }

    $title = $dayname . ' - ' . ucfirst(str_replace('_', ' ', $bucket));
    set_script_user($programowner);
    require_capability('mod/resource:addinstance', context_course::instance((int)$course->id));
    $moduleinfo = build_resource_moduleinfo_from_file($course, (int)$programowner->id, $sectionnum, $title, $cmidnumber, $sourcefile);
    $created = add_moduleinfo($moduleinfo, $course, null);

    $cmid = (int)$created->coursemodule;
    set_coursemodule_visible($cmid, 0);

    return $cmid;
}

function ensure_manual_enrolment(stdClass $course, int $userid, string $roleshortname): void {
    global $DB;

    $role = $DB->get_record('role', ['shortname' => $roleshortname], '*', MUST_EXIST);
    $plugin = enrol_get_plugin('manual');
    if (!$plugin) {
        throw new RuntimeException('Manual enrol plugin is not available');
    }

    $instance = null;
    $instances = enrol_get_instances((int)$course->id, true);
    foreach ($instances as $candidate) {
        if ($candidate->enrol === 'manual') {
            $instance = $candidate;
            break;
        }
    }

    if (!$instance) {
        $instanceid = $plugin->add_instance($course, []);
        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
    }

    if (!is_enrolled(context_course::instance((int)$course->id), $userid, '', true)) {
        $plugin->enrol_user($instance, $userid, (int)$role->id);
        return;
    }

    $context = context_course::instance((int)$course->id);
    if (!user_has_role_assignment($userid, (int)$role->id, (int)$context->id)) {
        role_assign((int)$role->id, $userid, (int)$context->id);
    }
}

function ensure_cohort(string $idnumber, string $name): stdClass {
    global $DB;

    $existing = $DB->get_record('cohort', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
    if ($existing) {
        return $existing;
    }

    $cohort = (object)[
        'contextid' => context_system::instance()->id,
        'name' => $name,
        'idnumber' => $idnumber,
        'description' => 'Automation cohort for Allied Health workflow tests',
        'descriptionformat' => FORMAT_HTML,
        'visible' => 1,
    ];
    $cohort->id = cohort_add_cohort($cohort);

    return $DB->get_record('cohort', ['id' => $cohort->id], '*', MUST_EXIST);
}

function ensure_cohort_member(int $cohortid, int $userid): void {
    global $DB;

    if ($DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid])) {
        return;
    }
    cohort_add_member($cohortid, $userid);
}

function ensure_cohort_enrolment_instance(stdClass $course, int $cohortid, int $roleid): stdClass {
    global $DB;

    $existing = $DB->get_record('enrol', [
        'courseid' => (int)$course->id,
        'enrol' => 'cohort',
        'customint1' => $cohortid,
    ], '*', IGNORE_MISSING);
    if ($existing) {
        return $existing;
    }

    $plugin = enrol_get_plugin('cohort');
    if (!$plugin) {
        throw new RuntimeException('Cohort enrol plugin is not available');
    }

    $instanceid = $plugin->add_instance($course, [
        'customint1' => $cohortid,
        'roleid' => $roleid,
        'status' => ENROL_INSTANCE_ENABLED,
    ]);
    if (!$instanceid) {
        throw new RuntimeException('Failed to create cohort enrol instance');
    }

    return $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
}

function sync_cohort_enrolments_for_course(int $courseid): void {
    if (function_exists('enrol_cohort_sync')) {
        $trace = new \core\output\progress_trace\null_progress_trace();
        enrol_cohort_sync($trace, $courseid);
    }
}

function get_cm_uservisible(int $courseid, int $userid, int $cmid): bool {
    $modinfo = get_fast_modinfo($courseid, $userid);
    $cm = $modinfo->get_cm($cmid);
    return (bool)$cm->uservisible;
}

function escape_gift_text(string $text): string {
    return strtr($text, [
        '\\' => '\\\\',
        '{' => '\\{',
        '}' => '\\}',
        '~' => '\\~',
        '=' => '\\=',
        '#' => '\\#',
        ':' => '\\:',
    ]);
}

function build_gift_question(array $row, int $position, array &$warnings): ?string {
    $qid = trim((string)($row['question_id'] ?? ('Q' . $position)));
    $qtype = strtolower(trim((string)($row['question_type'] ?? '')));
    $qtext = trim((string)($row['question_text'] ?? ''));
    $correct = trim((string)($row['correct_option'] ?? ''));

    if ($qtext === '' || $correct === '') {
        $warnings[] = "{$qid}: missing question_text or correct_option";
        return null;
    }

    $title = escape_gift_text($qid);
    $question = escape_gift_text($qtext);

    if (in_array($qtype, ['mcq', 'multichoice', 'singlechoice'], true)) {
        $options = [
            'A' => trim((string)($row['option_a'] ?? '')),
            'B' => trim((string)($row['option_b'] ?? '')),
            'C' => trim((string)($row['option_c'] ?? '')),
            'D' => trim((string)($row['option_d'] ?? '')),
            'E' => trim((string)($row['option_e'] ?? '')),
        ];
        $normalized = strtoupper($correct);
        if (!array_key_exists($normalized, $options) || $options[$normalized] === '') {
            foreach ($options as $label => $text) {
                if ($text !== '' && strcasecmp($text, $correct) === 0) {
                    $normalized = $label;
                    break;
                }
            }
        }
        if (!array_key_exists($normalized, $options) || $options[$normalized] === '') {
            $warnings[] = "{$qid}: correct option does not match options";
            return null;
        }

        $answers = [];
        foreach ($options as $label => $text) {
            if ($text === '') {
                continue;
            }
            $answers[] = (($label === $normalized) ? '=' : '~') . escape_gift_text($text);
        }
        return '::' . $title . ':: ' . $question . " {\n" . implode("\n", $answers) . "\n}";
    }

    if (in_array($qtype, ['true_false', 'truefalse', 'tf'], true)) {
        $normalized = strtoupper($correct);
        if (in_array($normalized, ['T', 'TRUE', '1', 'YES'], true)) {
            return '::' . $title . ':: ' . $question . ' {T}';
        }
        if (in_array($normalized, ['F', 'FALSE', '0', 'NO'], true)) {
            return '::' . $title . ':: ' . $question . ' {F}';
        }
        $warnings[] = "{$qid}: invalid true/false answer";
        return null;
    }

    if (in_array($qtype, ['short_answer', 'shortanswer', 'sa'], true)) {
        return '::' . $title . ':: ' . $question . ' {=' . escape_gift_text($correct) . '}';
    }

    $warnings[] = "{$qid}: unsupported question type {$qtype}";
    return null;
}

function import_quiz_questions_from_csv(stdClass $course, int $cmid, int $quizid, string $csvpath, array &$warnings): int {
    global $DB;

    $existing = (int)$DB->count_records('quiz_slots', ['quizid' => $quizid]);
    if ($existing > 0) {
        return $existing;
    }

    $rows = parse_csv_rows_assoc($csvpath);
    if (empty($rows)) {
        return 0;
    }

    $giftlines = [];
    foreach ($rows as $i => $row) {
        $gift = build_gift_question($row, $i + 1, $warnings);
        if ($gift !== null) {
            $giftlines[] = $gift;
        }
    }

    if (empty($giftlines)) {
        return 0;
    }

    $giftcontent = implode("\n\n", $giftlines) . "\n";
    $tmpdir = make_request_directory();
    $giftpath = $tmpdir . '/ahw_inline_quiz_' . time() . '_' . mt_rand(1000, 9999) . '.gift';
    if (file_put_contents($giftpath, $giftcontent) === false) {
        $warnings[] = basename($csvpath) . ': unable to write temporary GIFT file';
        return 0;
    }

    $context = context_module::instance($cmid);
    $category = question_get_default_category($context->id, true);
    if (empty($category)) {
        $warnings[] = basename($csvpath) . ': unable to resolve question category';
        return 0;
    }

    $qformat = new qformat_gift();
    $qformat->setCategory($category);
    $qformat->setContexts([$context]);
    $qformat->setCourse($course);
    $qformat->setFilename($giftpath);
    $qformat->setRealfilename(basename($giftpath));
    $qformat->setMatchgrades('error');
    $qformat->setCatfromfile(false);
    $qformat->setContextfromfile(false);
    $qformat->setStoponerror(true);
    $qformat->set_display_progress(false);
    $qformat->displayerrors = false;

    if (!$qformat->importpreprocess() || !$qformat->importprocess() || !$qformat->importpostprocess()) {
        $warnings[] = basename($csvpath) . ': failed to import GIFT questions';
        return 0;
    }

    if (empty($qformat->questionids)) {
        return 0;
    }

    $quizrecord = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
    foreach ($qformat->questionids as $questionid) {
        quiz_add_quiz_question((int)$questionid, $quizrecord, 0);
    }

    $quizobj = \mod_quiz\quiz_settings::create($quizid);
    $gradecalculator = \mod_quiz\grade_calculator::create($quizobj);
    $gradecalculator->recompute_quiz_sumgrades();

    return (int)$DB->count_records('quiz_slots', ['quizid' => $quizid]);
}

echo "=== Automated Test: Allied Health Workflow (Phase 2) ===\n";
echo "CATEGORY_IDNUMBER\t{$categoryidnumber}\n";
echo "COURSE_SHORTNAME\t{$courseshortname}\n";
echo "CONTENT_ROOT\t{$contentroot}\n\n";

try {
    $admin = get_admin();
    if (!$admin) {
        throw new RuntimeException('No admin user found');
    }
    set_script_user($admin);
    require_capability('moodle/site:config', context_system::instance());

    $programowner = get_user_or_throw($programownerusername);
    $trainer = get_user_or_throw($trainerusername);
    $learner = get_user_or_throw($learnerusername);
    log_check($results, 'AHW-AT-01', true, 'Required mock users exist');

    $category = find_allied_health_category($categoryidnumber);
    log_check(
        $results,
        'AHW-AT-02',
        (bool)$category,
        $category ? ('Category found: ' . $category->name . ' (id=' . $category->id . ')') : 'Missing Allied Health category'
    );
    if (!$category) {
        fail_exit($results, 'Category not found');
    }

    $days = discover_day_folders($contentroot);
    log_check($results, 'AHW-AT-03', count($days) > 0, 'Discovered day folders: ' . count($days));
    if (count($days) === 0) {
        fail_exit($results, 'No day folders found in content root');
    }

    $course = ensure_course_for_program_owner($category, $programowner, $courseshortname, $coursefullname);
    log_check($results, 'AHW-AT-04', (int)$course->id > 0, 'Course ready: id=' . $course->id . ' shortname=' . $course->shortname);
    $poresourcecap = has_capability('mod/resource:addinstance', context_course::instance((int)$course->id), $programowner);
    log_check(
        $results,
        'AHW-AT-04A',
        $poresourcecap,
        $poresourcecap ? 'Program Owner can add resources' : 'Program Owner lacks mod/resource:addinstance'
    );

    $samplequizcmid = 0;
    $samplecontentcmid = 0;
    $sampletrainercmid = 0;
    $quizcount = 0;
    $resourcecount = 0;

    $sectionnum = 1;
    foreach ($days as $day) {
        ensure_section_name($course, $sectionnum, $day['name']);

        $contentfile = first_file_in_dir($day['path'] . '/content');
        if ($contentfile) {
            $contentcmid = ensure_hidden_resource_for_day($course, $programowner, $sectionnum, $day['name'], 'content', $contentfile);
            $resourcecount++;
            if ($samplecontentcmid === 0) {
                $samplecontentcmid = $contentcmid;
            }
        }

        $lessonfile = first_file_in_dir($day['path'] . '/lesson_plan');
        if ($lessonfile) {
            $lessoncmid = ensure_hidden_resource_for_day($course, $programowner, $sectionnum, $day['name'], 'lesson_plan', $lessonfile);
            $resourcecount++;
            if ($sampletrainercmid === 0) {
                $sampletrainercmid = $lessoncmid;
            }
        }

        $roleplayfile = first_file_in_dir($day['path'] . '/roleplay');
        if ($roleplayfile) {
            ensure_hidden_resource_for_day($course, $programowner, $sectionnum, $day['name'], 'roleplay', $roleplayfile);
            $resourcecount++;
        }

        $quizcsv = get_first_csv_from_quiz_dir($day['path']);
        $hascsv = $quizcsv !== null;
        log_check($results, 'AHW-AT-05-' . $sectionnum, $hascsv, "Day '{$day['name']}' has quiz CSV");
        if ($hascsv) {
            $csvrows = parse_csv_rows_assoc($quizcsv);
            log_check($results, 'AHW-AT-06-' . $sectionnum, count($csvrows) > 0, basename($quizcsv) . ' contains ' . count($csvrows) . ' question rows');

            if (!empty($csvrows)) {
                $quizmeta = ensure_hidden_quiz_for_day($course, $programowner, $sectionnum, $day['name'], $quizcsv, count($csvrows));
                $warnings = [];
                $slotcount = import_quiz_questions_from_csv($course, (int)$quizmeta['cmid'], (int)$quizmeta['quizid'], $quizcsv, $warnings);
                $quizcount++;
                if ($samplequizcmid === 0) {
                    $samplequizcmid = (int)$quizmeta['cmid'];
                }
                log_check($results, 'AHW-AT-07-' . $sectionnum, $slotcount > 0, "Quiz ready for '{$day['name']}' (cmid={$quizmeta['cmid']} slots={$slotcount})");
                if (!empty($warnings)) {
                    log_check($results, 'AHW-AT-07W-' . $sectionnum, true, 'Quiz import warnings: ' . implode('; ', $warnings));
                }
            }
        }

        $sectionnum++;
    }

    log_check($results, 'AHW-AT-08', $quizcount > 0, 'At least one day quiz created/updated with questions');
    log_check($results, 'AHW-AT-09', $resourcecount > 0, 'Day resources added/updated: ' . $resourcecount);

    if ($samplequizcmid === 0 || $samplecontentcmid === 0 || $sampletrainercmid === 0) {
        fail_exit($results, 'Missing sample modules for visibility assertions');
    }

    ensure_manual_enrolment($course, (int)$trainer->id, 'sceh_trainer');
    log_check($results, 'AHW-AT-10', true, 'Trainer enrolled with sceh_trainer role');

    $cohort = ensure_cohort($cohortidnumber, 'Mock Allied Cohort 2026');
    ensure_cohort_member((int)$cohort->id, (int)$learner->id);
    $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
    ensure_cohort_enrolment_instance($course, (int)$cohort->id, (int)$studentrole->id);
    sync_cohort_enrolments_for_course((int)$course->id);

    $learnerenrolled = is_enrolled(context_course::instance((int)$course->id), (int)$learner->id, '', true);
    log_check($results, 'AHW-AT-11', $learnerenrolled, 'Learner enrolled via cohort sync');

    // Program Owner must make the course visible for the learner to see it.
    set_script_user($programowner);
    update_course((object)['id' => $course->id, 'visible' => 1]);
    log_check($results, 'AHW-AT-11V', true, 'Program Owner set course to VISIBLE');

    $prequiz = get_cm_uservisible((int)$course->id, (int)$learner->id, $samplequizcmid);
    $precontent = get_cm_uservisible((int)$course->id, (int)$learner->id, $samplecontentcmid);
    $pretrainer = get_cm_uservisible((int)$course->id, (int)$learner->id, $sampletrainercmid);

    log_check($results, 'AHW-AT-12', $prequiz === false, "Learner cannot see hidden quiz before release (cmid={$samplequizcmid})");
    log_check($results, 'AHW-AT-13', $precontent === false, "Learner cannot see hidden content before release (cmid={$samplecontentcmid})");
    log_check($results, 'AHW-AT-14', $pretrainer === false, "Learner cannot see trainer-only lesson plan (cmid={$sampletrainercmid})");

    set_script_user($trainer);
    
    // Check if trainer has required capability
    $has_visibility_cap = has_capability(
        'moodle/course:activityvisibility',
        context_module::instance($samplequizcmid),
        $trainer
    );
    
    if (!$has_visibility_cap) {
        log_check(
            $results,
            'AHW-AT-14A',
            false,
            'Trainer lacks moodle/course:activityvisibility - run configure_trainer_visibility_permissions.php'
        );
        fail_exit($results, 'Missing trainer permissions - run: php scripts/config/configure_trainer_visibility_permissions.php');
    }
    
    require_capability('moodle/course:activityvisibility', context_module::instance($samplequizcmid));
    require_capability('moodle/course:activityvisibility', context_module::instance($samplecontentcmid));

    set_coursemodule_visible($samplequizcmid, 1);
    set_coursemodule_visible($samplecontentcmid, 1);

    log_check($results, 'AHW-AT-15', true, "Trainer released quiz and content (cmid={$samplequizcmid},{$samplecontentcmid})");

    $postquiz = get_cm_uservisible((int)$course->id, (int)$learner->id, $samplequizcmid);
    $postcontent = get_cm_uservisible((int)$course->id, (int)$learner->id, $samplecontentcmid);
    $posttrainer = get_cm_uservisible((int)$course->id, (int)$learner->id, $sampletrainercmid);

    log_check($results, 'AHW-AT-16', $postquiz === true, "Learner can see quiz after release (cmid={$samplequizcmid})");
    log_check($results, 'AHW-AT-17', $postcontent === true, "Learner can see content after release (cmid={$samplecontentcmid})");
    log_check($results, 'AHW-AT-18', $posttrainer === false, "Learner still cannot see trainer lesson plan (cmid={$sampletrainercmid})");

} catch (Throwable $e) {
    log_check($results, 'AHW-AT-99', false, $e->getMessage());
}

$total = count($results);
$failed = count(array_filter($results, static function(array $result): bool {
    return !$result['pass'];
}));
$passed = $total - $failed;

echo "\n=== Summary ===\n";
echo "TOTAL\t{$total}\n";
echo "PASSED\t{$passed}\n";
echo "FAILED\t{$failed}\n";

exit($failed > 0 ? 1 : 0);
