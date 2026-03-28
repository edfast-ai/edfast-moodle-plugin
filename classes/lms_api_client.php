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
 * EdFast Moodle 4.0+ Plagiarism Plugin - API Client
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_edfast;

defined('MOODLE_INTERNAL') || die();

class lms_api_client {

    private $api_key;
    private $server_url;
    private $timeout;
    private $debug;

    public function __construct() {
        $this->api_key = get_config('plagiarism_edfast', 'api_key');
        $this->server_url = get_config('plagiarism_edfast', 'server_url');
        $this->timeout = get_config('plagiarism_edfast', 'webhook_timeout') ?: 30;
        $this->debug = get_config('plagiarism_edfast', 'debug_mode');
    }

    /**
     * Check if API is configured
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->api_key) && !empty($this->server_url);
    }

    /**
     * Submit a file for plagiarism analysis
     *
     * @param array $data Submission data
     * @return array|false
     */
    public function submit_for_analysis($data) {
        if (!$this->is_configured()) {
            $this->log_error('EdFast not configured');
            return false;
        }

        $url = $this->server_url . '/lms/submissions/analyze';

        $request_body = json_encode($data);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-Type: application/json',
                    'X-LMS-API-Key: ' . $this->api_key,
                    'User-Agent: EdFast-Moodle-Plugin/1.0',
                ),
                'content' => $request_body,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            )
        );

        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                $this->log_error('API connection failed: ' . $url);
                return false;
            }

            $result = json_decode($response, true);

            if ($this->debug) {
                $this->log_debug('API Response: ' . $response);
            }

            if (isset($result['id'])) {
                return array(
                    'submission_id' => $result['id'],
                    'item_id'       => $result['item_id'] ?? null,
                    'status'        => $result['processing_status'] ?? 'pending',
                );
            }

            $this->log_error('Invalid API response: ' . $response);
            return false;

        } catch (\Exception $e) {
            $this->log_error('API Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check submission status
     *
     * @param string $submission_id EdFast submission ID
     * @return array|false
     */
    public function get_submission_status($submission_id) {
        if (!$this->is_configured()) {
            return false;
        }

        $url = $this->server_url . '/lms/submissions/' . $submission_id;

        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => array(
                    'X-LMS-API-Key: ' . $this->api_key,
                    'User-Agent: EdFast-Moodle-Plugin/1.0',
                ),
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            )
        );

        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return false;
            }

            return json_decode($response, true);

        } catch (\Exception $e) {
            $this->log_error('Status check exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the current processing status and scores for an EdFast Item UUID.
     *
     * Used as a live-poll fallback when a webhook callback was missed.
     *
     * @param string $item_id EdFast Item UUID
     * @return array|false  Associative array with keys:
     *                       processing_status, similarity_score, ai_percentage,
     *                       essay_quality_score, word_count, detected_language.
     *                       Returns false on error or if not configured.
     */
    public function get_item_status($item_id) {
        if (!$this->is_configured()) {
            return false;
        }

        $url = $this->server_url . '/lms/items/' . rawurlencode($item_id) . '/status';

        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => array(
                    'X-LMS-API-Key: ' . $this->api_key,
                    'User-Agent: EdFast-Moodle-Plugin/1.0',
                ),
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            )
        );

        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return false;
            }

            $data = json_decode($response, true);
            if (!is_array($data)) {
                return false;
            }

            return $data;

        } catch (\Exception $e) {
            $this->log_error('Item status check exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log debug message
     *
     * @param string $message
     */
    private function log_debug($message) {
        if ($this->debug) {
            mtrace('[EdFast Debug] ' . $message);
        }
    }

    /**
     * Log error message
     *
     * @param string $message
     */
    private function log_error($message) {
        mtrace('[EdFast Error] ' . $message);
        
        // Also log to Moodle error log
        trigger_error('[plagiarism_edfast] ' . $message, E_USER_WARNING);
    }

    /**
     * Check API connectivity
     *
     * @return bool
     */
    public function health_check() {
        if (!$this->is_configured()) {
            return false;
        }

        $url = $this->server_url . '/utils/health-check';

        $options = array(
            'http' => array(
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
            )
        );

        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate a JWT token for seamless report access (v1.1.0)
     *
     * @param string $item_id EdFast Item UUID
     * @param int $expiration_minutes Token expiration time in minutes (default: 30)
     * @return string|false JWT token or false on error
     */
    public function generate_report_access_token(
            $item_id, $expiration_minutes = null, $requester_email = null, $requester_role = null) {
        global $CFG;

        // Use configured expiry, falling back to 30 minutes
        if ($expiration_minutes === null) {
            $expiration_minutes = (int)(get_config('plagiarism_edfast', 'report_token_expiry_minutes') ?: 30);
        }

        // Use webhook_secret (same as backend's jwt_secret)
        $jwt_secret = get_config('plagiarism_edfast', 'webhook_secret');
        $api_key_id = get_config('plagiarism_edfast', 'api_key_id');
        
        if (empty($jwt_secret)) {
            $this->log_error('JWT secret not configured');
            return false;
        }
        
        if (empty($api_key_id)) {
            $this->log_error('API Key ID not configured');
            return false;
        }
        
        if ($expiration_minutes < 1 || $expiration_minutes > 120) {
            $this->log_error('Token expiration must be between 1 and 120 minutes');
            return false;
        }
        
        try {
            $now = time();
            $expiration = $now + ($expiration_minutes * 60);
            
            // JWT header
            $header = array(
                'alg' => 'HS256',
                'typ' => 'JWT',
            );
            
            // JWT payload — embed requester identity when available so the backend
            // can issue a full session (email match) or viewer-only token (no match).
            $payload = array(
                'iat' => $now,
                'exp' => $expiration,
                'api_key_id' => $api_key_id,
                'item_id' => $item_id,
                'type' => 'lms_report',
                'lms_instance' => $CFG->wwwroot,
            );

            // Include requester identity when the email is available
            // (requires email sharing to be enabled in Moodle privacy settings).
            if (!empty($requester_email)) {
                $payload['requester_email'] = $requester_email;
            }
            if (!empty($requester_role)) {
                $payload['requester_role'] = $requester_role;
            }
            
            // Encode header and payload
            $header_encoded = $this->base64url_encode(json_encode($header));
            $payload_encoded = $this->base64url_encode(json_encode($payload));
            
            // Create signature
            $signature_input = $header_encoded . '.' . $payload_encoded;
            $signature = hash_hmac('sha256', $signature_input, $jwt_secret, true);
            $signature_encoded = $this->base64url_encode($signature);
            
            // Assemble JWT
            $jwt_token = $signature_input . '.' . $signature_encoded;
            
            if ($this->debug) {
                $this->log_debug('Generated JWT token for item ' . $item_id);
            }
            
            return $jwt_token;
            
        } catch (\Exception $e) {
            $this->log_error('JWT generation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Base64 URL-safe encoding (JWT standard)
     *
     * @param string $data Data to encode
     * @return string Base64url-encoded string
     */
    private function base64url_encode($data) {
        // Standard base64 encoding
        $b64 = base64_encode($data);
        
        // Convert to URL-safe alphabet (replace + with -, / with _, remove =)
        $b64 = str_replace(array('+', '/', '='), array('-', '_', ''), $b64);
        
        return $b64;
    }

    /**
     * Get report access link (v1.2.0 — LTI 1.3 aware)
     *
     * When lti_platform_id is configured, returns a direct report URL and relies
     * on the user's active LTI session for authentication (Phase 5 adapter).
     * Falls back to the legacy JWT viewer-token link during the bridging period.
     *
     * @param string $item_id EdFast Item UUID (from webhook.item_id)
     * @return string|false Report link or false on error
     */
    public function get_report_link($item_id, $requester_email = null, $requester_role = null) {
        if (!$this->is_configured()) {
            $this->log_error('EdFast not configured for report links');
            return false;
        }

        // Always use JWT-signed links from the plagiarism plugin.
        //
        // The token optionally carries the requesting user's email so the backend
        // can issue a full session JWT (case 1: email matches an EdFast account) or
        // fall back to an anonymous scoped viewer token (case 2: no match).

        // Get configured token expiration time (default 30 minutes)
        $expiration_minutes = (int)get_config('plagiarism_edfast', 'token_expiration_minutes') ?: 30;

        // Generate JWT token, embedding requester identity when available
        $token = $this->generate_report_access_token($item_id, $expiration_minutes, $requester_email, $requester_role);

        if ($token === false) {
            return false;
        }

        // Determine the EdFast frontend base URL.
        // Priority: explicit frontend_url setting > fallback derived from server_url host.
        $frontend_url = get_config('plagiarism_edfast', 'frontend_url');
        if (!empty($frontend_url)) {
            $base_url = rtrim($frontend_url, '/');
        } else {
            $parsed   = parse_url(rtrim($this->server_url, '/'));
            $base_url = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'edfast.ai');
        }

        $report_url = $base_url . '/lms/report/' . urlencode($item_id) . '?lms_token=' . urlencode($token);

        return $report_url;
    }

    /**
     * Check if seamless access is enabled
     *
     * @return bool
     */
    public function is_seamless_access_enabled() {
        return (bool)get_config('plagiarism_edfast', 'enable_seamless_access');
    }

    /**
     * Verify webhook signature using HMAC-SHA256
     *
     * @param string $payload Raw JSON payload from webhook
     * @param string $signature Signature from X-EDFAST-SIGNATURE header
     * @return bool True if signature is valid, false otherwise
     */
    public function verify_webhook_signature($payload, $signature) {
        // Get webhook_secret (same as backend's jwt_secret)
        $webhook_secret = get_config('plagiarism_edfast', 'webhook_secret');
        
        if (empty($webhook_secret)) {
            $this->log_error('Webhook secret not configured for signature verification');
            return false;
        }
        
        if (empty($signature)) {
            $this->log_error('No signature provided in webhook');
            return false;
        }
        
        // Backend signs the raw JSON body bytes it sends.
        // We verify on the raw payload string — no re-encoding needed (avoids float/ordering mismatches).
        $expected_signature = hash_hmac('sha256', $payload, $webhook_secret);
        
        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($expected_signature, $signature)) {
            $this->log_error('Webhook signature mismatch');
            return false;
        }
        
        if ($this->debug) {
            $this->log_debug('Webhook signature verified successfully');
        }
        
        return true;
    }
}
