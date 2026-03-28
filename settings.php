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

    set_config('enabled',              !empty($data->enabled) ? 1 : 0,  'plagiarism_edfast');
    set_config('api_key',              trim($data->api_key ?? ''),       'plagiarism_edfast');
    set_config('api_key_id',           trim($data->api_key_id ?? ''),    'plagiarism_edfast');
    set_config('server_url',           trim($data->server_url ?? ''),    'plagiarism_edfast');
    set_config('frontend_url',          trim($data->frontend_url ?? ''),   'plagiarism_edfast');
    set_config('webhook_callback_url',   trim($data->webhook_callback_url ?? ''), 'plagiarism_edfast');
    set_config('webhook_secret',       trim($data->webhook_secret ?? ''), 'plagiarism_edfast');
    set_config('lti_platform_id',      trim($data->lti_platform_id ?? ''), 'plagiarism_edfast');
    set_config('enable_seamless_access',  !empty($data->enable_seamless_access) ? 1 : 0, 'plagiarism_edfast');
    set_config('report_token_expiry_minutes', max(1, min(120, (int)($data->report_token_expiry_minutes ?? 30))), 'plagiarism_edfast');
    set_config('debug_mode',              !empty($data->debug_mode) ? 1 : 0, 'plagiarism_edfast');

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

$PAGE->set_title(get_string('pluginname', 'plagiarism_edfast'));
$PAGE->set_heading(get_string('pluginname', 'plagiarism_edfast'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'plagiarism_edfast'));

$sesskey = sesskey();
$actionurl = new moodle_url('/plagiarism/edfast/settings.php');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $actionurl->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => $sesskey]);

// ── Enable plugin ──────────────────────────────────────────────────────────
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('h3', get_string('plugin_heading', 'plagiarism_edfast'));
echo html_writer::start_tag('table', ['class' => 'admintable generaltable', 'style' => 'width:100%']);

$checked = $cfg->enabled ? ['checked' => 'checked'] : [];
echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('enabled', 'plagiarism_edfast'), ['for' => 'id_enabled']), ['style' => 'width:40%']) .
    html_writer::tag('td', html_writer::empty_tag('input', array_merge(['type' => 'checkbox', 'name' => 'enabled', 'id' => 'id_enabled', 'value' => '1'], $checked)))
);

// ── API Configuration ──────────────────────────────────────────────────────
echo html_writer::tag('tr', html_writer::tag('td', html_writer::tag('h3', get_string('api_heading', 'plagiarism_edfast')), ['colspan' => '2']));

echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_apikey', 'plagiarism_edfast'), ['for' => 'id_api_key'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'password', 'name' => 'api_key', 'id' => 'id_api_key', 'value' => s($cfg->api_key), 'size' => '60', 'class' => 'form-control']))
);

echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_apikey_id', 'plagiarism_edfast'), ['for' => 'id_api_key_id'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'text', 'name' => 'api_key_id', 'id' => 'id_api_key_id', 'value' => s($cfg->api_key_id), 'size' => '60', 'class' => 'form-control']))
);

echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_serverurl', 'plagiarism_edfast'), ['for' => 'id_server_url']) .
        html_writer::tag('p', get_string('setting_serverurl_help', 'plagiarism_edfast'), ['class' => 'text-muted small'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'url', 'name' => 'server_url', 'id' => 'id_server_url', 'value' => s($cfg->server_url), 'size' => '60', 'class' => 'form-control']))
);

echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_frontendurl', 'plagiarism_edfast'), ['for' => 'id_frontend_url']) .
        html_writer::tag('p', get_string('setting_frontendurl_help', 'plagiarism_edfast'), ['class' => 'text-muted small'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'url', 'name' => 'frontend_url', 'id' => 'id_frontend_url', 'value' => s($cfg->frontend_url), 'size' => '60', 'class' => 'form-control', 'placeholder' => 'https://edfast.ai']))
);

echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_webhooksecret', 'plagiarism_edfast'), ['for' => 'id_webhook_secret'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'text', 'name' => 'webhook_secret', 'id' => 'id_webhook_secret', 'value' => s($cfg->webhook_secret), 'size' => '60', 'class' => 'form-control']))
);

echo html_writer::tag('tr',
    html_writer::tag('td',
        html_writer::tag('label', get_string('setting_lti_platform_id', 'plagiarism_edfast'), ['for' => 'id_lti_platform_id']) .
        html_writer::tag('p', get_string('setting_lti_platform_id_help', 'plagiarism_edfast'), ['class' => 'text-muted small'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'text', 'name' => 'lti_platform_id', 'id' => 'id_lti_platform_id', 'value' => s($cfg->lti_platform_id), 'size' => '60', 'class' => 'form-control', 'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx']))
);

echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_webhook_callback_url', 'plagiarism_edfast'), ['for' => 'id_webhook_callback_url']) .
        html_writer::tag('p', get_string('setting_webhook_callback_url_help', 'plagiarism_edfast'), ['class' => 'text-muted small'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'url', 'name' => 'webhook_callback_url', 'id' => 'id_webhook_callback_url', 'value' => s($cfg->webhook_callback_url), 'size' => '60', 'class' => 'form-control', 'placeholder' => 'https://your-ngrok-url.ngrok-free.app/plagiarism/edfast/webhook.php']))
);

// ── Report Access Settings ─────────────────────────────────────────────────
echo html_writer::tag('tr', html_writer::tag('td', html_writer::tag('h3', get_string('setting_report_heading', 'plagiarism_edfast')), ['colspan' => '2']));

$chk4 = $cfg->enable_seamless_access ? ['checked' => 'checked'] : [];
echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_seamless_access', 'plagiarism_edfast'), ['for' => 'id_enable_seamless_access']) .
        html_writer::tag('p', get_string('setting_seamless_access_help', 'plagiarism_edfast'), ['class' => 'text-muted small'])) .
    html_writer::tag('td', html_writer::empty_tag('input', array_merge(['type' => 'checkbox', 'name' => 'enable_seamless_access', 'id' => 'id_enable_seamless_access', 'value' => '1'], $chk4)))
);

echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_report_expiry', 'plagiarism_edfast'), ['for' => 'id_report_token_expiry_minutes']) .
        html_writer::tag('p', get_string('setting_report_expiry_help', 'plagiarism_edfast'), ['class' => 'text-muted small'])) .
    html_writer::tag('td', html_writer::empty_tag('input', ['type' => 'number', 'name' => 'report_token_expiry_minutes', 'id' => 'id_report_token_expiry_minutes', 'value' => (int)$cfg->report_token_expiry_minutes, 'min' => '1', 'max' => '120', 'class' => 'form-control', 'style' => 'width:80px']))
);

// ── Debug ──────────────────────────────────────────────────────────────────
echo html_writer::tag('tr', html_writer::tag('td', html_writer::tag('h3', get_string('setting_developer_heading', 'plagiarism_edfast')), ['colspan' => '2']));

$chk3 = $cfg->debug_mode ? ['checked' => 'checked'] : [];
echo html_writer::tag('tr',
    html_writer::tag('td', html_writer::tag('label', get_string('setting_debug_mode', 'plagiarism_edfast'), ['for' => 'id_debug_mode'])) .
    html_writer::tag('td', html_writer::empty_tag('input', array_merge(['type' => 'checkbox', 'name' => 'debug_mode', 'id' => 'id_debug_mode', 'value' => '1'], $chk3)))
);

echo html_writer::end_tag('table');
echo html_writer::tag('p',
    html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary'])
);
echo $OUTPUT->box_end();
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
