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
 * EdFast Moodle 5.x Plagiarism Plugin - Standalone Settings Page
 *
 * In Moodle 5, plagiarism plugins are registered as admin_externalpage,
 * so settings.php must be a full standalone page (not a $settings->add() file).
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/plagiarism/edfast/settings.php'));
$PAGE->set_title(get_string('pluginname', 'plagiarism_edfast'));
$PAGE->set_heading(get_string('pluginname', 'plagiarism_edfast'));
$PAGE->set_pagelayout('admin');

// Handle form submission.
if ($data = data_submitted()) {
    require_sesskey();

    set_config('enabled', !empty($data->enabled) ? 1 : 0, 'plagiarism_edfast');
    set_config('api_key', trim($data->api_key ?? ''), 'plagiarism_edfast');
    set_config('api_key_id', trim($data->api_key_id ?? ''), 'plagiarism_edfast');
    set_config('server_url', trim($data->server_url ?? ''), 'plagiarism_edfast');
    set_config('frontend_url', trim($data->frontend_url ?? ''), 'plagiarism_edfast');
    set_config('webhook_callback_url', trim($data->webhook_callback_url ?? ''), 'plagiarism_edfast');
    set_config('webhook_secret', trim($data->webhook_secret ?? ''), 'plagiarism_edfast');
    set_config('lti_platform_id', trim($data->lti_platform_id ?? ''), 'plagiarism_edfast');
    set_config('enable_seamless_access', !empty($data->enable_seamless_access) ? 1 : 0, 'plagiarism_edfast');
    $expiry = max(1, min(120, (int)($data->report_token_expiry_minutes ?? 30)));
    set_config('report_token_expiry_minutes', $expiry, 'plagiarism_edfast');
    set_config('debug_mode', !empty($data->debug_mode) ? 1 : 0, 'plagiarism_edfast');

    \core\notification::success(get_string('changessaved'));
    redirect(new moodle_url('/plagiarism/edfast/settings.php'));
}

// Current config values.
$cfg = (object)[
    'enabled'              => get_config('plagiarism_edfast', 'enabled'),
    'api_key'              => get_config('plagiarism_edfast', 'api_key'),
    'api_key_id'           => get_config('plagiarism_edfast', 'api_key_id'),
    'server_url'           => get_config('plagiarism_edfast', 'server_url') ?: 'https://api.edfast.ai/api/v1',
    'frontend_url'         => get_config('plagiarism_edfast', 'frontend_url') ?: 'https://edfast.ai',
    'webhook_callback_url' => get_config('plagiarism_edfast', 'webhook_callback_url') ?: '',
    'webhook_secret'       => get_config('plagiarism_edfast', 'webhook_secret'),
    'enable_seamless_access'        => get_config('plagiarism_edfast', 'enable_seamless_access'),
    'report_token_expiry_minutes'   => get_config('plagiarism_edfast', 'report_token_expiry_minutes') ?: 30,
    'lti_platform_id'               => get_config('plagiarism_edfast', 'lti_platform_id') ?: '',
    'debug_mode'                    => get_config('plagiarism_edfast', 'debug_mode'),
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'plagiarism_edfast'));

$formaction = (new moodle_url('/plagiarism/edfast/settings.php'))->out(false);
echo '<form method="post" action="' . $formaction . '">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';

echo $OUTPUT->box_start('generalbox');
echo '<h3>' . get_string('plugin_heading', 'plagiarism_edfast') . '</h3>';
echo '<table class="admintable generaltable" style="width:100%">';

// Enable plugin.
$ck = $cfg->enabled ? ' checked="checked"' : '';
echo '<tr>';
echo '<td style="width:40%"><label for="id_enabled">';
echo get_string('enabled', 'plagiarism_edfast');
echo '</label></td>';
echo '<td><input type="checkbox" name="enabled" id="id_enabled" value="1"' . $ck . '></td>';
echo '</tr>';

// API configuration.
echo '<tr><td colspan="2"><h3>' . get_string('api_heading', 'plagiarism_edfast') . '</h3></td></tr>';

echo '<tr><td>';
echo '<label for="id_api_key">' . get_string('setting_apikey', 'plagiarism_edfast') . '</label>';
echo '</td><td>';
echo '<input type="password" name="api_key" id="id_api_key"';
echo ' value="' . s($cfg->api_key) . '" size="60" class="form-control">';
echo '</td></tr>';

echo '<tr><td>';
echo '<label for="id_api_key_id">' . get_string('setting_apikey_id', 'plagiarism_edfast') . '</label>';
echo '</td><td>';
echo '<input type="text" name="api_key_id" id="id_api_key_id"';
echo ' value="' . s($cfg->api_key_id) . '" size="60" class="form-control">';
echo '</td></tr>';

