<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Dashboard card link regression tests.
 *
 * @package    block_sceh_dashboard
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Card link regression tests.
 *
 * @group block_sceh_dashboard
 */
final class block_sceh_dashboard_card_links_test extends \advanced_testcase {
    /**
     * Ensure the block class and base dependencies are loaded.
     */
    private function load_block_class(): void {
        global $CFG;

        if (!class_exists(\block_sceh_dashboard::class, false)) {
            require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
            require_once($CFG->dirroot . '/blocks/sceh_dashboard/block_sceh_dashboard.php');
        }
    }

    /**
     * Trainer attendance card must not point to attendance index without a course id.
     */
    public function test_trainer_attendance_card_fallback_is_safe(): void {
        $this->resetAfterTest(true);
        $this->load_block_class();

        $user = $this->getDataGenerator()->create_user();
        $block = new \block_sceh_dashboard();
        $method = new \ReflectionMethod(\block_sceh_dashboard::class, 'get_trainer_cards');
        $method->setAccessible(true);
        $cards = $method->invoke($block, $user->id);

        $this->assertNotEmpty($cards);
        $firstcard = reset($cards);
        $firsturl = $firstcard['url']->out(false);

        $this->assertStringContainsString('/my/courses.php', $firsturl);
        $this->assertStringNotContainsString('/mod/attendance/index.php', $firsturl);
    }

    /**
     * Competency framework card must include required pagecontextid param.
     */
    public function test_system_admin_competency_card_has_pagecontextid(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->load_block_class();

        $block = new \block_sceh_dashboard();
        $method = new \ReflectionMethod(\block_sceh_dashboard::class, 'get_system_admin_cards');
        $method->setAccessible(true);
        $cards = $method->invoke($block);

        $competencycard = null;
        foreach ($cards as $card) {
            $url = $card['url']->out(false);
            if (strpos($url, '/admin/tool/lp/competencyframeworks.php') !== false) {
                $competencycard = $card;
                break;
            }
        }

        $this->assertNotNull($competencycard);
        $this->assertStringContainsString('pagecontextid=', $competencycard['url']->out(false));
    }

    /**
     * Badge management card must include the required badge type query param.
     */
    public function test_system_admin_badge_card_has_type_param(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $this->load_block_class();

        $block = new \block_sceh_dashboard();
        $method = new \ReflectionMethod(\block_sceh_dashboard::class, 'get_system_admin_cards');
        $method->setAccessible(true);
        $cards = $method->invoke($block);

        $badgecard = null;
        foreach ($cards as $card) {
            $url = $card['url']->out(false);
            if (strpos($url, '/badges/index.php') !== false) {
                $badgecard = $card;
                break;
            }
        }

        $this->assertNotNull($badgecard);
        $this->assertStringContainsString('type=1', $badgecard['url']->out(false));
    }

    /**
     * Learner card set should avoid links that require missing runtime params.
     */
    public function test_learner_cards_avoid_missingparam_routes(): void {
        $this->resetAfterTest(true);
        $this->load_block_class();

        $user = $this->getDataGenerator()->create_user();
        $block = new \block_sceh_dashboard();
        $method = new \ReflectionMethod(\block_sceh_dashboard::class, 'get_learner_cards');
        $method->setAccessible(true);
        $cards = $method->invoke($block, $user->id);

        foreach ($cards as $card) {
            $url = $card['url']->out(false);
            $this->assertStringNotContainsString('/mod/data/index.php', $url);
            $this->assertStringNotContainsString('/mod/attendance/index.php', $url);
            $this->assertStringNotContainsString('/report/outline/user.php', $url);
        }
    }

    /**
     * Trainer coach users should see the training evaluation card in trainer view.
     */
    public function test_trainer_coach_gets_training_evaluation_card(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->load_block_class();

        $user = $this->getDataGenerator()->create_user();

        $cohort = $this->getDataGenerator()->create_cohort([
            'name' => 'Trainer Coaches',
            'idnumber' => \local_sceh_rules\helper\trainer_coach_helper::TRAINER_COACH_COHORT_IDNUMBER,
            'contextid' => \context_system::instance()->id,
        ]);
        $DB->insert_record('cohort_members', [
            'cohortid' => $cohort->id,
            'userid' => $user->id,
            'timeadded' => time(),
        ]);

        $block = new \block_sceh_dashboard();
        $method = new \ReflectionMethod(\block_sceh_dashboard::class, 'get_trainer_cards');
        $method->setAccessible(true);
        $cards = $method->invoke($block, $user->id);

        $found = false;
        foreach ($cards as $card) {
            $url = $card['url']->out(false);
            if (strpos($url, '/local/kirkpatrick_dashboard/index.php') !== false) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }
}
