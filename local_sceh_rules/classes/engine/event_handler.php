<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Base event handler class for the rules engine
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\engine;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract base class for event handling
 */
abstract class event_handler {
    
    /**
     * Handle an event
     *
     * @param \core\event\base $event The event to handle
     * @return void
     */
    abstract public function handle(\core\event\base $event);
    
    /**
     * Check if the handler should process this event
     *
     * @param \core\event\base $event The event to check
     * @return bool True if should process
     */
    protected function should_process(\core\event\base $event) {
        // Check if rules engine is enabled
        if (!get_config('local_sceh_rules', 'enabled')) {
            return false;
        }
        
        // Check if event has required data
        if (empty($event->userid)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get user from event
     *
     * @param \core\event\base $event The event
     * @return int User ID
     */
    protected function get_user_from_event(\core\event\base $event) {
        return $event->userid;
    }
    
    /**
     * Get course from event
     *
     * @param \core\event\base $event The event
     * @return int Course ID
     */
    protected function get_course_from_event(\core\event\base $event) {
        return $event->courseid;
    }
}
