<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Package extraction and scan utilities.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Scans uploaded zip packages.
 */
class package_scanner {
    /** @var string[] */
    private const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
    /** @var string[] */
    private const MEDIA_EXTENSIONS = ['mp4', 'mov', 'mp3', 'wav'];
    /** @var string[] */
    private const QUIZ_EXTENSIONS = ['xml', 'gift'];
    /** @var string[] */
    private const QUIZ_CSV_EXTENSIONS = ['csv'];
    /** @var array<string, string> */
    private const BUCKET_TYPE_MAP = [
        'content' => 'resource',
        'lesson_plan' => 'lesson_plan',
        'lesson_plans' => 'lesson_plan',
        'roleplay' => 'roleplay_assessment',
        'assignment' => 'assignment',
        'assignments' => 'assignment',
        'quiz' => 'quiz',
        'quizzes' => 'quiz',
        'rubric' => 'resource',
        'rubrics' => 'resource',
    ];

    /**
     * Extract uploaded package to a temporary folder.
     *
     * @param \stored_file $zipfile
     * @param int $userid
     * @return string
     */
    public function extract_zip(\stored_file $zipfile, int $userid): string {
        global $CFG;

        $filename = $zipfile->get_filename();
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'zip') {
            throw new \moodle_exception('error_invalidzip', 'local_sceh_importer');
        }

        $tempbase = make_unique_writable_directory($CFG->dataroot . '/temp/local_sceh_importer/' . $userid);
        if (!$tempbase) {
            throw new \moodle_exception('error_extract', 'local_sceh_importer');
        }

        $zippath = $tempbase . '/package.zip';
        $zipfile->copy_content_to($zippath);

        $zip = new \ZipArchive();
        if ($zip->open($zippath) !== true) {
            $this->cleanup_temp_directory($tempbase);
            throw new \moodle_exception('error_zipopen', 'local_sceh_importer');
        }

        $extractdir = $tempbase . '/extract';
        if (!check_dir_exists($extractdir, true, true)) {
            $zip->close();
            $this->cleanup_temp_directory($tempbase);
            throw new \moodle_exception('error_extract', 'local_sceh_importer');
        }

        if (!$zip->extractTo($extractdir)) {
            $zip->close();
            $this->cleanup_temp_directory($tempbase);
            throw new \moodle_exception('error_extract', 'local_sceh_importer');
        }
        $zip->close();

