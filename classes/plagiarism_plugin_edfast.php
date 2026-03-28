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
 * EdFast Moodle 4.0+ Plagiarism Plugin - Main Plugin Class
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/lib.php');

/**
 * Plagiarism plugin class for EdFast service
 * Implements the plagiarism_plugin abstract class for Moodle 4.0+
 * Reference: https://moodledev.io/docs/4.5/apis/plugintypes/plagiarism
 *
 * @author  EdFast
 * @since   2026
 */
class plagiarism_plugin_edfast extends plagiarism_plugin {

    /**
     * Determine if EdFast is enabled (Moodle 4.5+ requirement)
     *
     * @return bool
     */
    public function is_enabled() {
        return (bool)get_config('plagiarism_edfast', 'enabled');
    }

    /**
     * Hook to save plugin settings in course module (Moodle 4.5+ requirement)
     * Required abstract method from plagiarism_plugin
     *
     * @param int $cmid Course module ID
     * @return void
     */
    public function save_form_elements($cmid) {
        // EdFast uses global settings, no per-module configuration
        // This method is required by plagiarism_plugin abstract class
    }

    /**
     * Hook to display form elements in assignment setup (Moodle 4.5+ requirement)
     * Required abstract method from plagiarism_plugin
     *
     * @param object $mform Moodle form object
     * @param object $context Course context
     * @param string $modulename The module name (e.g., 'assign')
     * @return void
     */
    public function get_form_elements_module($mform, $context, $modulename = '') {
        // EdFast uses global settings, no per-module configuration needed
        // This method is required by plagiarism_plugin abstract class
    }

    /**
     * Determine if this plugin can handle a specific file type
     *
     * @param string $filename
     * @return bool
     */
    public function can_check_file($filename = null) {
        $supported_types = array('.pdf', '.doc', '.docx', '.txt', '.rtf', '.odt');
        
        if ($filename) {
            $ext = strtolower(strrchr($filename, '.'));
            return in_array($ext, $supported_types);
        }
        
        return true;
    }

    /**
     * Check if EdFast is properly configured (Moodle 4.5+ best practice)
     *
     * @return bool
     */
    private function is_configured() {
        $api_key = get_config('plagiarism_edfast', 'api_key');
        $server_url = get_config('plagiarism_edfast', 'server_url');
        return !empty($api_key) && !empty($server_url);
    }

    /**
     * Hook called when assignment submission files are uploaded (Moodle 4.5+)
     * Called by Moodle event system when assignsubmission_file\event\assessable_uploaded fires
     *
     * @param object $eventdata Core event data object
     * @return void
     */
    public function event_file_uploaded($eventdata) {
        // Submission is handled by the event_observers registered in db/events.php,
        // which is the authoritative Moodle 4.x path. This method is a no-op to
        // prevent double-submission should the lib.php hook still be called.
        // Keeping the method signature to satisfy the plagiarism_plugin contract.
    }

    /**
     * Get the webhook URL for callbacks
     *
     * Uses admin-configured override when set — required for local/tunnel
     * environments where $CFG->wwwroot is localhost and not reachable by
     * EdFast's cloud backend. Falls back to Moodle site URL in production.
     *
     * @return string Full URL to webhook.php
     */
    private function get_webhook_url() {
        global $CFG;
        $override = get_config('plagiarism_edfast', 'webhook_callback_url');
        if (!empty($override)) {
            return rtrim($override, '/');
        }
        $url = $CFG->wwwroot . '/plagiarism/edfast/webhook.php';
        // Ensure HTTPS for secure webhook callbacks (Moodle 4.5+ best practice)
        return str_replace('http://', 'https://', $url);
    }

