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
 * Ad-hoc task to submit files to EdFast for analysis.
 *
 * Queued by event observers / hook callbacks so that the heavy API call
 * (file content upload, DB writes) runs asynchronously via cron instead
 * of blocking the user's HTTP request.
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_edfast\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc task: submit a single file to EdFast for plagiarism/AI analysis.
 */
class submit_file extends \core\task\adhoc_task {

    /**
     * Execute the task — submit the file to EdFast.
     *
     * Expected custom data keys:
     *   contextid, submissionid, userid, cmid — from the original event
     *
     * For file submissions: file_id is set (Moodle stored_file ID).
     * For online text: onlinetext_submission_id is set.
     */
    public function execute(): void {
        global $DB, $CFG;

        $data = $this->get_custom_data();

        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }

        $api_client = new \plagiarism_edfast\lms_api_client();
        if (!$api_client->is_configured()) {
            debugging('[EdFast] Plugin not configured — skipping adhoc task', DEBUG_DEVELOPER);
            return;
        }

        $apikey_id = get_config('plagiarism_edfast', 'api_key_id');
        $student = $DB->get_record('user', ['id' => $data->userid], 'id, email, firstname, lastname, auth');

        // Determine the effective webhook callback URL.
        $webhook_url = self::get_effective_webhook_url();

        // Resolve course module for metadata.
        $cm = null;
        if (!empty($data->cmid)) {
            $cm = get_coursemodule_from_id('assign', $data->cmid);
        }

        // Online text submission path.
        if (!empty($data->onlinetext_submission_id)) {
            $this->process_onlinetext($data, $cm, $student, $apikey_id, $api_client, $webhook_url);
            return;
        }

        // File submission path.
        if (!empty($data->file_id)) {
            $this->process_file($data, $cm, $student, $apikey_id, $api_client, $webhook_url);
            return;
        }

