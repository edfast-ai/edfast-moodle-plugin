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
 * EdFast Moodle 4/5 Plagiarism Plugin - Language Strings (English)
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin name and description
$string['pluginname'] = 'EdFast Plagiarism Detector';
$string['pluginname_desc'] = 'EdFast provides AI-powered plagiarism and AI detection for Moodle assignments. Integrates with EdFast cloud service for comprehensive document analysis.';

// Plugin enable/disable
$string['plugin_heading'] = 'EdFast Plugin Configuration';
$string['plugin_heading_desc'] = 'Enable or disable the EdFast plagiarism detection plugin';
$string['enabled'] = 'Enable EdFast Plugin';
$string['enabled_desc'] = 'When enabled, EdFast plagiarism checking can be used in assignments';

// API Configuration
$string['api_heading'] = 'EdFast API Configuration';
$string['api_heading_desc'] = 'Configure connection to EdFast cloud service';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Your EdFast API key for this institution. Keep this secure!';
$string['apikey_id'] = 'API Key ID';
$string['apikey_id_desc'] = 'The UUID of your API key (provided when generated in EdFast). Required for report access.';
$string['serverurl'] = 'EdFast Server URL';
$string['serverurl_desc'] = 'Base URL for EdFast API (e.g., https://api.edfast.ai/api/v1)';
$string['frontendurl'] = 'EdFast Frontend URL';
$string['frontendurl_desc'] = 'Base URL of the EdFast web application used for report links (e.g., https://edfast.ai). Must match the URL where teachers and students access EdFast reports.';
$string['webhooksecret'] = 'Webhook Secret';
$string['webhooksecret_desc'] = 'Secret token for webhook verification (optional, for extra security)';

// Detection Settings
$string['detection_heading'] = 'Detection Settings';
$string['detection_heading_desc'] = 'Configure plagiarism and AI detection parameters';
$string['plagiarism_enabled'] = 'Enable Plagiarism Detection';
$string['plagiarism_enabled_desc'] = 'Check student submissions against internet and other student work';
$string['similarity_threshold'] = 'Similarity Threshold (%)';
$string['similarity_threshold_desc'] = 'Flag submissions with similarity above this percentage (0-100)';
$string['ai_enabled'] = 'Enable AI Detection';
$string['ai_enabled_desc'] = 'Detect if submission contains AI-generated content';
$string['ai_threshold'] = 'AI Detection Threshold (%)';
$string['ai_threshold_desc'] = 'Flag submissions with AI percentage above this threshold (0-100)';

// Advanced Settings
$string['advanced_heading'] = 'Advanced Settings';
$string['advanced_heading_desc'] = 'For advanced users - modify with caution';
$string['cross_batch_analysis'] = 'Enable Cross-Batch Analysis';
$string['cross_batch_analysis_desc'] = 'Compare submissions across all batches/assignments for maximum plagiarism detection';
$string['max_file_size'] = 'Maximum File Size (MB)';
$string['max_file_size_desc'] = 'Largest file size accepted for analysis (default: 20MB)';
$string['webhook_timeout'] = 'Webhook Timeout (seconds)';
$string['webhook_timeout_desc'] = 'Maximum time to wait for webhook callback (default: 30)';
$string['debug_mode'] = 'Debug Mode';
$string['debug_mode_desc'] = 'Enable extra logging for troubleshooting (disable in production)';

// LTI 1.3 Integration
$string['lti_platform_id'] = 'LTI 1.3 Platform ID';
$string['lti_platform_id_desc'] = 'Optional. When set, report links use your LTI 1.3 session instead of the legacy JWT viewer-token. Paste the Platform UUID from EdFast Institution Settings → LTI Platforms. Leave blank to keep using the legacy JWT report link (bridging period).';

// Report display
$string['similarity'] = 'Similarity';
$string['ai_percentage'] = 'AI %';
$string['essay_score'] = 'Essay Score';
$string['word_count'] = 'Word Count';
$string['detected_language'] = 'Language';
$string['readability'] = 'Readability';
$string['not_analyzed'] = 'Not analyzed yet';
$string['analyzing'] = 'Analysis in progress...';
$string['analysis_failed'] = 'Analysis failed';
$string['resubmit'] = 'Resubmit for analysis';

// Status messages
$string['pending'] = 'Pending analysis';
$string['processing'] = 'Processing...';
$string['completed'] = 'Analysis complete';
$string['error'] = 'Analysis error';

// Capabilities
$string['edfast:viewreport'] = 'View EdFast plagiarism reports';
$string['edfast:checkfile'] = 'Submit files for plagiarism checking';
$string['edfast:manage'] = 'Manage EdFast plagiarism plugin settings';

// Events
$string['event_analysis_complete'] = 'EdFast analysis complete';

