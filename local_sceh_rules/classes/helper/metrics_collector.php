<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Lightweight metrics collector for rules engine telemetry.
 *
 * Stores daily-bucketed success/failure counters per rule in a dedicated table,
 * avoiding bloat in {logstore_standard_log}.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Records rule evaluation metrics for observability dashboards.
 */
class metrics_collector {

    /**
     * Record a successful rule evaluation.
     *
     * @param string $ruletype 'attendance' or 'roster'
     * @param int $ruleid The rule ID
     * @param float $durationms Evaluation duration in milliseconds
     */
    public static function record_success(string $ruletype, int $ruleid, float $durationms = 0.0) {
        self::upsert_metric($ruletype, $ruleid, 1, 0, (int) $durationms, null);
    }

    /**
     * Record a failed rule evaluation.
     *
     * @param string $ruletype 'attendance' or 'roster'
     * @param int $ruleid The rule ID
     * @param string $errormessage The error message
     * @param float $durationms Evaluation duration in milliseconds
     */
    public static function record_failure(string $ruletype, int $ruleid, string $errormessage, float $durationms = 0.0) {
        self::upsert_metric($ruletype, $ruleid, 0, 1, (int) $durationms, $errormessage);
    }

    /**
     * Get aggregated metrics summary for a time window.
     *
     * @param int $days Number of days to look back (default 7)
     * @return array Array of metric summaries keyed by ruletype.
     */
    public static function get_summary(int $days = 7): array {
        global $DB;

        $cutoff = date('Y-m-d', time() - ($days * DAYSECS));

        $sql = "SELECT ruletype,
                       SUM(success_count) AS total_success,
                       SUM(failure_count) AS total_failure,
                       SUM(total_duration_ms) AS total_ms,
                       (SUM(success_count) + SUM(failure_count)) AS total_evals
                  FROM {local_sceh_rules_metrics}
                 WHERE metric_date >= :cutoff
              GROUP BY ruletype
              ORDER BY ruletype";

        $rows = $DB->get_records_sql($sql, ['cutoff' => $cutoff]);
        $summary = [];

        foreach ($rows as $row) {
            $totalevals = (int) $row->total_evals;
            $summary[$row->ruletype] = [
                'total_success' => (int) $row->total_success,
                'total_failure' => (int) $row->total_failure,
                'total_evaluations' => $totalevals,
                'failure_rate' => $totalevals > 0
                    ? round((int) $row->total_failure / $totalevals * 100, 2)
                    : 0.0,
                'avg_duration_ms' => $totalevals > 0
                    ? round((int) $row->total_ms / $totalevals, 1)
                    : 0.0,
            ];
        }

        return $summary;
    }

    /**
     * Upsert a metric row for the current day.
     *
     * @param string $ruletype Rule type
     * @param int $ruleid Rule ID
     * @param int $successincrement Success counter increment
     * @param int $failureincrement Failure counter increment
     * @param int $durationms Duration in ms to add
     * @param string|null $errormessage Error message (overwrites last_error)
     */
    protected static function upsert_metric(
        string $ruletype,
        int $ruleid,
        int $successincrement,
        int $failureincrement,
        int $durationms,
        ?string $errormessage
    ) {
        global $DB;

        $metricdate = date('Y-m-d');
        $now = time();

        // Try to find existing record for today.
        $existing = $DB->get_record('local_sceh_rules_metrics', [
            'ruletype' => $ruletype,
            'ruleid' => $ruleid,
            'metric_date' => $metricdate,
        ]);

        if ($existing) {
            $existing->success_count += $successincrement;
            $existing->failure_count += $failureincrement;
            $existing->total_duration_ms += $durationms;
            if ($errormessage !== null) {
                $existing->last_error = $errormessage;
            }
            $existing->timemodified = $now;
            $DB->update_record('local_sceh_rules_metrics', $existing);
        } else {
            $record = new \stdClass();
            $record->ruletype = $ruletype;
            $record->ruleid = $ruleid;
            $record->metric_date = $metricdate;
            $record->success_count = $successincrement;
            $record->failure_count = $failureincrement;
            $record->total_duration_ms = $durationms;
            $record->last_error = $errormessage;
            $record->timemodified = $now;
            $DB->insert_record('local_sceh_rules_metrics', $record);
        }
    }
}
