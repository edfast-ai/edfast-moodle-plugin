<?php
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

