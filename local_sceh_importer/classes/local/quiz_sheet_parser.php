<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Quiz CSV parser for non-technical quiz authoring input.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Parses optional quiz CSV files.
 */
class quiz_sheet_parser {
    /** @var string[] */
    private const REQUIRED_COLUMNS = [
        'question_id',
        'question_type',
        'question_text',
        'correct_option',
    ];

    /**
     * Parse a stored CSV into normalized quiz rows.
     *
     * @param \stored_file $csvfile
     * @return array
     */
    public function parse(\stored_file $csvfile): array {
        $tmpdir = make_request_directory();
        $localpath = $tmpdir . '/' . $csvfile->get_filename();
        $csvfile->copy_content_to($localpath);

        $handle = fopen($localpath, 'r');
        if ($handle === false) {
            throw new \moodle_exception('error_csvopen', 'local_sceh_importer');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        $index = [];
        foreach ($header as $i => $column) {
            $index[strtolower(trim((string)$column))] = $i;
        }

        foreach (self::REQUIRED_COLUMNS as $required) {
            if (!array_key_exists($required, $index)) {
                fclose($handle);
                throw new \moodle_exception('error_missingcolumn', 'local_sceh_importer', '', $required);
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $questiontext = trim((string)$data[$index['question_text']]);
            if ($questiontext === '') {
                continue;
            }

            $rows[] = [
                'question_id' => trim((string)$data[$index['question_id']]),
                'question_type' => trim((string)$data[$index['question_type']]),
                'question_text' => $questiontext,
                'correct_option' => trim((string)$data[$index['correct_option']]),
            ];
        }

        fclose($handle);
        return $rows;
    }
}
