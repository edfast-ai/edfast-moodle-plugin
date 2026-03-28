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
 * EdFast Moodle 4.0+ Plagiarism Plugin - Webhook Receiver
 *
 * This file receives analysis results from EdFast service via webhook callbacks.
 * No authentication required - validation via webhook secret signature (HMAC-SHA256).
 *
 * Endpoint: {moodle-url}/plagiarism/edfast/webhook.php
 * Method: POST
 * Authentication: HMAC-SHA256 signature verification
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Machine-to-machine endpoint — no Moodle session/cookie needed.
// Authentication is via HMAC-SHA256 signature in X-EdFast-Signature header.
define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');

header('Content-Type: application/json');

try {
    // Get raw payload
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(array('error' => get_string('webhook_invalid_json', 'plagiarism_edfast')));
        exit;
    }

    // Verify signature
    $api_client = new \plagiarism_edfast\lms_api_client();
    $signature = $_SERVER['HTTP_X_EDFAST_SIGNATURE'] ?? '';

    if (!$api_client->verify_webhook_signature($payload, $signature)) {
        http_response_code(403);
        echo json_encode(array('error' => get_string('webhook_invalid_signature', 'plagiarism_edfast')));
        exit;
    }

    // Log webhook receipt
    if (get_config('plagiarism_edfast', 'debug_mode')) {
        mtrace('[EdFast] Webhook received for moodle_submission_id: ' . ($data['moodle_submission_id'] ?? 'unknown'));
    }

    // Update submission record in database
    // Primary lookup: by EdFast item_id — guaranteed unique per submission in the plugin DB.
    // Fallback: by moodle_submission_id — less reliable because a student can re-upload to
    // the same assignment (keeping the same Moodle submission ID) which creates a second row;
    // $DB->get_record() without IGNORE_MULTIPLE would throw or return the wrong record.
    global $DB;
    $submission = null;

    // Primary: item_id is unique and always present in the EdFast webhook payload
    if (!empty($data['item_id'])) {
        $submission = $DB->get_record('plagiarism_edfast_submissions',
                                      array('item_id' => $data['item_id']),
                                      '*', IGNORE_MULTIPLE);
    }

    // Fallback: moodle_submission_id (handles very old records that pre-date item_id storage)
    if (!$submission && !empty($data['moodle_submission_id'])) {
        $submission = $DB->get_record('plagiarism_edfast_submissions',
                                      array('moodle_submission_id' => $data['moodle_submission_id']),
                                      '*', IGNORE_MULTIPLE);
    }

    if (!$submission) {
        http_response_code(404);
        echo json_encode(array('error' => get_string('webhook_submission_not_found', 'plagiarism_edfast'), 'moodle_submission_id' => $data['moodle_submission_id'] ?? null, 'item_id' => $data['item_id'] ?? null));
        exit;
    }

    // Parse results from webhook payload (v1.1.0 format)
    // Backend sends: {status, moodle_submission_id, item_id, timestamp, results, webhook_token}
    $results = $data['results'] ?? array();
    
    // Update submission status and results
    $submission->status = $data['status'] ?? 'completed';
    $submission->item_id = $data['item_id'] ?? null;  // EdFast Item UUID
    $submission->similarity_score = $results['similarity_score'] ?? null;
    $submission->ai_percentage = $results['ai_percentage'] ?? null;
    $submission->readability_score = $results['readability_score'] ?? null;
    $submission->word_count = $results['word_count'] ?? null;
    $submission->detected_language = $results['detected_language'] ?? null;
    $submission->processing_status = $results['processing_status'] ?? null;
    $submission->error_message = $data['error_message'] ?? null;
    $submission->timemodified = time();
    $submission->response_data = $payload;  // Store full response for debugging

    $DB->update_record('plagiarism_edfast_submissions', $submission);

    // Trigger event for plugins/reports to listen to
    $event_data = array(
        'objectid' => $submission->id,
        'context'  => \context_system::instance(),
        'other' => array(
            'edfast_id' => $data['item_id'] ?? $submission->edfast_submission_id,
            'status' => $submission->status,
            'similarity_score' => $submission->similarity_score,
            'ai_percentage' => $submission->ai_percentage,
        )
    );

    $event = \plagiarism_edfast\event\analysis_complete::create($event_data);
    $event->trigger();

    // Return success
    http_response_code(200);
    echo json_encode(array(
        'success' => true,
        'message' => get_string('webhook_success', 'plagiarism_edfast'),
        'submission_id' => $submission->id
    ));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'error' => $e->getMessage()
    ));
}

exit;
