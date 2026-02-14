<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Theme callbacks for theme_sceh.
 *
 * @package    theme_sceh
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the SCSS content for the theme.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_sceh_get_main_scss_content($theme): string {
    global $CFG;

    $scss = theme_boost_get_main_scss_content($theme);
    $scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/sceh/scss/tokens.scss');
    $scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/sceh/scss/components.scss');
    $scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/sceh/scss/internal.scss');
    $scss .= "\n" . file_get_contents($CFG->dirroot . '/theme/sceh/scss/login.scss');
    return $scss;
}
