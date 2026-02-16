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

        $tempbase = $CFG->dataroot . '/temp/local_sceh_importer/' . $userid . '/' . time() . '-' . random_int(1000, 9999);
        if (!check_dir_exists($tempbase, true, true)) {
            throw new \moodle_exception('error_extract', 'local_sceh_importer');
        }

        $zippath = $tempbase . '/package.zip';
        $zipfile->copy_content_to($zippath);

        $zip = new \ZipArchive();
        if ($zip->open($zippath) !== true) {
            throw new \moodle_exception('error_zipopen', 'local_sceh_importer');
        }

        $extractdir = $tempbase . '/extract';
        if (!check_dir_exists($extractdir, true, true)) {
            throw new \moodle_exception('error_extract', 'local_sceh_importer');
        }

        if (!$zip->extractTo($extractdir)) {
            $zip->close();
            throw new \moodle_exception('error_extract', 'local_sceh_importer');
        }
        $zip->close();

        return $extractdir;
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
        $activities = [];

        $sections['SEC-COMMON'] = 'Common Foundation';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractdir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }

            $fullpath = $fileinfo->getPathname();
            $relativepath = ltrim(str_replace($extractdir, '', $fullpath), '/');
            $files[$relativepath] = true;

            $normalized = str_replace('\\', '/', strtolower($relativepath));
            $extension = strtolower(pathinfo($relativepath, PATHINFO_EXTENSION));

            if (strpos($normalized, 'assets/streams/') === 0) {
                $streamslug = $this->stream_slug_from_path($normalized);
                if ($streamslug !== '') {
                    $sectionid = 'SEC-STREAM-' . strtoupper($streamslug);
                    $sections[$sectionid] = 'STREAM - ' . $this->pretty_stream_name($streamslug);
                    if ($this->is_content_file($extension)) {
                        $activities[] = $this->make_activity('resource', $relativepath, $sections[$sectionid], $sectionid, 'learner');
                    }
                }
                continue;
            }

            if (strpos($normalized, 'assets/common/') === 0 && $this->is_content_file($extension)) {
                $activities[] = $this->make_activity('resource', $relativepath, $sections['SEC-COMMON'], 'SEC-COMMON', 'learner');
                continue;
            }

            if (strpos($normalized, 'lesson_plans/') === 0 && $this->is_content_file($extension)) {
                $activities[] = $this->make_activity('lesson_plan', $relativepath, $sections['SEC-COMMON'], 'SEC-COMMON', 'trainer');
                continue;
            }

            if (strpos($normalized, 'assignments/') === 0 && $this->is_content_file($extension)) {
                $activities[] = $this->make_activity('assignment', $relativepath, $sections['SEC-COMMON'], 'SEC-COMMON', 'learner');
                continue;
            }

            if (strpos($normalized, 'roleplay/') === 0 && $this->is_content_file($extension)) {
                $activities[] = $this->make_activity('roleplay_assessment', $relativepath, $sections['SEC-COMMON'], 'SEC-COMMON', 'trainer');
                continue;
            }

            if (strpos($normalized, 'quizzes/') === 0 && in_array($extension, self::QUIZ_EXTENSIONS, true)) {
                $activities[] = $this->make_activity('quiz', $relativepath, $sections['SEC-COMMON'], 'SEC-COMMON', 'learner');
            }
        }

        ksort($sections);

        return [
            'sections' => $sections,
            'activities' => $activities,
            'files' => array_keys($files),
        ];
    }

    /**
     * @param string $path
     * @return string
     */
    private function stream_slug_from_path(string $path): string {
        $parts = explode('/', $path);
        $streamindex = array_search('streams', $parts, true);
        if ($streamindex === false || !isset($parts[$streamindex + 1])) {
            return '';
        }
        return preg_replace('/[^a-z0-9]+/', '-', $parts[$streamindex + 1]);
    }

    /**
     * @param string $slug
     * @return string
     */
    private function pretty_stream_name(string $slug): string {
        $label = str_replace('-', ' ', $slug);
        return ucwords(trim($label));
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
}
