<?php
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

require_once($CFG->dirroot . '/plagiarism/edfast/classes/lms_api_client.php');

class event_observers {

    /**
     * Handle assignsubmission_file\event\assessable_uploaded
     * (Moodle 4.x primary entry point — fires when a file is staged)
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
     */
    public static function submission_updated(\core\event\base $event): void {
        if (!get_config('plagiarism_edfast', 'enabled')) {
            return;
        }
        self::process_event($event);
    }

    /**
     * Core processing logic — extracts files from the event context and
     * forwards each supported file to EdFast for analysis.
     *
     * @param \core\event\base $event
     */
    private static function process_event(\core\event\base $event): void {
        global $DB, $CFG;

        try {
            $api_client = new \plagiarism_edfast\lms_api_client();
            if (!$api_client->is_configured()) {
                error_log('[EdFast] Plugin not configured — skipping event ' . $event->eventname);
                return;
            }

            $contextid   = $event->contextid;
            $userid      = $event->userid;
            $submissionid = $event->objectid;    // submission record id
            $cmid        = $event->get_context()->instanceid;

            // Pull all files from the assignment submission file area
            $fs    = get_file_storage();
            $files = $fs->get_area_files(
                $contextid,
                'assignsubmission_file',
                'submission_files',
                $submissionid,
                'filename',
                false
            );

            if (empty($files)) {
                // No files staged yet — this is normal for draft saves or non-file submissions.
                // submission_updated will fire again once files are committed.
                error_log('[EdFast] No files found for submission ' . $submissionid . ' (event: ' . $event->eventname . ') — skipping');
                return;
            }

            $supported = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];

            // Load assignment record to get course/assignment IDs
            $cm = get_coursemodule_from_id('assign', $cmid);
            $student = $DB->get_record('user', ['id' => $userid], 'id, email, firstname, lastname, auth');
            $apikey_id = get_config('plagiarism_edfast', 'api_key_id');

            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

                if ($ext === 'zip') {
                    // Extract supported files from ZIP and submit each one
                    self::process_zip_file(
                        $file, $cm, $student, $cmid, $submissionid, $userid, $apikey_id, $api_client
                    );
                    continue;
                }

                if (!in_array($ext, $supported)) {
                    error_log('[EdFast] Skipping unsupported file type: ' . $file->get_filename());
                    continue;
                }

                // Read file content and base64-encode for API
                $content = $file->get_content();
                if (empty($content)) {
                    error_log('[EdFast] Empty file content: ' . $file->get_filename());
                    continue;
                }

                self::submit_one_file(
                    $content,
                    $file->get_filename(),
                    (int)$file->get_id(),          // real Moodle file ID
                    $file->get_contenthash(),
                    (int)$file->get_filesize(),
                    $cm, $student, $cmid, $submissionid, $userid, $apikey_id, $api_client
                );
            }

        } catch (\Exception $e) {
            error_log('[EdFast] event_observers::process_event error: ' . $e->getMessage());
        }
    }

    /**
     * Extract supported files from a ZIP archive and submit each one to EdFast.
     *
     * PHP's ZipArchive is used to read entries in-memory (no temp-file extraction).
     * Each embedded file gets a deterministic virtual moodle_file_id derived from the
     * parent ZIP's file ID + entry index so that duplicate-submission tracking works
     * correctly across re-submissions.
     *
     * @param \stored_file        $zip_file    The ZIP stored_file from Moodle's file API
     * @param mixed               $cm          Course-module object (may be null)
     * @param mixed               $student     User record (may be null)
     * @param int                 $cmid        Course-module ID
     * @param int                 $submissionid Assignment submission record ID
     * @param int                 $userid      Moodle user ID
     * @param string              $apikey_id   EdFast API key ID from plugin config
     * @param lms_api_client      $api_client  Configured API client instance
     */
    private static function process_zip_file(
        $zip_file, $cm, $student, int $cmid, int $submissionid,
        int $userid, string $apikey_id, $api_client
    ): void {
        global $CFG;

        if (!class_exists('ZipArchive')) {
            error_log('[EdFast] ZipArchive extension not available — cannot process ZIP: ' . $zip_file->get_filename());
            return;
        }

        $supported = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];

        // Write ZIP to a temp file so ZipArchive can open it
        $tmp_path = tempnam(sys_get_temp_dir(), 'edfast_zip_');
        try {
            file_put_contents($tmp_path, $zip_file->get_content());

            $za = new \ZipArchive();
            $result = $za->open($tmp_path);
            if ($result !== true) {
                error_log('[EdFast] Failed to open ZIP file: ' . $zip_file->get_filename() . ' (ZipArchive error ' . $result . ')');
                return;
            }

            $zip_file_id = (int)$zip_file->get_id();
            $found = 0;

            for ($i = 0; $i < $za->numFiles; $i++) {
                $entry_name = $za->getNameIndex($i);

                // Skip directories and macOS metadata entries:
                //   - trailing '/' = directory entry
                //   - __MACOSX/ folder that macOS adds to ZIPs
                //   - .DS_Store files
                //   - ._* AppleDouble resource forks (e.g. ._Quantum physics.pdf)
                $base = basename($entry_name);
                if (substr($entry_name, -1) === '/'
                    || strpos($entry_name, '__MACOSX/') !== false
                    || $base === '.DS_Store'
                    || strncmp($base, '._', 2) === 0) {
                    continue;
                }

                $entry_ext = strtolower(pathinfo($entry_name, PATHINFO_EXTENSION));
                if (!in_array($entry_ext, $supported)) {
                    error_log('[EdFast] ZIP entry skipped (unsupported type): ' . $entry_name);
                    continue;
                }

                $content = $za->getFromIndex($i);
                if ($content === false || strlen($content) === 0) {
                    error_log('[EdFast] ZIP entry empty or unreadable: ' . $entry_name);
                    continue;
                }

                // Generate a stable virtual file ID for this ZIP entry.
                // abs(crc32(...)) can reach 2147483648 on 64-bit PHP, but PostgreSQL
                // INTEGER is signed 32-bit (max 2147483647).  Masking with 0x7FFFFFFF
                // keeps the value in [0, 2147483647] and preserves uniqueness.
                $virtual_file_id = abs(crc32($zip_file_id . ':' . $i)) & 0x7FFFFFFF;
                $content_hash    = sha1($content);
                $display_name    = basename($entry_name);  // show only the filename, not internal path

                error_log('[EdFast] ZIP entry found: ' . $display_name . ' (' . strlen($content) . ' bytes) from ' . $zip_file->get_filename());

                self::submit_one_file(
                    $content,
                    $display_name,
                    $virtual_file_id,
                    $content_hash,
                    strlen($content),
                    $cm, $student, $cmid, $submissionid, $userid, $apikey_id, $api_client
                );
                $found++;
            }

            $za->close();

            if ($found === 0) {
                error_log('[EdFast] ZIP file contained no supported documents: ' . $zip_file->get_filename());
            } else {
                error_log('[EdFast] ZIP processed: ' . $found . ' file(s) submitted from ' . $zip_file->get_filename());
            }
        } finally {
            if (file_exists($tmp_path)) {
                unlink($tmp_path);
            }
        }
    }

    /**
     * Submit a single file (by raw content) to EdFast and record the tracking entry.
     *
     * Used both for direct Moodle stored_files and for files extracted from a ZIP archive.
     *
     * @param string         $content         Raw binary file content
     * @param string         $filename        Filename to display in EdFast
     * @param int            $file_id         Moodle file ID (or virtual ID for ZIP entries)
     * @param string         $content_hash    SHA1/contenthash of the content
     * @param int            $file_size       File size in bytes
     * @param mixed          $cm              Course-module object (may be null)
     * @param mixed          $student         User record (may be null)
     * @param int            $cmid            Course-module ID
     * @param int            $submissionid    Assignment submission record ID
     * @param int            $userid          Moodle user ID
     * @param string         $apikey_id       EdFast API key ID
     * @param lms_api_client $api_client      Configured API client
     */
    /**
     * Return the Azure AD Object ID for a Moodle user if the school uses
     * Azure AD SSO via the auth_oidc plugin (Microsoft 365 integration).
     *
     * When present, EdFast uses this as the primary identity key (saml_name_id)
     * which survives email changes. Falls back to null if the user doesn't
     * authenticate via OIDC or if the auth_oidc tables are absent.
     *
     * @param mixed $student  User record with ->auth field (may be null)
     * @param int   $userid   Moodle user ID
     * @return string|null    Azure AD OID, or null
     */
    private static function get_azure_oid($student, int $userid): ?string {
        global $DB;

        // Only attempt if the user authenticates via Azure AD OIDC plugin.
        if (!$student || !isset($student->auth) || $student->auth !== 'oidc') {
            return null;
        }

        // auth_oidc_token stores the Azure AD Object ID in oidcuniqid.
        // The table may not exist on non-OIDC Moodle installs — catch gracefully.
        try {
            $token = $DB->get_record('auth_oidc_token', ['userid' => $userid], 'oidcuniqid');
            if ($token && !empty($token->oidcuniqid)) {
                return (string)$token->oidcuniqid;
            }
        } catch (\Exception $e) {
            // auth_oidc plugin not installed — not an error, just no OID available.
        }

        return null;
    }

    private static function submit_one_file(
        string $content, string $filename, int $file_id, string $content_hash,
        int $file_size, $cm, $student, int $cmid, int $submissionid,
        int $userid, string $apikey_id, $api_client
    ): void {
        global $DB, $CFG;

        // Pre-call guard: skip if this exact file was already submitted with the same content.
        // For ZIP entries, file_id is a virtual (crc32-derived) ID, so this still de-duplicates
        // re-submissions of the same archive correctly.
        $already_submitted = $DB->get_record('plagiarism_edfast_submissions', [
            'moodle_file_id' => $file_id,
        ]);
        if ($already_submitted) {
            $stored_hash = $already_submitted->file_contenthash ?? null;
            if ($stored_hash !== null && $stored_hash === $content_hash) {
                error_log('[EdFast] File already submitted with identical content (moodle_file_id=' . $file_id . ') — skipping duplicate');
                return;
            }
            // Content has changed — delete old tracking record and re-submit.
            error_log('[EdFast] File re-submitted with new content (moodle_file_id=' . $file_id . ', hash changed) — processing update');
            $DB->delete_records('plagiarism_edfast_submissions', ['id' => $already_submitted->id]);
        }

        $data = [
            'moodle_course_id'      => $cm ? (int)$cm->course : 0,
            'moodle_assignment_id'  => (int)$cmid,
            'moodle_submission_id'  => (int)$submissionid,
            'moodle_user_id'        => (int)$userid,
            'moodle_file_id'        => $file_id,
            'file_contenthash'      => $content_hash,
            'moodle_user_email'     => $student ? strtolower(trim($student->email)) : '',
            'moodle_user_name'      => $student ? trim($student->firstname . ' ' . $student->lastname) : '',
            'saml_name_id'          => self::get_azure_oid($student, $userid),  // Azure AD OID (null if not OIDC auth)
            'file_name'             => $filename,
            'file_content'          => base64_encode($content),
            'file_size_bytes'       => $file_size,
            'api_key_id'            => $apikey_id,
            // Use the admin-configured webhook callback URL override when set.
            // This is required when Moodle's $CFG->wwwroot is localhost (Docker dev)
            // and the EdFast cloud backend needs a publicly reachable ngrok URL instead.
            'webhook_url'           => self::get_effective_webhook_url($CFG),
        ];

        error_log('[EdFast] Submitting file: ' . $filename . ' (submission ' . $submissionid . ', user ' . $userid . ')');
        $result = $api_client->submit_for_analysis($data);
        if ($result) {
            error_log('[EdFast] Accepted — submission_id: ' . ($result['submission_id'] ?? 'n/a'));

            // Save local tracking record so webhook.php can look up by moodle_submission_id.
            $existing = $DB->get_record('plagiarism_edfast_submissions', [
                'edfast_submission_id' => $result['submission_id'],
            ]);
            if ($existing) {
                error_log('[EdFast] Tracking record already exists for submission_id: ' . $result['submission_id'] . ' — skipping insert');
            } else {
                $record = new \stdClass();
                $record->moodle_file_id       = $file_id;
                $record->file_contenthash     = $content_hash;
                $record->moodle_submission_id = (int)$submissionid;
                $record->edfast_submission_id = $result['submission_id'];
                $record->item_id              = $result['item_id'] ?? null;
                $record->status               = 'pending';
                $record->timecreated          = time();
                $record->timemodified         = time();
                $DB->insert_record('plagiarism_edfast_submissions', $record);
            }
        } else {
            error_log('[EdFast] submit_for_analysis returned false for file: ' . $filename);
        }
    }

    /**
     * Return the effective webhook callback URL for this Moodle instance.
     *
     * Priority:
     *   1. Admin-configured override (plagiarism_edfast | webhook_callback_url)
     *      — required when $CFG->wwwroot is localhost/Docker and a public ngrok
     *        URL is needed for the EdFast cloud backend to reach Moodle.
     *   2. $CFG->wwwroot + standard path  (production default)
     *
     * @param object $CFG Moodle global $CFG
     * @return string Full URL to webhook.php
     */
    private static function get_effective_webhook_url($CFG): string {
        $override = get_config('plagiarism_edfast', 'webhook_callback_url');
        if (!empty($override)) {
            // Strip any trailing path the admin may have accidentally included,
            // then re-append the canonical path.
            $base = rtrim($override, '/');
            // If the override already ends with webhook.php, use as-is.
            if (substr($base, -strlen('webhook.php')) === 'webhook.php') {
                return $base;
            }
            return $base . '/plagiarism/edfast/webhook.php';
        }
        return $CFG->wwwroot . '/plagiarism/edfast/webhook.php';
    }
}