        return $extractdir;
    }
    
    /**
     * Clean up temporary directory
     *
     * @param string $directory
     * @return void
     */
    private function cleanup_temp_directory(string $directory): void {
        if (is_dir($directory)) {
            remove_dir($directory);
        }
    }

    /**
     * Scan extracted package and infer sections and activities.
     *
     * @param string $extractdir
     * @return array
     */
    public function scan(string $extractdir): array {
        $files = [];
        $sections = [];
        $topics = [];
        $topicmap = [];
        $quizcsvactivities = [];
        $activities = [];
        $linkscsv = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractdir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }

            $fullpath = $fileinfo->getPathname();
            $relativepath = ltrim(str_replace($extractdir, '', $fullpath), '/');
            if ($this->should_ignore_path($relativepath)) {
                continue;
            }
            $files[$relativepath] = true;

            $normalized = str_replace('\\', '/', strtolower($relativepath));
            $extension = strtolower(pathinfo($relativepath, PATHINFO_EXTENSION));
            $basename = strtolower(basename($relativepath));

            if ($basename === 'links.csv') {
                $linkscsv[$relativepath] = $fullpath;
                continue;
            }

            $mapped = $this->map_structured_path($relativepath, $normalized, $extension, $sections, $topics, $topicmap);
            if ($mapped !== null) {
                if ($mapped['kind'] === 'quiz_csv' && !empty($mapped['activity'])) {
                    $quizcsvactivities[] = $mapped['activity'];
                } else if ($mapped['kind'] === 'activity') {
                    $activities[] = $mapped['activity'];
                }
                continue;
            }

            $legacy = $this->map_legacy_path($relativepath, $normalized, $extension, $sections);
            if ($legacy !== null) {
                if ($legacy['kind'] === 'quiz_csv' && !empty($legacy['activity'])) {
                    $quizcsvactivities[] = $legacy['activity'];
                } else if ($legacy['kind'] === 'activity' && !empty($legacy['activity'])) {
                    $activities[] = $legacy['activity'];
                }
                continue;
            }
        }

        foreach ($linkscsv as $relativepath => $fullpath) {
            $parsed = $this->parse_links_csv($fullpath, $relativepath, $sections, $topicmap);
            if (!empty($parsed)) {
                $activities = array_merge($activities, $parsed);
            }
        }

        ksort($sections);
        usort($topics, static function(array $a, array $b): int {
            $asection = (string)($a['section_idnumber'] ?? '');
            $bsection = (string)($b['section_idnumber'] ?? '');
            if ($asection !== $bsection) {
                return strcmp($asection, $bsection);
            }
            $aorder = (int)($a['order'] ?? 0);
            $border = (int)($b['order'] ?? 0);
            if ($aorder !== $border) {
                return $aorder <=> $border;
            }
            return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });
        usort($activities, static function(array $a, array $b): int {
            $aleft = implode('|', [
                (string)($a['section_idnumber'] ?? ''),
                (string)($a['topic_idnumber'] ?? ''),
                (string)($a['type'] ?? ''),
                (string)($a['title'] ?? ''),
                (string)($a['idnumber'] ?? ''),
            ]);
            $bright = implode('|', [
                (string)($b['section_idnumber'] ?? ''),
                (string)($b['topic_idnumber'] ?? ''),
                (string)($b['type'] ?? ''),
                (string)($b['title'] ?? ''),
                (string)($b['idnumber'] ?? ''),
            ]);
            return strcmp($aleft, $bright);
        });
        usort($quizcsvactivities, static function(array $a, array $b): int {
            $aleft = implode('|', [
                (string)($a['section_idnumber'] ?? ''),
                (string)($a['topic_idnumber'] ?? ''),
                (string)($a['title'] ?? ''),
                (string)($a['idnumber'] ?? ''),
            ]);
            $bright = implode('|', [
                (string)($b['section_idnumber'] ?? ''),
                (string)($b['topic_idnumber'] ?? ''),
                (string)($b['title'] ?? ''),
                (string)($b['idnumber'] ?? ''),
            ]);
            return strcmp($aleft, $bright);
        });

        return [
            'sections' => $sections,
            'topics' => $topics,
            'activities' => $activities,
            'quiz_csv_activities' => $quizcsvactivities,
            'files' => array_keys($files),
        ];
    }

    /**
     * Map generic numbered folder structure (section/topic/activity buckets).
     *
     * @param string $relativepath
     * @param string $normalized
     * @param string $extension
     * @param array<string, string> $sections
     * @param array<int, array> $topics
     * @param array<string, bool> $topicmap
     * @return array|null
     */
    private function map_structured_path(
        string $relativepath,
        string $normalized,
        string $extension,
        array &$sections,
        array &$topics,
        array &$topicmap
    ): ?array {
        $parts = explode('/', $normalized);
        if (empty($parts)) {
            return null;
        }

        $sectionindex = null;
        $sectionmeta = null;
        foreach ($parts as $index => $part) {
            $candidate = $this->parse_ordered_segment($part);
            if ($candidate !== null) {
                $sectionindex = $index;
                $sectionmeta = $candidate;
                break;
            }
        }
        if ($sectionmeta === null || $sectionindex === null) {
            return null;
        }

        $sectionid = $this->build_section_idnumber($sectionmeta['order'], $sectionmeta['title']);
        $sections[$sectionid] = $sectionmeta['title'];

        $cursor = $sectionindex + 1;
        $topicid = null;
        if (isset($parts[$cursor])) {
            $topicmeta = $this->parse_ordered_segment($parts[$cursor]);
            if ($topicmeta !== null) {
                $topicid = $this->build_topic_idnumber($sectionid, $topicmeta['order'], $topicmeta['title']);
                $topickey = $sectionid . '::' . $topicid;
                if (!isset($topicmap[$topickey])) {
                    $topics[] = [
                        'idnumber' => $topicid,
                        'name' => $topicmeta['title'],
                        'section_idnumber' => $sectionid,
                        'order' => (int)$topicmeta['order'],
                    ];
                    $topicmap[$topickey] = true;
                }
                $cursor++;
            }
        }

        if (!isset($parts[$cursor])) {
            return null;
        }

        $bucket = $parts[$cursor];
        if (!isset(self::BUCKET_TYPE_MAP[$bucket])) {
            return null;
        }

        $type = self::BUCKET_TYPE_MAP[$bucket];
        if ($type === 'quiz') {
            if (in_array($extension, self::QUIZ_CSV_EXTENSIONS, true)) {
                $activity = $this->make_activity('quiz', $relativepath, $sections[$sectionid], $sectionid, 'learner');
                if ($topicid !== null) {
                    $activity['topic_idnumber'] = $topicid;
                }
                $activity['quiz_csv_path'] = $relativepath;
                return [
                    'kind' => 'quiz_csv',
                    'activity' => $activity,
                ];
            }
            if (!in_array($extension, self::QUIZ_EXTENSIONS, true)) {
                return null;
            }
        } else if (!$this->is_content_file($extension)) {
            return null;
        }

        $audience = in_array($type, ['lesson_plan', 'roleplay_assessment'], true) ? 'trainer' : 'learner';
        if ($bucket === 'rubric' || $bucket === 'rubrics') {
            $audience = 'trainer';
        }

        $activity = $this->make_activity($type, $relativepath, $sections[$sectionid], $sectionid, $audience);
        if ($topicid !== null) {
            $activity['topic_idnumber'] = $topicid;
        }

        return [
            'kind' => 'activity',
            'activity' => $activity,
        ];
    }

    /**
     * Ensure the legacy common section key exists.
     *
     * @param array<string, string> $sections
     * @return void
     */
    private function ensure_default_common_section(array &$sections): void {
        if (!isset($sections['SEC-COMMON'])) {
            $sections['SEC-COMMON'] = 'Common Foundation';
        }
    }

    /**
     * Parse ordered segment names like "01. Topic Name" or "2-Another Topic".
     *
     * @param string $segment
     * @return array{order:int,title:string}|null
     */
    private function parse_ordered_segment(string $segment): ?array {
        $raw = trim($segment);
        if (!preg_match('/^(\d{1,3})[.\-\s_]+(.+)$/', $raw, $matches)) {
            return null;
        }
        $title = trim((string)$matches[2]);
        if ($title === '') {
            return null;
        }
        return [
            'order' => (int)$matches[1],
            'title' => $this->title_from_filename($title),
        ];
    }

    /**
     * Legacy fallback mapper for explicit top-level bucket structure.
     *
     * Supports wrapper-root zips by finding the first matching bucket segment.
     * Intentionally does not parse `assets/*` paths anymore.
     *
     * @param string $relativepath
     * @param string $normalized
     * @param string $extension
     * @param array<string, string> $sections
     * @return array|null
     */
    private function map_legacy_path(
        string $relativepath,
        string $normalized,
        string $extension,
        array &$sections
    ): ?array {
        $parts = explode('/', $normalized);
        $bucketindex = null;
        $bucket = '';
        foreach ($parts as $index => $part) {
            if (in_array($part, ['lesson_plans', 'assignments', 'roleplay', 'quizzes'], true)) {
                $bucketindex = $index;
                $bucket = $part;
                break;
            }
        }
        if ($bucketindex === null) {
            return null;
        }
        if (!isset($parts[$bucketindex + 1])) {
            return null;
        }

        $this->ensure_default_common_section($sections);
        $sectionname = $sections['SEC-COMMON'];
        $sectionid = 'SEC-COMMON';

        if ($bucket === 'lesson_plans' && $this->is_content_file($extension)) {
            return ['kind' => 'activity', 'activity' => $this->make_activity('lesson_plan', $relativepath, $sectionname, $sectionid, 'trainer')];
        }
        if ($bucket === 'assignments' && $this->is_content_file($extension)) {
            return ['kind' => 'activity', 'activity' => $this->make_activity('assignment', $relativepath, $sectionname, $sectionid, 'learner')];
        }
        if ($bucket === 'roleplay' && $this->is_content_file($extension)) {
            return ['kind' => 'activity', 'activity' => $this->make_activity('roleplay_assessment', $relativepath, $sectionname, $sectionid, 'trainer')];
        }
        if ($bucket === 'quizzes' && in_array($extension, self::QUIZ_EXTENSIONS, true)) {
            return ['kind' => 'activity', 'activity' => $this->make_activity('quiz', $relativepath, $sectionname, $sectionid, 'learner')];
        }
        if ($bucket === 'quizzes' && in_array($extension, self::QUIZ_CSV_EXTENSIONS, true)) {
            $activity = $this->make_activity('quiz', $relativepath, $sectionname, $sectionid, 'learner');
            $activity['quiz_csv_path'] = $relativepath;
            return ['kind' => 'quiz_csv', 'activity' => $activity];
        }
        return null;
    }

    /**
     * Build deterministic section idnumber from order + title.
     *
     * @param int $order
     * @param string $title
     * @return string
     */
    private function build_section_idnumber(int $order, string $title): string {
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $title));
        return 'SEC-' . str_pad((string)$order, 2, '0', STR_PAD_LEFT) . '-' . trim($slug, '-');
    }

    /**
     * Build deterministic topic idnumber from section + order + title.
     *
     * @param string $sectionid
     * @param int $order
     * @param string $title
     * @return string
     */
    private function build_topic_idnumber(string $sectionid, int $order, string $title): string {
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $title));
        return $sectionid . '-TOP-' . str_pad((string)$order, 2, '0', STR_PAD_LEFT) . '-' . trim($slug, '-');
    }

    /**
     * @param string $extension
     * @return bool
     */
    private function is_content_file(string $extension): bool {
        return in_array($extension, self::DOCUMENT_EXTENSIONS, true) || in_array($extension, self::MEDIA_EXTENSIONS, true);
    }

    /**
     * @param string $type
     * @param string $path
     * @param string $sectionname
     * @param string $sectionidnumber
     * @param string $audience
     * @return array
     */
    private function make_activity(string $type, string $path, string $sectionname, string $sectionidnumber, string $audience): array {
        $basename = pathinfo($path, PATHINFO_FILENAME);
        $idbase = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $basename));
        $prefixmap = [
            'resource' => 'ACT',
            'assignment' => 'ASSIGN',
            'quiz' => 'QUIZ',
            'lesson_plan' => 'LP',
            'roleplay_assessment' => 'ROLEPLAY',
        ];
        $prefix = $prefixmap[$type] ?? 'ACT';

        return [
            'idnumber' => $prefix . '-' . $idbase,
            'type' => $type,
            'title' => $this->title_from_filename($basename),
            'section_name' => $sectionname,
            'section_idnumber' => $sectionidnumber,
            'file' => $path,
            'audience' => $audience,
        ];
    }

    /**
     * @param string $filename
     * @return string
     */
    private function title_from_filename(string $filename): string {
        $value = preg_replace('/[_-]+/', ' ', trim($filename));
        return ucwords($value);
    }

    /**
     * Ignore zip metadata and hidden file artifacts (e.g. macOS AppleDouble).
     *
     * @param string $relativepath
     * @return bool
     */
    private function should_ignore_path(string $relativepath): bool {
        $normalized = str_replace('\\', '/', $relativepath);
        $parts = explode('/', $normalized);
        $basename = basename($normalized);
        
        // Ignore common metadata files
        if (in_array(strtolower($basename), ['readme.txt', '.ds_store', 'thumbs.db', 'desktop.ini', 'template_quiz.csv'], true)) {
            return true;
        }
        
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '__MACOSX') {
                return true;
            }
            if (strpos($part, '._') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parse links.csv file and create URL activities.
     *
     * @param string $fullpath
     * @param string $relativepath
     * @param array<string, string> $sections
     * @param array<string, bool> $topicmap
     * @return array
     */
    private function parse_links_csv(string $fullpath, string $relativepath, array &$sections, array $topicmap): array {
        if (!is_readable($fullpath)) {
            return [];
        }

        $normalized = str_replace('\\', '/', strtolower($relativepath));
        $parts = explode('/', $normalized);

        $sectionid = null;
        $topicid = null;
        $bucket = null;

        $sectionindex = null;
        $sectionmeta = null;
        foreach ($parts as $index => $part) {
            $candidate = $this->parse_ordered_segment($part);
            if ($candidate !== null) {
                $sectionindex = $index;
                $sectionmeta = $candidate;
                break;
            }
        }

        if ($sectionmeta !== null && $sectionindex !== null) {
            $sectionid = $this->build_section_idnumber($sectionmeta['order'], $sectionmeta['title']);
            if (!isset($sections[$sectionid])) {
                $sections[$sectionid] = $sectionmeta['title'];
            }

            $cursor = $sectionindex + 1;
            if (isset($parts[$cursor])) {
                $topicmeta = $this->parse_ordered_segment($parts[$cursor]);
                if ($topicmeta !== null) {
                    $topicid = $this->build_topic_idnumber($sectionid, $topicmeta['order'], $topicmeta['title']);
                    $cursor++;
                }
            }

            if (isset($parts[$cursor])) {
                $bucket = $parts[$cursor];
            }
        } else {
            $this->ensure_default_common_section($sections);
            $sectionid = 'SEC-COMMON';
        }

        if ($bucket === 'quiz' || $bucket === 'quizzes') {
            return [];
        }

        $handle = fopen($fullpath, 'r');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        $header = array_map('trim', array_map('strtolower', $header));
        $orderindex = array_search('order', $header, true);
        $titleindex = array_search('title', $header, true);
        $urlindex = array_search('url', $header, true);
        $typeindex = array_search('type', $header, true);
        $audienceindex = array_search('audience', $header, true);

        if ($titleindex === false || $urlindex === false) {
            fclose($handle);
            return [];
        }

        $rows = [];
        $rownum = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rownum++;
            $title = isset($row[$titleindex]) ? trim($row[$titleindex]) : '';
            $url = isset($row[$urlindex]) ? trim($row[$urlindex]) : '';
            if ($title === '' || $url === '') {
                continue;
            }

            $order = ($orderindex !== false && isset($row[$orderindex])) ? (int)$row[$orderindex] : 0;
            $type = ($typeindex !== false && isset($row[$typeindex])) ? trim($row[$typeindex]) : '';
            $audience = ($audienceindex !== false && isset($row[$audienceindex])) ? trim(strtolower($row[$audienceindex])) : 'learner';

            if (!in_array($audience, ['learner', 'trainer'], true)) {
                $audience = 'learner';
            }

            $rows[] = [
                'order' => $order,
                'rownum' => $rownum,
                'title' => $title,
                'url' => $url,
                'type' => $type,
                'audience' => $audience,
            ];
        }
        fclose($handle);

        usort($rows, static function(array $a, array $b): int {
            if ($a['order'] !== $b['order']) {
                return $a['order'] <=> $b['order'];
            }
            return $a['rownum'] <=> $b['rownum'];
        });

        $activities = [];
        foreach ($rows as $row) {
            $idbase = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $row['title']));
            $activity = [
                'idnumber' => 'URL-' . $idbase,
                'type' => 'url',
                'title' => $row['title'],
                'section_name' => $sections[$sectionid] ?? 'Common Foundation',
                'section_idnumber' => $sectionid,
                'url' => $row['url'],
                'url_type' => $row['type'],
                'audience' => $row['audience'],
            ];
            if ($topicid !== null) {
                $activity['topic_idnumber'] = $topicid;
            }
            $activities[] = $activity;
        }

        return $activities;
    }
}
