<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Theme renderer overrides.
 *
 * @package    theme_sceh
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_sceh\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Core renderer override for theme_sceh.
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Check whether user has Program Owner assignment in any category context.
     *
     * @param int $userid
     * @return bool
     */
    private function has_program_owner_category_assignment(int $userid): bool {
        global $DB;

        if ($userid <= 0) {
            return false;
        }

        $sql = "SELECT 1
                  FROM {role_assignments} ra
                  JOIN {context} ctx
                    ON ctx.id = ra.contextid
                   AND ctx.contextlevel = :contextlevel
                  JOIN {role} r
                    ON r.id = ra.roleid
                 WHERE ra.userid = :userid
                   AND r.shortname IN (:shortname, :fallbackshortname)";

        return $DB->record_exists_sql($sql, [
            'contextlevel' => CONTEXT_COURSECAT,
            'userid' => $userid,
            'shortname' => 'sceh_program_owner',
            'fallbackshortname' => 'programowner',
        ]);
    }

    /**
     * Page header.
     *
     * Redirect logged-in users from site home (/) to Dashboard (/my/).
     *
     * @return string HTML
     */
    public function header() {
        if ($this->page->pagetype === 'site-index') {
            if (isloggedin() && !isguestuser()) {
                redirect(new \moodle_url('/my/'));
            }
            if (!isloggedin()) {
                redirect(new \moodle_url('/login/index.php'));
            }
        }

        return parent::header();
    }

    /**
     * Add role classes to body so dashboard blocks can be shown/hidden per role.
     *
     * @param array $additionalclasses
     * @return string
     */
    public function body_attributes($additionalclasses = []) {
        global $USER;

        if (isloggedin() && !isguestuser()) {
            $context = \context_system::instance();
            $userid = (int)($USER->id ?? 0);

            if (has_capability('local/sceh_rules:systemadmin', $context)) {
                $additionalclasses[] = 'sceh-role-systemadmin';
            } else if (
                has_capability('local/sceh_rules:programowner', $context) ||
                $this->has_program_owner_category_assignment($userid)
            ) {
                $additionalclasses[] = 'sceh-role-programowner';
            } else if (has_capability('local/sceh_rules:trainer', $context)) {
                $additionalclasses[] = 'sceh-role-trainer';
                if (
                    class_exists('\local_sceh_rules\helper\trainer_coach_helper') &&
                    \local_sceh_rules\helper\trainer_coach_helper::is_trainer_coach($userid)
                ) {
                    $additionalclasses[] = 'sceh-role-trainercoach';
                }
            } else {
                $additionalclasses[] = 'sceh-role-learner';
            }
        }

        return parent::body_attributes($additionalclasses);
    }
}
