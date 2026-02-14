<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for rules page renderer helper.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules;

use local_sceh_rules\helper\rules_table_renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * Rules table/card renderer tests.
 *
 * @group local_sceh_rules
 */
class rules_table_renderer_test extends \advanced_testcase {
    /**
     * Attendance renderer returns no-rules message for empty input.
     */
    public function test_render_attendance_rules_table_empty() {
        $this->resetAfterTest(true);

        $html = rules_table_renderer::render_attendance_rules_table([], 'edit_attendance_rule.php', 'attendance_rules.php');
        $this->assertStringContainsString(get_string('norulesfound', 'local_sceh_rules'), $html);
    }

    /**
     * Attendance renderer includes card and action links.
     */
    public function test_render_attendance_rules_table_card_output() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $competency = $this->create_test_competency();

        $rule = (object)[
            'competencyid' => $competency->id,
            'courseid' => $course->id,
            'threshold' => 75,
            'enabled' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $rule->id = $DB->insert_record('local_sceh_attendance_rules', $rule);

        $rules = $DB->get_records('local_sceh_attendance_rules');
        $html = rules_table_renderer::render_attendance_rules_table($rules, 'edit_attendance_rule.php', 'attendance_rules.php');

        $this->assertStringContainsString('sceh-card-system', $html);
        $this->assertStringContainsString(format_string($competency->shortname), $html);
        $this->assertStringContainsString('action=delete', $html);
    }

    /**
     * Roster renderer includes card and action links.
     */
    public function test_render_roster_rules_table_card_output() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $competency = $this->create_test_competency();
        $rule = (object)[
            'rostertype' => 'morning',
            'competencyid' => $competency->id,
            'evidencedesc' => 'Completed morning class roster',
            'enabled' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $rule->id = $DB->insert_record('local_sceh_roster_rules', $rule);

        $rules = $DB->get_records('local_sceh_roster_rules');
        $html = rules_table_renderer::render_roster_rules_table($rules, 'edit_roster_rule.php', 'roster_rules.php');

        $this->assertStringContainsString('sceh-card-system', $html);
        $this->assertStringContainsString(get_string('roster_type_morning', 'local_sceh_rules'), $html);
        $this->assertStringContainsString('action=delete', $html);
    }

    /**
     * Helper: Create test competency.
     *
     * @return \stdClass
     */
    protected function create_test_competency() {
        global $DB;

        static $counter = 0;
        $counter++;

        $generator = $this->getDataGenerator()->get_plugin_generator('core_competency');
        $framework = $generator->create_framework([
            'shortname' => 'testrenderframework' . $counter,
            'idnumber' => 'TRF' . $counter,
            'visible' => 1,
        ]);
        $frameworkid = $framework->get('id');

        $competencypersistent = $generator->create_competency([
            'shortname' => 'testrendercomp' . $counter,
            'idnumber' => 'TRC' . $counter,
            'competencyframeworkid' => $frameworkid,
        ]);

        return $DB->get_record('competency', ['id' => $competencypersistent->get('id')], '*', MUST_EXIST);
    }
}