// Settings page labels
$string['setting_apikey'] = 'API Key';
$string['setting_apikey_id'] = 'API Key ID (UUID)';
$string['setting_serverurl'] = 'Server URL';
$string['setting_serverurl_help'] = 'The EdFast backend API base URL, e.g. https://api.edfast.ai/api/v1';
$string['setting_frontendurl'] = 'Frontend URL';
$string['setting_frontendurl_help'] = 'The EdFast web application URL used for report links, e.g. https://edfast.ai';
$string['setting_webhooksecret'] = 'Webhook Secret';
$string['setting_lti_platform_id'] = 'LTI 1.3 Platform ID (optional)';
$string['setting_lti_platform_id_help'] = 'When set, report links use your LTI 1.3 session instead of the legacy JWT viewer-token. Paste the Platform UUID from EdFast → Institution Settings → LTI Platforms.';
$string['setting_webhook_callback_url'] = 'Webhook Callback URL (optional)';
$string['setting_webhook_callback_url_help'] = 'Override the webhook callback URL that EdFast uses to deliver results back to Moodle. Required for local/testing environments behind a tunnel (e.g. ngrok). Leave blank to use the default Moodle site URL.';
$string['setting_report_heading'] = 'Report Access Settings';
$string['setting_seamless_access'] = 'Enable Seamless Report Access (SSO)';
$string['setting_seamless_access_help'] = 'When enabled, report links auto-log users into EdFast using the Webhook Secret as signing key.';
$string['setting_report_expiry'] = 'Report Link Expiry (minutes)';
$string['setting_report_expiry_help'] = 'How long a report link stays valid after it is opened (1-120 minutes). Default: 30.';
$string['setting_developer_heading'] = 'Developer Settings';
$string['setting_debug_mode'] = 'Debug Mode';

// Webhook errors
$string['webhook_invalid_json'] = 'Invalid JSON payload';
$string['webhook_invalid_signature'] = 'Invalid signature';
$string['webhook_submission_not_found'] = 'Submission not found';
$string['webhook_success'] = 'Webhook processed successfully';

// Health check
$string['healthcheck_not_configured'] = 'EdFast not configured - API key or server URL missing';
$string['healthcheck_reachable'] = 'EdFast service is reachable';
$string['healthcheck_unreachable'] = 'EdFast service is not reachable - check API URL and network connectivity';

// Privacy API
$string['privacy:metadata:plagiarism_edfast_submissions'] = 'Information about user submissions sent to EdFast for plagiarism analysis.';
$string['privacy:metadata:plagiarism_edfast_submissions:moodle_file_id'] = 'The Moodle file ID of the submitted document.';
$string['privacy:metadata:plagiarism_edfast_submissions:moodle_submission_id'] = 'The Moodle assignment submission ID.';
$string['privacy:metadata:plagiarism_edfast_submissions:edfast_submission_id'] = 'The unique submission ID assigned by EdFast.';
$string['privacy:metadata:plagiarism_edfast_submissions:status'] = 'The analysis processing status.';
$string['privacy:metadata:plagiarism_edfast_submissions:similarity_score'] = 'The plagiarism similarity percentage.';
$string['privacy:metadata:plagiarism_edfast_submissions:ai_percentage'] = 'The AI-generated content percentage.';
$string['privacy:metadata:plagiarism_edfast_submissions:timecreated'] = 'When the submission was sent for analysis.';
$string['privacy:metadata:plagiarism_edfast_submissions:timemodified'] = 'When the analysis result was last updated.';
$string['privacy:metadata:edfast_server'] = 'The EdFast cloud service receives document content for plagiarism and AI analysis.';
$string['privacy:metadata:edfast_server:file_content'] = 'The content of the submitted document.';
$string['privacy:metadata:edfast_server:file_name'] = 'The filename of the submitted document.';
$string['privacy:metadata:edfast_server:moodle_user_email'] = 'The email address of the submitting user.';
$string['privacy:metadata:edfast_server:moodle_user_name'] = 'The full name of the submitting user.';

// Errors
$string['error_api_key_missing'] = 'EdFast API key not configured. Contact your administrator.';
$string['error_api_connection'] = 'Failed to connect to EdFast service. Please try again later.';
$string['error_file_too_large'] = 'File exceeds maximum size limit ({$a}MB)';
$string['error_unsupported_file'] = 'File type not supported for analysis';
$string['error_webhook_failed'] = 'Failed to process webhook response';

// Success messages
$string['submission_received'] = 'Submission received for analysis';
$string['analysis_started'] = 'Analysis started - results will be displayed shortly';

// Seamless Access (JWT-based deep linking)
$string['seamless_access_heading'] = 'Seamless Access Settings';
$string['seamless_access_heading_desc'] = 'Configure JWT-based authentication for direct report access without re-login';
$string['enable_seamless_access'] = 'Enable Seamless Access';
$string['enable_seamless_access_desc'] = 'Allow logged-in users to view EdFast reports directly from Moodle without additional authentication';
$string['jwt_secret'] = 'JWT Secret Key';
$string['jwt_secret_desc'] = 'Secret key for signing JWT tokens. Generate a strong random key. Store securely and never share!';
$string['token_expiration_minutes'] = 'Token Expiration Time (minutes)';
$string['token_expiration_minutes_desc'] = 'How long tokens remain valid (5-120 minutes, default: 30)';
$string['view_full_report'] = 'View Full Report';
$string['auto_login_note'] = 'You are automatically logged in';

