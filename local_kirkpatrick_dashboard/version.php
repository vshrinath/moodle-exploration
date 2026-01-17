<?php
/**
 * Version information for Unified Kirkpatrick Dashboard Plugin
 *
 * @package    local_kirkpatrick_dashboard
 * @copyright  2025 Competency-Based Learning System
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_kirkpatrick_dashboard';
$plugin->version = 2025011700;
$plugin->requires = 2022041900; // Moodle 4.0
$plugin->maturity = MATURITY_BETA;
$plugin->release = 'v1.0.0-beta';
$plugin->dependencies = [
    'local_kirkpatrick_level4' => 2025011700 // Optional dependency
];
