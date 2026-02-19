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

        if ($this->is_program_owner_user((int)$USER->id)) {
            $this->content->text .= $this->render_program_owner_dashboard((int)$USER->id);
        } else {
            // Get user role-based cards.
            $cards = $this->get_dashboard_cards();

            // Render cards.
            $this->content->text .= html_writer::start_div('sceh-dashboard-grid');
            foreach ($cards as $card) {
                $this->content->text .= $this->render_card($card);
            }
            $this->content->text .= html_writer::end_div();
        }

        $this->content->text .= $this->render_workflow_queue($USER->id);
        
        return $this->content;
    }

    /**
     * Check whether user should use Program Owner dashboard layout.
     *
     * @param int $userid
     * @return bool
     */
    private function is_program_owner_user(int $userid): bool {
        $context = context_system::instance();
        if (has_capability('local/sceh_rules:systemadmin', $context, $userid)) {
            return false;
        }
        if (has_capability('local/sceh_rules:trainer', $context, $userid)) {
            return false;
        }
        if (has_capability('local/sceh_rules:programowner', $context, $userid)) {
            return true;
        }
        return !empty($this->get_program_owner_categories($userid));
    }

    /**
     * Render Program Owner dashboard sections.
     *
     * @param int $userid
     * @return string
     */
    private function render_program_owner_dashboard(int $userid): string {
        $actions = $this->get_program_owner_quick_actions($userid);
        $statuscards = $this->get_program_owner_status_cards($userid);

        $html = html_writer::start_div('sceh-program-owner-dashboard');

        $html .= html_writer::tag('h4', get_string('poquickactions', 'block_sceh_dashboard'));
        $html .= html_writer::start_div('sceh-dashboard-grid sceh-po-quick-actions');
        foreach ($actions as $action) {
            $html .= $this->render_program_owner_action($action);
        }
        $html .= html_writer::end_div();
        $html .= $this->render_program_owner_subactions_bar($actions);

        $html .= html_writer::tag('h4', get_string('postatusmonitoring', 'block_sceh_dashboard'), ['class' => 'mt-4']);
        $html .= html_writer::start_div('sceh-dashboard-grid sceh-po-status-grid');
        foreach ($statuscards as $statuscard) {
            $html .= $this->render_program_owner_status_summary_card($statuscard);
        }
        $html .= html_writer::end_div();
        $html .= $this->render_program_owner_status_panels($statuscards);

        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render single quick action row (accordion when sub-actions exist).
     *
     * @param array $action
     * @return string
     */
    private function render_program_owner_action(array $action): string {
        if (empty($action['children'])) {
            return $this->render_card([
                'title' => $action['title'],
                'icon' => $action['icon'],
                'color' => $action['color'] ?? 'blue',
                'url' => $action['url'],
            ]);
        }

        $actionkey = clean_param(core_text::strtolower($action['title']), PARAM_ALPHANUMEXT);
        $html = html_writer::start_div('sceh-card sceh-card-' . ($action['color'] ?? 'indigo'));
        $html .= html_writer::link(
            '#',
            html_writer::div('<i class="fa ' . $action['icon'] . ' fa-3x"></i>', 'sceh-card-icon') .
            html_writer::div(format_string($action['title']), 'sceh-card-title'),
            [
                'class' => 'sceh-card-link sceh-po-open-subactions',
                'data-action-key' => $actionkey,
            ]
        );
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render top-level Program Owner status summary card.
     *
     * @param array $statuscard
     * @return string
     */
    private function render_program_owner_status_summary_card(array $statuscard): string {
        $statuskey = clean_param(core_text::strtolower($statuscard['title']), PARAM_ALPHANUMEXT);
        $color = $this->program_owner_status_color((string)($statuscard['status'] ?? 'info'));
        $totalcount = (int)array_sum(array_column($statuscard['steps'], 'count'));

        $html = html_writer::start_div('sceh-card sceh-card-' . $color);
        $html .= html_writer::link(
            '#',
            html_writer::div('<i class="fa ' . $statuscard['icon'] . ' fa-3x"></i>', 'sceh-card-icon') .
            html_writer::div(format_string($statuscard['title']), 'sceh-card-title') .
            html_writer::div((string)$totalcount, 'sceh-po-status-total'),
            [
                'class' => 'sceh-card-link sceh-po-open-status',
                'data-status-key' => $statuskey,
            ]
        );
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render inline status detail panels below status summary cards.
     *
     * @param array $statuscards
     * @return string
     */
    private function render_program_owner_status_panels(array $statuscards): string {
        $html = '';
        $hasbar = false;

        $html .= html_writer::start_div('sceh-po-status-bar', ['id' => 'sceh-po-status-bar']);
        foreach ($statuscards as $statuscard) {
            if (empty($statuscard['steps'])) {
                continue;
            }
            $hasbar = true;
            $statuskey = clean_param(core_text::strtolower($statuscard['title']), PARAM_ALPHANUMEXT);
            $color = $this->program_owner_status_color((string)($statuscard['status'] ?? 'info'));
            $html .= html_writer::start_div(
                'sceh-po-status-panel sceh-po-subactions-panel sceh-po-status-panel-' . $color . ' sceh-po-subactions-panel-' . $color,
                [
                'id' => 'sceh-po-status-panel-' . $statuskey,
                'data-status-key' => $statuskey,
                'data-color' => $color,
                'hidden' => 'hidden',
            ]);
            $html .= html_writer::start_div('sceh-po-status-header sceh-po-subactions-header');
            $html .= html_writer::tag('h5', format_string($statuscard['title']));
            $html .= html_writer::tag('button', '&times;', [
                'type' => 'button',
                'class' => 'sceh-po-status-close sceh-po-subactions-close',
                'data-status-key' => $statuskey,
                'aria-label' => 'Close',
            ]);
            $html .= html_writer::end_div();

            $html .= html_writer::start_div('sceh-po-status-grid-steps sceh-po-subactions-grid');
            foreach ($statuscard['steps'] as $step) {
                $stepcontent = html_writer::div((string)((int)$step['count']), 'sceh-po-status-step-count');
                $stepcontent .= html_writer::div(format_string($step['label']), 'sceh-po-status-step-title');
                $html .= html_writer::link(
                    $step['url'],
                    $stepcontent,
                    ['class' => 'sceh-po-status-step-card sceh-po-subaction-card sceh-po-status-step-card-' . $color . ' sceh-po-subaction-card-' . $color]
                );
            }
            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        if (!$hasbar) {
            return $html;
        }

        $html .= html_writer::script(
            "(function(){"
            . "const bar=document.getElementById('sceh-po-status-bar');"
            . "if(!bar){return;}"
            . "const panels=bar.querySelectorAll('.sceh-po-status-panel');"
            . "const closeAll=()=>{panels.forEach((p)=>{p.classList.remove('is-open');p.hidden=true;});};"
            . "document.querySelectorAll('.sceh-po-open-status').forEach((btn)=>btn.addEventListener('click',function(e){"
            . "e.preventDefault();"
            . "const key=btn.dataset.statusKey;"
            . "const target=bar.querySelector('.sceh-po-status-panel[data-status-key=\"'+key+'\"]');"
            . "const isopen=target && target.classList.contains('is-open') && !target.hidden;"
            . "closeAll();"
            . "if(target && !isopen){target.classList.add('is-open');target.hidden=false;}"
            . "}));"
            . "bar.querySelectorAll('.sceh-po-status-close').forEach((btn)=>btn.addEventListener('click',function(){"
            . "const key=btn.dataset.statusKey;"
            . "const target=bar.querySelector('.sceh-po-status-panel[data-status-key=\"'+key+'\"]');"
            . "if(target){target.classList.remove('is-open');target.hidden=true;}"
            . "}));"
            . "})();"
        );

        return $html;
    }

    /**
     * Map status value to existing accessible card color token.
     *
     * @param string $status
     * @return string
     */
    private function program_owner_status_color(string $status): string {
        switch ($status) {
            case 'success':
                return 'green';
            case 'warning':
                return 'orange';
            case 'danger':
                return 'red';
            case 'info':
            default:
                return 'blue';
        }
    }

    /**
     * Render inline sub-action bar for quick actions that have sub-options.
     *
     * @param array $actions
     * @return string
     */
    private function render_program_owner_subactions_bar(array $actions): string {
        $html = '';
        $hasbar = false;

        $html .= html_writer::start_div('sceh-po-subactions-bar', ['id' => 'sceh-po-subactions-bar']);
        foreach ($actions as $action) {
            if (empty($action['children'])) {
                continue;
            }
            $hasbar = true;
            $actionkey = clean_param(core_text::strtolower($action['title']), PARAM_ALPHANUMEXT);
            $color = clean_param((string)($action['color'] ?? 'blue'), PARAM_ALPHANUMEXT);
            $html .= html_writer::start_div('sceh-po-subactions-panel', [
                'id' => 'sceh-po-panel-' . $actionkey,
                'data-action-key' => $actionkey,
                'data-color' => $color,
                'class' => 'sceh-po-subactions-panel sceh-po-subactions-panel-' . $color,
                'hidden' => 'hidden',
            ]);
            $html .= html_writer::start_div('sceh-po-subactions-header');
            $html .= html_writer::tag('h5', format_string($action['title']));
            $html .= html_writer::tag('button', '&times;', [
                'type' => 'button',
                'class' => 'sceh-po-subactions-close',
                'data-action-key' => $actionkey,
                'aria-label' => 'Close',
            ]);
            $html .= html_writer::end_div();
            $html .= html_writer::start_div('sceh-po-subactions-grid');
            foreach ($action['children'] as $child) {
                $html .= html_writer::link(
                    $child['url'],
                    format_string($child['title']),
                    ['class' => 'sceh-po-subaction-card sceh-po-subaction-card-' . $color]
                );
            }
            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();

        if (!$hasbar) {
            return $html;
        }

        $html .= html_writer::script(
            "(function(){"
            . "const bar=document.getElementById('sceh-po-subactions-bar');"
            . "if(!bar){return;}"
            . "const panels=bar.querySelectorAll('.sceh-po-subactions-panel');"
            . "const closeAll=()=>{panels.forEach((p)=>{p.classList.remove('is-open');p.hidden=true;});};"
            . "document.querySelectorAll('.sceh-po-open-subactions').forEach((btn)=>btn.addEventListener('click',function(e){"
            . "e.preventDefault();"
            . "const key=btn.dataset.actionKey;"
            . "const target=bar.querySelector('.sceh-po-subactions-panel[data-action-key=\"'+key+'\"]');"
            . "const isopen=target && target.classList.contains('is-open') && !target.hidden;"
            . "closeAll();"
            . "if(target && !isopen){target.classList.add('is-open');target.hidden=false;}"
            . "}));"
            . "bar.querySelectorAll('.sceh-po-subactions-close').forEach((btn)=>btn.addEventListener('click',function(){"
            . "const key=btn.dataset.actionKey;"
            . "const target=bar.querySelector('.sceh-po-subactions-panel[data-action-key=\"'+key+'\"]');"
            . "if(target){target.classList.remove('is-open');target.hidden=true;}"
            . "}));"
            . "})();"
        );

        return $html;
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
        if (!$is_program_owner) {
            $is_program_owner = !empty($this->get_program_owner_categories($userid));
        }
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

    /**
     * Program Owner quick actions and nested links.
     *
     * @param int $userid
     * @return array
     */
    private function get_program_owner_quick_actions(int $userid): array {
        $categories = $this->get_program_owner_categories($userid);
        $categoryids = $this->get_program_owner_category_ids($userid);
        $primarycategoryid = $categoryids[0] ?? 0;
        $courseids = $this->get_program_owner_course_ids($userid);
        $primarycourseid = $courseids[0] ?? 0;
        $canmanagecohorts = has_any_capability([
            'moodle/cohort:view',
            'moodle/cohort:manage',
        ], context_system::instance(), $userid);

        $managecoursechildren = [
            [
                'title' => get_string('poallcourses', 'block_sceh_dashboard'),
                'url' => new moodle_url('/course/index.php'),
            ],
        ];

        foreach ($categories as $category) {
            $categoryname = format_string($category->name);
            $managecoursechildren[] = [
                'title' => get_string('pocreateincategory', 'block_sceh_dashboard', $categoryname),
                'url' => new moodle_url('/course/edit.php', ['category' => (int)$category->id]),
            ];
            $managecoursechildren[] = [
                'title' => get_string('poeditincategory', 'block_sceh_dashboard', $categoryname),
                'url' => new moodle_url('/course/management.php', ['categoryid' => (int)$category->id]),
            ];
        }

        $managecoursechildren[] = [
            'title' => get_string('pobulkimport', 'block_sceh_dashboard'),
            'url' => $primarycourseid > 0
                ? new moodle_url('/local/sceh_importer/index.php', ['courseid' => $primarycourseid])
                : new moodle_url('/local/sceh_importer/index.php'),
        ];
        $managecoursechildren[] = [
            'title' => get_string('povalidatecourses', 'block_sceh_dashboard'),
            'url' => new moodle_url('/local/sceh_rules/stream_setup_check.php'),
        ];
        $managecoursechildren[] = [
            'title' => get_string('popublishcourses', 'block_sceh_dashboard'),
            'url' => $primarycategoryid > 0
                ? new moodle_url('/course/management.php', ['categoryid' => $primarycategoryid])
                : new moodle_url('/course/index.php'),
        ];

        $actions = [
            [
                'title' => get_string('pomanagecourses', 'block_sceh_dashboard'),
                'icon' => 'fa-book-open',
                'color' => 'indigo',
                'url' => new moodle_url('/course/index.php'),
                'children' => $managecoursechildren,
            ],
            [
                'title' => get_string('pomanagecompetencies', 'block_sceh_dashboard'),
                'icon' => 'fa-sitemap',
                'color' => 'green',
                'url' => new moodle_url('/admin/tool/lp/competencyframeworks.php', [
                    'pagecontextid' => context_system::instance()->id,
                ]),
                'children' => [
                    [
                        'title' => get_string('poaddframework', 'block_sceh_dashboard'),
                        'url' => new moodle_url('/admin/tool/lp/competencyframeworks.php', [
                            'pagecontextid' => context_system::instance()->id,
                            'action' => 'edit',
                        ]),
                    ],
                    [
                        'title' => get_string('poviewframeworks', 'block_sceh_dashboard'),
                        'url' => new moodle_url('/admin/tool/lp/competencyframeworks.php', [
                            'pagecontextid' => context_system::instance()->id,
                        ]),
                    ],
                ],
            ],
            [
                'title' => get_string('poassigntrainers', 'block_sceh_dashboard'),
                'icon' => 'fa-user-check',
                'color' => 'orange',
                'url' => $primarycourseid > 0
                    ? new moodle_url('/enrol/users.php', ['id' => $primarycourseid])
                    : new moodle_url('/course/index.php'),
                'children' => [],
            ],
        ];

        if ($canmanagecohorts) {
            $actions[] = [
                'title' => get_string('pomanagecohorts', 'block_sceh_dashboard'),
                'icon' => 'fa-users',
                'color' => 'blue',
                'url' => new moodle_url('/cohort/index.php'),
                'children' => [],
            ];
        }

        return $actions;
    }

    /**
     * Build Program Owner status/monitoring cards.
     *
     * @param int $userid
     * @return array
     */
    private function get_program_owner_status_cards(int $userid): array {
        $courseids = $this->get_program_owner_course_ids($userid);
        $courseids = array_map('intval', $courseids);

        $publishing = $this->get_program_owner_publishing_status($userid, $courseids);
        $cohorts = $this->get_program_owner_cohort_status($courseids);
        $learners = $this->get_program_owner_learner_status($courseids);
        $trainers = $this->get_program_owner_trainer_status($userid, $courseids);
        $content = $this->get_program_owner_content_pipeline_status($userid, $courseids);

        return [
            [
                'title' => get_string('popublishing', 'block_sceh_dashboard'),
                'icon' => 'fa-upload',
                'status' => $publishing['status'],
                'steps' => $publishing['steps'],
            ],
            [
                'title' => get_string('pocohorts', 'block_sceh_dashboard'),
                'icon' => 'fa-users',
                'status' => $cohorts['status'],
                'steps' => $cohorts['steps'],
            ],
            [
                'title' => get_string('polearners', 'block_sceh_dashboard'),
                'icon' => 'fa-user-graduate',
                'status' => $learners['status'],
                'steps' => $learners['steps'],
            ],
            [
                'title' => get_string('potrainerassignment', 'block_sceh_dashboard'),
                'icon' => 'fa-user-check',
                'status' => $trainers['status'],
                'steps' => $trainers['steps'],
            ],
            [
                'title' => get_string('pocontentpipeline', 'block_sceh_dashboard'),
                'icon' => 'fa-boxes-stacked',
                'status' => $content['status'],
                'steps' => $content['steps'],
            ],
        ];
    }

    /**
     * Program Owner category ids.
     *
     * @param int $userid
     * @return int[]
     */
    private function get_program_owner_category_ids(int $userid): array {
        $categories = $this->get_program_owner_categories($userid);
        return array_values(array_map(static function($category): int {
            return (int)$category->id;
        }, $categories));
    }

    /**
     * Program Owner course ids in assigned categories.
     *
     * @param int $userid
     * @return int[]
     */
    private function get_program_owner_course_ids(int $userid): array {
        global $DB;

        $categoryids = $this->get_program_owner_category_ids($userid);
        if (empty($categoryids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
        $records = $DB->get_records_select('course', 'category ' . $insql, $params, 'id ASC', 'id');
        return array_values(array_map(static function($record): int {
            return (int)$record->id;
        }, $records));
    }

    /**
     * Build publishing status payload.
     *
     * @param int $userid
     * @param int[] $courseids
     * @return array
     */
    private function get_program_owner_publishing_status(int $userid, array $courseids): array {
        global $DB;

        $draft = 0;
        $live = 0;
        if (!empty($courseids)) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
            $draft = (int)$DB->count_records_select('course', 'id ' . $insql . ' AND visible = 0', $params);
            $live = (int)$DB->count_records_select('course', 'id ' . $insql . ' AND visible = 1', $params);
        }

        $needschanges = $this->count_program_owner_stream_issues($userid);
        $pendingapproval = max(0, $draft - $needschanges);
        $warn = ($needschanges > 0 || $draft > 0);

        $baseurl = new moodle_url('/course/index.php');
        return [
            'status' => $warn ? 'warning' : 'success',
            'steps' => [
                ['label' => get_string('postepdraft', 'block_sceh_dashboard'), 'count' => $draft, 'desc' => get_string('postepdraftdesc', 'block_sceh_dashboard'), 'url' => $baseurl],
                ['label' => get_string('postepneedschanges', 'block_sceh_dashboard'), 'count' => $needschanges, 'desc' => get_string('postepneedschangesdesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/local/sceh_rules/stream_setup_check.php')],
                ['label' => get_string('posteppendingapproval', 'block_sceh_dashboard'), 'count' => $pendingapproval, 'desc' => get_string('posteppendingapprovaldesc', 'block_sceh_dashboard'), 'url' => $baseurl],
                ['label' => get_string('posteplive', 'block_sceh_dashboard'), 'count' => $live, 'desc' => get_string('posteplivedesc', 'block_sceh_dashboard'), 'url' => $baseurl],
            ],
        ];
    }

    /**
     * Build cohort readiness payload.
     *
     * @param int[] $courseids
     * @return array
     */
    private function get_program_owner_cohort_status(array $courseids): array {
        global $DB;

        $notconfigured = 0;
        $setupprogress = 0;
        $ready = 0;
        $active = 0;

        if (!empty($courseids)) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
            $rows = $DB->get_records_sql(
                "SELECT e.courseid, r.shortname AS roleshortname
                   FROM {enrol} e
                   JOIN {role} r ON r.id = e.roleid
                  WHERE e.courseid {$insql}
                    AND e.enrol = :enroltype
                    AND e.status = 0",
                $params + ['enroltype' => 'cohort']
            );

            $courseflags = [];
            foreach ($courseids as $courseid) {
                $courseflags[(int)$courseid] = ['student' => false, 'editingteacher' => false];
            }
            foreach ($rows as $row) {
                $courseid = (int)$row->courseid;
                if (!isset($courseflags[$courseid])) {
                    continue;
                }
                if ($row->roleshortname === 'student') {
                    $courseflags[$courseid]['student'] = true;
                }
                if ($row->roleshortname === 'editingteacher') {
                    $courseflags[$courseid]['editingteacher'] = true;
                }
            }

            $visiblerecords = $DB->get_records_list('course', 'id', $courseids, '', 'id,visible');
            foreach ($courseflags as $courseid => $flags) {
                $hasboth = ($flags['student'] && $flags['editingteacher']);
                $hasany = ($flags['student'] || $flags['editingteacher']);
                $visible = !empty($visiblerecords[$courseid]) ? (int)$visiblerecords[$courseid]->visible : 0;
                if (!$hasany) {
                    $notconfigured++;
                } else if (!$hasboth) {
                    $setupprogress++;
                } else if ($visible === 1) {
                    $active++;
                } else {
                    $ready++;
                }
            }
        }

        $warn = ($notconfigured > 0 || $setupprogress > 0);
        $url = new moodle_url('/cohort/index.php');
        return [
            'status' => $warn ? 'warning' : 'success',
            'steps' => [
                ['label' => get_string('postepnotconfigured', 'block_sceh_dashboard'), 'count' => $notconfigured, 'desc' => get_string('postepnotconfigureddesc', 'block_sceh_dashboard'), 'url' => $url],
                ['label' => get_string('postepsetupprogress', 'block_sceh_dashboard'), 'count' => $setupprogress, 'desc' => get_string('postepsetupprogressdesc', 'block_sceh_dashboard'), 'url' => $url],
                ['label' => get_string('postepreadylaunch', 'block_sceh_dashboard'), 'count' => $ready, 'desc' => get_string('postepreadylaunchdesc', 'block_sceh_dashboard'), 'url' => $url],
                ['label' => get_string('postepactive', 'block_sceh_dashboard'), 'count' => $active, 'desc' => get_string('postepactivedesc', 'block_sceh_dashboard'), 'url' => $url],
            ],
        ];
    }

    /**
     * Build learner progression payload.
     *
     * @param int[] $courseids
     * @return array
     */
    private function get_program_owner_learner_status(array $courseids): array {
        global $DB;

        $notstarted = 0;
        $inprogress = 0;
        $atrisk = 0;
        $completed = 0;

        if (!empty($courseids)) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
            $sql = "SELECT DISTINCT ra.userid, ctx.instanceid AS courseid
                      FROM {role_assignments} ra
                      JOIN {context} ctx
                        ON ctx.id = ra.contextid
                       AND ctx.contextlevel = :coursecontext
                      JOIN {role} r
                        ON r.id = ra.roleid
                     WHERE ctx.instanceid {$insql}
                       AND r.shortname = :studentshortname";
            $enrolled = $DB->get_records_sql($sql, $params + [
                'coursecontext' => CONTEXT_COURSE,
                'studentshortname' => 'student',
            ]);

            foreach ($enrolled as $record) {
                $cc = $DB->get_record('course_completions', [
                    'course' => (int)$record->courseid,
                    'userid' => (int)$record->userid,
                ], 'id,timecompleted', IGNORE_MISSING);
                if (!$cc) {
                    $notstarted++;
                    continue;
                }
                if (!empty($cc->timecompleted)) {
                    $completed++;
                    continue;
                }
                $inprogress++;

                $lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', [
                    'courseid' => (int)$record->courseid,
                    'userid' => (int)$record->userid,
                ], IGNORE_MISSING);
                if (!$lastaccess || (int)$lastaccess < (time() - (14 * DAYSECS))) {
                    $atrisk++;
                }
            }
        }

        $warn = $atrisk > 0;
        $url = new moodle_url('/local/sceh_rules/stream_progress.php');
        return [
            'status' => $warn ? 'warning' : 'success',
            'steps' => [
                ['label' => get_string('postepnotstarted', 'block_sceh_dashboard'), 'count' => $notstarted, 'desc' => get_string('postepnotstarteddesc', 'block_sceh_dashboard'), 'url' => $url],
                ['label' => get_string('postepinprogress', 'block_sceh_dashboard'), 'count' => $inprogress, 'desc' => get_string('postepinprogressdesc', 'block_sceh_dashboard'), 'url' => $url],
                ['label' => get_string('postepatrisk', 'block_sceh_dashboard'), 'count' => $atrisk, 'desc' => get_string('postepatriskdesc', 'block_sceh_dashboard'), 'url' => $url],
                ['label' => get_string('postepcompleted', 'block_sceh_dashboard'), 'count' => $completed, 'desc' => get_string('postepcompleteddesc', 'block_sceh_dashboard'), 'url' => $url],
            ],
        ];
    }

    /**
     * Build trainer assignment payload.
     *
     * @param int $userid
     * @param int[] $courseids
     * @return array
     */
    private function get_program_owner_trainer_status(int $userid, array $courseids): array {
        global $DB;

        $assigned = 0;
        $unassigned = 0;
        $needsreview = $this->count_trainer_ungraded_submissions($userid);
        $ontrack = 0;

        if (!empty($courseids)) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
            $rows = $DB->get_records_sql(
                "SELECT DISTINCT ctx.instanceid AS courseid
                   FROM {role_assignments} ra
                   JOIN {context} ctx
                     ON ctx.id = ra.contextid
                    AND ctx.contextlevel = :coursecontext
                   JOIN {role} r
                     ON r.id = ra.roleid
                  WHERE ctx.instanceid {$insql}
                    AND r.shortname = :editingshortname",
                $params + [
                    'coursecontext' => CONTEXT_COURSE,
                    'editingshortname' => 'editingteacher',
                ]
            );
            $assignedcourseids = array_map(static function($row): int {
                return (int)$row->courseid;
            }, $rows);
            $assigned = count($assignedcourseids);
            $unassigned = max(0, count($courseids) - $assigned);
            $ontrack = max(0, $assigned - min($assigned, $needsreview));
        }

        $warn = ($unassigned > 0 || $needsreview > 0);
        $primarycourseid = $courseids[0] ?? 0;
        return [
            'status' => $warn ? 'warning' : 'success',
            'steps' => [
                ['label' => get_string('postepunassigned', 'block_sceh_dashboard'), 'count' => $unassigned, 'desc' => get_string('postepunassigneddesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/course/index.php')],
                ['label' => get_string('postepassigned', 'block_sceh_dashboard'), 'count' => $assigned, 'desc' => get_string('postepassigneddesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/enrol/users.php', ['id' => $primarycourseid])],
                ['label' => get_string('postepneedsreview', 'block_sceh_dashboard'), 'count' => $needsreview, 'desc' => get_string('postepneedsreviewdesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/my/courses.php')],
                ['label' => get_string('postepontrack', 'block_sceh_dashboard'), 'count' => $ontrack, 'desc' => get_string('postepontrackdesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/my/courses.php')],
            ],
        ];
    }

    /**
     * Build content pipeline payload.
     *
     * @param int $userid
     * @param int[] $courseids
     * @return array
     */
    private function get_program_owner_content_pipeline_status(int $userid, array $courseids): array {
        global $DB;

        $newimports = 0;
        $needsfixed = $this->count_program_owner_stream_issues($userid);
        $readyreview = 0;
        $approved = 0;

        if (!empty($courseids) && $DB->get_manager()->table_exists('local_sceh_importer_prog')) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'course');
            $imported = $DB->get_records_sql(
                "SELECT courseid, timemodified
                   FROM {local_sceh_importer_prog}
                  WHERE courseid {$insql}",
                $params
            );
            foreach ($imported as $record) {
                if ((int)$record->timemodified >= (time() - (7 * DAYSECS))) {
                    $newimports++;
                }
                $course = $DB->get_record('course', ['id' => (int)$record->courseid], 'id,visible', IGNORE_MISSING);
                if (!$course) {
                    continue;
                }
                if ((int)$course->visible === 1) {
                    $approved++;
                } else {
                    $readyreview++;
                }
            }
        }

        $warn = ($needsfixed > 0);
        $importurl = new moodle_url('/local/sceh_importer/index.php');
        return [
            'status' => $warn ? 'warning' : 'success',
            'steps' => [
                ['label' => get_string('postepnewimports', 'block_sceh_dashboard'), 'count' => $newimports, 'desc' => get_string('postepnewimportsdesc', 'block_sceh_dashboard'), 'url' => $importurl],
                ['label' => get_string('postepneedsfixes', 'block_sceh_dashboard'), 'count' => $needsfixed, 'desc' => get_string('postepneedsfixesdesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/local/sceh_rules/stream_setup_check.php')],
                ['label' => get_string('postepreadyreview', 'block_sceh_dashboard'), 'count' => $readyreview, 'desc' => get_string('postepreadyreviewdesc', 'block_sceh_dashboard'), 'url' => $importurl],
                ['label' => get_string('postepapproved', 'block_sceh_dashboard'), 'count' => $approved, 'desc' => get_string('postepapproveddesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/course/index.php')],
            ],
        ];
    }
    
    private function get_dashboard_cards() {
        global $USER;
        
        $context = context_system::instance();
        
        // Check user roles via local_sceh_rules capability model.
        $is_system_admin = has_capability('local/sceh_rules:systemadmin', $context);
        $is_program_owner = has_capability('local/sceh_rules:programowner', $context);
        if (!$is_program_owner) {
            $is_program_owner = !empty($this->get_program_owner_categories((int)$USER->id));
        }
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
