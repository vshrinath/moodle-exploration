<?php
defined('MOODLE_INTERNAL') || die();

class block_sceh_dashboard extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_sceh_dashboard');
    }
    
    public function get_content() {
        global $USER, $PAGE;
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass();
        $this->content->text = '';

        // Reuse shared card styles for workflow queue rendering.
        $PAGE->requires->css(new moodle_url('/local/sceh_rules/styles/sceh_card_system.css'));
        
        // Get user role-based cards
        $cards = $this->get_dashboard_cards();
        
        // Render cards
        $this->content->text .= html_writer::start_div('sceh-dashboard-grid');
        
        foreach ($cards as $card) {
            $this->content->text .= $this->render_card($card);
        }

        $this->content->text .= html_writer::end_div();
        $this->content->text .= $this->render_workflow_queue($USER->id);
        
        return $this->content;
    }

    /**
     * Render role-based workflow queue (Do Now / This Week / Watchlist).
     *
     * @param int $userid
     * @return string
     */
    private function render_workflow_queue($userid) {
        if (!class_exists('\local_sceh_rules\output\sceh_card')) {
            return '';
        }

        $queue = $this->get_workflow_queue_items($userid);
        if (empty($queue)) {
            return '';
        }

        $html = html_writer::start_div('sceh-workflow-queue mt-4');
        $html .= html_writer::tag('h4', get_string('workflowqueue', 'block_sceh_dashboard'));
        $html .= html_writer::div(get_string('workflowqueuedesc', 'block_sceh_dashboard'), 'text-muted mb-3');
        $html .= html_writer::start_div('row');

        foreach ($queue as $bucket) {
            $items = [];
            foreach ($bucket['items'] as $item) {
                $items[] = [
                    'icon' => $item['icon'],
                    'text' => $item['text'],
                    'subtext' => $item['subtext'],
                    'actions' => [
                        [
                            'text' => get_string('open', 'block_sceh_dashboard'),
                            'url' => $item['url'],
                            'style' => 'secondary',
                        ],
                    ],
                ];
            }

            if (empty($items)) {
                $items[] = [
                    'icon' => 'fa-circle-check',
                    'text' => get_string('workflowempty', 'block_sceh_dashboard'),
                    'subtext' => get_string('workflowemptydesc', 'block_sceh_dashboard'),
                ];
            }

            $html .= html_writer::start_div('col-12 col-xl-4 mb-3');
            $html .= \local_sceh_rules\output\sceh_card::list([
                'title' => $bucket['title'],
                'icon' => $bucket['icon'],
                'status' => $bucket['status'],
                'status_text' => $bucket['statustext'],
                'count' => count($bucket['items']),
                'items' => $items,
                'size' => 'medium',
            ]);
            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Build workflow queue buckets for current user role.
     *
     * @param int $userid
     * @return array
     */
    private function get_workflow_queue_items($userid) {
        $context = context_system::instance();
        
        // Re-verify capabilities for defense-in-depth
        $is_system_admin = has_capability('local/sceh_rules:systemadmin', $context);
        $is_program_owner = has_capability('local/sceh_rules:programowner', $context);
        $is_trainer = has_capability('local/sceh_rules:trainer', $context);

        if ($is_system_admin) {
            return $this->build_system_admin_queue($userid);
        } else if ($is_program_owner) {
            return $this->build_program_owner_queue($userid);
        } else if ($is_trainer) {
            return $this->build_trainer_queue($userid);
        }

        return $this->build_learner_queue($userid);
    }

    /**
     * Queue for system admin workflow.
     *
     * @param int $userid
     * @return array
     */
    private function build_system_admin_queue($userid) {
        $failedtasks = $this->count_failed_scheduled_tasks();
        $overdueevents = $this->count_user_overdue_events($userid);
        $upcomingevents = $this->count_user_upcoming_events($userid, 7);

        return [
            [
                'title' => get_string('workflownow', 'block_sceh_dashboard'),
                'icon' => 'fa-bolt',
                'status' => ($failedtasks + $overdueevents) > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatusnow', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-gears',
                        get_string('workflowfailedtasks', 'block_sceh_dashboard', $failedtasks),
                        get_string('workflowfailedtasksdesc', 'block_sceh_dashboard'),
                        new moodle_url('/admin/tool/task/scheduledtasks.php'),
                        $failedtasks > 0
                    ),
                    $this->workflow_item(
                        'fa-triangle-exclamation',
                        get_string('workflowoverdueevents', 'block_sceh_dashboard', $overdueevents),
                        get_string('workflowoverdueeventsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/calendar/view.php'),
                        $overdueevents > 0
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowweek', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-week',
                'status' => 'info',
                'statustext' => get_string('workflowstatusweek', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-users',
                        get_string('workflowmanagecohorts', 'block_sceh_dashboard'),
                        get_string('workflowcohortcount', 'block_sceh_dashboard', $this->count_total_cohorts()),
                        new moodle_url('/cohort/index.php'),
                        true
                    ),
                    $this->workflow_item(
                        'fa-calendar-day',
                        get_string('workflowupcomingevents', 'block_sceh_dashboard', $upcomingevents),
                        get_string('workflowupcomingeventsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/calendar/view.php'),
                        true
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowwatchlist', 'block_sceh_dashboard'),
                'icon' => 'fa-binoculars',
                'status' => $failedtasks > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatuswatchlist', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-chart-pie',
                        get_string('workflowevaluation', 'block_sceh_dashboard'),
                        get_string('workflowevaluationdesc', 'block_sceh_dashboard'),
                        new moodle_url('/local/kirkpatrick_dashboard/index.php'),
                        has_capability('local/kirkpatrick_dashboard:view', context_system::instance())
                    ),
                    $this->workflow_item(
                        'fa-file-lines',
                        get_string('workflowreports', 'block_sceh_dashboard'),
                        get_string('workflowreportsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/admin/category.php', ['category' => 'reports']),
                        true
                    ),
                ]),
            ],
        ];
    }

    /**
     * Queue for program owner workflow.
     *
     * @param int $userid
     * @return array
     */
    private function build_program_owner_queue($userid) {
        $streamissues = $this->count_program_owner_stream_issues($userid);
        $upcomingevents = $this->count_user_upcoming_events($userid, 7);
        $categorycount = count($this->get_program_owner_categories($userid));

        return [
            [
                'title' => get_string('workflownow', 'block_sceh_dashboard'),
                'icon' => 'fa-bolt',
                'status' => $streamissues > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatusnow', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-tasks',
                        get_string('workflowstreamissues', 'block_sceh_dashboard', $streamissues),
                        get_string('workflowstreamissuesdesc', 'block_sceh_dashboard'),
                        new moodle_url('/local/sceh_rules/stream_setup_check.php'),
                        true
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowweek', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-week',
                'status' => 'info',
                'statustext' => get_string('workflowstatusweek', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-folder-tree',
                        get_string('workflowassignedcategories', 'block_sceh_dashboard', $categorycount),
                        get_string('workflowassignedcategoriesdesc', 'block_sceh_dashboard'),
                        new moodle_url('/course/index.php'),
                        true
                    ),
                    $this->workflow_item(
                        'fa-calendar-day',
                        get_string('workflowupcomingevents', 'block_sceh_dashboard', $upcomingevents),
                        get_string('workflowupcomingeventsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/calendar/view.php'),
                        true
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowwatchlist', 'block_sceh_dashboard'),
                'icon' => 'fa-binoculars',
                'status' => $streamissues > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatuswatchlist', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-sitemap',
                        get_string('workflowcompetencyreview', 'block_sceh_dashboard'),
                        get_string('workflowcompetencyreviewdesc', 'block_sceh_dashboard'),
                        new moodle_url('/admin/tool/lp/competencyframeworks.php', [
                            'pagecontextid' => context_system::instance()->id,
                        ]),
                        true
                    ),
                    $this->workflow_item(
                        'fa-chart-column',
                        get_string('workflowreviewreports', 'block_sceh_dashboard'),
                        get_string('workflowreviewreportsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/admin/category.php', ['category' => 'reports']),
                        true
                    ),
                ]),
            ],
        ];
    }

    /**
     * Queue for trainer workflow.
     *
     * @param int $userid
     * @return array
     */
    private function build_trainer_queue($userid) {
        $courses = \local_sceh_rules\helper\cohort_filter::get_trainer_courses($userid);
        $coursecount = count($courses);
        $ungraded = $this->count_trainer_ungraded_submissions($userid);
        $overdueevents = $this->count_user_overdue_events($userid);

        return [
            [
                'title' => get_string('workflownow', 'block_sceh_dashboard'),
                'icon' => 'fa-bolt',
                'status' => ($ungraded + $overdueevents) > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatusnow', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-clipboard-check',
                        get_string('workflowungraded', 'block_sceh_dashboard', $ungraded),
                        get_string('workflowungradeddesc', 'block_sceh_dashboard'),
                        new moodle_url('/my/courses.php'),
                        true
                    ),
                    $this->workflow_item(
                        'fa-calendar-check',
                        get_string('workflowoverdueevents', 'block_sceh_dashboard', $overdueevents),
                        get_string('workflowoverdueeventsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/calendar/view.php'),
                        $overdueevents > 0
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowweek', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-week',
                'status' => 'info',
                'statustext' => get_string('workflowstatusweek', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-users',
                        get_string('workflowassignedcourses', 'block_sceh_dashboard', $coursecount),
                        get_string('workflowassignedcoursesdesc', 'block_sceh_dashboard'),
                        new moodle_url('/my/courses.php'),
                        true
                    ),
                    $this->workflow_item(
                        'fa-calendar-day',
                        get_string('workflowupcomingevents', 'block_sceh_dashboard', $this->count_user_upcoming_events($userid, 7)),
                        get_string('workflowupcomingeventsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/calendar/view.php'),
                        true
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowwatchlist', 'block_sceh_dashboard'),
                'icon' => 'fa-binoculars',
                'status' => $ungraded > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatuswatchlist', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-chart-line',
                        get_string('workflowstreamprogress', 'block_sceh_dashboard'),
                        get_string('workflowstreamprogressdesc', 'block_sceh_dashboard'),
                        new moodle_url('/local/sceh_rules/stream_progress.php'),
                        true
                    ),
                ]),
            ],
        ];
    }

    /**
     * Queue for learner workflow.
     *
     * @param int $userid
     * @return array
     */
    private function build_learner_queue($userid) {
        $pendingstream = $this->count_learner_pending_stream_selection($userid);
        $overdueevents = $this->count_user_overdue_events($userid);
        $upcomingevents = $this->count_user_upcoming_events($userid, 7);

        return [
            [
                'title' => get_string('workflownow', 'block_sceh_dashboard'),
                'icon' => 'fa-bolt',
                'status' => ($overdueevents + $pendingstream) > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatusnow', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-calendar-xmark',
                        get_string('workflowoverdueevents', 'block_sceh_dashboard', $overdueevents),
                        get_string('workflowoverdueeventsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/calendar/view.php'),
                        $overdueevents > 0
                    ),
                    $this->workflow_item(
                        'fa-code-branch',
                        get_string('workflowpendingstream', 'block_sceh_dashboard', $pendingstream),
                        get_string('workflowpendingstreamdesc', 'block_sceh_dashboard'),
                        new moodle_url('/local/sceh_rules/stream_progress.php'),
                        $pendingstream > 0
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowweek', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-week',
                'status' => 'info',
                'statustext' => get_string('workflowstatusweek', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-calendar-day',
                        get_string('workflowupcomingevents', 'block_sceh_dashboard', $upcomingevents),
                        get_string('workflowupcomingeventsdesc', 'block_sceh_dashboard'),
                        new moodle_url('/calendar/view.php'),
                        true
                    ),
                    $this->workflow_item(
                        'fa-chart-line',
                        get_string('workflowtrackprogress', 'block_sceh_dashboard'),
                        get_string('workflowtrackprogressdesc', 'block_sceh_dashboard'),
                        new moodle_url('/local/sceh_rules/stream_progress.php'),
                        true
                    ),
                ]),
            ],
            [
                'title' => get_string('workflowwatchlist', 'block_sceh_dashboard'),
                'icon' => 'fa-binoculars',
                'status' => $pendingstream > 0 ? 'warning' : 'success',
                'statustext' => get_string('workflowstatuswatchlist', 'block_sceh_dashboard'),
                'items' => array_filter([
                    $this->workflow_item(
                        'fa-award',
                        get_string('workflowbadges', 'block_sceh_dashboard'),
                        get_string('workflowbadgesdesc', 'block_sceh_dashboard'),
                        new moodle_url('/badges/mybadges.php'),
                        true
                    ),
                ]),
            ],
        ];
    }

    /**
     * Build a workflow item payload.
     *
     * @param string $icon
     * @param string $text
     * @param string $subtext
     * @param moodle_url $url
     * @param bool $enabled
     * @return array|null
     */
    private function workflow_item($icon, $text, $subtext, moodle_url $url, $enabled = true) {
        if (!$enabled) {
            return null;
        }

        return [
            'icon' => $icon,
            'text' => $text,
            'subtext' => $subtext,
            'url' => $url,
        ];
    }

    /**
     * Count failed scheduled tasks.
     *
     * @return int
     */
    private function count_failed_scheduled_tasks() {
        global $DB;
        return (int)$DB->count_records_select('task_scheduled', 'faildelay > 0');
    }

    /**
     * Count overdue events for a user.
     *
     * @param int $userid
     * @return int
     */
    private function count_user_overdue_events($userid) {
        global $DB;
        return (int)$DB->count_records_select(
            'event',
            'userid = :userid AND timestart > 0 AND timestart < :now',
            ['userid' => $userid, 'now' => time()]
        );
    }

    /**
     * Count upcoming events for a user in N days.
     *
     * @param int $userid
     * @param int $days
     * @return int
     */
    private function count_user_upcoming_events($userid, $days = 7) {
        global $DB;
        $now = time();
        $horizon = $now + ($days * DAYSECS);
        return (int)$DB->count_records_select(
            'event',
            'userid = :userid AND timestart >= :now AND timestart <= :horizon',
            ['userid' => $userid, 'now' => $now, 'horizon' => $horizon]
        );
    }

    /**
     * Count total cohorts.
     *
     * @return int
     */
    private function count_total_cohorts() {
        global $DB;
        return (int)$DB->count_records('cohort');
    }

    /**
     * Count stream setup issues in program-owner categories.
     *
     * @param int $userid
     * @return int
     */
    private function count_program_owner_stream_issues($userid) {
        global $DB;

        $categories = $this->get_program_owner_categories($userid);
        if (empty($categories)) {
            return 0;
        }

        $categoryids = array_map(function($category) {
            return (int)$category->id;
        }, $categories);
        list($catsql, $catparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');

        $courses = $DB->get_records_select('course', 'category ' . $catsql, $catparams, '', 'id');
        $issues = 0;

        foreach ($courses as $course) {
            $streamsections = \local_sceh_rules\helper\stream_helper::get_course_stream_sections($course->id);
            $haschoiceoptions = (bool)$DB->get_field_sql(
                "SELECT COUNT(co.id)
                   FROM {choice_options} co
                   JOIN {choice} c
                     ON c.id = co.choiceid
                  WHERE c.course = :courseid
                    AND (LOWER(c.name) LIKE :streamname OR LOWER(c.name) LIKE :specializationname)",
                [
                    'courseid' => $course->id,
                    'streamname' => '%stream%',
                    'specializationname' => '%specialization%',
                ]
            );

            if (empty($streamsections) || !$haschoiceoptions) {
                $issues++;
            }
        }

        return $issues;
    }

    /**
     * Count ungraded trainer submissions in assigned courses.
     *
     * @param int $userid
     * @return int
     */
    private function count_trainer_ungraded_submissions($userid) {
        global $DB;

        $courses = \local_sceh_rules\helper\cohort_filter::get_trainer_courses($userid);
        if (empty($courses)) {
            return 0;
        }

        $courseids = array_map(function($course) {
            return (int)$course->id;
        }, $courses);
        list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');

        $sql = "SELECT COUNT(s.id)
                  FROM {assign_submission} s
                  JOIN {assign} a
                    ON a.id = s.assignment
             LEFT JOIN {assign_grades} g
                    ON g.assignment = a.id
                   AND g.userid = s.userid
                 WHERE a.course {$insql}
                   AND s.latest = 1
                   AND s.status = :submitted
                   AND g.id IS NULL";

        $params['submitted'] = 'submitted';
        return (int)$DB->count_records_sql($sql, $params);
    }

    /**
     * Count learner courses with stream sections but no selected stream.
     *
     * @param int $userid
     * @return int
     */
    private function count_learner_pending_stream_selection($userid) {
        $courses = enrol_get_users_courses($userid, true, 'id');
        if (empty($courses)) {
            return 0;
        }

        $pending = 0;
        foreach ($courses as $course) {
            $streamsections = \local_sceh_rules\helper\stream_helper::get_course_stream_sections($course->id);
            if (empty($streamsections)) {
                continue;
            }

            $selected = \local_sceh_rules\helper\stream_helper::get_user_selected_stream($course->id, $userid);
            if (!$selected) {
                $pending++;
            }
        }

        return $pending;
    }
    
    private function get_dashboard_cards() {
        global $USER;
        
        $context = context_system::instance();
        
        // Check user roles via local_sceh_rules capability model.
        $is_system_admin = has_capability('local/sceh_rules:systemadmin', $context);
        $is_program_owner = has_capability('local/sceh_rules:programowner', $context);
        $is_trainer = has_capability('local/sceh_rules:trainer', $context);

        if ($is_system_admin) {
            return $this->get_system_admin_cards();
        } else if ($is_program_owner) {
            return $this->get_program_owner_cards($USER->id);
        } else if ($is_trainer) {
            return $this->get_trainer_cards($USER->id);
        }

        return $this->get_learner_cards($USER->id);
    }

    /**
     * Dashboard cards for learners.
     *
     * @param int $userid
     * @return array
     */
    private function get_learner_cards($userid) {
        $cards = [
            [
                'title' => get_string('caselogbook', 'block_sceh_dashboard'),
                'icon' => 'fa-clipboard-list',
                'color' => 'blue',
                'url' => new moodle_url('/my/courses.php'),
            ],
            [
                'title' => get_string('mycompetencies', 'block_sceh_dashboard'),
                'icon' => 'fa-check-circle',
                'color' => 'green',
                'url' => new moodle_url('/admin/tool/lp/plans.php', ['userid' => $userid]),
            ],
            [
                'title' => get_string('attendance', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-check',
                'color' => 'red',
                'url' => new moodle_url('/my/courses.php'),
            ],
            [
                'title' => get_string('mybadges', 'block_sceh_dashboard'),
                'icon' => 'fa-trophy',
                'color' => 'yellow',
                'url' => new moodle_url('/badges/mybadges.php'),
            ],
            [
                'title' => get_string('credentialingsheet', 'block_sceh_dashboard'),
                'icon' => 'fa-certificate',
                'color' => 'purple',
                'url' => new moodle_url('/my/courses.php'),
            ],
            [
                'title' => get_string('videolibrary', 'block_sceh_dashboard'),
                'icon' => 'fa-video',
                'color' => 'teal',
                'url' => new moodle_url('/course/index.php'),
            ],
            [
                'title' => get_string('myprogress', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-line',
                'color' => 'orange',
                'url' => new moodle_url('/local/sceh_rules/stream_progress.php'),
            ],
        ];

        $streamcard = $this->get_learner_stream_card($userid);
        if ($streamcard) {
            array_splice($cards, 2, 0, [$streamcard]);
        }

        return $cards;
    }

    /**
     * Dashboard cards for system admins.
     *
     * @return array
     */
    private function get_system_admin_cards() {
        global $DB, $USER;
        
        $systemcontext = context_system::instance();
        $cards = [];
        $attendanceurl = new moodle_url('/my/courses.php');
        $attendancecourseid = $this->get_first_enrolled_course_id($USER->id);

        if ($attendancecourseid) {
            $attendanceurl = new moodle_url('/mod/attendance/index.php', ['id' => $attendancecourseid]);
        }

        if (has_capability('moodle/cohort:view', $systemcontext)) {
            $cards[] = [
                'title' => get_string('managecohorts', 'block_sceh_dashboard'),
                'icon' => 'fa-users',
                'color' => 'blue',
                'url' => new moodle_url('/cohort/index.php'),
            ];
        }

        if (has_any_capability([
            'moodle/competency:competencyview',
            'moodle/competency:competencymanage',
        ], $systemcontext)) {
            $cards[] = [
                'title' => get_string('competencyframework', 'block_sceh_dashboard'),
                'icon' => 'fa-sitemap',
                'color' => 'green',
                'url' => new moodle_url('/admin/tool/lp/competencyframeworks.php', [
                    'pagecontextid' => $systemcontext->id,
                ]),
            ];
        }

        $cards[] = [
                'title' => get_string('attendancereports', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-bar',
                'color' => 'red',
                'url' => $attendanceurl,
            ];

        if (has_capability('local/kirkpatrick_dashboard:view', $systemcontext)) {
            $cards[] = [
                'title' => get_string('trainingevaluation', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-pie',
                'color' => 'purple',
                'url' => new moodle_url('/local/kirkpatrick_dashboard/index.php'),
            ];
        }

        if (has_any_capability([
            'moodle/badges:viewbadges',
            'moodle/badges:viewawarded',
            'moodle/badges:createbadge',
            'moodle/badges:awardbadge',
            'moodle/badges:configurecriteria',
            'moodle/badges:configuremessages',
            'moodle/badges:configuredetails',
            'moodle/badges:deletebadge',
        ], $systemcontext)) {
            // Count site badges
            $badgecount = $DB->count_records('badge', ['type' => 1]);
            $badgetitle = get_string('badgemanagement', 'block_sceh_dashboard') . 
                          ' (' . $badgecount . ')';
            
            $cards[] = [
                'title' => $badgetitle,
                'icon' => 'fa-award',
                'color' => 'yellow',
                'url' => new moodle_url('/badges/index.php', ['type' => 1]),
            ];
        }

        $cards[] = [
                'title' => get_string('programstructure', 'block_sceh_dashboard'),
                'icon' => 'fa-graduation-cap',
                'color' => 'teal',
                'url' => new moodle_url('/course/index.php'),
            ];

        if (has_capability('moodle/site:config', $systemcontext)) {
            $cards[] = [
                'title' => get_string('customreports', 'block_sceh_dashboard'),
                'icon' => 'fa-file-alt',
                'color' => 'orange',
                'url' => new moodle_url('/admin/category.php', ['category' => 'reports']),
            ];
        }

        if (has_capability('local/sceh_rules:managerules', $systemcontext)) {
            $cards[] = [
                'title' => get_string('rosterrules', 'block_sceh_dashboard'),
                'icon' => 'fa-cogs',
                'color' => 'indigo',
                'url' => new moodle_url('/local/sceh_rules/roster_rules.php'),
            ];
        }

        return $cards;
    }

    /**
     * Dashboard cards for program owners, scoped to assigned categories.
     *
     * @param int $userid
     * @return array
     */
    private function get_program_owner_cards($userid) {
        $systemcontext = context_system::instance();

        $cards = [
            [
                'title' => get_string('competencyframework', 'block_sceh_dashboard'),
                'icon' => 'fa-sitemap',
                'color' => 'green',
                'url' => new moodle_url('/admin/tool/lp/competencyframeworks.php', [
                    'pagecontextid' => $systemcontext->id,
                ]),
            ],
            [
                'title' => get_string('customreports', 'block_sceh_dashboard'),
                'icon' => 'fa-file-alt',
                'color' => 'orange',
                'url' => new moodle_url('/admin/category.php', ['category' => 'reports']),
            ],
            [
                'title' => get_string('streamsetupcheck', 'block_sceh_dashboard'),
                'icon' => 'fa-tasks',
                'color' => 'indigo',
                'url' => new moodle_url('/local/sceh_rules/stream_setup_check.php'),
            ],
        ];

        $categories = $this->get_program_owner_categories($userid);

        foreach ($categories as $category) {
            $cards[] = [
                'title' => format_string($category->name),
                'icon' => 'fa-graduation-cap',
                'color' => 'teal',
                'url' => new moodle_url('/course/management.php', ['categoryid' => $category->id]),
            ];
        }

        return $cards;
    }

    /**
     * Dashboard cards for trainers, scoped to assigned cohort courses.
     *
     * @param int $userid
     * @return array
     */
    private function get_trainer_cards($userid) {
        $context = context_system::instance();
        $courses = [];
        $attendanceurl = new moodle_url('/my/courses.php');
        $istrainercoach = \local_sceh_rules\helper\trainer_coach_helper::is_trainer_coach($userid);

        if (has_capability('local/sceh_rules:viewassignedcohortsonly', $context)) {
            $courses = \local_sceh_rules\helper\cohort_filter::get_trainer_courses($userid);
            if (!empty($courses)) {
                $firstcourse = reset($courses);
                $attendanceurl = new moodle_url('/mod/attendance/index.php', ['id' => $firstcourse->id]);
            }
        }

        $cards = [
            [
                'title' => get_string('attendancereports', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-bar',
                'color' => 'red',
                'url' => $attendanceurl,
            ],
        ];

        if (has_capability('local/sceh_rules:viewassignedcohortsonly', $context)) {
            foreach ($courses as $course) {
                $streamsections = \local_sceh_rules\helper\stream_helper::get_course_stream_sections($course->id);
                $streamcount = count($streamsections);
                $coursetitle = format_string($course->fullname);
                if ($streamcount > 0) {
                    $coursetitle .= ' (' . get_string('streamcount', 'block_sceh_dashboard', $streamcount) . ')';
                }

                $cards[] = [
                    'title' => $coursetitle,
                    'icon' => 'fa-users',
                    'color' => 'blue',
                    'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
                ];

                foreach ($streamsections as $section) {
                    $cards[] = [
                        'title' => get_string('streamcardprefix', 'block_sceh_dashboard', format_string($section->streamname)),
                        'icon' => 'fa-code-branch',
                        'color' => 'indigo',
                        'url' => new moodle_url('/course/view.php', [
                            'id' => $course->id,
                            'section' => $section->section,
                        ]),
                    ];
                }
            }
        } else {
            $cards[] = [
                'title' => get_string('programstructure', 'block_sceh_dashboard'),
                'icon' => 'fa-graduation-cap',
                'color' => 'teal',
                'url' => new moodle_url('/course/index.php'),
            ];
        }

        if ($istrainercoach) {
            $cards[] = [
                'title' => get_string('trainingevaluation', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-pie',
                'color' => 'purple',
                'url' => new moodle_url('/local/kirkpatrick_dashboard/index.php'),
            ];
        }

        return $cards;
    }

    /**
     * Categories where user is assigned as sceh_program_owner.
     *
     * @param int $userid
     * @return array
     */
    private function get_program_owner_categories($userid) {
        global $DB;

        $sql = "SELECT DISTINCT cc.id, cc.name
                  FROM {course_categories} cc
                  JOIN {context} ctx
                    ON ctx.instanceid = cc.id
                   AND ctx.contextlevel = :contextlevel
                  JOIN {role_assignments} ra
                    ON ra.contextid = ctx.id
                  JOIN {role} r
                    ON r.id = ra.roleid
                 WHERE ra.userid = :userid
                   AND r.shortname IN (:shortname, :fallbackshortname)
              ORDER BY cc.name";

        return $DB->get_records_sql($sql, [
            'contextlevel' => CONTEXT_COURSECAT,
            'userid' => $userid,
            'shortname' => 'sceh_program_owner',
            'fallbackshortname' => 'programowner',
        ]);
    }
    
    private function render_card($card) {
        if (class_exists('\local_sceh_rules\output\sceh_card')) {
            return \local_sceh_rules\output\sceh_card::simple($card);
        }

        $html = html_writer::start_div('sceh-card sceh-card-' . $card['color']);
        $html .= html_writer::start_tag('a', ['href' => $card['url'], 'class' => 'sceh-card-link']);
        
        $html .= html_writer::div('<i class="fa ' . $card['icon'] . ' fa-3x"></i>', 'sceh-card-icon');
        $html .= html_writer::div($card['title'], 'sceh-card-title');
        
        $html .= html_writer::end_tag('a');
        $html .= html_writer::end_div();
        
        return $html;
    }

    /**
     * Build learner stream card from Choice response if available.
     *
     * @param int $userid
     * @return array|null
     */
    private function get_learner_stream_card($userid) {
        $courses = enrol_get_users_courses($userid, true, 'id, fullname, visible');
        if (empty($courses)) {
            return null;
        }

        foreach ($courses as $course) {
            $streamname = \local_sceh_rules\helper\stream_helper::get_user_selected_stream($course->id, $userid);
            if (!$streamname) {
                continue;
            }

            $section = \local_sceh_rules\helper\stream_helper::get_section_number_for_stream($course->id, $streamname);
            $urlparams = ['id' => $course->id];
            if ($section > 0) {
                $urlparams['section'] = $section;
            }

            return [
                'title' => get_string('yourstream', 'block_sceh_dashboard', format_string($streamname)),
                'icon' => 'fa-code-branch',
                'color' => 'indigo',
                'url' => new moodle_url('/course/view.php', $urlparams),
            ];
        }

        return null;
    }

    /**
     * Get the first regular course id (excluding site course).
     *
     * @return int
     */
    private function get_first_regular_course_id() {
        global $DB;

        $courseid = $DB->get_field_sql(
            'SELECT id FROM {course} WHERE id > :sitecourseid ORDER BY id ASC',
            ['sitecourseid' => 1],
            IGNORE_MULTIPLE
        );

        if (!$courseid) {
            return 0;
        }

        return (int)$courseid;
    }

    /**
     * Get first enrolled course id for a user.
     *
     * @param int $userid
     * @return int
     */
    private function get_first_enrolled_course_id($userid) {
        $courses = enrol_get_users_courses($userid, true, 'id');
        if (empty($courses)) {
            return 0;
        }

        $courseids = array_map(function($course) {
            return (int)$course->id;
        }, $courses);
        sort($courseids, SORT_NUMERIC);

        return (int)reset($courseids);
    }
    

    public function applicable_formats() {
        return [
            'site-index' => true,
            'my' => true,
            'course-view' => false
        ];
    }
    
    public function has_config() {
        return false;
    }
}
