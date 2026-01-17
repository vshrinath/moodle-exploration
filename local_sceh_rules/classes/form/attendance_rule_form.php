<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form for editing attendance rules
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Attendance rule edit form
 */
class attendance_rule_form extends \moodleform {
    
    /**
     * Define the form
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Competency selector
        $competencies = $DB->get_records_menu('competency', null, 'shortname', 'id, shortname');
        $mform->addElement('select', 'competencyid', 
            get_string('attendance_rule_competency', 'local_sceh_rules'), 
            $competencies);
        $mform->addRule('competencyid', null, 'required', null, 'client');
        
        // Course selector
        $courses = $DB->get_records_menu('course', ['id' => ['>', 1]], 'fullname', 'id, fullname');
        $mform->addElement('select', 'courseid', 
            get_string('course'), 
            $courses);
        $mform->addRule('courseid', null, 'required', null, 'client');
        
        // Threshold
        $mform->addElement('text', 'threshold', 
            get_string('attendance_rule_threshold', 'local_sceh_rules'));
        $mform->setType('threshold', PARAM_FLOAT);
        $mform->addRule('threshold', null, 'required', null, 'client');
        $mform->addRule('threshold', null, 'numeric', null, 'client');
        $mform->setDefault('threshold', 75);
        $mform->addHelpButton('threshold', 'attendance_rule_threshold', 'local_sceh_rules');
        
        // Enabled
        $mform->addElement('advcheckbox', 'enabled', 
            get_string('enabled', 'local_sceh_rules'));
        $mform->setDefault('enabled', 1);
        
        // Hidden ID field for editing
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $this->add_action_buttons();
    }
    
    /**
     * Validate the form data
     *
     * @param array $data Form data
     * @param array $files Uploaded files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate threshold is between 0 and 100
        if ($data['threshold'] < 0 || $data['threshold'] > 100) {
            $errors['threshold'] = get_string('error_threshold_range', 'local_sceh_rules');
        }
        
        return $errors;
    }
}
