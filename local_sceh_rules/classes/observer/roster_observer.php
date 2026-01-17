<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Roster event observer
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\observer;

use local_sceh_rules\engine\event_handler;
use local_sceh_rules\rules\roster_rule;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer for roster-related events
 */
class roster_observer extends event_handler {
    
    /**
     * Handle roster completion event
     *
     * @param \core\event\base $event The roster completion event
     * @return void
     */
    public static function roster_completed(\core\event\base $event) {
        $observer = new self();
        $observer->handle($event);
    }
    
    /**
     * Handle the event
     *
     * @param \core\event\base $event The event to handle
     * @return void
     */
    public function handle(\core\event\base $event) {
        if (!$this->should_process($event)) {
            return;
        }
        
        if (!get_config('local_sceh_rules', 'roster_rules_enabled')) {
            return;
        }
        
        // Extract roster type from event data
        $rostertype = $this->extract_roster_type($event);
        if (!$rostertype) {
            return;
        }
        
        $userid = $this->get_user_from_event($event);
        
        // Process roster completion
        $evaluator = new roster_rule();
        $evaluator->process_roster_completion($userid, $rostertype);
    }
    
    /**
     * Extract roster type from event
     *
     * @param \core\event\base $event The event
     * @return string|null Roster type or null if not found
     */
    protected function extract_roster_type(\core\event\base $event) {
        // Try to get roster type from event other data
        if (isset($event->other['rostertype'])) {
            return $event->other['rostertype'];
        }
        
        // Try to get from event object data
        if (isset($event->other['objectid'])) {
            // This would need to query the scheduler or database activity
            // to determine the roster type based on the appointment/entry
            return $this->determine_roster_type_from_appointment($event->other['objectid']);
        }
        
        return null;
    }
    
    /**
     * Determine roster type from appointment or database entry
     *
     * @param int $appointmentid Appointment or entry ID
     * @return string|null Roster type
     */
    protected function determine_roster_type_from_appointment($appointmentid) {
        global $DB;
        
        // This is a placeholder - actual implementation would depend on
        // how roster types are stored in the scheduler or database activity
        
        // Example: Check if appointment has a custom field indicating roster type
        $sql = "SELECT d.name as fieldname, dd.content
                FROM {data_content} dd
                JOIN {data_fields} d ON d.id = dd.fieldid
                WHERE dd.recordid = :recordid
                AND d.name = 'rostertype'";
        
        $result = $DB->get_record_sql($sql, ['recordid' => $appointmentid]);
        
        if ($result && in_array($result->content, ['morning', 'night', 'training', 'satellite', 'posting'])) {
            return $result->content;
        }
        
        return null;
    }
}
