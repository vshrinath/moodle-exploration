<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Helper class for database transaction management
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides transaction management utilities
 */
class transaction_helper {
    
    /**
     * Execute a callback within a database transaction
     *
     * @param callable $callback Function to execute within transaction
     * @param string $error_message Custom error message prefix
     * @return mixed Result from callback
     * @throws \Exception If transaction fails
     */
    public static function execute_in_transaction(callable $callback, $error_message = 'Transaction failed') {
        global $DB;
        
        $transaction = $DB->start_delegated_transaction();
        
        try {
            $result = $callback();
            $transaction->allow_commit();
            return $result;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw new \Exception($error_message . ': ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Create multiple records in a transaction
     *
     * @param string $table Table name
     * @param array $records Array of record objects
     * @return array Array of inserted record IDs
     * @throws \Exception If any insert fails
     */
    public static function insert_records_transactional($table, array $records) {
        return self::execute_in_transaction(function() use ($table, $records) {
            global $DB;
            $ids = [];
            foreach ($records as $record) {
                $ids[] = $DB->insert_record($table, $record);
            }
            return $ids;
        }, "Failed to insert records into {$table}");
    }
}
