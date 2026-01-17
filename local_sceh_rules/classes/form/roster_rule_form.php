<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form for editing roster rules
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Roster rule edit form
 */
class roster_rule_form extends \moodleform {
    
    /**
     * Define the form
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Roster type selector
        $rostertypes = [
            'morning' => get_string('roster_type_morning', 'local_sceh_rules'),
            'night' => get_string('roster_type_night', 'local_sceh_rules'),
            'training' => get_string('roster_type_training', 'local_sceh_rules'),
            'satellite' => get_string('roster_type_satellite', 'local_sceh_rules'),
            'posting' => get_string('roster_type_posting', 'local_sceh_rules'),
        ];
        $mform->addElement('select', 'rostertype', 
            get_string('roster_rule_type', 'local_sceh_rules'), 
            $rostertypes);
        $mform->addRule('rostertype', null, 'required', null, 'client');
        
        // Competency selector
        $competencies = $DB->get_records_menu('competency', null, 'shortname', 'id, shortname');
        $mform->addElement('select', 'competencyid', 
            get_string('roster_rule_competency', 'local_sceh_rules'), 
            $competencies);
        $mform->addRule('competencyid', null, 'required', null, 'client');
        
        // Evidence description
        $mform->addElement('textarea', 'evidencedesc', 
            get_string('roster_rule_evidence', 'local_sceh_rules'),
            ['rows' => 4, 'cols' => 50]);
        $mform->setType('evidencedesc', PARAM_TEXT);
        $mform->addRule('evidencedesc', null, 'required', null, 'client');
        
        // Enabled
        $mform->addElement('advcheckbox', 'enabled', 
            get_string('enabled', 'local_sceh_rules'));
        $mform->setDefault('enabled', 1);
        
        // Hidden ID field for editing
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $this->add_action_buttons();
    }
}
