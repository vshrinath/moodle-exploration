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
        $courses = $this->_customdata['courses'] ?? [];
        $programs = $this->_customdata['programs'] ?? [];
        $courseoptions = [0 => get_string('selecttargetcourse', 'local_sceh_importer')] + $courses;
        $programoptions = $programs;

        $mform->addElement('select', 'programmode', get_string('programmode', 'local_sceh_importer'), [
            'existing' => get_string('programmode_existing', 'local_sceh_importer'),
            'new' => get_string('programmode_new', 'local_sceh_importer'),
        ]);
        $mform->setDefault('programmode', !empty($programs) ? 'existing' : 'new');

        $mform->addElement('select', 'selectedprogramidnumber', get_string('programselect', 'local_sceh_importer'), $programoptions);
        $mform->setDefault('selectedprogramidnumber', !empty($programs) ? (string)array_key_first($programs) : '');
        $mform->hideIf('selectedprogramidnumber', 'programmode', 'eq', 'new');

        $mform->addElement('select', 'coursemode', get_string('coursemode', 'local_sceh_importer'), [
            'existing' => get_string('coursemode_existing', 'local_sceh_importer'),
            'new' => get_string('coursemode_new', 'local_sceh_importer'),
        ]);
        $mform->setDefault('coursemode', !empty($courses) ? 'existing' : 'new');

        $mform->addElement('select', 'targetcourseid', get_string('targetcourse', 'local_sceh_importer'), $courseoptions);
        $mform->setDefault('targetcourseid', !empty($courses) ? (int)array_key_first($courses) : 0);
        $mform->hideIf('targetcourseid', 'coursemode', 'eq', 'new');

        $mform->addElement('text', 'newcoursefullname', get_string('newcoursefullname', 'local_sceh_importer'), ['size' => 80]);
        $mform->setType('newcoursefullname', PARAM_TEXT);
        $mform->hideIf('newcoursefullname', 'coursemode', 'eq', 'existing');

        $mform->addElement('text', 'programidnumber', get_string('programidnumber', 'local_sceh_importer'), ['size' => 40]);
        $mform->setType('programidnumber', PARAM_ALPHANUMEXT);
        $mform->hideIf('programidnumber', 'programmode', 'eq', 'existing');

        $mform->addElement('text', 'programname', get_string('programname', 'local_sceh_importer'), ['size' => 80]);
        $mform->setType('programname', PARAM_TEXT);
        $mform->hideIf('programname', 'programmode', 'eq', 'existing');

        $mform->addElement('filepicker', 'packagezip', get_string('packagezip', 'local_sceh_importer'), null, [
            'accepted_types' => ['.zip'],
            'maxbytes' => 0,
        ]);
        $mform->addHelpButton('packagezip', 'packagezip', 'local_sceh_importer');
        $mform->addRule('packagezip', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('validatezip', 'local_sceh_importer'));
    }

    /**
     * Form validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $programmode = (string)($data['programmode'] ?? 'existing');
        $coursemode = (string)($data['coursemode'] ?? 'existing');

        if ($programmode === 'existing') {
            if (trim((string)($data['selectedprogramidnumber'] ?? '')) === '') {
                $errors['selectedprogramidnumber'] = get_string('error_programrequired', 'local_sceh_importer');
            }
        } else {
            $programidnumber = trim((string)($data['programidnumber'] ?? ''));
            $programname = trim((string)($data['programname'] ?? ''));
            if ($programidnumber === '') {
                $errors['programidnumber'] = get_string('error_programrequired', 'local_sceh_importer');
            }
            if ($programname === '') {
                $errors['programname'] = get_string('error_programname_required', 'local_sceh_importer');
            }

            $existingprogramids = array_keys((array)($this->_customdata['programs'] ?? []));
            foreach ($existingprogramids as $existingprogramid) {
                if (\core_text::strtolower(trim((string)$existingprogramid)) === \core_text::strtolower($programidnumber)) {
                    $errors['programidnumber'] = get_string('error_programidnumber_taken', 'local_sceh_importer');
                    break;
                }
            }

            $existingprogramnames = (array)($this->_customdata['programnames'] ?? []);
            foreach ($existingprogramnames as $existingprogramname) {
                if (\core_text::strtolower(trim((string)$existingprogramname)) === \core_text::strtolower($programname)) {
                    $errors['programname'] = get_string('error_programname_taken', 'local_sceh_importer');
                    break;
                }
            }
        }

        if ($coursemode === 'existing') {
            if (empty($data['targetcourseid'])) {
                $errors['targetcourseid'] = get_string('error_selecttargetcourse', 'local_sceh_importer');
            }
        } else {
            $newcoursefullname = trim((string)($data['newcoursefullname'] ?? ''));
            if ($newcoursefullname === '') {
                $errors['newcoursefullname'] = get_string('error_newcoursefullname', 'local_sceh_importer');
            } else {
                $existingcoursefullnames = (array)($this->_customdata['coursefullnames'] ?? []);
                foreach ($existingcoursefullnames as $existingcoursefullname) {
                    if (\core_text::strtolower(trim((string)$existingcoursefullname)) === \core_text::strtolower($newcoursefullname)) {
                        $errors['newcoursefullname'] = get_string('error_newcoursefullname_taken', 'local_sceh_importer');
                        break;
                    }
                }
            }
        }
        return $errors;
    }
}
