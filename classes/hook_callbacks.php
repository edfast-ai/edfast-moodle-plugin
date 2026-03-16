<?php
/**
 * EdFast Plagiarism Plugin - Moodle 5.x Hook Callback Handlers
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_edfast;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/edfast/classes/lms_api_client.php');

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
            return rtrim($override, '/');
        }
        return self::get_webhook_url();
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
     * Process a file-based assignment submission by sending it to EdFast.
     *
     * @param \stdClass $submission Moodle submission record
     * @param \assign   $assign     The assignment instance
     */
    private static function process_file_submission(\stdClass $submission, \assign $assign): void {
        global $DB, $CFG;

        try {
            $cm      = $assign->get_course_module();
            $context = $assign->get_context();

            $fs    = get_file_storage();
            $files = $fs->get_area_files(
                $context->id,
                'assignsubmission_file',
                'submission_files',
                $submission->id,
                'filename',
                false
            );

            if (empty($files)) {
                return;
            }

            $api_client = new \plagiarism_edfast\lms_api_client();
            if (!$api_client->is_configured()) {
                debugging('[EdFast] Plugin not configured — skipping submission ' . $submission->id, DEBUG_DEVELOPER);
                return;
            }

            $supported = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
            $userid    = $submission->userid;
            $student   = $DB->get_record('user', ['id' => $userid], 'id, email, firstname, lastname, auth');
            $apikey_id = get_config('plagiarism_edfast', 'api_key_id');

            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

                if ($ext === 'zip') {
                    self::process_zip_file($file, $cm, $student, $cm->id, $submission->id, $userid, $apikey_id, $api_client);
                    continue;
                }

                if (!in_array($ext, $supported)) {
                    continue;
                }

                $content = $file->get_content();
                if (empty($content)) {
                    continue;
                }

                $file_id      = (int)$file->get_id();
                $content_hash = $file->get_contenthash();

                // Duplicate detection: skip unchanged re-submissions, re-submit on content change.
                $existing = $DB->get_record('plagiarism_edfast_submissions', ['moodle_file_id' => $file_id]);
                if ($existing) {
                    if ($existing->file_contenthash !== null && $existing->file_contenthash === $content_hash) {
                        error_log('[EdFast] File already submitted with identical content (moodle_file_id=' . $file_id . ') — skipping');
                        continue;
                    }
                    $DB->delete_records('plagiarism_edfast_submissions', ['id' => $existing->id]);
                }

                $data = [
                    'moodle_course_id'     => $cm ? (int)$cm->course : 0,
                    'moodle_assignment_id' => (int)$cm->id,
                    'moodle_submission_id' => (int)$submission->id,
                    'moodle_user_id'       => (int)$userid,
                    'moodle_file_id'       => $file_id,
                    'file_contenthash'     => $content_hash,
                    'moodle_user_email'    => $student ? strtolower(trim($student->email)) : '',
                    'moodle_user_name'     => $student ? trim($student->firstname . ' ' . $student->lastname) : '',
                    'saml_name_id'         => self::get_azure_oid($student, $userid),
                    'file_name'            => $file->get_filename(),
                    'file_content'         => base64_encode($content),
                    'file_size_bytes'      => (int)$file->get_filesize(),
                    'api_key_id'           => $apikey_id,
                    'webhook_url'          => self::get_webhook_url(),
                ];

                $result = $api_client->submit_for_analysis($data);
                if ($result) {
                    $record = new \stdClass();
                    $record->moodle_file_id       = $file_id;
                    $record->file_contenthash     = $content_hash;
                    $record->moodle_submission_id = (int)$submission->id;
                    $record->edfast_submission_id = $result['submission_id'];
                    $record->item_id              = $result['item_id'] ?? null;
                    $record->status               = 'pending';
                    $record->timecreated          = time();
                    $record->timemodified         = time();
                    $DB->insert_record('plagiarism_edfast_submissions', $record);
                }
            }
        } catch (\Exception $e) {
            debugging('[EdFast] process_file_submission error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Process an online-text assignment submission by sending it to EdFast.
     *
     * The text is encoded as a UTF-8 .txt file and submitted via the same
     * /lms/submissions/analyze endpoint used for file uploads.
     *
     * @param \stdClass $submission Moodle submission record
     * @param \assign   $assign     The assignment instance
     */
    private static function process_onlinetext_submission(\stdClass $submission, \assign $assign): void {
        global $DB, $CFG;

        try {
            $cm = $assign->get_course_module();

            $onlinetext = $DB->get_record(
                'assignsubmission_onlinetext',
                ['submission' => $submission->id]
            );

            $plain_text = $onlinetext ? trim(strip_tags($onlinetext->onlinetext)) : '';
            if (empty($plain_text)) {
                return;
            }

            $api_client = new \plagiarism_edfast\lms_api_client();
            if (!$api_client->is_configured()) {
                debugging('[EdFast] Plugin not configured — skipping submission ' . $submission->id, DEBUG_DEVELOPER);
                return;
            }

            $userid    = $submission->userid;
            $student   = $DB->get_record('user', ['id' => $userid], 'id, email, firstname, lastname, auth');
            $apikey_id = get_config('plagiarism_edfast', 'api_key_id');
            $content   = $plain_text;

            // Use a stable virtual file ID so duplicate detection works across re-submissions.
            // Mask to signed 32-bit range for PostgreSQL INTEGER compatibility.
            $file_id      = abs(crc32('onlinetext:' . $submission->id)) & 0x7FFFFFFF;
            $content_hash = sha1($content);

            // Duplicate detection.
            $existing = $DB->get_record('plagiarism_edfast_submissions', ['moodle_file_id' => $file_id]);
            if ($existing) {
                if ($existing->file_contenthash !== null && $existing->file_contenthash === $content_hash) {
                    error_log('[EdFast] Online text already submitted with identical content (submission=' . $submission->id . ') — skipping');
                    return;
                }
                $DB->delete_records('plagiarism_edfast_submissions', ['id' => $existing->id]);
            }

            $filename = 'submission_' . $submission->id . '.txt';

            $data = [
                'moodle_course_id'     => $cm ? (int)$cm->course : 0,
                'moodle_assignment_id' => (int)$cm->id,
                'moodle_submission_id' => (int)$submission->id,
                'moodle_user_id'       => (int)$userid,
                'moodle_file_id'       => $file_id,
                'file_contenthash'     => $content_hash,
                'moodle_user_email'    => $student ? strtolower(trim($student->email)) : '',
                'moodle_user_name'     => $student ? trim($student->firstname . ' ' . $student->lastname) : '',
                'saml_name_id'         => self::get_azure_oid($student, $userid),
                'file_name'            => $filename,
                'file_content'         => base64_encode($content),
                'file_size_bytes'      => strlen($content),
                'api_key_id'           => $apikey_id,
                'webhook_url'          => self::get_webhook_url(),
            ];

            $result = $api_client->submit_for_analysis($data);
            if ($result) {
                $record = new \stdClass();
                $record->moodle_file_id       = $file_id;
                $record->file_contenthash     = $content_hash;
                $record->moodle_submission_id = (int)$submission->id;
                $record->edfast_submission_id = $result['submission_id'];
                $record->item_id              = $result['item_id'] ?? null;
                $record->status               = 'pending';
                $record->timecreated          = time();
                $record->timemodified         = time();
                $DB->insert_record('plagiarism_edfast_submissions', $record);
            }
        } catch (\Exception $e) {
            debugging('[EdFast] process_onlinetext_submission error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Extract supported files from a ZIP archive and submit each one to EdFast.
     *
     * @param \stored_file   $zip_file   The ZIP file from Moodle's file storage
     * @param mixed          $cm         Course-module object
     * @param mixed          $student    User record
     * @param int            $cmid       Course-module ID
     * @param int            $submissionid
     * @param int            $userid
     * @param string         $apikey_id
     * @param lms_api_client $api_client
     */
    private static function process_zip_file(
        $zip_file, $cm, $student, int $cmid, int $submissionid,
        int $userid, string $apikey_id, $api_client
    ): void {
        global $DB, $CFG;

        if (!class_exists('ZipArchive')) {
            error_log('[EdFast] ZipArchive not available — cannot process ZIP: ' . $zip_file->get_filename());
            return;
        }

        $supported   = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
        $tmp_path    = tempnam(sys_get_temp_dir(), 'edfast_zip_');
        $zip_file_id = (int)$zip_file->get_id();

        try {
            file_put_contents($tmp_path, $zip_file->get_content());
            $za = new \ZipArchive();
            if ($za->open($tmp_path) !== true) {
                error_log('[EdFast] Failed to open ZIP: ' . $zip_file->get_filename());
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
                $content_hash    = sha1($content);
                $filename        = basename($entry_name);

                $existing = $DB->get_record('plagiarism_edfast_submissions', ['moodle_file_id' => $virtual_file_id]);
                if ($existing) {
                    if ($existing->file_contenthash !== null && $existing->file_contenthash === $content_hash) {
                        continue;
                    }
                    $DB->delete_records('plagiarism_edfast_submissions', ['id' => $existing->id]);
                }

                $data = [
                    'moodle_course_id'     => $cm ? (int)$cm->course : 0,
                    'moodle_assignment_id' => (int)$cmid,
                    'moodle_submission_id' => (int)$submissionid,
                    'moodle_user_id'       => (int)$userid,
                    'moodle_file_id'       => $virtual_file_id,
                    'file_contenthash'     => $content_hash,
                    'moodle_user_email'    => $student ? strtolower(trim($student->email)) : '',
                    'moodle_user_name'     => $student ? trim($student->firstname . ' ' . $student->lastname) : '',
                    'saml_name_id'         => self::get_azure_oid($student, $userid),
                    'file_name'            => $filename,
                    'file_content'         => base64_encode($content),
                    'file_size_bytes'      => strlen($content),
                    'api_key_id'           => $apikey_id,
                    'webhook_url'          => self::get_webhook_url(),
                ];

                $result = $api_client->submit_for_analysis($data);
                if ($result) {
                    $record = new \stdClass();
                    $record->moodle_file_id       = $virtual_file_id;
                    $record->file_contenthash     = $content_hash;
                    $record->moodle_submission_id = (int)$submissionid;
                    $record->edfast_submission_id = $result['submission_id'];
                    $record->item_id              = $result['item_id'] ?? null;
                    $record->status               = 'pending';
                    $record->timecreated          = time();
                    $record->timemodified         = time();
                    $DB->insert_record('plagiarism_edfast_submissions', $record);
                }
            }
            $za->close();
        } finally {
            if (file_exists($tmp_path)) {
                unlink($tmp_path);
            }
        }
    }

    /**
     * Return the Azure AD Object ID for a Moodle user if using OIDC auth.
     *
     * @param mixed $student User record with ->auth field (may be null)
     * @param int   $userid  Moodle user ID
     * @return string|null
     */
    private static function get_azure_oid($student, int $userid): ?string {
        global $DB;

        if (!$student || $student->auth !== 'oidc') {
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
}
