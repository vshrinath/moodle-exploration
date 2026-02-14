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
 * Tests for shared SCEH card renderer.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules;

use local_sceh_rules\output\sceh_card;

defined('MOODLE_INTERNAL') || die();

/**
 * Card renderer tests.
 *
 * @group local_sceh_rules
 */
class sceh_card_test extends \advanced_testcase {
    /**
     * Simple card includes expected structure.
     */
    public function test_simple_card_structure() {
        $this->resetAfterTest(true);

        $html = sceh_card::simple([
            'title' => 'Manage Cohorts',
            'icon' => 'fa-users',
            'color' => 'blue',
            'size' => 'small',
            'url' => new \moodle_url('/cohort/index.php'),
        ]);

        $this->assertStringContainsString('sceh-card', $html);
        $this->assertStringContainsString('sceh-card-blue', $html);
        $this->assertStringContainsString('sceh-card-small', $html);
        $this->assertStringContainsString('Manage Cohorts', $html);
        $this->assertStringContainsString('/cohort/index.php', $html);
    }

    /**
     * Metric card includes metric and status blocks.
     */
    public function test_metric_card_structure() {
        $this->resetAfterTest(true);

        $html = sceh_card::metric([
            'title' => 'Attendance Rate',
            'value' => '78%',
            'trend' => '+5%',
            'status' => 'warning',
            'details' => '2 learners below threshold',
            'size' => 'small',
        ]);

        $this->assertStringContainsString('sceh-card-system', $html);
        $this->assertStringContainsString('sceh-metric-value', $html);
        $this->assertStringContainsString('78%', $html);
        $this->assertStringContainsString('sceh-status-warning', $html);
    }

    /**
     * List card includes list items and footer actions.
     */
    public function test_list_card_structure() {
        $this->resetAfterTest(true);

        $html = sceh_card::list([
            'title' => 'At-Risk Learners',
            'icon' => 'fa-users',
            'status' => 'danger',
            'items' => [
                [
                    'icon' => 'fa-user',
                    'text' => 'Dr. Kumar',
                    'subtext' => '45% attendance',
                ],
            ],
            'footer_actions' => [
                [
                    'text' => 'View All',
                    'url' => new \moodle_url('/my/'),
                    'style' => 'secondary',
                ],
            ],
        ]);

        $this->assertStringContainsString('sceh-list-item', $html);
        $this->assertStringContainsString('Dr. Kumar', $html);
        $this->assertStringContainsString('View All', $html);
        $this->assertStringNotContainsString('sceh-status-danger', $html);

        $htmlwithstatus = sceh_card::list([
            'title' => 'At-Risk Learners',
            'status' => 'danger',
            'status_text' => 'Immediate attention required',
            'items' => [],
        ]);
        $this->assertStringContainsString('sceh-status-danger', $htmlwithstatus);
    }

    /**
     * Detail card includes sections and actions.
     */
    public function test_detail_card_structure() {
        $this->resetAfterTest(true);

        $html = sceh_card::detail([
            'title' => 'Attendance Rule',
            'status' => 'success',
            'status_text' => 'Enabled',
            'sections' => [
                [
                    'title' => 'Course',
                    'content' => 'Mock Allied Assist Program',
                ],
            ],
            'actions' => [
                [
                    'text' => 'Edit',
                    'url' => new \moodle_url('/local/sceh_rules/edit_attendance_rule.php'),
                    'style' => 'secondary',
                ],
            ],
        ]);

        $this->assertStringContainsString('sceh-detail-section', $html);
        $this->assertStringContainsString('Mock Allied Assist Program', $html);
        $this->assertStringContainsString('Edit', $html);
        $this->assertStringContainsString('sceh-status-success', $html);
    }
}
