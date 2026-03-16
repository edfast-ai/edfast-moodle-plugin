<?php
/**
 * EdFast Moodle 4.5+ Plagiarism Plugin - Health Check Endpoint
 *
 * Allows Moodle admin to verify connection to EdFast service.
 * Requires admin login and plagiarism/edfast:manage capability.
 *
 * Endpoint: {moodle-url}/plagiarism/edfast/health_check.php
 * Method: GET
 * Authentication: Moodle user session + capability check
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_login();
require_capability('plagiarism/edfast:manage', context_system::instance());

header('Content-Type: application/json');

try {
    $api_client = new \plagiarism_edfast\lms_api_client();

    if (!$api_client->is_configured()) {
        http_response_code(400);
        echo json_encode(array(
            'status' => 'error',
            'message' => 'EdFast not configured - API key or server URL missing'
        ));
        exit;
    }

    $is_healthy = $api_client->health_check();

    if ($is_healthy) {
        http_response_code(200);
        echo json_encode(array(
            'status' => 'ok',
            'message' => 'EdFast service is reachable',
            'timestamp' => time()
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            'status' => 'error',
            'message' => 'EdFast service is not reachable - check API URL and network connectivity',
            'timestamp' => time()
        ));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'status' => 'error',
        'message' => $e->getMessage()
    ));
}

exit;
