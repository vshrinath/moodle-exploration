<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Package imported event.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a course package is successfully imported.
 */
class package_imported extends \core\event\base {
    
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'course';
    }
    
    public static function get_name() {
        return get_string('event_package_imported', 'local_sceh_importer');
    }
    
    public function get_description() {
        $created = $this->other['created_count'] ?? 0;
        $replaced = $this->other['replaced_count'] ?? 0;
        return "User {$this->userid} imported package into course {$this->courseid}: " .
               "{$created} activities created, {$replaced} activities replaced.";
    }
    
    public function get_url() {
        return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
    }
}
