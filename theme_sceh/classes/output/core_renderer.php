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
}
