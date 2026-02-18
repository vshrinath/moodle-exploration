<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * File upload form for individual activity replacement.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for uploading a single replacement file.
 */
class file_upload_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $customdata['courseid']);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $customdata['cmid']);

        $mform->addElement('static', 'activityinfo', get_string('activity'), 
            format_string($customdata['activityname']));

        $mform->addElement('filemanager', 'replacementfile', get_string('replacementfile', 'local_sceh_importer'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => '*',
        ]);
        $mform->addRule('replacementfile', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('preview', 'local_sceh_importer'));
    }
}
