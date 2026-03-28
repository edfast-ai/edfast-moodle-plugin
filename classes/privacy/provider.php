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
 * Privacy Subsystem implementation for plagiarism_edfast.
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_edfast\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for plagiarism_edfast.
 *
 * Declares stored and exported user data, and handles data export/deletion.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the type of data stored or transmitted by this plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        // Local database table.
        $collection->add_database_table(
            'plagiarism_edfast_submissions',
            [
                'moodle_file_id'       => 'privacy:metadata:plagiarism_edfast_submissions:moodle_file_id',
                'moodle_submission_id'  => 'privacy:metadata:plagiarism_edfast_submissions:moodle_submission_id',
                'edfast_submission_id'  => 'privacy:metadata:plagiarism_edfast_submissions:edfast_submission_id',
                'status'               => 'privacy:metadata:plagiarism_edfast_submissions:status',
                'similarity_score'     => 'privacy:metadata:plagiarism_edfast_submissions:similarity_score',
                'ai_percentage'        => 'privacy:metadata:plagiarism_edfast_submissions:ai_percentage',
                'timecreated'          => 'privacy:metadata:plagiarism_edfast_submissions:timecreated',
                'timemodified'         => 'privacy:metadata:plagiarism_edfast_submissions:timemodified',
            ],
            'privacy:metadata:plagiarism_edfast_submissions'
        );

        // External system (EdFast cloud service).
        $collection->add_external_location_link(
            'edfast_server',
            [
                'file_content'       => 'privacy:metadata:edfast_server:file_content',
                'file_name'          => 'privacy:metadata:edfast_server:file_name',
                'moodle_user_email'  => 'privacy:metadata:edfast_server:moodle_user_email',
                'moodle_user_name'   => 'privacy:metadata:edfast_server:moodle_user_name',
            ],
            'privacy:metadata:edfast_server'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * Plagiarism submissions are linked to assignment submissions via moodle_submission_id.
     * We join through assign_submission to find the relevant course module contexts.
     *
     * @param int $userid The user to search.
     * @return contextlist The list of contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT ctx.id
                  FROM {plagiarism_edfast_submissions} pes
                  JOIN {assign_submission} asub ON asub.id = pes.moodle_submission_id
                  JOIN {course_modules} cm ON cm.instance = asub.assignment
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE asub.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT DISTINCT asub.userid
                  FROM {plagiarism_edfast_submissions} pes
                  JOIN {assign_submission} asub ON asub.id = pes.moodle_submission_id
                  JOIN {course_modules} cm ON cm.instance = asub.assignment
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * Export all user data for the specified approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export data for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $sql = "SELECT pes.*
                      FROM {plagiarism_edfast_submissions} pes
                      JOIN {assign_submission} asub ON asub.id = pes.moodle_submission_id
                      JOIN {course_modules} cm ON cm.instance = asub.assignment
                      JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                     WHERE asub.userid = :userid AND cm.id = :cmid";

            $records = $DB->get_records_sql($sql, [
                'userid' => $userid,
                'cmid' => $context->instanceid,
            ]);

            if (empty($records)) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object) [
                    'edfast_submission_id' => $record->edfast_submission_id,
                    'item_id'             => $record->item_id,
                    'status'              => $record->status,
                    'similarity_score'    => $record->similarity_score,
                    'ai_percentage'       => $record->ai_percentage,
                    'readability_score'   => $record->readability_score,
                    'word_count'          => $record->word_count,
                    'detected_language'   => $record->detected_language,
                    'timecreated'         => \core_privacy\local\request\transform::datetime($record->timecreated),
                    'timemodified'        => \core_privacy\local\request\transform::datetime($record->timemodified),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'plagiarism_edfast')],
                (object) ['submissions' => $data]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT pes.id
                  FROM {plagiarism_edfast_submissions} pes
                  JOIN {assign_submission} asub ON asub.id = pes.moodle_submission_id
                  JOIN {course_modules} cm ON cm.instance = asub.assignment
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                 WHERE cm.id = :cmid";

        $ids = $DB->get_fieldset_sql($sql, ['cmid' => $context->instanceid]);

        if (!empty($ids)) {
            list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('plagiarism_edfast_submissions', "id $insql", $params);
        }
    }

    /**
     * Delete all user data for the specified user in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $sql = "SELECT pes.id
                      FROM {plagiarism_edfast_submissions} pes
                      JOIN {assign_submission} asub ON asub.id = pes.moodle_submission_id
                      JOIN {course_modules} cm ON cm.instance = asub.assignment
                      JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                     WHERE asub.userid = :userid AND cm.id = :cmid";

            $ids = $DB->get_fieldset_sql($sql, [
                'userid' => $userid,
                'cmid' => $context->instanceid,
            ]);

            if (!empty($ids)) {
                list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
                $DB->delete_records_select('plagiarism_edfast_submissions', "id $insql", $params);
            }
        }
    }

    /**
     * Delete multiple users' data within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $sql = "SELECT pes.id
                  FROM {plagiarism_edfast_submissions} pes
                  JOIN {assign_submission} asub ON asub.id = pes.moodle_submission_id
                  JOIN {course_modules} cm ON cm.instance = asub.assignment
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                 WHERE cm.id = :cmid AND asub.userid $usersql";

        $params = array_merge(['cmid' => $context->instanceid], $userparams);
        $ids = $DB->get_fieldset_sql($sql, $params);

        if (!empty($ids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('plagiarism_edfast_submissions', "id $insql", $inparams);
        }
    }
}
