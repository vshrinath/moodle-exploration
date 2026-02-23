<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Adhoc task for batch course import.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_importer\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to process course package imports in the background.
 */
class import_package_task extends \core\task\adhoc_task {

    /**
     * Run the import task.
     */
    public function execute() {
        global $DB, $CFG;

        $data = $this->get_custom_data();
        $jobid = (int)($data->jobid ?? 0);

        if ($jobid <= 0) {
            return;
        }

        $job = $DB->get_record('local_sceh_importer_job', ['id' => $jobid]);
        if (!$job) {
            return;
        }

        // Setup job as processing.
        $job->status = 'processing';
        $job->timemodified = time();
        $DB->update_record('local_sceh_importer_job', $job);

        try {
            $manifest = json_decode($job->manifest, true);
            if (!$manifest) {
                throw new \moodle_exception('error_invalidmanifest', 'local_sceh_importer');
            }

            $executor = new \local_sceh_importer\local\import_executor();
            
            // Refactor executor execute() to accept a job monitor callback or update the record.
            $result = $executor->execute(
                (int)$job->courseid,
                (int)$job->userid,
                (string)$job->extractdir,
                $manifest,
                $jobid // Pass job ID for progress tracking.
            );

            // Mark as completed.
            $job->status = 'completed';
            $job->result = json_encode($result);
            $job->timemodified = time();
            $DB->update_record('local_sceh_importer_job', $job);

        } catch (\Exception $e) {
            $job->status = 'failed';
            $job->error = $e->getMessage() . "\n" . $e->getTraceAsString();
            $job->timemodified = time();
            $DB->update_record('local_sceh_importer_job', $job);
            
            // Re-throw so Moodle doesn't mark it as successfully finished if it was a transient failure.
            // But for this prototype, we usually want to mark it as fixed-failed.
            throw $e;
        }
    }
}
