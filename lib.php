<?php
/**
 * EdFast Moodle 4.0+ Plagiarism Plugin - Main Library File
 *
 * This file provides Moodle event hooks that are called automatically
 * by the Moodle event system for Moodle 4.0+.
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/lib.php');
require_once($CFG->dirroot . '/plagiarism/edfast/classes/plagiarism_plugin_edfast.php');

/**
 * Hook: Return plagiarism plugin instance
 */
function plagiarism_edfast_get_instance() {
    return new \plagiarism_plugin_edfast();
}

/**
 * Hook: Process file upload events for Moodle 4.5+ (core\event\base)
 *
 * Called automatically when assignsubmission_file\event\assessable_uploaded is fired
 * This is the standard Moodle event system integration for plagiarism plugins.
 *
 * @param \core\event\base $event The Moodle event object
 * @return void
 */
function plagiarism_edfast_assessable_uploaded(\core\event\base $event) {
    if (!get_config('plagiarism_edfast', 'enabled')) {
        return;
    }

    try {
        $plugin = plagiarism_edfast_get_instance();
        $plugin->event_file_uploaded($event);
    } catch (Exception $e) {
        mtrace('EdFast assessable_uploaded hook error: ' . $e->getMessage());
        debugging('EdFast error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Hook: Display plagiarism report in assignment submission view
 *
 * Called by assignment module when displaying submission details
 * Expected array keys: 'cm', 'userid', 'file', 'files'
 *
 * @param array $linkarray Array with context about the submission
 * @return string HTML string for report display, or empty string
 */
function plagiarism_edfast_get_links($linkarray) {
    if (!get_config('plagiarism_edfast', 'enabled')) {
        return '';
    }

    try {
        $plugin = plagiarism_edfast_get_instance();
        return $plugin->get_links($linkarray);
    } catch (Exception $e) {
        mtrace('EdFast get_links error: ' . $e->getMessage());
        debugging('EdFast error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return '';
    }
}

/**
 * Hook: Scheduled task (cron) for cleanup and retry logic
 *
 * Called every 5 minutes by Moodle cron (as per version.php $plugin->cron setting)
 * Retry failed webhooks and clean old records
 *
 * @return bool Success status
 */
function plagiarism_edfast_cron() {
    if (!get_config('plagiarism_edfast', 'enabled')) {
        return true;
    }

    try {
        $plugin = plagiarism_edfast_get_instance();
        $plugin->cron();
        return true;
    } catch (Exception $e) {
        mtrace('EdFast cron error: ' . $e->getMessage());
        debugging('EdFast cron error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Hook: Plugin uninstall cleanup (Moodle 4.5+)
 *
 * Called during plugin uninstallation to clean up any plugin-specific data
 *
 * @return void
 */
function plagiarism_edfast_uninstall() {
    // Clean up any plugin-specific configuration
    // Database tables are automatically removed by Moodle
    unset_config('enabled', 'plagiarism_edfast');
    unset_config('api_key', 'plagiarism_edfast');
    unset_config('server_url', 'plagiarism_edfast');
    
    return true;
}
