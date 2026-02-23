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
use local_sceh_rules\task\evaluate_rules_task;

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
            debugging(
                'roster_observer: could not determine roster type from event ' .
                $event->eventname . ' (objectid=' . ($event->objectid ?? 'null') . ')',
                DEBUG_DEVELOPER
            );
            return;
        }
        
        $userid = $this->get_user_from_event($event);
        
        // Queue async evaluation instead of blocking the web request.
        $this->queue_roster_evaluation($userid, $rostertype);
    }
    
    /**
     * Extract roster type from event
     *
     * @param \core\event\base $event The event
     * @return string|null Roster type or null if not found
     */
    protected function extract_roster_type(\core\event\base $event) {
        // 1. Check event other data (custom events may provide this directly).
        if (!empty($event->other['rostertype'])) {
            return $event->other['rostertype'];
        }
        
        // 2. Try to resolve from mod_scheduler appointment.
        if (!empty($event->objectid)) {
            return $this->determine_roster_type_from_scheduler($event->objectid);
        }
        
        return null;
    }
    
    /**
     * Determine roster type from a scheduler appointment.
     *
     * Queries {scheduler_appointment} → {scheduler_slots} → slot name,
     * then maps the slot name to a known roster type.
     *
     * @param int $appointmentid Scheduler appointment ID
     * @return string|null Roster type or null if not resolvable
     */
    protected function determine_roster_type_from_scheduler($appointmentid) {
        global $DB;
        
        // Check if scheduler tables exist (plugin may not be installed).
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('scheduler_appointment') || !$dbman->table_exists('scheduler_slots')) {
            debugging('roster_observer: mod_scheduler tables not found', DEBUG_DEVELOPER);
            return null;
        }
        
        // Appointment → Slot → Slot name contains the roster type keyword.
        $sql = "SELECT s.appointmentlocation, s.notes
                  FROM {scheduler_appointment} a
                  JOIN {scheduler_slots} s ON s.id = a.slotid
                 WHERE a.id = :appointmentid";
        
        $slot = $DB->get_record_sql($sql, ['appointmentid' => $appointmentid]);
        
        if (!$slot) {
            return null;
        }
        
        // Map slot metadata to roster type.
        // Convention: the slot's appointmentlocation or notes field contains
        // one of the known roster type keywords.
        return $this->match_roster_type(
            ($slot->appointmentlocation ?? '') . ' ' . ($slot->notes ?? '')
        );
    }
    
    /**
     * Match a text string to a known roster type.
     *
     * @param string $text Text to search for roster type keywords
     * @return string|null Matched roster type or null
     */
    protected function match_roster_type(string $text): ?string {
        $text = strtolower($text);
        $types = ['morning', 'night', 'training', 'satellite', 'posting'];
        
        foreach ($types as $type) {
            if (strpos($text, $type) !== false) {
                return $type;
            }
        }
        
        return null;
    }
    
    /**
     * Queue an adhoc task for roster rule evaluation.
     *
     * @param int $userid User ID
     * @param string $rostertype Roster type
     */
    protected function queue_roster_evaluation(int $userid, string $rostertype) {
        $task = new evaluate_rules_task();
        $task->set_custom_data((object) [
            'ruletype' => 'roster',
            'userid' => $userid,
            'rostertype' => $rostertype,
        ]);
        $task->set_component('local_sceh_rules');
        \core\task\manager::queue_adhoc_task($task, true); // true = deduplicate.
    }
}
