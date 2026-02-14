<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Tests for trainer coach helper.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules;

use local_sceh_rules\helper\trainer_coach_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Trainer coach helper tests.
 *
 * @group local_sceh_rules
 */
class trainer_coach_helper_test extends \advanced_testcase {
    /**
     * Users in the trainer coach cohort should be detected.
     */
    public function test_is_trainer_coach_detects_member_by_idnumber() {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();

        $cohort = $this->getDataGenerator()->create_cohort([
            'name' => 'Trainer Coaches',
            'idnumber' => trainer_coach_helper::TRAINER_COACH_COHORT_IDNUMBER,
            'contextid' => \context_system::instance()->id,
        ]);
        $DB->insert_record('cohort_members', [
            'cohortid' => $cohort->id,
            'userid' => $user->id,
            'timeadded' => time(),
        ]);

        $this->assertTrue(trainer_coach_helper::is_trainer_coach((int)$user->id));
    }

    /**
     * Users outside trainer coach cohort should not be detected.
     */
    public function test_is_trainer_coach_returns_false_for_non_member() {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(trainer_coach_helper::is_trainer_coach((int)$user->id));
    }
}
