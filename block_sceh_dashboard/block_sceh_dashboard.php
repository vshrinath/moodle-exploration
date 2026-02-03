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
        global $USER, $DB;
        
        $context = context_system::instance();
        $cards = [];
        
        // Check user roles
        $is_admin = has_capability('moodle/site:config', $context);
        $is_teacher = has_capability('moodle/course:update', $context);
        $is_student = !$is_admin && !$is_teacher;
        
        if ($is_student) {
            // Trainee cards
            $cards[] = [
                'title' => get_string('caselogbook', 'block_sceh_dashboard'),
                'icon' => 'fa-clipboard-list',
                'color' => 'blue',
                'url' => new moodle_url('/mod/data/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('mycompetencies', 'block_sceh_dashboard'),
                'icon' => 'fa-check-circle',
                'color' => 'green',
                'url' => new moodle_url('/admin/tool/lp/plans.php', ['userid' => $USER->id])
            ];
            
            $cards[] = [
                'title' => get_string('attendance', 'block_sceh_dashboard'),
                'icon' => 'fa-calendar-check',
                'color' => 'red',
                'url' => new moodle_url('/mod/attendance/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('mybadges', 'block_sceh_dashboard'),
                'icon' => 'fa-trophy',
                'color' => 'yellow',
                'url' => new moodle_url('/badges/mybadges.php')
            ];
            
            $cards[] = [
                'title' => get_string('credentialingsheet', 'block_sceh_dashboard'),
                'icon' => 'fa-certificate',
                'color' => 'purple',
                'url' => new moodle_url('/mod/data/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('videolibrary', 'block_sceh_dashboard'),
                'icon' => 'fa-video',
                'color' => 'teal',
                'url' => new moodle_url('/course/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('myprogress', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-line',
                'color' => 'orange',
                'url' => new moodle_url('/report/outline/user.php', ['id' => $USER->id, 'course' => 1, 'mode' => 'outline'])
            ];
            
        } else if ($is_teacher || $is_admin) {
            // Admin/Mentor cards
            $cards[] = [
                'title' => get_string('managecohorts', 'block_sceh_dashboard'),
                'icon' => 'fa-users',
                'color' => 'blue',
                'url' => new moodle_url('/cohort/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('competencyframework', 'block_sceh_dashboard'),
                'icon' => 'fa-sitemap',
                'color' => 'green',
                'url' => new moodle_url('/admin/tool/lp/competencyframeworks.php')
            ];
            
            $cards[] = [
                'title' => get_string('attendancereports', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-bar',
                'color' => 'red',
                'url' => new moodle_url('/mod/attendance/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('trainingevaluation', 'block_sceh_dashboard'),
                'icon' => 'fa-chart-pie',
                'color' => 'purple',
                'url' => new moodle_url('/local/kirkpatrick_dashboard/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('badgemanagement', 'block_sceh_dashboard'),
                'icon' => 'fa-award',
                'color' => 'yellow',
                'url' => new moodle_url('/badges/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('programstructure', 'block_sceh_dashboard'),
                'icon' => 'fa-graduation-cap',
                'color' => 'teal',
                'url' => new moodle_url('/course/index.php')
            ];
            
            $cards[] = [
                'title' => get_string('customreports', 'block_sceh_dashboard'),
                'icon' => 'fa-file-alt',
                'color' => 'orange',
                'url' => new moodle_url('/admin/category.php', ['category' => 'reports'])
            ];
            
            $cards[] = [
                'title' => get_string('rosterrules', 'block_sceh_dashboard'),
                'icon' => 'fa-cogs',
                'color' => 'indigo',
                'url' => new moodle_url('/local/sceh_rules/roster_rules.php')
            ];
        }
        
        return $cards;
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
    
    private function get_activity_id($shortname) {
        global $DB;
        // Try to find the activity by shortname or name
        $cm = $DB->get_record_sql(
            "SELECT cm.id FROM {course_modules} cm
             JOIN {modules} m ON m.id = cm.module
             JOIN {data} d ON d.id = cm.instance
             WHERE m.name = 'data' AND d.name LIKE ?
             LIMIT 1",
            ['%' . $shortname . '%']
        );
        return $cm ? $cm->id : 0;
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
