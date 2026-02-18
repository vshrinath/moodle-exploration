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
    /** @var string */
    private const IDNUMBER_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]{1,100}$/';
    /** @var string[] */
    private const SUPPORTED_INLINE_QUIZ_TYPES = [
        'mcq',
        'multichoice',
        'singlechoice',
        'true_false',
        'truefalse',
        'tf',
        'short_answer',
        'shortanswer',
        'sa',
    ];

    /**
     * Build manifest array from scanned package artifacts.
     *
     * @param array $scan
     * @param array $quizrows
     * @param string $importmode
     * @param bool $dryrun
     * @param array $inlinequizactivities
     * @param string $changenote
     * @param string $programidnumber
     * @param string $programname
     * @param array<string, string> $coursemeta
     * @return array
     */
    public function build(
        array $scan,
        array $quizrows,
        string $importmode,
        bool $dryrun,
        array $inlinequizactivities,
        string $changenote,
        string $programidnumber,
        string $programname,
        array $coursemeta
    ): array {
        $sections = [];
        $order = 1;
        foreach ($scan['sections'] as $idnumber => $name) {
            $sections[] = [
                'idnumber' => $idnumber,
                'name' => $name,
                'order' => $order++,
            ];
        }
        $topics = $scan['topics'] ?? [];

        $activities = [];
        foreach ($scan['activities'] as $activity) {
            $entry = [
                'idnumber' => $activity['idnumber'],
                'type' => $activity['type'],
                'section_idnumber' => $activity['section_idnumber'],
                'title' => $activity['title'],
                'audience' => $activity['audience'],
            ];
            if (!empty($activity['topic_idnumber'])) {
                $entry['topic_idnumber'] = $activity['topic_idnumber'];
            }

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

        foreach ($inlinequizactivities as $inlinequizactivity) {
            $entry = [
                'idnumber' => (string)($inlinequizactivity['idnumber'] ?? ''),
                'type' => 'quiz',
                'section_idnumber' => (string)($inlinequizactivity['section_idnumber'] ?? 'SEC-COMMON'),
                'title' => (string)($inlinequizactivity['title'] ?? 'Quiz'),
                'audience' => (string)($inlinequizactivity['audience'] ?? 'learner'),
                'quiz_source' => [
                    'format' => 'inline',
                    'question_count' => count((array)($inlinequizactivity['rows'] ?? [])),
                    'rows' => (array)($inlinequizactivity['rows'] ?? []),
                ],
            ];
            if (!empty($inlinequizactivity['topic_idnumber'])) {
                $entry['topic_idnumber'] = (string)$inlinequizactivity['topic_idnumber'];
            }
            $activities[] = $entry;
        }

        if (!empty($quizrows)) {
            if (!isset($scan['sections']['SEC-COMMON'])) {
                $sections[] = [
                    'idnumber' => 'SEC-COMMON',
                    'name' => 'Common Foundation',
                    'order' => count($sections) + 1,
                ];
            }
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
            'program_idnumber' => $programidnumber,
            'program_name' => $programname,
            'program_version' => 'draft',
            'package_version' => date('Y.m.d.His'),
            'change_note' => $changenote,
            'course' => [
                'idnumber' => (string)($coursemeta['idnumber'] ?? ''),
                'shortname' => (string)($coursemeta['shortname'] ?? ''),
                'fullname' => (string)($coursemeta['fullname'] ?? ''),
            ],
            'import' => [
                'mode' => $importmode,
                'dry_run' => $dryrun,
                'scope' => 'course',
            ],
            'sections' => $sections,
            'topics' => $topics,
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
        $sectionids = [];
        foreach ($manifest['sections'] ?? [] as $section) {
            $sectionid = (string)($section['idnumber'] ?? '');
            if ($sectionid === '') {
                $errors[] = 'Section is missing idnumber.';
                continue;
            }
            if (!$this->is_valid_idnumber($sectionid)) {
                $errors[] = 'Section idnumber has invalid format: ' . $sectionid;
            }
            if (isset($sectionids[$sectionid])) {
                $errors[] = 'Duplicate section idnumber: ' . $sectionid;
                continue;
            }
            $sectionids[$sectionid] = true;
        }

        $idnumbers = [];
        $topicids = [];
        foreach ($manifest['topics'] ?? [] as $topic) {
            $topicid = (string)($topic['idnumber'] ?? '');
            if ($topicid === '') {
                $errors[] = 'Topic is missing idnumber.';
                continue;
            }
            if (isset($topicids[$topicid])) {
                $errors[] = 'Duplicate topic idnumber: ' . $topicid;
                continue;
            }
            if (!$this->is_valid_idnumber($topicid)) {
                $errors[] = 'Topic idnumber has invalid format: ' . $topicid;
            }
            $topicsectionid = (string)($topic['section_idnumber'] ?? '');
            if ($topicsectionid === '' || !isset($sectionids[$topicsectionid])) {
                $errors[] = 'Topic references unknown section_idnumber: ' . $topicid;
            }
            $topicids[$topicid] = true;
        }

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
            if (!$this->is_valid_idnumber((string)$idnumber)) {
                $errors[] = 'Activity idnumber has invalid format: ' . $idnumber;
            }

            if (empty($activity['title'])) {
                $errors[] = 'Activity is missing title: ' . $idnumber;
            }

            $activitysectionid = (string)($activity['section_idnumber'] ?? '');
            if ($activitysectionid === '' || !isset($sectionids[$activitysectionid])) {
                $errors[] = 'Activity references unknown section_idnumber: ' . $idnumber;
            }

            if (!empty($activity['topic_idnumber']) && !isset($topicids[(string)$activity['topic_idnumber']])) {
                $errors[] = 'Activity references unknown topic_idnumber: ' . $idnumber;
            }

            if (!empty($activity['file']) && !in_array($activity['file'], $knownfiles, true)) {
                $errors[] = 'File not found in package: ' . $activity['file'];
            }

            if (!empty($activity['quiz_source']['path']) && !in_array($activity['quiz_source']['path'], $knownfiles, true)) {
                $errors[] = 'Quiz source file not found in package: ' . $activity['quiz_source']['path'];
            }

            if (($activity['type'] ?? '') === 'quiz' && (($activity['quiz_source']['format'] ?? '') === 'inline')) {
                $inlineerrors = $this->validate_inline_quiz_rows((string)$idnumber, (array)($activity['quiz_source']['rows'] ?? []));
                foreach ($inlineerrors as $inlineerror) {
                    $errors[] = $inlineerror;
                }
            }

        }

        if (($manifest['import']['mode'] ?? '') === 'replace' && trim((string)($manifest['change_note'] ?? '')) === '') {
            $errors[] = 'Replace mode requires a change_note.';
        }

        $courseidnumber = trim((string)($manifest['course']['idnumber'] ?? ''));
        if ($courseidnumber === '') {
            $errors[] = 'Course idnumber is required.';
        } else if (!$this->is_valid_idnumber($courseidnumber)) {
            $errors[] = 'Course idnumber has invalid format: ' . $courseidnumber;
        }

        $programidnumber = trim((string)($manifest['program_idnumber'] ?? ''));
        if ($programidnumber !== '' && !$this->is_valid_idnumber($programidnumber)) {
            $errors[] = 'Program ID number has invalid format: ' . $programidnumber;
        }

        if (trim((string)($manifest['program_idnumber'] ?? '')) === '') {
            $warnings[] = 'Program ID number is empty. Add program_idnumber to group Foundation/VT/MRA courses under one program.';
        }

        if (trim((string)($manifest['program_name'] ?? '')) === '') {
            $warnings[] = 'Program name is empty. Add program_name for clearer grouped program views.';
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate idnumber format.
     *
     * @param string $idnumber
     * @return bool
     */
    private function is_valid_idnumber(string $idnumber): bool {
        return preg_match(self::IDNUMBER_PATTERN, $idnumber) === 1;
    }

    /**
     * Validate inline quiz rows at preview time.
     *
     * @param string $activityidnumber
     * @param array $rows
     * @return string[]
     */
    private function validate_inline_quiz_rows(string $activityidnumber, array $rows): array {
        $errors = [];
        if (empty($rows)) {
            $errors[] = 'Inline quiz has no questions: ' . $activityidnumber;
            return $errors;
        }

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $errors[] = 'Inline quiz row is invalid at question #' . ($index + 1) . ': ' . $activityidnumber;
                continue;
            }
            $qid = trim((string)($row['question_id'] ?? 'Q' . ($index + 1)));
            $qtype = strtolower(trim((string)($row['question_type'] ?? '')));
            $qtext = trim((string)($row['question_text'] ?? ''));
            $correct = trim((string)($row['correct_option'] ?? ''));
            $prefix = $activityidnumber . ' / ' . $qid . ': ';

            if ($qtext === '') {
                $errors[] = $prefix . 'question_text is required.';
            }
            if ($correct === '') {
                $errors[] = $prefix . 'correct_option is required.';
            }
            if ($qtype === '') {
                $errors[] = $prefix . 'question_type is required.';
                continue;
            }
            if (!in_array($qtype, self::SUPPORTED_INLINE_QUIZ_TYPES, true)) {
                $errors[] = $prefix . 'unsupported question_type "' . $qtype . '".';
                continue;
            }

            if (in_array($qtype, ['mcq', 'multichoice', 'singlechoice'], true)) {
                $options = [
                    'A' => trim((string)($row['option_a'] ?? '')),
                    'B' => trim((string)($row['option_b'] ?? '')),
                    'C' => trim((string)($row['option_c'] ?? '')),
                    'D' => trim((string)($row['option_d'] ?? '')),
                    'E' => trim((string)($row['option_e'] ?? '')),
                ];
                $filled = array_filter($options, static fn($text): bool => $text !== '');
                if (count($filled) < 2) {
                    $errors[] = $prefix . 'mcq requires at least 2 options.';
                    continue;
                }

                $normalizedcorrect = strtoupper($correct);
                $matcheslabel = array_key_exists($normalizedcorrect, $options) && $options[$normalizedcorrect] !== '';
                $matchestext = false;
                foreach ($filled as $optiontext) {
                    if (strcasecmp($optiontext, $correct) === 0) {
                        $matchestext = true;
                        break;
                    }
                }
                if (!$matcheslabel && !$matchestext) {
                    $errors[] = $prefix . 'correct_option must match an option label (A-E) or option text.';
                }
            }

            if (in_array($qtype, ['true_false', 'truefalse', 'tf'], true)) {
                $normalized = strtoupper($correct);
                $valid = in_array($normalized, ['T', 'TRUE', '1', 'YES', 'F', 'FALSE', '0', 'NO'], true);
                if (!$valid) {
                    $errors[] = $prefix . 'true/false correct_option must be TRUE/FALSE (or T/F, 1/0, YES/NO).';
                }
            }
        }

        return $errors;
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
