<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Manifest execution utilities for SCEH package importer.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Executes manifest activities into a target course.
 */
class import_executor {
    /**
     * Execute import actions for the given manifest.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $extractdir
     * @param array $manifest
     * @return array{created:array,skipped:array,replaced:array,warnings:array}
     */
    public function execute(int $courseid, int $userid, string $extractdir, array $manifest): array {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/lib/resourcelib.php');
        require_once($CFG->dirroot . '/lib/questionlib.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/format/gift/format.php');
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/classes/quiz_settings.php');
        require_once($CFG->dirroot . '/mod/quiz/classes/grade_calculator.php');

        $mode = (string)($manifest['import']['mode'] ?? 'upsert');
        if ($mode === 'replace') {
            throw new \moodle_exception('error_replace_notsupported', 'local_sceh_importer');
        }

        $course = get_course($courseid);
        $sectionmap = $this->ensure_sections($course, $manifest['sections'] ?? []);
        $topicmap = $this->index_topics($manifest['topics'] ?? []);
        $renderedtopics = [];

        $result = [
            'created' => [],
            'skipped' => [],
            'replaced' => [],
            'warnings' => [],
        ];
        $this->persist_program_linkage($courseid, $userid, $manifest);

        foreach ($manifest['activities'] ?? [] as $activity) {
            $idnumber = (string)($activity['idnumber'] ?? '');
            if ($idnumber === '') {
                $result['warnings'][] = 'Skipped activity with missing idnumber.';
                continue;
            }

            $existingcm = $this->find_course_module_by_idnumber($courseid, $idnumber);
            if ($existingcm) {
                if ($mode === 'assert') {
                    throw new \moodle_exception('error_assert_conflict', 'local_sceh_importer', '', $idnumber);
                }
                if ($mode === 'upsert'
                    && $existingcm->modulename === 'quiz'
                    && (($activity['quiz_source']['format'] ?? '') === 'inline')) {
                    $moduleinfo = (object)[
                        'coursemodule' => (int)$existingcm->id,
                        'instance' => (int)$existingcm->instance,
                    ];
                    $added = $this->import_inline_quiz_questions($course, $moduleinfo, $activity, $result['warnings']);
                    if ($added > 0) {
                        $result['created'][] = $idnumber . ' (added ' . $added . ' questions)';
                        continue;
                    }
                }
                $result['skipped'][] = $idnumber . ' (already exists)';
                continue;
            }

            $sectionidnumber = (string)($activity['section_idnumber'] ?? 'SEC-COMMON');
            $sectionnum = $sectionmap[$sectionidnumber] ?? 0;

            $topicidnumber = trim((string)($activity['topic_idnumber'] ?? ''));
            if ($topicidnumber !== '' && empty($renderedtopics[$topicidnumber])) {
                $topic = $topicmap[$topicidnumber] ?? null;
                if ($topic === null) {
                    $result['warnings'][] = 'Activity ' . $idnumber . ' references unknown topic ' . $topicidnumber . '.';
                } else {
                    $topicsectionidnumber = (string)($topic['section_idnumber'] ?? $sectionidnumber);
                    $topicsectionnum = $sectionmap[$topicsectionidnumber] ?? $sectionnum;
                    $this->ensure_topic_label($course, $topic, $topicsectionnum, $mode);
                    $renderedtopics[$topicidnumber] = true;
                }
            }

            $cm = $this->create_activity($course, $userid, $extractdir, $activity, $sectionnum, $result['warnings']);
            if ($cm !== null) {
                if (!empty($activity['archive_existing_activity']['cmid'])) {
                    $archivecmid = (int)$activity['archive_existing_activity']['cmid'];
                    $archiveidnumber = (string)($activity['archive_existing_activity']['idnumber'] ?? '');
                    $archivemodname = (string)($activity['archive_existing_activity']['modname'] ?? '');
                    if ($this->archive_existing_activity($archivecmid, $archiveidnumber, $archivemodname, $result['warnings'])) {
                        $label = $archiveidnumber !== '' ? $archiveidnumber : ('cmid ' . $archivecmid);
                        if ($archivemodname !== '') {
                            $label .= ' (' . $archivemodname . ')';
                        }
                        $result['replaced'][] = $label;
                    }
                }
                $result['created'][] = $idnumber . ' (cmid ' . $cm->id . ')';
            }
        }

        rebuild_course_cache($courseid, true);

        return $result;
    }