        // Full-submission path (process all files in the submission area).
        $this->process_submission_files($data, $cm, $student, $apikey_id, $api_client, $webhook_url);
    }

    /**
     * Process a single stored file by its file ID.
     */
    private function process_file(
        object $data, $cm, $student, string $apikey_id, $api_client, string $webhook_url
    ): void {
        global $DB;

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($data->file_id);
        if (!$file || $file->is_directory()) {
            debugging('[EdFast] File not found for adhoc task: file_id=' . $data->file_id, DEBUG_DEVELOPER);
            return;
        }

        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        $supported = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];

        if ($ext === 'zip') {
            $this->process_zip_file(
                $file, $cm, $student, (int)$data->cmid, (int)$data->submissionid,
                (int)$data->userid, $apikey_id, $api_client, $webhook_url
            );
            return;
        }

        if (!in_array($ext, $supported)) {
            debugging('[EdFast] Skipping unsupported file type: ' . $file->get_filename(), DEBUG_DEVELOPER);
            return;
        }

        $content = $file->get_content();
        if (empty($content)) {
            debugging('[EdFast] Empty file content: ' . $file->get_filename(), DEBUG_DEVELOPER);
            return;
        }

        $this->submit_one_file(
            $content, $file->get_filename(), (int)$file->get_id(),
            $file->get_contenthash(), (int)$file->get_filesize(),
            $cm, $student, (int)$data->cmid, (int)$data->submissionid,
            (int)$data->userid, $apikey_id, $api_client, $webhook_url
        );
    }

    /**
     * Process all files attached to a submission.
     */
    private function process_submission_files(
        object $data, $cm, $student, string $apikey_id, $api_client, string $webhook_url
    ): void {
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $data->contextid,
            'assignsubmission_file',
            'submission_files',
            $data->submissionid,
            'filename',
            false
        );

        if (empty($files)) {
            debugging('[EdFast] No files found for submission ' . $data->submissionid, DEBUG_DEVELOPER);
            return;
        }

        $supported = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];

        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

            if ($ext === 'zip') {
                $this->process_zip_file(
                    $file, $cm, $student, (int)$data->cmid, (int)$data->submissionid,
                    (int)$data->userid, $apikey_id, $api_client, $webhook_url
                );
                continue;
            }

            if (!in_array($ext, $supported)) {
                debugging('[EdFast] Skipping unsupported file type: ' . $file->get_filename(), DEBUG_DEVELOPER);
                continue;
            }

            $content = $file->get_content();
            if (empty($content)) {
                debugging('[EdFast] Empty file content: ' . $file->get_filename(), DEBUG_DEVELOPER);
                continue;
            }

            $this->submit_one_file(
                $content, $file->get_filename(), (int)$file->get_id(),
                $file->get_contenthash(), (int)$file->get_filesize(),
                $cm, $student, (int)$data->cmid, (int)$data->submissionid,
                (int)$data->userid, $apikey_id, $api_client, $webhook_url
            );
        }
    }

    /**
     * Process an online text submission.
     */
    private function process_onlinetext(
        object $data, $cm, $student, string $apikey_id, $api_client, string $webhook_url
    ): void {
        global $DB;

        $onlinetext = $DB->get_record(
            'assignsubmission_onlinetext',
            ['submission' => $data->submissionid]
        );

        $plain_text = $onlinetext ? trim(strip_tags($onlinetext->onlinetext)) : '';
        if (empty($plain_text)) {
            return;
        }

        $file_id = abs(crc32('onlinetext:' . $data->submissionid)) & 0x7FFFFFFF;
        $content_hash = sha1($plain_text);

        // Duplicate detection.
        $existing = $DB->get_record('plagiarism_edfast_submissions', ['moodle_file_id' => $file_id]);
        if ($existing) {
            if ($existing->file_contenthash !== null && $existing->file_contenthash === $content_hash) {
                debugging('[EdFast] Online text already submitted with identical content — skipping', DEBUG_DEVELOPER);
                return;
            }
            $DB->delete_records('plagiarism_edfast_submissions', ['id' => $existing->id]);
        }

        $filename = 'submission_' . $data->submissionid . '.txt';
        $this->submit_one_file(
            $plain_text, $filename, $file_id, $content_hash, strlen($plain_text),
            $cm, $student, (int)$data->cmid, (int)$data->submissionid,
            (int)$data->userid, $apikey_id, $api_client, $webhook_url
        );
    }

    /**
     * Submit a single file to EdFast and record the tracking entry.
     */
    private function submit_one_file(
        string $content, string $filename, int $file_id, string $content_hash,
        int $file_size, $cm, $student, int $cmid, int $submissionid,
        int $userid, string $apikey_id, $api_client, string $webhook_url
    ): void {
        global $DB;

        // Duplicate detection.
        $existing = $DB->get_record('plagiarism_edfast_submissions', ['moodle_file_id' => $file_id]);
        if ($existing) {
            if ($existing->file_contenthash !== null && $existing->file_contenthash === $content_hash) {
                debugging(
                    '[EdFast] File already submitted with identical content (moodle_file_id=' .
                    $file_id . ') — skipping',
                    DEBUG_DEVELOPER
                );
                return;
            }
            $DB->delete_records('plagiarism_edfast_submissions', ['id' => $existing->id]);
        }

        $azure_oid = self::get_azure_oid($student, $userid);

        $request_data = [
            'moodle_course_id'     => $cm ? (int)$cm->course : 0,
            'moodle_assignment_id' => $cmid,
            'moodle_submission_id' => $submissionid,
            'moodle_user_id'       => $userid,
            'moodle_file_id'       => $file_id,
            'file_contenthash'     => $content_hash,
            'moodle_user_email'    => $student ? strtolower(trim($student->email)) : '',
            'moodle_user_name'     => $student ? trim($student->firstname . ' ' . $student->lastname) : '',
            'saml_name_id'         => $azure_oid,
            'file_name'            => $filename,
            'file_content'         => base64_encode($content),
            'file_size_bytes'      => $file_size,
            'api_key_id'           => $apikey_id,
            'webhook_url'          => $webhook_url,
        ];

        debugging(
            '[EdFast] Submitting file: ' . $filename . ' (submission ' . $submissionid . ', user ' . $userid . ')',
            DEBUG_DEVELOPER
        );
        $result = $api_client->submit_for_analysis($request_data);

        if ($result) {
            debugging('[EdFast] Accepted — submission_id: ' . ($result['submission_id'] ?? 'n/a'), DEBUG_DEVELOPER);

            $record_exists = $DB->get_record('plagiarism_edfast_submissions', [
                'edfast_submission_id' => $result['submission_id'],
            ]);
            if (!$record_exists) {
                $record = new \stdClass();
                $record->moodle_file_id       = $file_id;
                $record->file_contenthash     = $content_hash;
                $record->moodle_submission_id = $submissionid;
                $record->edfast_submission_id = $result['submission_id'];
                $record->item_id              = $result['item_id'] ?? null;
                $record->status               = 'pending';
                $record->timecreated          = time();
                $record->timemodified         = time();
                $DB->insert_record('plagiarism_edfast_submissions', $record);
            }
        } else {
            debugging('[EdFast] submit_for_analysis returned false for file: ' . $filename, DEBUG_DEVELOPER);
        }
    }

    /**
     * Extract supported files from a ZIP archive and submit each one.
     */
    private function process_zip_file(
        $zip_file, $cm, $student, int $cmid, int $submissionid,
        int $userid, string $apikey_id, $api_client, string $webhook_url
    ): void {
        global $DB;

        if (!class_exists('ZipArchive')) {
            debugging(
                '[EdFast] ZipArchive extension not available — cannot process ZIP: ' .
                $zip_file->get_filename(),
                DEBUG_DEVELOPER
            );
            return;
        }

        $supported = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
        $tmp_path = tempnam(sys_get_temp_dir(), 'edfast_zip_');
        $zip_file_id = (int)$zip_file->get_id();

        try {
            file_put_contents($tmp_path, $zip_file->get_content());
            $za = new \ZipArchive();
            if ($za->open($tmp_path) !== true) {
                debugging('[EdFast] Failed to open ZIP: ' . $zip_file->get_filename(), DEBUG_DEVELOPER);
                return;
            }

            for ($i = 0; $i < $za->numFiles; $i++) {
                $entry_name = $za->getNameIndex($i);
                $base = basename($entry_name);

                if (substr($entry_name, -1) === '/'
                    || strpos($entry_name, '__MACOSX/') !== false
                    || $base === '.DS_Store'
                    || strncmp($base, '._', 2) === 0) {
                    continue;
                }

                $ext = strtolower(pathinfo($entry_name, PATHINFO_EXTENSION));
                if (!in_array($ext, $supported)) {
                    continue;
                }

                $content = $za->getFromIndex($i);
                if ($content === false || strlen($content) === 0) {
                    continue;
                }

                $virtual_file_id = abs(crc32($zip_file_id . ':' . $i)) & 0x7FFFFFFF;
                $content_hash = sha1($content);
                $filename = basename($entry_name);

                $this->submit_one_file(
                    $content, $filename, $virtual_file_id, $content_hash, strlen($content),
                    $cm, $student, $cmid, $submissionid, $userid, $apikey_id, $api_client, $webhook_url
                );
            }
            $za->close();
        } finally {
            if (file_exists($tmp_path)) {
                unlink($tmp_path);
            }
        }
    }

    /**
     * Return the Azure AD Object ID for a user if using OIDC auth.
     *
     * @param mixed $student User record with ->auth field (may be null)
     * @param int $userid Moodle user ID
     * @return string|null Azure AD OID or null
     */
    private static function get_azure_oid($student, int $userid): ?string {
        global $DB;

        if (!$student || !isset($student->auth) || $student->auth !== 'oidc') {
            return null;
        }

        try {
            $token = $DB->get_record('auth_oidc_token', ['userid' => $userid], 'oidcuniqid');
            if ($token && !empty($token->oidcuniqid)) {
                return (string)$token->oidcuniqid;
            }
        } catch (\Exception $e) {
            // auth_oidc plugin not installed.
        }

        return null;
    }

    /**
     * Return the effective webhook callback URL.
     *
     * @return string Full URL to webhook.php
     */
    private static function get_effective_webhook_url(): string {
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
}
