<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Version information for local_sceh_importer plugin.
 *
 * @package    local_sceh_importer
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_sceh_importer';
$plugin->version = 2026021702;
$plugin->requires = 2024051700; // Moodle 5.1.
$plugin->maturity = MATURITY_ALPHA;

// Template version for support troubleshooting
define('LOCAL_SCEH_IMPORTER_TEMPLATE_VERSION', '1.0');
$plugin->release = '0.1.0';
