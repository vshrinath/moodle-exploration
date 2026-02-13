<?php
defined('MOODLE_INTERNAL') || die();

class block_sceh_dashboard extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_sceh_dashboard');
    }
    
    public function get_content() {
        global $OUTPUT, $USER;
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass();
        $this->content->text = '';
        
        // Get user role-based cards
        $cards = $this->get_dashboard_cards();
        
        // Render cards
        $this->content->text .= html_writer::start_div('sceh-dashboard-grid');
        
        foreach ($cards as $card) {
            $this->content->text .= $this->render_card($card);
        }
        
        $this->content->text .= html_writer::end_div();
        
        return $this->content;
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
        global $DB;
        
        $systemcontext = context_system::instance();
        $cards = [];
        $attendanceurl = new moodle_url('/my/courses.php');
        $attendancecourseid = $this->get_first_regular_course_id();

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
        $attendanceurl = new moodle_url('/mod/attendance/index.php');

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