    /**
     * Store submission record in local database (v1.1.0 format)
     *
     * @param int $file_id Moodle file ID
     * @param string $edfast_submission_id EdFast submission ID
     * @param int $moodle_submission_id Moodle assignment submission ID
     * @param string|null $file_contenthash File content hash for dedup detection
     */
    private function store_submission_record($file_id, $edfast_submission_id, $moodle_submission_id = 0, $file_contenthash = null) {
        global $DB;
        
        // Note: This would use a local table for tracking
        // Table structure would be created in install.xml
        $record = new stdClass();
        $record->moodle_file_id = $file_id;
        $record->file_contenthash = $file_contenthash;   // SHA1 for re-submission detection
        $record->moodle_submission_id = $moodle_submission_id;
        $record->edfast_submission_id = $edfast_submission_id;
        $record->status = 'submitted';
        $record->timecreated = time();
        $record->timemodified = time();
        
        $DB->insert_record('plagiarism_edfast_submissions', $record);
    }

    /**
     * Get plagiarism report links for display (Moodle 4.5+ requirement)
     * Required abstract method from plagiarism_plugin base class
     *
     * @param array $linkarray Array with 'cm', 'userid', 'file', 'files' keys
     * @return string HTML for report display
     */
    public function get_links($linkarray) {
        if (!$this->is_enabled()) {
            return '';
        }

        // Moodle passes either 'file' (singular stored_file) or 'files' (array).
        // Normalise to a flat array of stored_file objects.
        $files = array();
        if (!empty($linkarray['file']) && is_object($linkarray['file'])) {
            $files[] = $linkarray['file'];
        }
        if (!empty($linkarray['files']) && is_array($linkarray['files'])) {
            foreach ($linkarray['files'] as $f) {
                $files[] = $f;
            }
        }

        if (empty($files)) {
            return '';
        }

        $html = '';
        foreach ($files as $file) {
            $submission = $this->get_submission_status($file->get_id());

            if ($submission) {
                if ($submission->status === 'completed') {
                    $html .= $this->render_report($submission, $linkarray);
                } else {
                    // Still processing — show status badge and report link if item_id available
                    $label = ($submission->status === 'pending')
                        ? get_string('pending',    'plagiarism_edfast')
                        : get_string('analyzing',  'plagiarism_edfast');
                    $html .= '<div class="alert alert-info" style="margin:4px 0;padding:4px 8px;font-size:0.85em;">'
                           . $label . '</div>';

                    // Show report link as soon as we have item_id (even while pending)
                    if (!empty($submission->item_id)) {
                        $api_client_pending = new \plagiarism_edfast\lms_api_client();
                        global $USER;
                        $seamless = (bool)get_config('plagiarism_edfast', 'enable_seamless_access');
                        $requester_email = ($seamless && !empty($USER->email)) ? $USER->email : null;
                        $requester_role  = $seamless ? $this->get_moodle_user_role() : null;
                        $report_url = $api_client_pending->get_report_link($submission->item_id, $requester_email, $requester_role);
                        if (!$report_url) {
                            $configured_frontend = get_config('plagiarism_edfast', 'frontend_url');
                            if (!empty($configured_frontend)) {
                                $base_url = rtrim($configured_frontend, '/');
                            } else {
                                $parsed_url = parse_url(rtrim(get_config('plagiarism_edfast', 'server_url'), '/'));
                                $base_url   = ($parsed_url['scheme'] ?? 'https') . '://' . ($parsed_url['host'] ?? 'edfast.ai');
                            }
                            $report_url = $base_url . '/items/' . htmlspecialchars($submission->item_id) . '/details';
                        }
                        $html .= '<a href="' . $report_url . '" class="btn btn-sm btn-outline-primary" '
                               . 'target="_blank" rel="noopener" style="font-size:0.8em;margin-top:2px;">'
                               . get_string('view_full_report', 'plagiarism_edfast')
                               . '</a>';
                    }
                }
            } else {
                $html .= '<div class="alert alert-secondary" style="margin:4px 0;padding:4px 8px;font-size:0.85em;">'
                       . get_string('not_analyzed', 'plagiarism_edfast')
                       . '</div>';
            }
        }

        return $html;
    }

