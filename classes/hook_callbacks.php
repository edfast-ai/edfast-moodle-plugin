<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * EdFast Plagiarism Plugin - Moodle 5.x Hook Callback Handlers
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_edfast;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for Moodle 5.x hooks-based event system.
 * Replaces the deprecated plagiarism_edfast_assessable_uploaded() functions.
 */
class hook_callbacks {

    /**
     * Get the effective webhook callback URL.
     *
     * Uses the admin-configured override (webhook_callback_url setting) when set —
     * required for local/tunnel environments where $CFG->wwwroot is localhost.
     * Falls back to the standard Moodle site URL in production.
     *
     * @return string Full URL to webhook.php
     */
    private static function get_webhook_url(): string {
        global $CFG;
        $override = get_config('plagiarism_edfast', 'webhook_callback_url');
        if (!empty($override)) {
            $base = rtrim($override, '/');
            if (substr($base, -strlen('webhook.php')) === 'webhook.php') {
                return $base;
            }
            return $base . '/plagiarism/edfast/webhook.php';
        }
        return $CFG->wwwroot . '/plagiarism/edfast/webhook.php';
    }

    /**
     * Called when a new file submission is created in an assignment.
     *
     * @param \assignsubmission_file\hook\submission_created $hook
     */
    public static function submission_file_created(
        \assignsubmission_file\hook\submission_created $hook
    ): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_file_submission($hook->get_submission(), $hook->get_assign());
    }

    /**
     * Called when an existing file submission is updated in an assignment.
     *
     * @param \assignsubmission_file\hook\submission_updated $hook
     */
    public static function submission_file_updated(
        \assignsubmission_file\hook\submission_updated $hook
    ): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_file_submission($hook->get_submission(), $hook->get_assign());
    }

    /**
     * Called when a new online text submission is created.
     *
     * @param \assignsubmission_onlinetext\hook\submission_created $hook
     */
    public static function submission_onlinetext_created(
        \assignsubmission_onlinetext\hook\submission_created $hook
    ): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_onlinetext_submission($hook->get_submission(), $hook->get_assign());
    }

    /**
     * Called when an existing online text submission is updated.
     *
     * @param \assignsubmission_onlinetext\hook\submission_updated $hook
     */
    public static function submission_onlinetext_updated(
        \assignsubmission_onlinetext\hook\submission_updated $hook
    ): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_onlinetext_submission($hook->get_submission(), $hook->get_assign());
    }

    /**
     * Queue an ad-hoc task to process a file-based assignment submission.
     *
     * @param \stdClass $submission Moodle submission record
     * @param \assign   $assign     The assignment instance
     */
    private static function process_file_submission(\stdClass $submission, \assign $assign): void {
        $task = new \plagiarism_edfast\task\submit_file();
        $task->set_custom_data([
            'contextid'    => $assign->get_context()->id,
            'userid'       => $submission->userid,
            'submissionid' => $submission->id,
            'cmid'         => $assign->get_course_module()->id,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
        debugging('[EdFast] Queued adhoc task for file submission ' . $submission->id, DEBUG_DEVELOPER);
    }

    /**
     * Queue an ad-hoc task to process an online-text assignment submission.
     *
     * @param \stdClass $submission Moodle submission record
     * @param \assign   $assign     The assignment instance
     */
    private static function process_onlinetext_submission(\stdClass $submission, \assign $assign): void {
        $task = new \plagiarism_edfast\task\submit_file();
        $task->set_custom_data([
            'contextid'               => $assign->get_context()->id,
            'userid'                  => $submission->userid,
            'submissionid'            => $submission->id,
            'cmid'                    => $assign->get_course_module()->id,
            'onlinetext_submission_id' => $submission->id,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
        debugging('[EdFast] Queued adhoc task for online text submission ' . $submission->id, DEBUG_DEVELOPER);
    }
}
