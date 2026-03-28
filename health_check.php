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
            'message' => get_string('healthcheck_not_configured', 'plagiarism_edfast')
        ));
        exit;
    }

    $is_healthy = $api_client->health_check();

    if ($is_healthy) {
        http_response_code(200);
        echo json_encode(array(
            'status' => 'ok',
            'message' => get_string('healthcheck_reachable', 'plagiarism_edfast'),
            'timestamp' => time()
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            'status' => 'error',
            'message' => get_string('healthcheck_unreachable', 'plagiarism_edfast'),
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