echo '<tr><td>';
echo '<label for="id_server_url">' . get_string('setting_serverurl', 'plagiarism_edfast') . '</label>';
echo '<p class="text-muted small">' . get_string('setting_serverurl_help', 'plagiarism_edfast') . '</p>';
echo '</td><td>';
echo '<input type="url" name="server_url" id="id_server_url"';
echo ' value="' . s($cfg->server_url) . '" size="60" class="form-control">';
echo '</td></tr>';

echo '<tr><td>';
echo '<label for="id_frontend_url">' . get_string('setting_frontendurl', 'plagiarism_edfast') . '</label>';
echo '<p class="text-muted small">' . get_string('setting_frontendurl_help', 'plagiarism_edfast') . '</p>';
echo '</td><td>';
echo '<input type="url" name="frontend_url" id="id_frontend_url"';
echo ' value="' . s($cfg->frontend_url) . '" size="60" class="form-control"';
echo ' placeholder="https://edfast.ai">';
echo '</td></tr>';

echo '<tr><td>';
echo '<label for="id_webhook_secret">' . get_string('setting_webhooksecret', 'plagiarism_edfast') . '</label>';
echo '</td><td>';
echo '<input type="text" name="webhook_secret" id="id_webhook_secret"';
echo ' value="' . s($cfg->webhook_secret) . '" size="60" class="form-control">';
echo '</td></tr>';

echo '<tr><td>';
echo '<label for="id_lti_platform_id">' . get_string('setting_lti_platform_id', 'plagiarism_edfast') . '</label>';
echo '<p class="text-muted small">' . get_string('setting_lti_platform_id_help', 'plagiarism_edfast') . '</p>';
echo '</td><td>';
echo '<input type="text" name="lti_platform_id" id="id_lti_platform_id"';
echo ' value="' . s($cfg->lti_platform_id) . '" size="60" class="form-control"';
echo ' placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">';
echo '</td></tr>';

echo '<tr><td>';
echo '<label for="id_webhook_callback_url">';
echo get_string('setting_webhook_callback_url', 'plagiarism_edfast');
echo '</label>';
echo '<p class="text-muted small">' . get_string('setting_webhook_callback_url_help', 'plagiarism_edfast') . '</p>';
echo '</td><td>';
echo '<input type="url" name="webhook_callback_url" id="id_webhook_callback_url"';
echo ' value="' . s($cfg->webhook_callback_url) . '" size="60" class="form-control"';
echo ' placeholder="https://your-ngrok-url.ngrok-free.app/plagiarism/edfast/webhook.php">';
echo '</td></tr>';

// Report access settings.
echo '<tr><td colspan="2"><h3>' . get_string('setting_report_heading', 'plagiarism_edfast') . '</h3></td></tr>';

$ck4 = $cfg->enable_seamless_access ? ' checked="checked"' : '';
echo '<tr><td>';
echo '<label for="id_enable_seamless_access">';
echo get_string('setting_seamless_access', 'plagiarism_edfast');
echo '</label>';
echo '<p class="text-muted small">' . get_string('setting_seamless_access_help', 'plagiarism_edfast') . '</p>';
echo '</td><td>';
echo '<input type="checkbox" name="enable_seamless_access"';
echo ' id="id_enable_seamless_access" value="1"' . $ck4 . '>';
echo '</td></tr>';

echo '<tr><td>';
echo '<label for="id_report_token_expiry_minutes">';
echo get_string('setting_report_expiry', 'plagiarism_edfast');
echo '</label>';
echo '<p class="text-muted small">' . get_string('setting_report_expiry_help', 'plagiarism_edfast') . '</p>';
echo '</td><td>';
echo '<input type="number" name="report_token_expiry_minutes"';
echo ' id="id_report_token_expiry_minutes"';
echo ' value="' . (int)$cfg->report_token_expiry_minutes . '" min="1" max="120"';
echo ' class="form-control" style="width:80px">';
echo '</td></tr>';

// Developer settings.
echo '<tr><td colspan="2"><h3>';
echo get_string('setting_developer_heading', 'plagiarism_edfast');
echo '</h3></td></tr>';

$ck3 = $cfg->debug_mode ? ' checked="checked"' : '';
echo '<tr><td>';
echo '<label for="id_debug_mode">' . get_string('setting_debug_mode', 'plagiarism_edfast') . '</label>';
echo '</td><td>';
echo '<input type="checkbox" name="debug_mode" id="id_debug_mode" value="1"' . $ck3 . '>';
echo '</td></tr>';

echo '</table>';
echo '<p><input type="submit" value="' . get_string('savechanges') . '" class="btn btn-primary"></p>';
echo $OUTPUT->box_end();
echo '</form>';

echo $OUTPUT->footer();