    /**
     * Persist program-to-course linkage for grouped program queries.
     *
     * @param int $courseid
     * @param int $userid
     * @param array $manifest
     * @return void
     */
    private function persist_program_linkage(int $courseid, int $userid, array $manifest): void {
        global $DB;

        $programidnumber = trim((string)($manifest['program_idnumber'] ?? ''));
        if ($programidnumber === '') {
            return;
        }
        $programname = trim((string)($manifest['program_name'] ?? ''));

        $existing = $DB->get_record('local_sceh_importer_prog', ['courseid' => $courseid]);
        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->programidnumber = $programidnumber;
        $record->programname = $programname;
        $record->timemodified = time();
        $record->usermodified = $userid;
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_sceh_importer_prog', $record);
            return;
        }
        $DB->insert_record('local_sceh_importer_prog', $record);
    }

    /**
     * Ensure all manifest sections exist and return section-idnumber to section number mapping.
     *
     * @param \stdClass $course
     * @param array $sections
     * @return array<string, int>
     */
    private function ensure_sections(\stdClass $course, array $sections): array {
        global $DB;

        $map = [];
        $existing = $DB->get_records('course_sections', ['course' => $course->id], '', 'id,section,name');

        foreach ($sections as $section) {
            $idnumber = (string)($section['idnumber'] ?? '');
            $name = trim((string)($section['name'] ?? ''));
            if ($idnumber === '') {
                continue;
            }

            if ($name !== '') {
                $normalizedname = \core_text::strtolower($name);
                foreach ($existing as $row) {
                    if (\core_text::strtolower(trim((string)$row->name)) === $normalizedname) {
                        $map[$idnumber] = (int)$row->section;
                        continue 2;
                    }
                }
            }

            $nextsection = (int)$DB->get_field_sql('SELECT COALESCE(MAX(section), 0) + 1 FROM {course_sections} WHERE course = :course', [
                'course' => $course->id,
            ]);
            course_create_sections_if_missing($course, [$nextsection]);

            $newsection = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $nextsection], '*', MUST_EXIST);
            $newsection->name = $name !== '' ? $name : $idnumber;
            $DB->update_record('course_sections', $newsection);

            $map[$idnumber] = $nextsection;
            $existing[$newsection->id] = $newsection;
        }

        return $map;
    }

    /**
     * Index topic definitions by idnumber.
     *
     * @param array $topics
     * @return array<string, array>
     */
    private function index_topics(array $topics): array {
        $indexed = [];
        foreach ($topics as $topic) {
            $idnumber = trim((string)($topic['idnumber'] ?? ''));
            if ($idnumber === '') {
                continue;
            }
            $indexed[$idnumber] = $topic;
        }
        return $indexed;
    }

    /**
     * Ensure a topic marker exists as a label module in the section.
     *
     * @param \stdClass $course
     * @param array $topic
     * @param int $sectionnum
     * @param string $mode
     * @return string created|skipped
     */
    private function ensure_topic_label(\stdClass $course, array $topic, int $sectionnum, string $mode): string {
        $topicidnumber = trim((string)($topic['idnumber'] ?? ''));
        if ($topicidnumber === '') {
            return 'skipped';
        }

        $existing = $this->find_course_module_by_idnumber((int)$course->id, $topicidnumber);
        if ($existing) {
            if ($mode === 'assert') {
                throw new \moodle_exception('error_assert_conflict', 'local_sceh_importer', '', $topicidnumber);
            }
            return 'skipped';
        }

        $moduleinfo = $this->build_topic_label_moduleinfo($course, $topic, $sectionnum);
        add_moduleinfo($moduleinfo, $course, null);
        return 'created';
    }

    /**
     * Create a single activity.
     *
     * @param \stdClass $course
     * @param int $userid
     * @param string $extractdir
     * @param array $activity
     * @param int $sectionnum
     * @param array $warnings
     * @return \stdClass|null
     */
    private function create_activity(\stdClass $course, int $userid, string $extractdir, array $activity, int $sectionnum, array &$warnings): ?\stdClass {
        $type = (string)($activity['type'] ?? '');

        if (in_array($type, ['resource', 'lesson_plan', 'roleplay_assessment'], true)) {
            $moduleinfo = $this->build_resource_moduleinfo($course, $userid, $extractdir, $activity, $sectionnum);
            return add_moduleinfo($moduleinfo, $course, null);
        }

        if ($type === 'assignment') {
            $moduleinfo = $this->build_assignment_moduleinfo($course, $activity, $sectionnum);
            return add_moduleinfo($moduleinfo, $course, null);
        }

        if ($type === 'quiz') {
            $moduleinfo = $this->build_quiz_moduleinfo($course, $activity, $sectionnum);
            $cm = add_moduleinfo($moduleinfo, $course, null);
            if (($activity['quiz_source']['format'] ?? '') === 'inline') {
                $created = $this->import_inline_quiz_questions($course, $cm, $activity, $warnings);
                if ($created === 0) {
                    $warnings[] = ($activity['idnumber'] ?? 'quiz') . ': no inline questions imported.';
                }
            }
            return $cm;
        }

        $warnings[] = ($activity['idnumber'] ?? 'unknown') . ': unsupported activity type ' . $type;
        return null;
    }

    /**
     * Build moduleinfo for resource-like activities.
     *
     * @param \stdClass $course
     * @param int $userid
     * @param string $extractdir
     * @param array $activity
     * @param int $sectionnum
     * @return \stdClass
     */
    private function build_resource_moduleinfo(\stdClass $course, int $userid, string $extractdir, array $activity, int $sectionnum): \stdClass {
        global $DB;

        $moduleinfo = $this->build_common_moduleinfo($course, $activity, $sectionnum, 'resource');
        $moduleinfo->display = RESOURCELIB_DISPLAY_AUTO;
        $moduleinfo->printintro = 1;
        $moduleinfo->showsize = 1;
        $moduleinfo->showtype = 1;

        $relativefile = (string)($activity['file'] ?? '');
        if ($relativefile === '') {
            throw new \moodle_exception('error_missingactivityfile', 'local_sceh_importer', '', (string)($activity['idnumber'] ?? 'unknown'));
        }
        $sourcepath = $extractdir . '/' . ltrim($relativefile, '/');
        if (!is_readable($sourcepath)) {
            throw new \moodle_exception('error_missingfilepath', 'local_sceh_importer', '', $relativefile);
        }

        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($userid);
        $filerecord = [
            'component' => 'user',
            'filearea' => 'draft',
            'contextid' => $usercontext->id,
            'itemid' => $draftitemid,
            'filename' => basename($sourcepath),
            'filepath' => '/',
        ];
        $fs = get_file_storage();
        $fs->create_file_from_pathname($filerecord, $sourcepath);

        $moduleinfo->files = $draftitemid;
        $moduleinfo->uploaded = 1;

        $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);

        return $moduleinfo;
    }

    /**
     * Build moduleinfo for assignment.
     *
     * @param \stdClass $course
     * @param array $activity
     * @param int $sectionnum
     * @return \stdClass
     */
    private function build_assignment_moduleinfo(\stdClass $course, array $activity, int $sectionnum): \stdClass {
        global $DB;

        $moduleinfo = $this->build_common_moduleinfo($course, $activity, $sectionnum, 'assign');

        $defaults = [
            'alwaysshowdescription' => 1,
            'submissiondrafts' => 1,
            'requiresubmissionstatement' => 0,
            'sendnotifications' => 0,
            'sendstudentnotifications' => 1,
            'sendlatenotifications' => 0,
            'duedate' => 0,
            'allowsubmissionsfromdate' => 0,
            'grade' => 100,
            'cutoffdate' => 0,
            'gradingduedate' => 0,
            'teamsubmission' => !empty($activity['group_submission']) ? 1 : 0,
            'requireallteammemberssubmit' => 0,
            'teamsubmissiongroupingid' => 0,
            'blindmarking' => 0,
            'attemptreopenmethod' => 'untilpass',
            'maxattempts' => 1,
            'markingworkflow' => 0,
            'markingallocation' => 0,
            'markinganonymous' => 0,
            'activityformat' => 0,
            'timelimit' => 0,
            'submissionattachments' => 1,
        ];
        foreach ($defaults as $key => $value) {
            $moduleinfo->{$key} = $value;
        }

        $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST);
        return $moduleinfo;
    }

    /**
     * Build moduleinfo for quiz shell.
     *
     * @param \stdClass $course
     * @param array $activity
     * @param int $sectionnum
     * @return \stdClass
     */
    private function build_quiz_moduleinfo(\stdClass $course, array $activity, int $sectionnum): \stdClass {
        global $DB;

        $moduleinfo = $this->build_common_moduleinfo($course, $activity, $sectionnum, 'quiz');

        $defaults = [
            'timeopen' => 0,
            'timeclose' => 0,
            'preferredbehaviour' => 'deferredfeedback',
            'attempts' => 0,
            'attemptonlast' => 0,
            'grademethod' => QUIZ_GRADEHIGHEST,
            'decimalpoints' => 2,
            'questiondecimalpoints' => -1,
            'attemptduring' => 1,
            'correctnessduring' => 1,
            'maxmarksduring' => 1,
            'marksduring' => 1,
            'specificfeedbackduring' => 1,
            'generalfeedbackduring' => 1,
            'rightanswerduring' => 1,
            'overallfeedbackduring' => 0,
            'attemptimmediately' => 1,
            'correctnessimmediately' => 1,
            'maxmarksimmediately' => 1,
            'marksimmediately' => 1,
            'specificfeedbackimmediately' => 1,
            'generalfeedbackimmediately' => 1,
            'rightanswerimmediately' => 1,
            'overallfeedbackimmediately' => 1,
            'attemptopen' => 1,
            'correctnessopen' => 1,
            'maxmarksopen' => 1,
            'marksopen' => 1,
            'specificfeedbackopen' => 1,
            'generalfeedbackopen' => 1,
            'rightansweropen' => 1,
            'overallfeedbackopen' => 1,
            'attemptclosed' => 1,
            'correctnessclosed' => 1,
            'maxmarksclosed' => 1,
            'marksclosed' => 1,
            'specificfeedbackclosed' => 1,
            'generalfeedbackclosed' => 1,
            'rightanswerclosed' => 1,
            'overallfeedbackclosed' => 1,
            'questionsperpage' => 1,
            'shuffleanswers' => 1,
            'sumgrades' => 0,
            'grade' => 100,
            'timelimit' => 0,
            'overduehandling' => 'autosubmit',
            'graceperiod' => 86400,
            'quizpassword' => '',
            'subnet' => '',
            'browsersecurity' => '',
            'delay1' => 0,
            'delay2' => 0,
            'showuserpicture' => 0,
            'showblocks' => 0,
            'navmethod' => QUIZ_NAVMETHOD_FREE,
        ];
        foreach ($defaults as $key => $value) {
            $moduleinfo->{$key} = $value;
        }

        $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST);

        return $moduleinfo;
    }

    /**
     * Build common moduleinfo fields.
     *
     * @param \stdClass $course
     * @param array $activity
     * @param int $sectionnum
     * @param string $modulename
     * @return \stdClass
     */
    private function build_common_moduleinfo(\stdClass $course, array $activity, int $sectionnum, string $modulename): \stdClass {
        $moduleinfo = new \stdClass();
        $moduleinfo->course = (int)$course->id;
        $moduleinfo->modulename = $modulename;
        $moduleinfo->name = (string)($activity['title'] ?? 'Untitled');
        $moduleinfo->intro = '';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->section = $sectionnum;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->completion = 0;
        $moduleinfo->cmidnumber = (string)($activity['idnumber'] ?? '');

        return $moduleinfo;
    }

    /**
     * Build a label module for topic headings.
     *
     * @param \stdClass $course
     * @param array $topic
     * @param int $sectionnum
     * @return \stdClass
     */
    private function build_topic_label_moduleinfo(\stdClass $course, array $topic, int $sectionnum): \stdClass {
        global $DB;

        $topicname = trim((string)($topic['name'] ?? 'Topic'));
        $moduleinfo = new \stdClass();
        $moduleinfo->course = (int)$course->id;
        $moduleinfo->modulename = 'label';
        $moduleinfo->name = $topicname;
        $moduleinfo->intro = '<h4>' . s($topicname) . '</h4>';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->section = $sectionnum;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->completion = 0;
        $moduleinfo->cmidnumber = trim((string)($topic['idnumber'] ?? ''));
        $moduleinfo->module = $DB->get_field('modules', 'id', ['name' => 'label'], MUST_EXIST);
        return $moduleinfo;
    }

    /**
     * Find existing course module by cm.idnumber.
     *
     * @param int $courseid
     * @param string $idnumber
     * @return \stdClass|null
     */
    private function find_course_module_by_idnumber(int $courseid, string $idnumber): ?\stdClass {
        global $DB;

        $sql = "SELECT cm.id, cm.instance, m.name AS modulename
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.course = :courseid
                   AND cm.idnumber = :idnumber";

        return $DB->get_record_sql($sql, [
            'courseid' => $courseid,
            'idnumber' => $idnumber,
        ]) ?: null;
    }

    /**
     * Archive an existing activity module before importing a versioned replacement.
     *
     * @param int $cmid
     * @param string $originalidnumber
     * @param string $originalmodname
     * @param array $warnings
     * @return bool
     */
    private function archive_existing_activity(int $cmid, string $originalidnumber, string $originalmodname, array &$warnings): bool {
        global $DB;

        if ($cmid <= 0) {
            return false;
        }

        $cm = $DB->get_record_sql(
            "SELECT cm.id, cm.instance, m.name AS modulename
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.id = :cmid",
            ['cmid' => $cmid]
        );
        if (!$cm) {
            return false;
        }

        set_coursemodule_visible((int)$cm->id, 0);

        $modname = (string)$cm->modulename;
        $modtable = $this->resolve_module_table_for_name($modname);
        if ($modtable !== null) {
            $record = $DB->get_record($modtable, ['id' => (int)$cm->instance], 'id,name');
            if ($record && isset($record->name)) {
                $archivedprefix = '[Archived ' . userdate(time(), '%Y-%m-%d') . '] ';
                $currentname = trim((string)$record->name);
                if (strpos($currentname, $archivedprefix) !== 0) {
                    $newname = $archivedprefix . $currentname;
                    if (\core_text::strlen($newname) > 255) {
                        $newname = \core_text::substr($newname, 0, 255);
                    }
                    $record->name = $newname;
                    $DB->update_record($modtable, $record);
                }
            }
        }

        $label = $originalidnumber !== '' ? $originalidnumber : ('cmid ' . $cmid);
        $displaymodname = $originalmodname !== '' ? $originalmodname : $modname;
        $warnings[] = $label . ': archived existing ' . $displaymodname . ' before importing new version.';
        return true;
    }

    /**
     * Resolve Moodle activity module name to its instance table.
     *
     * @param string $modname
     * @return string|null
     */
    private function resolve_module_table_for_name(string $modname): ?string {
        $map = [
            'quiz' => 'quiz',
            'resource' => 'resource',
            'assign' => 'assign',
        ];
        return $map[$modname] ?? null;
    }

    /**
     * Import inline quiz rows by converting them to GIFT and attaching them to the quiz.
     *
     * @param \stdClass $course
     * @param \stdClass $cm
     * @param array $activity
     * @param array $warnings
     * @return int
     */
    private function import_inline_quiz_questions(\stdClass $course, \stdClass $moduleinfo, array $activity, array &$warnings): int {
        global $DB;

        $rows = $activity['quiz_source']['rows'] ?? [];
        if (empty($rows) || !is_array($rows)) {
            return 0;
        }

        $giftlines = [];
        foreach ($rows as $index => $row) {
            $gift = $this->build_gift_question((array)$row, $index + 1, $warnings);
            if ($gift !== null) {
                $giftlines[] = $gift;
            }
        }

        if (empty($giftlines)) {
            return 0;
        }

        $giftcontent = implode("\n\n", $giftlines) . "\n";
        $tmpdir = make_request_directory();
        $giftpath = $tmpdir . '/inline_quiz_' . time() . '.gift';
        if (file_put_contents($giftpath, $giftcontent) === false) {
            $warnings[] = ($activity['idnumber'] ?? 'quiz') . ': unable to write temporary quiz file.';
            return 0;
        }

        if (empty($moduleinfo->coursemodule) || empty($moduleinfo->instance)) {
            $warnings[] = ($activity['idnumber'] ?? 'quiz') . ': created quiz is missing coursemodule/instance references.';
            return 0;
        }

        $quizrecord = $DB->get_record('quiz', ['id' => (int)$moduleinfo->instance], '*', MUST_EXIST);
        $existingcount = (int)$DB->count_records('quiz_slots', ['quizid' => (int)$moduleinfo->instance]);
        if ($existingcount > 0) {
            if ((float)$quizrecord->sumgrades <= 0.0) {
                $this->recompute_quiz_sumgrades((int)$quizrecord->id);
                $warnings[] = ($activity['idnumber'] ?? 'quiz') . ': quiz already had questions; recomputed quiz grades.';
            }
            $warnings[] = ($activity['idnumber'] ?? 'quiz') . ': quiz already has questions, skipped inline import.';
            return 0;
        }

        $context = \context_module::instance((int)$moduleinfo->coursemodule);
        $category = question_get_default_category($context->id, true);
        if (empty($category)) {
            $warnings[] = ($activity['idnumber'] ?? 'quiz') . ': unable to resolve question category.';
            return 0;
        }

        $qformat = new \qformat_gift();
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
            $warnings[] = ($activity['idnumber'] ?? 'quiz') . ': failed to import inline questions.';
            return 0;
        }

        if (empty($qformat->questionids)) {
            return 0;
        }

        $beforecount = (int)$DB->count_records('quiz_slots', ['quizid' => (int)$quizrecord->id]);
        foreach ($qformat->questionids as $questionid) {
            quiz_add_quiz_question((int)$questionid, $quizrecord, 0);
        }
        $aftercount = (int)$DB->count_records('quiz_slots', ['quizid' => (int)$quizrecord->id]);
        $added = max(0, $aftercount - $beforecount);
        if ($added > 0) {
            $this->recompute_quiz_sumgrades((int)$quizrecord->id);
        }

        return $added;
    }

    /**
     * Recompute quiz sumgrades from current slots.
     *
     * @param int $quizid
     * @return void
     */
    private function recompute_quiz_sumgrades(int $quizid): void {
        $quizobj = \mod_quiz\quiz_settings::create($quizid);
        $gradecalculator = \mod_quiz\grade_calculator::create($quizobj);
        $gradecalculator->recompute_quiz_sumgrades();
    }

    /**
     * Build one GIFT question from an inline row.
     *
     * @param array $row
     * @param int $position
     * @param array $warnings
     * @return string|null
     */
    private function build_gift_question(array $row, int $position, array &$warnings): ?string {
        $qid = trim((string)($row['question_id'] ?? 'Q' . $position));
        $qtype = strtolower(trim((string)($row['question_type'] ?? '')));
        $qtext = trim((string)($row['question_text'] ?? ''));
        $correct = trim((string)($row['correct_option'] ?? ''));

        if ($qtext === '' || $correct === '') {
            $warnings[] = $qid . ': missing question_text or correct_option.';
            return null;
        }

        $title = $this->escape_gift_text($qid);
        $question = $this->escape_gift_text($qtext);
        if (in_array($qtype, ['mcq', 'multichoice', 'singlechoice'], true)) {
            $options = [
                'A' => trim((string)($row['option_a'] ?? '')),
                'B' => trim((string)($row['option_b'] ?? '')),
                'C' => trim((string)($row['option_c'] ?? '')),
                'D' => trim((string)($row['option_d'] ?? '')),
                'E' => trim((string)($row['option_e'] ?? '')),
            ];
            $normalizedcorrect = strtoupper($correct);
            if (!array_key_exists($normalizedcorrect, $options) || $options[$normalizedcorrect] === '') {
                foreach ($options as $label => $optiontext) {
                    if ($optiontext !== '' && strcasecmp($optiontext, $correct) === 0) {
                        $normalizedcorrect = $label;
                        break;
                    }
                }
            }
            if (!array_key_exists($normalizedcorrect, $options) || $options[$normalizedcorrect] === '') {
                $warnings[] = $qid . ': correct option does not match provided options.';
                return null;
            }

            $answers = [];
            foreach ($options as $label => $optiontext) {
                if ($optiontext === '') {
                    continue;
                }
                $prefix = ($label === $normalizedcorrect) ? '=' : '~';
                $answers[] = $prefix . $this->escape_gift_text($optiontext);
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
            $warnings[] = $qid . ': invalid true/false correct option.';
            return null;
        }

        if (in_array($qtype, ['short_answer', 'shortanswer', 'sa'], true)) {
            return '::' . $title . ':: ' . $question . ' {=' . $this->escape_gift_text($correct) . '}';
        }

        $warnings[] = $qid . ': unsupported question type ' . $qtype . ' (skipped).';
        return null;
    }

    /**
     * Escape GIFT control characters.
     *
     * @param string $text
     * @return string
     */
    private function escape_gift_text(string $text): string {
        $replacements = [
            '\\\\' => '\\\\\\\\',
            '{' => '\\{',
            '}' => '\\}',
            '~' => '\\~',
            '=' => '\\=',
            '#' => '\\#',
            ':' => '\\:',
        ];
        return strtr($text, $replacements);
    }
}
