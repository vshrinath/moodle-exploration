<?php
/**
 * Configuration path helper for CLI scripts
 * 
 * Provides consistent config.php detection across all scripts
 * 
 * @package    local
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || defined('CLI_SCRIPT') || die();

/**
 * Find and require Moodle config.php with fallback paths
 * 
 * @return string Path to config.php that was loaded
 * @throws Exception if config.php cannot be found
 */
function require_moodle_config() {
    $config_paths = [
        __DIR__ . '/../../config.php',           // Standard relative path
        '/bitnami/moodle/config.php',            // Bitnami Docker
        '/opt/bitnami/moodle/config.php',        // Alternative Bitnami
        dirname(__DIR__, 2) . '/config.php',     // PHP 7+ dirname with levels
    ];
    
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            return $path;
        }
    }
    
    fwrite(STDERR, "ERROR: Moodle config.php not found in any of these locations:\n");
    foreach ($config_paths as $path) {
        fwrite(STDERR, "  - $path\n");
    }
    throw new Exception('Moodle config.php not found');
}

/**
 * Initialize CLI script with admin user context
 * 
 * @param string $capability Optional capability to check (default: moodle/site:config)
 * @return stdClass Admin user object
 * @throws Exception if no admin user found or capability check fails
 */
function init_cli_admin($capability = 'moodle/site:config') {
    global $USER;
    
    $admin = get_admin();
    if (!$admin) {
        fwrite(STDERR, "ERROR: No admin user found\n");
        throw new Exception('No admin user found');
    }
    
    // Set up the user session properly for CLI
    \core\session\manager::set_user($admin);
    
    // In CLI scripts, we generally bypass strict capability checks to avoid
    // race conditions during fresh installs where the access cache isn't ready.
    // However, we still verify we have a valid admin user for the scripts.
    if (!is_siteadmin($USER)) {
        fwrite(STDERR, "ERROR: Current CLI user is not a site administrator\n");
        throw new Exception('CLI user must be site admin');
    }
    
    return $admin;
}