    /**
     * Get submission status from database.
     *
     * If the local record is still 'pending' and item_id is known, polls the
     * EdFast API for the latest status (self-healing fallback for missed webhooks).
     *
     * @param int $file_id
     * @return object|false
     */
    private function get_submission_status($file_id) {
        global $DB;
        $submission = $DB->get_record(
            'plagiarism_edfast_submissions',
            array('moodle_file_id' => $file_id),
            '*',
            IGNORE_MULTIPLE
        );

        if (!$submission) {
            return false;
        }

        // Self-healing: if still pending and item_id is known, poll the EdFast
        // API once (at most every 30 s per record) so missed webhooks don't
        // leave the UI stuck on "Pending analysis" permanently.
        if ($submission->status !== 'completed' && !empty($submission->item_id)) {
            $age = time() - (int)$submission->timecreated;
            $last_poll = (int)($submission->timemodified ?? 0);
            $poll_interval = 30; // seconds between live checks
            if ($age > 60 && (time() - $last_poll) >= $poll_interval) {
                try {
                    $api_client = new \plagiarism_edfast\lms_api_client();
                    $api_status = $api_client->get_item_status($submission->item_id);
                    if ($api_status && ($api_status['processing_status'] ?? '') === 'COMPLETED') {
                        $submission->status            = 'completed';
                        $submission->similarity_score  = $api_status['similarity_score'] ?? $submission->similarity_score;
                        $submission->ai_percentage     = $api_status['ai_percentage'] ?? $submission->ai_percentage;
                        $submission->readability_score = $api_status['essay_quality_score'] ?? $submission->readability_score;
                        $submission->word_count        = $api_status['word_count'] ?? $submission->word_count;
                        $submission->detected_language = $api_status['detected_language'] ?? $submission->detected_language;
                        $submission->timemodified      = time();
                        $DB->update_record('plagiarism_edfast_submissions', $submission);
                    } else {
                        // Touch timemodified to throttle future polls.
                        $submission->timemodified = time();
                        $DB->update_record('plagiarism_edfast_submissions', $submission);
                    }
                } catch (\Exception $e) {
                    // Poll failed — continue with local status, try again next page load.
                }
            }
        }

        return $submission;
    }

    /**
     * Get hex color for a percentage score (similarity / AI %).
     * Mirrors the frontend getPercentageColor() utility (5-tier scale).
     *
     * @param float $score
     * @return string  CSS hex colour
     */
    private function get_percentage_color($score) {
        if ($score <= 20) return '#38A169'; // green.500
        if ($score <= 40) return '#D69E2E'; // yellow.500
        if ($score <= 60) return '#ED8936'; // orange.400
        if ($score <= 80) return '#DD6B20'; // orange.600
        return '#E53E3E';                   // red.500
    }

    /**
     * Get hex color for essay quality score (0–100 scale).
     * Mirrors the frontend getQualityScoreColorScheme() utility.
     *
     * @param float $score
     * @return string  CSS hex colour
     */
    private function get_essay_score_color($score) {
        if ($score >= 70) return '#38A169'; // green.500
        if ($score >= 50) return '#D69E2E'; // yellow.500
        return '#ED8936';                   // orange.400
    }

    /**
     * Determine the current Moodle user's role as a simple string.
     * Returns 'admin', 'teacher', or 'student'.
     * Used to embed requester_role in the report lms_token JWT.
     *
     * @return string
     */
    private function get_moodle_user_role() {
        global $USER;
        if (is_siteadmin($USER)) {
            return 'admin';
        }
        // Check for any editing-teacher or teacher role anywhere on the site
        $teacher_roles = get_roles_with_capability('moodle/course:update', CAP_ALLOW);
        foreach ($teacher_roles as $role) {
            if (user_has_role_assignment($USER->id, $role->id)) {
                return 'teacher';
            }
        }
        return 'student';
    }

