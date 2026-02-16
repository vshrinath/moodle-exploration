<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upload form for SCEH package importer.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Import upload form.
 */
class upload_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'packagezip', get_string('packagezip', 'local_sceh_importer'), null, [
            'accepted_types' => ['.zip'],
            'maxbytes' => 0,
        ]);
        $mform->addHelpButton('packagezip', 'packagezip', 'local_sceh_importer');
        $mform->addRule('packagezip', null, 'required', null, 'client');

        $mform->addElement('filepicker', 'quizsheet', get_string('quizsheet', 'local_sceh_importer'), null, [
            'accepted_types' => ['.csv'],
            'maxbytes' => 0,
        ]);
        $mform->addHelpButton('quizsheet', 'quizsheet', 'local_sceh_importer');

        $mform->addElement('select', 'importmode', get_string('importmode', 'local_sceh_importer'), [
            'assert' => get_string('importmode_assert', 'local_sceh_importer'),
            'upsert' => get_string('importmode_upsert', 'local_sceh_importer'),
            'replace' => get_string('importmode_replace', 'local_sceh_importer'),
        ]);
        $mform->setDefault('importmode', 'upsert');

        $mform->addElement('advcheckbox', 'dryrun', get_string('dryrun', 'local_sceh_importer'), get_string('dryrun_desc', 'local_sceh_importer'));
        $mform->setDefault('dryrun', 1);

        $mform->addElement('text', 'changenote', get_string('changenote', 'local_sceh_importer'), ['size' => 80]);
        $mform->setType('changenote', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('previewheading', 'local_sceh_importer'));
    }
}
