<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Manifest build and validation helpers.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds and validates draft manifests.
 */
class manifest_builder {
    /**
     * Build manifest array from scanned package artifacts.
     *
     * @param array $scan
     * @param array $quizrows
     * @param string $importmode
     * @param bool $dryrun
     * @param string $changenote
     * @return array
     */
    public function build(array $scan, array $quizrows, string $importmode, bool $dryrun, string $changenote): array {
        $sections = [];
        $order = 1;
        foreach ($scan['sections'] as $idnumber => $name) {
            $sections[] = [
                'idnumber' => $idnumber,
                'name' => $name,
                'order' => $order++,
            ];
        }

        $activities = [];
        foreach ($scan['activities'] as $activity) {
            $entry = [
                'idnumber' => $activity['idnumber'],
                'type' => $activity['type'],
                'section_idnumber' => $activity['section_idnumber'],
                'title' => $activity['title'],
                'audience' => $activity['audience'],
            ];

            if (!empty($activity['file'])) {
                $entry['file'] = $activity['file'];
            }
            if ($activity['type'] === 'assignment') {
                $entry['submission_types'] = ['file', 'online_text'];
                $entry['allowed_filetypes'] = ['pdf', 'doc', 'docx', 'mp4', 'mov', 'mp3', 'wav'];
                $entry['group_submission'] = false;
                $entry['reviewer_role'] = 'trainer';
            }
            if ($activity['type'] === 'quiz' && !empty($activity['file'])) {
                $entry['quiz_source'] = [
                    'format' => strtolower(pathinfo($activity['file'], PATHINFO_EXTENSION)) === 'gift' ? 'gift' : 'moodle_xml',
                    'path' => $activity['file'],
                ];
                unset($entry['file']);
            }

            $activities[] = $entry;
        }

        if (!empty($quizrows)) {
            $activities[] = [
                'idnumber' => 'QUIZ-INLINE-SPREADSHEET',
                'type' => 'quiz',
                'section_idnumber' => 'SEC-COMMON',
                'title' => 'Quiz From Spreadsheet',
                'audience' => 'learner',
                'quiz_source' => [
                    'format' => 'inline',
                    'question_count' => count($quizrows),
                    'rows' => $quizrows,
                ],
            ];
        }

        return [
            'manifest_version' => '1.0',
            'program_version' => 'draft',
            'package_version' => date('Y.m.d.His'),
            'change_note' => $changenote,
            'import' => [
                'mode' => $importmode,
                'dry_run' => $dryrun,
                'scope' => 'course',
            ],
            'sections' => $sections,
            'activities' => $activities,
        ];
    }

    /**
     * Validate draft manifest content against basic policy checks.
     *
     * @param array $manifest
     * @param string[] $knownfiles
     * @return array{errors:array,warnings:array}
     */
    public function validate(array $manifest, array $knownfiles): array {
        $errors = [];
        $warnings = [];

        $idnumbers = [];
        foreach ($manifest['activities'] as $activity) {
            $idnumber = $activity['idnumber'] ?? '';
            if ($idnumber === '') {
                $errors[] = 'Activity is missing idnumber.';
                continue;
            }

            if (isset($idnumbers[$idnumber])) {
                $errors[] = 'Duplicate activity idnumber: ' . $idnumber;
            }
            $idnumbers[$idnumber] = true;

            if (empty($activity['title'])) {
                $errors[] = 'Activity is missing title: ' . $idnumber;
            }

            if (!empty($activity['file']) && !in_array($activity['file'], $knownfiles, true)) {
                $errors[] = 'File not found in package: ' . $activity['file'];
            }

            if (!empty($activity['quiz_source']['path']) && !in_array($activity['quiz_source']['path'], $knownfiles, true)) {
                $errors[] = 'Quiz source file not found in package: ' . $activity['quiz_source']['path'];
            }

            if (($activity['type'] ?? '') === 'roleplay_assessment' && empty($activity['rubric_idnumber'])) {
                $warnings[] = 'Roleplay activity has no rubric_idnumber: ' . $idnumber;
            }
        }

        if (($manifest['import']['mode'] ?? '') === 'replace' && trim((string)($manifest['change_note'] ?? '')) === '') {
            $errors[] = 'Replace mode requires a change_note.';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Render a human-readable YAML string.
     *
     * @param array $data
     * @param int $indent
     * @return string
     */
    public function to_yaml(array $data, int $indent = 0): string {
        $lines = [];
        $prefix = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->is_list($value)) {
                    $lines[] = $prefix . $key . ':';
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $lines[] = $prefix . '  -';
                            $lines[] = $this->to_yaml($item, $indent + 2);
                        } else {
                            $lines[] = $prefix . '  - ' . $this->yaml_scalar($item);
                        }
                    }
                } else {
                    $lines[] = $prefix . $key . ':';
                    $lines[] = $this->to_yaml($value, $indent + 1);
                }
            } else {
                $lines[] = $prefix . $key . ': ' . $this->yaml_scalar($value);
            }
        }

        return implode("\n", array_filter($lines, static fn($line) => $line !== ''));
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function yaml_scalar($value): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }
        if (is_numeric($value)) {
            return (string)$value;
        }

        $text = (string)$value;
        $needsquotes = $text === '' || preg_match('/[:#\-\{\}\[\],&\*\?\|\>\!%@`]/', $text);
        if ($needsquotes) {
            return '"' . str_replace('"', '\\"', $text) . '"';
        }

        return $text;
    }

    /**
     * @param array $array
     * @return bool
     */
    private function is_list(array $array): bool {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