    /**
     * Render plagiarism report with optional seamless access link (v1.1.0)
     *
     * @param object $submission
     * @param array $linkarray Array from get_links (contains userid info)
     * @return string
     */
    private function render_report($submission, $linkarray = array()) {
        // Compact td style — tighter than Bootstrap table-sm default
        $td_label = 'style="padding:2px 4px;vertical-align:middle;border:none;color:#555;font-size:0.88em;white-space:nowrap;"';

        $html = '<div class="plagiarism-report edfast-report" style="margin-top:4px;">';
        $html .= '<table style="border-collapse:collapse;width:100%;">';

        // Similarity (shown if score is not null — backend omits it when feature disabled)
        if (!is_null($submission->similarity_score)) {
            $color = $this->get_percentage_color((float)$submission->similarity_score);
            $td_value = 'style="padding:2px 4px;vertical-align:middle;border:none;' .
                'font-weight:600;font-size:0.88em;color:' . $color . ';"';
            $html .= '<tr>';
            $html .= '<td ' . $td_label . '>' . get_string('similarity', 'plagiarism_edfast') . ':</td>';
            $html .= '<td ' . $td_value . '>' . round($submission->similarity_score, 1) . '%</td>';
            $html .= '</tr>';
        }

        // AI % (shown if score is not null)
        if (!is_null($submission->ai_percentage)) {
            $color = $this->get_percentage_color((float)$submission->ai_percentage);
            $td_value = 'style="padding:2px 4px;vertical-align:middle;border:none;' .
                'font-weight:600;font-size:0.88em;color:' . $color . ';"';
            $html .= '<tr>';
            $html .= '<td ' . $td_label . '>' . get_string('ai_percentage', 'plagiarism_edfast') . ':</td>';
            $html .= '<td ' . $td_value . '>' . round($submission->ai_percentage, 1) . '%</td>';
            $html .= '</tr>';
        }

        // Essay score / readability (shown if score is not null)
        if (!is_null($submission->readability_score) && $submission->readability_score !== '') {
            $color = $this->get_essay_score_color((float)$submission->readability_score);
            $td_value = 'style="padding:2px 4px;vertical-align:middle;border:none;' .
                'font-weight:600;font-size:0.88em;color:' . $color . ';"';
            $html .= '<tr>';
            $html .= '<td ' . $td_label . '>' . get_string('essay_score', 'plagiarism_edfast') . ':</td>';
            $html .= '<td ' . $td_value . '>' . round($submission->readability_score, 1) . '</td>';
            $html .= '</tr>';
        }

        
        // Generate JWT-signed report link (no EdFast login required)
        $report_uuid = !empty($submission->item_id) ? $submission->item_id : null;
        if ($report_uuid) {
            $api_client  = new \plagiarism_edfast\lms_api_client();
            global $USER;
            $seamless = (bool)get_config('plagiarism_edfast', 'enable_seamless_access');
            $requester_email = ($seamless && !empty($USER->email)) ? $USER->email : null;
            $requester_role  = $seamless ? $this->get_moodle_user_role() : null;
            $report_url  = $api_client->get_report_link($report_uuid, $requester_email, $requester_role);
            if (!$report_url) {
                // Fallback: direct link (user needs EdFast session)
                $configured_frontend = get_config('plagiarism_edfast', 'frontend_url');
                if (!empty($configured_frontend)) {
                    $base_url = rtrim($configured_frontend, '/');
                } else {
                    $parsed   = parse_url(rtrim(get_config('plagiarism_edfast', 'server_url'), '/'));
                    $base_url = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'edfast.ai');
                }
                $report_url = $base_url . '/items/' . htmlspecialchars($report_uuid) . '/details';
            }
            $html .= '<tr><td colspan="2" style="padding-top:6px;">';
            $html .= '<a href="' . $report_url . '" class="btn btn-primary btn-sm" target="_blank" rel="noopener">';
            $html .= get_string('view_full_report', 'plagiarism_edfast');
            $html .= '</a>';
            $html .= '</td></tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Scheduled task to retry failed webhooks
     * (Called by Moodle cron)
     */
    public function cron() {
        global $DB;
        
        // Find failed submissions and retry
        $failed = $DB->get_records(
            'plagiarism_edfast_submissions',
            array('status' => 'error'),
            'timemodified ASC',
            '*',
            0,
            10
        );
        
        foreach ($failed as $submission) {
            // Retry webhook callback
            $this->retry_webhook($submission);
        }
    }

    /**
     * Retry webhook for a submission
     *
     * @param object $submission
     */
    private function retry_webhook($submission) {
        // Implementation for retrying failed webhooks
        mtrace('EdFast: Retrying webhook for submission ' . $submission->edfast_submission_id);
    }
}
