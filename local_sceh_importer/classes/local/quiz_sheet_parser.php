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
    /** @var array<string, string[]> */
    private const COLUMN_ALIASES = [
        'question_id' => ['questionid', 'qid', 'id'],
        'question_type' => ['type', 'qtype'],
        'question_text' => ['question', 'questiontitle', 'question_title'],
        'correct_option' => ['answer', 'correctanswer', 'correct_answer'],
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

        return $this->parse_path($localpath);
    }

    /**
     * Parse a CSV from filesystem path into normalized quiz rows.
     *
     * @param string $localpath
     * @return array
     */
    public function parse_path(string $localpath): array {
        if (!is_readable($localpath)) {
            throw new \moodle_exception('error_csvopen', 'local_sceh_importer');
        }

        $handle = fopen($localpath, 'r');
        if ($handle === false) {
            throw new \moodle_exception('error_csvopen', 'local_sceh_importer');
        }

        $rows = $this->parse_handle($handle);
        fclose($handle);
        return $rows;
    }

    /**
     * Parse open CSV handle.
     *
     * @param resource $handle
     * @return array
     */
    private function parse_handle($handle): array {
        $header = fgetcsv($handle);
        if ($header === false) {
            return [];
        }

        $index = [];
        foreach ($header as $i => $column) {
            $normalized = $this->normalize_header((string)$column);
            if ($normalized === '') {
                continue;
            }
            $index[$normalized] = $i;
        }

        foreach (self::REQUIRED_COLUMNS as $required) {
            $resolvedcolumn = $this->resolve_required_column($required, $index);
            if ($resolvedcolumn === null) {
                throw new \moodle_exception('error_missingcolumn', 'local_sceh_importer', '', $required);
            }
            if (!array_key_exists($required, $index)) {
                $index[$required] = $index[$resolvedcolumn];
            }
        }

        $rows = [];
        $optionalindex = [];
        foreach (['option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'explanation', 'marks', 'competency_idnumber', 'difficulty', 'audience'] as $column) {
            $resolved = $this->resolve_optional_column($column, $index);
            if ($resolved !== null) {
                $optionalindex[$column] = $index[$resolved];
            }
        }
        while (($data = fgetcsv($handle)) !== false) {
            $questiontext = trim((string)$data[$index['question_text']]);
            if ($questiontext === '') {
                continue;
            }

            $row = [
                'question_id' => trim((string)$data[$index['question_id']]),
                'question_type' => trim((string)$data[$index['question_type']]),
                'question_text' => $questiontext,
                'correct_option' => trim((string)$data[$index['correct_option']]),
            ];
            foreach ($optionalindex as $column => $columnindex) {
                $row[$column] = trim((string)($data[$columnindex] ?? ''));
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Normalize CSV headers to stable keys.
     *
     * @param string $header
     * @return string
     */
    private function normalize_header(string $header): string {
        $clean = trim($header);
        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $clean) ?? $clean;
        $clean = strtolower($clean);
        $clean = preg_replace('/[^a-z0-9]+/', '_', $clean) ?? $clean;
        return trim($clean, '_');
    }

    /**
     * Resolve required column from canonical name or known aliases.
     *
     * @param string $required
     * @param array<string, int> $index
     * @return string|null
     */
    private function resolve_required_column(string $required, array $index): ?string {
        if (array_key_exists($required, $index)) {
            return $required;
        }
        foreach (self::COLUMN_ALIASES[$required] ?? [] as $alias) {
            if (array_key_exists($alias, $index)) {
                return $alias;
            }
        }
        return null;
    }

    /**
     * Resolve optional columns from canonical name or aliases.
     *
     * @param string $optional
     * @param array<string, int> $index
     * @return string|null
     */
    private function resolve_optional_column(string $optional, array $index): ?string {
        if (array_key_exists($optional, $index)) {
            return $optional;
        }
        $aliases = [
            'option_a' => ['a', 'option1', 'choice_a'],
            'option_b' => ['b', 'option2', 'choice_b'],
            'option_c' => ['c', 'option3', 'choice_c'],
            'option_d' => ['d', 'option4', 'choice_d'],
            'option_e' => ['e', 'option5', 'choice_e'],
            'explanation' => ['feedback', 'rationale'],
            'marks' => ['mark', 'score', 'points'],
            'competency_idnumber' => ['competency', 'competency_id'],
            'difficulty' => ['level'],
            'audience' => ['target_audience'],
        ];
        foreach ($aliases[$optional] ?? [] as $alias) {
            if (array_key_exists($alias, $index)) {
                return $alias;
            }
        }
        return null;
    }
}
