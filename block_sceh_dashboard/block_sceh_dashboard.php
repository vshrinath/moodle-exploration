<?php
defined('MOODLE_INTERNAL') || die();

class block_sceh_dashboard extends block_base
{

    public function init()
    {
        $this->title = get_string('pluginname', 'block_sceh_dashboard');
    }

    public function get_content()
    {
        global $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // Reuse shared card styles for workflow queue rendering.
        $PAGE->requires->css(new moodle_url('/local/sceh_rules/styles/sceh_card_system.css'));

        $context = context_system::instance();
        $userid = (int)$USER->id;

        if ($this->is_program_owner_user($userid)) {
            $this->content->text .= $this->render_program_owner_dashboard($userid);
        }
        else if (has_capability('local/sceh_rules:systemadmin', $context)) {
            $this->content->text .= $this->render_sysadmin_dashboard($userid);
        }
        else if (has_capability('local/sceh_rules:trainer', $context)) {
            $this->content->text .= $this->render_trainer_dashboard($userid);
        }
        else {
            $this->content->text .= $this->render_learner_dashboard($userid);
        }

        // Workflow Queue intentionally hidden for current rollout.
        // $this->content->text .= $this->render_workflow_queue($USER->id);

        // Inject "Help" link into primary navigation.
        $helpurl = (new moodle_url('/local/sceh_rules/help.php'))->out(false);
        $PAGE->requires->js_amd_inline("
            require([], function() {
                var nav = document.querySelector('.primary-navigation .moremenu .nav');
                if (nav && !document.querySelector('.sceh-help-nav')) {
                    var li = document.createElement('li');
                    li.className = 'nav-item sceh-help-nav';
                    li.innerHTML = '<a class=\"nav-link\" href=\"{$helpurl}\">' +
                        '<i class=\"fa fa-circle-question me-1\"></i>Help</a>';
                    nav.appendChild(li);
                }
            });
        ");

        return $this->content;
    }

    /**
     * Check whether user should use Program Owner dashboard layout.
     *
     * @param int $userid
     * @return bool
     */
    private function is_program_owner_user(int $userid): bool
    {
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
    private function render_program_owner_dashboard(int $userid): string
    {
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
     * Render System Admin dashboard with sections.
     *
     * @param int $userid
     * @return string
     */
    private function render_sysadmin_dashboard(int $userid): string
    {
        $cards = $this->get_system_admin_cards();
        $statuscards = $this->get_sysadmin_status_cards($userid);

        $html = html_writer::start_div('sceh-program-owner-dashboard');

        // Quick Actions.
        $html .= html_writer::tag('h4', get_string('sysadminquickactions', 'block_sceh_dashboard'));
        $html .= html_writer::start_div('sceh-dashboard-grid sceh-po-quick-actions');
        foreach ($cards as $card) {
            $html .= $this->render_card($card);
        }
        $html .= html_writer::end_div();

        // Status & Monitoring.
        $html .= html_writer::tag('h4', get_string('sysadminmonitoring', 'block_sceh_dashboard'), ['class' => 'mt-4']);
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
     * Render Trainer dashboard with sections.
     *
     * @param int $userid
     * @return string
     */
    private function render_trainer_dashboard(int $userid): string
    {
        $actions = $this->get_trainer_cards($userid);
        $statuscards = $this->get_trainer_status_cards($userid);

        $html = html_writer::start_div('sceh-program-owner-dashboard');

        // Quick Actions (with expandable sub-action bar for courses).
        $html .= html_writer::tag('h4', get_string('trainerquickactions', 'block_sceh_dashboard'));
        $html .= html_writer::start_div('sceh-dashboard-grid sceh-po-quick-actions');
        foreach ($actions as $action) {
            if (!empty($action['children'])) {
                $html .= $this->render_program_owner_action($action);
            }
            else {
                $html .= $this->render_card($action);
            }
        }
        $html .= html_writer::end_div();
        $html .= $this->render_program_owner_subactions_bar($actions);

        // Status.
        $html .= html_writer::tag('h4', get_string('trainerstatus', 'block_sceh_dashboard'), ['class' => 'mt-4']);
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
     * Render Learner dashboard — flat card grid, no section headings.
     *
     * @param int $userid
     * @return string
     */
    private function render_learner_dashboard(int $userid): string
    {
        $cards = $this->get_learner_cards($userid);

        $html = html_writer::start_div('sceh-program-owner-dashboard');
        $html .= html_writer::start_div('sceh-dashboard-grid');
        foreach ($cards as $card) {
            $html .= $this->render_card($card);
        }
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render single quick action row (accordion when sub-actions exist).
     *
     * @param array $action
     * @return string
     */
    private function render_program_owner_action(array $action): string
    {
        if (empty($action['children'])) {
            return $this->render_card([
                'title' => $action['title'],
                'icon' => $action['icon'],
                'color' => $action['color'] ?? 'blue',
                'url' => $action['url'],
            ]);
        }

        $actionkey = clean_param(core_text::strtolower($action['title']), PARAM_ALPHANUMEXT);
        $html = html_writer::start_div('sceh-card sceh-card-system sceh-card-' . ($action['color'] ?? 'indigo'));
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
    private function render_program_owner_status_summary_card(array $statuscard): string
    {
        $statuskey = clean_param(core_text::strtolower($statuscard['title']), PARAM_ALPHANUMEXT);
        $color = $this->program_owner_status_color((string)($statuscard['status'] ?? 'info'));
        $totalcount = (int)array_sum(array_column($statuscard['steps'], 'count'));

        $html = html_writer::start_div('sceh-card sceh-card-system sceh-card-' . $color);
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
    private function render_program_owner_status_panels(array $statuscards): string
    {
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
    private function program_owner_status_color(string $status): string
    {
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
    private function render_program_owner_subactions_bar(array $actions): string
    {
        $html = '';
        $hasbar = false;
        $openeddefault = false;

        $html .= html_writer::start_div('sceh-po-subactions-bar', ['id' => 'sceh-po-subactions-bar']);
        foreach ($actions as $action) {
            if (empty($action['children'])) {
                continue;
            }
            $hasbar = true;
            $actionkey = clean_param(core_text::strtolower($action['title']), PARAM_ALPHANUMEXT);
            $color = clean_param((string)($action['color'] ?? 'blue'), PARAM_ALPHANUMEXT);
            $attrs = [
                'id' => 'sceh-po-panel-' . $actionkey,
                'data-action-key' => $actionkey,
                'data-color' => $color,
                'class' => 'sceh-po-subactions-panel sceh-po-subactions-panel-' . $color,
            ];
            if ($openeddefault) {
                $attrs['hidden'] = 'hidden';
            }
            else {
                $attrs['class'] .= ' is-open';
                $openeddefault = true;
            }
            $html .= html_writer::start_div('sceh-po-subactions-panel', $attrs);
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
    private function render_workflow_queue($userid)
    {
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
    private function get_workflow_queue_items($userid)
    {
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
        }
        else if ($is_program_owner) {
            return $this->build_program_owner_queue($userid);
        }
        else if ($is_trainer) {
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
    private function build_system_admin_queue($userid)
    {
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
    private function build_program_owner_queue($userid)
    {
        $issueids = $this->get_program_owner_stream_issue_course_ids($userid);
        $streamissues = count($issueids);
        $streamissuesurl = new moodle_url('/local/sceh_rules/stream_setup_check.php');
        if ($streamissues === 1) {
            $streamissuesurl->param('id', reset($issueids));
        }

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
                    $streamissuesurl,
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
    private function build_trainer_queue($userid)
    {
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
    private function build_learner_queue($userid)
    {
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
    private function workflow_item($icon, $text, $subtext, moodle_url $url, $enabled = true)
    {
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
    private function count_failed_scheduled_tasks()
    {
        global $DB;
        return (int)$DB->count_records_select('task_scheduled', 'faildelay > 0');
    }

    /**
     * Count overdue events for a user.
     *
     * @param int $userid
     * @return int
     */
    private function count_user_overdue_events($userid)
    {
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
    private function count_user_upcoming_events($userid, $days = 7)
    {
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
    private function count_total_cohorts()
    {
        global $DB;
        return (int)$DB->count_records('cohort');
    }

    /**
     * Count stream setup issues in program-owner categories.
     *
     * @param int $userid
     * @return int
     */
    private function count_program_owner_stream_issues($userid)
    {
        return count($this->get_program_owner_stream_issue_course_ids($userid));
    }

    /**
     * Get IDs of courses with stream setup issues in program-owner categories.
     *
     * @param int $userid
     * @return int[]
     */
    private function get_program_owner_stream_issue_course_ids($userid)
    {
        global $DB;

        $categories = $this->get_program_owner_categories($userid);
        if (empty($categories)) {
            return [];
        }

        $categoryids = array_map(function ($category) {
            return (int)$category->id;
        }, $categories);
        list($catsql, $catparams) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');

        $courses = $DB->get_records_select('course', 'category ' . $catsql, $catparams, '', 'id');
        $issuecourseids = [];

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
                $issuecourseids[] = (int)$course->id;
            }
        }

        return $issuecourseids;
    }

    /**
     * Count ungraded trainer submissions in assigned courses.
     *
     * @param int $userid
     * @return int
     */
    private function count_trainer_ungraded_submissions($userid)
    {
        global $DB;

        $courses = \local_sceh_rules\helper\cohort_filter::get_trainer_courses($userid);
        if (empty($courses)) {
            return 0;
        }

        $courseids = array_map(function ($course) {
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
    private function count_learner_pending_stream_selection($userid)
    {
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
    private function get_program_owner_quick_actions(int $userid): array
    {
        $categories = $this->get_program_owner_categories($userid);
        $categoryids = $this->get_program_owner_category_ids($userid);
        $primarycategoryid = $categoryids[0] ?? 0;
        $courseids = $this->get_program_owner_course_ids($userid);
        $primarycourseid = $courseids[0] ?? 0;
        // Detect single program vs multiple programs.
        $is_single_program = (count($categoryids) === 1);
        $singlecatparams = $is_single_program ? ['categoryid' => $primarycategoryid] : [];

        $managecoursechildren = [];

        if ($is_single_program) {
            // Simplified view for single program owner.
            $managecoursechildren[] = [
                'title' => get_string('poallcourses', 'block_sceh_dashboard'),
                'url' => new moodle_url('/course/index.php', $singlecatparams),
            ];
            $managecoursechildren[] = [
                'title' => get_string('pocreatecourse', 'block_sceh_dashboard'),
                'url' => new moodle_url('/course/edit.php', ['category' => $primarycategoryid]),
            ];
        }
        else {
            // Multi-program view (existing logic consolidated).
            $managecoursechildren[] = [
                'title' => get_string('poconsole', 'block_sceh_dashboard'),
                'url' => new moodle_url('/course/management.php'),
            ];
            $managecoursechildren[] = [
                'title' => get_string('poallcourses', 'block_sceh_dashboard'),
                'url' => new moodle_url('/course/index.php'),
            ];

            foreach ($categories as $category) {
                $catname = format_string($category->name);
                $managecoursechildren[] = [
                    'title' => get_string('pomanagecategory', 'block_sceh_dashboard', $catname),
                    'url' => new moodle_url('/course/management.php', ['categoryid' => (int)$category->id]),
                ];
            }
        }

        // Shared secondary actions.
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

        $actions = [
            [
                'title' => get_string('pomanagecourses', 'block_sceh_dashboard'),
                'icon' => 'fa-book-open',
                'color' => 'indigo',
                'url' => new moodle_url('/course/index.php', $singlecatparams),
                'children' => $managecoursechildren,
            ],
            [
                'title' => get_string('pomanagecompetencies', 'block_sceh_dashboard'),
                'icon' => 'fa-sitemap',
                'color' => 'green',
                'url' => new moodle_url('/admin/tool/lp/competencyframeworks.php', [
                    'pagecontextid' => context_system::instance()->id,
                ] + $singlecatparams),
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
                ? new moodle_url('/user/index.php', ['id' => $primarycourseid])
                : new moodle_url('/course/index.php'),
                'children' => [],
            ],
        ];

        $canmanagecohorts = has_capability('moodle/cohort:manage', context_system::instance(), $userid);
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
    private function get_program_owner_status_cards(int $userid): array
    {
        $categoryids = $this->get_program_owner_category_ids($userid);
        $courseids = $this->get_program_owner_course_ids($userid);
        $courseids = array_map('intval', $courseids);

        // Contextual params.
        $singlecatparams = (count($categoryids) === 1) ? ['categoryid' => reset($categoryids)] : [];

        $publishing = $this->get_program_owner_publishing_status($userid, $courseids);

        // Apply issue filter and single category scoping to publishing links.
        foreach ($publishing['steps'] as &$step) {
            $labelkey = str_replace('postep', '', $step['label']);
            if (strpos($step['url']->out(), 'stream_setup_check.php') !== false) {
                if ($step['count'] > 1) {
                    $step['url']->param('filter', 'issues');
                }
                if (!empty($singlecatparams)) {
                    $step['url']->params($singlecatparams);
                }
            }
            else if (strpos($step['url']->out(), 'course/index.php') !== false) {
                if (!empty($singlecatparams)) {
                    $step['url']->params($singlecatparams);
                }
            }
        }

        $cohorts = $this->get_program_owner_cohort_status($courseids);
        foreach ($cohorts['steps'] as &$step) {
            if (!empty($singlecatparams)) {
                $step['url']->params($singlecatparams);
            }
        }
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
    private function get_program_owner_category_ids(int $userid): array
    {
        $categories = $this->get_program_owner_categories($userid);
        return array_values(array_map(static function ($category): int {
            return (int)$category->id;
        }, $categories));
    }

    /**
     * Program Owner course ids in assigned categories.
     *
     * @param int $userid
     * @return int[]
     */
    private function get_program_owner_course_ids(int $userid): array
    {
        global $DB;

        $categoryids = $this->get_program_owner_category_ids($userid);
        if (empty($categoryids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
        $records = $DB->get_records_select('course', 'category ' . $insql, $params, 'id DESC', 'id');
        return array_values(array_map(static function ($record): int {
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
    private function get_program_owner_publishing_status(int $userid, array $courseids): array
    {
        global $DB;

        $draft = 0;
        $live = 0;
        $draftids = [];
        if (!empty($courseids)) {
            list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
            $draftrecords = $DB->get_records_select('course', 'id ' . $insql . ' AND visible = 0', $params, '', 'id');
            $draftids = array_keys($draftrecords);
            $draft = count($draftids);
            $live = (int)$DB->count_records_select('course', 'id ' . $insql . ' AND visible = 1', $params);
        }

        $issueids = $this->get_program_owner_stream_issue_course_ids($userid);
        $needschanges = count($issueids);
        $pendingapproval = max(0, $draft - $needschanges);
        $warn = ($needschanges > 0 || $draft > 0);

        $baseurl = new moodle_url('/course/index.php');

        // Deep linking logic.
        $drafturl = $baseurl;
        if ($draft === 1) {
            $drafturl = new moodle_url('/course/edit.php', ['id' => reset($draftids)]);
        }

        $needschangesurl = new moodle_url('/local/sceh_rules/stream_setup_check.php');
        if ($needschanges === 1) {
            $needschangesurl->param('id', reset($issueids));
        }

        return [
            'status' => $warn ? 'warning' : 'success',
            'steps' => [
                ['label' => get_string('postepdraft', 'block_sceh_dashboard'), 'count' => $draft, 'desc' => get_string('postepdraftdesc', 'block_sceh_dashboard'), 'url' => $drafturl],
                ['label' => get_string('postepneedschanges', 'block_sceh_dashboard'), 'count' => $needschanges, 'desc' => get_string('postepneedschangesdesc', 'block_sceh_dashboard'), 'url' => $needschangesurl],
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
    private function get_program_owner_cohort_status(array $courseids): array
    {
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
                }
                else if (!$hasboth) {
                    $setupprogress++;
                }
                else if ($visible === 1) {
                    $active++;
                }
                else {
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
    private function get_program_owner_learner_status(array $courseids): array
    {
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
    private function get_program_owner_trainer_status(int $userid, array $courseids): array
    {
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
            $assignedcourseids = array_map(static function ($row): int {
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
                ['label' => get_string('postepassigned', 'block_sceh_dashboard'), 'count' => $assigned, 'desc' => get_string('postepassigneddesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/user/index.php', ['id' => $primarycourseid])],
                ['label' => get_string('postepneedsreview', 'block_sceh_dashboard'), 'count' => $needsreview, 'desc' => get_string('postepneedsreviewdesc', 'block_sceh_dashboard'), 'url' => new moodle_url('/my/courses.php')],
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
    private function get_program_owner_content_pipeline_status(int $userid, array $courseids): array
    {
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
                }
                else {
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
            ],
        ];
    }

    private function get_dashboard_cards()
    {
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
        }
        else if ($is_program_owner) {
            return $this->get_program_owner_cards($USER->id);
        }
        else if ($is_trainer) {
            return $this->get_trainer_cards($USER->id);
        }

        return $this->get_learner_cards($USER->id);
    }

    /**
     * Dashboard cards for learners — flat grid, no section headings.
     * Order: Stream → Progress → Deadlines → Stream Selection → Competencies → Badges.
     *
     * @param int $userid
     * @return array
     */
    private function get_learner_cards($userid)
    {
        global $DB;
        $cards = [];

        // 1. Your Stream (if selected) — daily course launcher.
        $streamcard = $this->get_learner_stream_card($userid);
        if ($streamcard) {
            $cards[] = $streamcard;
        }

        // 2. My Progress — completion tracking.
        $cards[] = [
            'title' => get_string('myprogress', 'block_sceh_dashboard'),
            'icon' => 'fa-chart-line',
            'color' => 'orange',
            'url' => new moodle_url('/local/sceh_rules/stream_progress.php'),
        ];

        // 3. Upcoming Deadlines — with event count.
        $upcomingevents = $this->count_user_upcoming_events($userid, 7);
        $cards[] = [
            'title' => get_string('learnerupcomingtitle', 'block_sceh_dashboard'),
            'icon' => 'fa-calendar-day',
            'color' => 'red',
            'url' => new moodle_url('/calendar/view.php'),
            'count' => $upcomingevents,
        ];

        // 4. Stream Selection — pending count (only if > 0).
        $pendingstream = $this->count_learner_pending_stream_selection($userid);
        if ($pendingstream > 0) {
            $cards[] = [
                'title' => get_string('learnerpendingstreamtitle', 'block_sceh_dashboard'),
                'icon' => 'fa-code-branch',
                'color' => 'indigo',
                'url' => new moodle_url('/local/sceh_rules/stream_progress.php'),
                'count' => $pendingstream,
            ];
        }

        // 5. My Competencies — link to course competencies if active course exists.
        $competencyurl = new moodle_url('/admin/tool/lp/plans.php', ['userid' => $userid]);
        $learnercourses = enrol_get_users_courses($userid, true, 'id');
        if (!empty($learnercourses)) {
            $firstcourse = reset($learnercourses);
            $competencyurl = new moodle_url('/admin/tool/lp/coursecompetencies.php', [
                'courseid' => $firstcourse->id,
            ]);
        }
        $cards[] = [
            'title' => get_string('mycompetencies', 'block_sceh_dashboard'),
            'icon' => 'fa-check-circle',
            'color' => 'green',
            'url' => $competencyurl,
        ];

        // 6. My Badges — with earned count.
        $badgecount = (int)$DB->count_records_select(
            'badge_issued',
            'userid = :userid',
        ['userid' => $userid]
        );
        $cards[] = [
            'title' => get_string('mybadges', 'block_sceh_dashboard'),
            'icon' => 'fa-trophy',
            'color' => 'yellow',
            'url' => new moodle_url('/badges/mybadges.php'),
            'count' => $badgecount,
        ];

        return $cards;
    }

    /**
     * Dashboard cards for system admins.
     *
     * @return array
     */
    private function get_system_admin_cards()
    {
        global $DB, $USER;

        $systemcontext = context_system::instance();
        $cards = [];

        // 1. Manage Cohorts.
        if (has_capability('moodle/cohort:view', $systemcontext)) {
            $cards[] = [
                'title' => get_string('managecohorts', 'block_sceh_dashboard'),
                'icon' => 'fa-users',
                'color' => 'blue',
                'url' => new moodle_url('/cohort/index.php'),
            ];
        }

        // 2. Program Structure.
        $cards[] = [
            'title' => get_string('programstructure', 'block_sceh_dashboard'),
            'icon' => 'fa-graduation-cap',
            'color' => 'teal',
            'url' => new moodle_url('/course/index.php'),
        ];

        // 3. Custom Reports.
        if (has_capability('moodle/site:config', $systemcontext)) {
            $cards[] = [
                'title' => get_string('customreports', 'block_sceh_dashboard'),
                'icon' => 'fa-file-alt',
                'color' => 'orange',
                'url' => new moodle_url('/admin/category.php', ['category' => 'reports']),
            ];
        }

        // 4. Training Evaluation.
        if (has_capability('local/kirkpatrick_dashboard:view', $systemcontext)) {
            $cards[] = [
                'title' => get_string('trainingevaluation', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-pie',
                'color' => 'purple',
                'url' => new moodle_url('/local/kirkpatrick_dashboard/index.php'),
            ];
        }

        // 5. Badge Management.
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

        // 6. Competency Framework.
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

        return $cards;
    }

    /**
     * Dashboard cards for program owners, scoped to assigned categories.
     *
     * @param int $userid
     * @return array
     */
    private function get_program_owner_cards($userid)
    {
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
    /**
     * Dashboard cards for trainers.
     * Uses expandable sub-action pattern for courses (like PO's Manage Courses).
     * Single course → direct link. Multiple courses → expandable card.
     *
     * @param int $userid
     * @return array
     */
    private function get_trainer_cards($userid)
    {
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

        $cards = [];

        // 1. My Courses — single card or expandable.
        if (count($courses) === 1) {
            $course = reset($courses);
            $cards[] = [
                'title' => format_string($course->fullname),
                'icon' => 'fa-book-open',
                'color' => 'blue',
                'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
            ];
        }
        else if (count($courses) > 1) {
            $children = [];
            foreach ($courses as $course) {
                $children[] = [
                    'title' => format_string($course->fullname),
                    'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
                ];
            }
            $cards[] = [
                'title' => get_string('trainermycourses', 'block_sceh_dashboard'),
                'icon' => 'fa-book-open',
                'color' => 'blue',
                'children' => $children,
            ];
        }
        else {
            // No assigned courses — fallback to program structure.
            $cards[] = [
                'title' => get_string('programstructure', 'block_sceh_dashboard'),
                'icon' => 'fa-graduation-cap',
                'color' => 'teal',
                'url' => new moodle_url('/course/index.php'),
            ];
        }

        // 2. Attendance Reports.
        $cards[] = [
            'title' => get_string('attendancereports', 'block_sceh_dashboard'),
            'icon' => 'fa-chart-bar',
            'color' => 'red',
            'url' => $attendanceurl,
        ];

        // 3. Training Evaluation (coach only).
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
    private function get_program_owner_categories($userid)
    {
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

    private function render_card($card)
    {
        if (class_exists('\local_sceh_rules\output\sceh_card') && !isset($card['count'])) {
            return \local_sceh_rules\output\sceh_card::simple($card);
        }

        $html = html_writer::start_div('sceh-card sceh-card-system sceh-card-' . $card['color']);
        $html .= html_writer::start_tag('a', ['href' => $card['url'], 'class' => 'sceh-card-link']);

        $html .= html_writer::div('<i class="fa ' . $card['icon'] . ' fa-3x"></i>', 'sceh-card-icon');
        $html .= html_writer::div($card['title'], 'sceh-card-title');

        // Optional count badge.
        if (isset($card['count'])) {
            $html .= html_writer::div((string)(int)$card['count'], 'sceh-po-status-total');
        }

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
    private function get_learner_stream_card($userid)
    {
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
    private function get_first_regular_course_id()
    {
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
    private function get_first_enrolled_course_id($userid)
    {
        $courses = enrol_get_users_courses($userid, true, 'id');
        if (empty($courses)) {
            return 0;
        }

        $courseids = array_map(function ($course) {
            return (int)$course->id;
        }, $courses);
        sort($courseids, SORT_NUMERIC);

        return (int)reset($courseids);
    }

    /**
     * Build Sysadmin status/monitoring cards.
     *
     * @param int $userid
     * @return array
     */
    private function get_sysadmin_status_cards(int $userid): array
    {
        global $DB;
        $failedtasks = $this->count_failed_scheduled_tasks();
        $totalcohorts = $this->count_total_cohorts();
        $overdueevents = $this->count_user_overdue_events($userid);
        $activeusers = (int)$DB->count_records_select('user', 'suspended = 0 AND deleted = 0 AND id > 1');

        return [
            [
                'title' => get_string('sysadmincrontasks', 'block_sceh_dashboard'),
                'icon' => 'fa-gears',
                'status' => $failedtasks > 0 ? 'danger' : 'success',
                'steps' => [
                    [
                        'label' => get_string('sysadminfailedtasks', 'block_sceh_dashboard'),
                        'count' => $failedtasks,
                        'url' => new moodle_url('/admin/tool/task/scheduledtasks.php'),
                    ],
                ],
            ],
            [
                'title' => get_string('sysadminactiveusers', 'block_sceh_dashboard'),
                'icon' => 'fa-user-group',
                'status' => 'info',
                'steps' => [
                    [
                        'label' => get_string('sysadmintotalactive', 'block_sceh_dashboard'),
                        'count' => $activeusers,
                        'url' => new moodle_url('/admin/user.php'),
                    ],
                ],
            ],
            [
                'title' => get_string('sysadminoverdue', 'block_sceh_dashboard'),
                'icon' => 'fa-triangle-exclamation',
                'status' => $overdueevents > 0 ? 'warning' : 'success',
                'steps' => [
                    [
                        'label' => get_string('sysadminoverdueevents', 'block_sceh_dashboard'),
                        'count' => $overdueevents,
                        'url' => new moodle_url('/calendar/view.php'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Build Trainer status/monitoring cards.
     *
     * @param int $userid
     * @return array
     */
    private function get_trainer_status_cards(int $userid): array
    {
        $courses = \local_sceh_rules\helper\cohort_filter::get_trainer_courses($userid);
        $coursecount = count($courses);
        $ungraded = $this->count_trainer_ungraded_submissions($userid);
        $upcomingevents = $this->count_user_upcoming_events($userid, 7);

        return [
            [
                'title' => get_string('trainerungradedtitle', 'block_sceh_dashboard'),
                'icon' => 'fa-clipboard-check',
                'status' => $ungraded > 0 ? 'warning' : 'success',
                'steps' => [
                    [
                        'label' => get_string('trainerungradedlabel', 'block_sceh_dashboard'),
                        'count' => $ungraded,
                        'url' => new moodle_url('/my/courses.php'),
                    ],
                ],
            ],
            [
                'title' => get_string('trainercoursestitle', 'block_sceh_dashboard'),
                'icon' => 'fa-book-open',
                'status' => 'info',
                'steps' => [
                    [
                        'label' => get_string('trainercourseslabel', 'block_sceh_dashboard'),
                        'count' => $coursecount,
                        'url' => new moodle_url('/my/courses.php'),
                    ],
                ],
            ],
            [
                'title' => get_string('trainerupcomingtitle', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-day',
                'status' => 'info',
                'steps' => [
                    [
                        'label' => get_string('trainerupcominglabel', 'block_sceh_dashboard'),
                        'count' => $upcomingevents,
                        'url' => new moodle_url('/calendar/view.php'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Build Learner status/monitoring cards.
     *
     * @param int $userid
     * @return array
     */
    private function get_learner_status_cards(int $userid): array
    {
        global $DB;
        $pendingstream = $this->count_learner_pending_stream_selection($userid);
        $upcomingevents = $this->count_user_upcoming_events($userid, 7);
        $badgecount = (int)$DB->count_records_select(
            'badge_issued',
            'userid = :userid',
        ['userid' => $userid]
        );

        return [
            [
                'title' => get_string('learnerpendingstreamtitle', 'block_sceh_dashboard'),
                'icon' => 'fa-code-branch',
                'status' => $pendingstream > 0 ? 'warning' : 'success',
                'steps' => [
                    [
                        'label' => get_string('learnerpendingstreamlabel', 'block_sceh_dashboard'),
                        'count' => $pendingstream,
                        'url' => new moodle_url('/local/sceh_rules/stream_progress.php'),
                    ],
                ],
            ],
            [
                'title' => get_string('learnerupcomingtitle', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-day',
                'status' => 'info',
                'steps' => [
                    [
                        'label' => get_string('learnerupcominglabel', 'block_sceh_dashboard'),
                        'count' => $upcomingevents,
                        'url' => new moodle_url('/calendar/view.php'),
                    ],
                ],
            ],
            [
                'title' => get_string('learnerbadgestitle', 'block_sceh_dashboard'),
                'icon' => 'fa-award',
                'status' => 'info',
                'steps' => [
                    [
                        'label' => get_string('learnerbadgeslabel', 'block_sceh_dashboard'),
                        'count' => $badgecount,
                        'url' => new moodle_url('/badges/mybadges.php'),
                    ],
                ],
            ],
        ];
    }


    public function applicable_formats()
    {
        return [
            'site-index' => true,
            'my' => true,
            'course-view' => false
        ];
    }

    public function has_config()
    {
        return false;
    }
}
