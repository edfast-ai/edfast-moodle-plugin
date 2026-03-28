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
 * EdFast Plagiarism Plugin - Event Observer Handlers
 *
 * Handles Moodle legacy event dispatches for assignment file submissions.
 * These observers are registered in db/events.php and work on all
 * Moodle 4.x / 5.x versions.
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_edfast;

defined('MOODLE_INTERNAL') || die();

class event_observers {

    /**
     * Handle assignsubmission_file\event\assessable_uploaded
     * (Moodle 4.x primary entry point — fires when a file is staged)
     *
     * @param \core\event\base $event The Moodle event object
     */
    public static function assessable_uploaded(\core\event\base $event): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_event($event);
    }

    /**
     * Handle assignsubmission_file\event\submission_created
     * (Fires when the student clicks "Save submission" for the first time)
     *
     * @param \core\event\base $event The Moodle event object
     */
    public static function submission_created(\core\event\base $event): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_event($event);
    }

    /**
     * Handle assignsubmission_file\event\submission_updated
     * (Fires when the student re-submits a file)
     *
     * @param \core\event\base $event The Moodle event object
     */
    public static function submission_updated(\core\event\base $event): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_event($event);
    }

    /**
     * Queue an ad-hoc task that processes the submission files asynchronously.
     *
     * The heavy work (file I/O, base64 encoding, API call) is performed by
     * {@see \plagiarism_edfast\task\submit_file} so the student's HTTP request
     * is not blocked.
     *
     * @param \core\event\base $event
     */
    private static function process_event(\core\event\base $event): void {
        $task = new \plagiarism_edfast\task\submit_file();
        $task->set_custom_data([
            'contextid'    => $event->contextid,
            'userid'       => $event->userid,
            'submissionid' => $event->objectid,
            'cmid'         => $event->get_context()->instanceid,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
        debugging('[EdFast] Queued adhoc task for submission ' . $event->objectid, DEBUG_DEVELOPER);
    }
}
