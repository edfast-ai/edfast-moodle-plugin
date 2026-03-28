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
 * EdFast Moodle 4/5 Plagiarism Plugin - Language Strings (Traditional Chinese)
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// 外掛名稱與描述
$string['pluginname'] = 'EdFast 抄襲偵測器';
$string['pluginname_desc'] = 'EdFast 為 Moodle 作業提供 AI 驅動的抄襲與 AI 生成內容偵測，整合 EdFast 雲端服務進行全面文件分析。';

// 外掛啟用／停用
$string['plugin_heading'] = 'EdFast 外掛設定';
$string['plugin_heading_desc'] = '啟用或停用 EdFast 抄襲偵測外掛';
$string['enabled'] = '啟用 EdFast 外掛';
$string['enabled_desc'] = '啟用後，可在作業中使用 EdFast 抄襲檢查功能';

// API 設定
$string['api_heading'] = 'EdFast API 設定';
$string['api_heading_desc'] = '設定與 EdFast 雲端服務的連線';
$string['apikey'] = 'API 金鑰';
$string['apikey_desc'] = '您所在機構的 EdFast API 金鑰。請妥善保管！';
$string['apikey_id'] = 'API 金鑰 ID';
$string['apikey_id_desc'] = 'API 金鑰的 UUID（在 EdFast 產生後提供），存取報告時必須填寫。';
$string['serverurl'] = 'EdFast 伺服器 URL';
$string['serverurl_desc'] = 'EdFast API 的基礎 URL（例如：https://api.edfast.ai/api/v1）';
$string['frontendurl'] = 'EdFast 前端 URL';
$string['frontendurl_desc'] = '用於報告連結的 EdFast 網頁應用程式基礎 URL（例如：https://edfast.ai），須與教師及學生存取 EdFast 報告的 URL 一致。';
$string['webhooksecret'] = 'Webhook 密鑰';
$string['webhooksecret_desc'] = '用於驗證 Webhook 的密鑰（選填，可提高安全性）';

// 偵測設定
$string['detection_heading'] = '偵測設定';
$string['detection_heading_desc'] = '設定抄襲與 AI 偵測參數';
$string['plagiarism_enabled'] = '啟用抄襲偵測';
$string['plagiarism_enabled_desc'] = '將學生繳交內容與網路及其他學生作業進行比對';
$string['similarity_threshold'] = '相似度門檻值（%）';
$string['similarity_threshold_desc'] = '標記相似度超過此百分比的繳交內容（0–100）';
$string['ai_enabled'] = '啟用 AI 偵測';
$string['ai_enabled_desc'] = '偵測繳交內容是否含有 AI 生成的文字';
$string['ai_threshold'] = 'AI 偵測門檻值（%）';
$string['ai_threshold_desc'] = '標記 AI 比例超過此門檻的繳交內容（0–100）';

// 進階設定
$string['advanced_heading'] = '進階設定';
$string['advanced_heading_desc'] = '供進階使用者使用，請謹慎修改';
$string['cross_batch_analysis'] = '啟用跨批次分析';
$string['cross_batch_analysis_desc'] = '跨所有批次／作業比對繳交內容，以達到最全面的抄襲偵測';
$string['max_file_size'] = '最大檔案大小（MB）';
$string['max_file_size_desc'] = '可接受分析的最大檔案大小（預設：20MB）';
$string['webhook_timeout'] = 'Webhook 逾時（秒）';
$string['webhook_timeout_desc'] = '等待 Webhook 回呼的最長時間（預設：30 秒）';
$string['debug_mode'] = '偵錯模式';
$string['debug_mode_desc'] = '啟用額外記錄以協助排查問題（正式環境請停用）';

// LTI 1.3 整合
$string['lti_platform_id'] = 'LTI 1.3 平台 ID';
$string['lti_platform_id_desc'] = '選填。設定後，報告連結將使用 LTI 1.3 工作階段而非舊版 JWT 檢視 Token。請貼上 EdFast 機構設定 → LTI 平台中的平台 UUID。若留空則繼續使用舊版 JWT 報告連結。';

// 報告顯示
$string['similarity'] = '相似度';
$string['ai_percentage'] = 'AI 比例';
$string['essay_score'] = '文章評分';
$string['word_count'] = '字數';
$string['detected_language'] = '語言';
$string['readability'] = '可讀性';
$string['not_analyzed'] = '尚未分析';
$string['analyzing'] = '分析中…';
$string['analysis_failed'] = '分析失敗';
$string['resubmit'] = '重新提交分析';

// 狀態訊息
$string['pending'] = '等待分析';
$string['processing'] = '處理中…';
$string['completed'] = '分析完成';
$string['error'] = '分析錯誤';

// 權限
$string['edfast:viewreport'] = '檢視 EdFast 抄襲報告';
$string['edfast:checkfile'] = '提交檔案進行抄襲檢查';
$string['edfast:manage'] = '管理 EdFast 抄襲偵測外掛設定';

// 事件
$string['event_analysis_complete'] = 'EdFast 分析完成';

// 設定頁面標籤
$string['setting_apikey'] = 'API 金鑰';
$string['setting_apikey_id'] = 'API 金鑰 ID（UUID）';
$string['setting_serverurl'] = '伺服器 URL';
$string['setting_serverurl_help'] = 'EdFast 後端 API 基礎 URL，例如 https://api.edfast.ai/api/v1';
$string['setting_frontendurl'] = '前端 URL';
$string['setting_frontendurl_help'] = '用於報告連結的 EdFast 網頁應用程式 URL，例如 https://edfast.ai';
$string['setting_webhooksecret'] = 'Webhook 密鑰';
$string['setting_lti_platform_id'] = 'LTI 1.3 平台 ID（選填）';
$string['setting_lti_platform_id_help'] = '設定後，報告連結將使用 LTI 1.3 工作階段而非舊版 JWT 檢視 Token。請貼上 EdFast 機構設定 → LTI 平台中的平台 UUID。';
$string['setting_webhook_callback_url'] = 'Webhook 回呼 URL（選填）';
$string['setting_webhook_callback_url_help'] = '覆蓋 EdFast 用於將結果傳回 Moodle 的 Webhook 回呼 URL。本地/測試環境（如 ngrok）需要設定。留空則使用預設 Moodle 站點 URL。';
$string['setting_report_heading'] = '報告存取設定';
$string['setting_seamless_access'] = '啟用無縫報告存取（SSO）';
$string['setting_seamless_access_help'] = '啟用後，報告連結將使用 Webhook 密鑰自動登入 EdFast。';
$string['setting_report_expiry'] = '報告連結有效期（分鐘）';
$string['setting_report_expiry_help'] = '報告連結開啟後的有效時間（1–120 分鐘），預設 30 分鐘。';
$string['setting_developer_heading'] = '開發者設定';
$string['setting_debug_mode'] = '偵錯模式';

// Webhook 錯誤
$string['webhook_invalid_json'] = '無效的 JSON 資料';
$string['webhook_invalid_signature'] = '無效的簽章';
$string['webhook_submission_not_found'] = '未找到繳交紀錄';
$string['webhook_success'] = 'Webhook 處理成功';

// 健康檢查
$string['healthcheck_not_configured'] = 'EdFast 未設定 - 缺少 API 金鑰或伺服器 URL';
$string['healthcheck_reachable'] = 'EdFast 服務可存取';
$string['healthcheck_unreachable'] = 'EdFast 服務無法存取 - 請檢查 API URL 和網路連線';

// 隱私 API
$string['privacy:metadata:plagiarism_edfast_submissions'] = '傳送至 EdFast 進行抄襲分析的使用者繳交資訊。';
$string['privacy:metadata:plagiarism_edfast_submissions:moodle_file_id'] = '繳交檔案的 Moodle 檔案 ID。';
$string['privacy:metadata:plagiarism_edfast_submissions:moodle_submission_id'] = 'Moodle 作業繳交 ID。';
$string['privacy:metadata:plagiarism_edfast_submissions:edfast_submission_id'] = 'EdFast 分配的唯一繳交 ID。';
$string['privacy:metadata:plagiarism_edfast_submissions:status'] = '分析處理狀態。';
$string['privacy:metadata:plagiarism_edfast_submissions:similarity_score'] = '抄襲相似度百分比。';
$string['privacy:metadata:plagiarism_edfast_submissions:ai_percentage'] = 'AI 生成內容百分比。';
$string['privacy:metadata:plagiarism_edfast_submissions:timecreated'] = '繳交分析的時間。';
$string['privacy:metadata:plagiarism_edfast_submissions:timemodified'] = '分析結果最後更新時間。';
$string['privacy:metadata:edfast_server'] = 'EdFast 雲端服務接收檔案內容進行抄襲和 AI 分析。';
$string['privacy:metadata:edfast_server:file_content'] = '繳交檔案的內容。';
$string['privacy:metadata:edfast_server:file_name'] = '繳交檔案的檔案名稱。';
$string['privacy:metadata:edfast_server:moodle_user_email'] = '繳交使用者的電子郵件地址。';
$string['privacy:metadata:edfast_server:moodle_user_name'] = '繳交使用者的全名。';

// 錯誤訊息
$string['error_api_key_missing'] = 'EdFast API 金鑰尚未設定，請聯繫管理員。';
$string['error_api_connection'] = '無法連線至 EdFast 服務，請稍後再試。';
$string['error_file_too_large'] = '檔案超過最大大小限制（{$a}MB）';
$string['error_unsupported_file'] = '不支援此檔案類型進行分析';
$string['error_webhook_failed'] = '無法處理 Webhook 回應';

// 成功訊息
$string['submission_received'] = '繳交內容已收到，正在進行分析';
$string['analysis_started'] = '分析已啟動，結果將於稍後顯示';

// 無縫存取（基於 JWT 的深度連結）
$string['seamless_access_heading'] = '無縫存取設定';
$string['seamless_access_heading_desc'] = '設定基於 JWT 的驗證，讓使用者無需重新登入即可直接存取報告';
$string['enable_seamless_access'] = '啟用無縫存取';
$string['enable_seamless_access_desc'] = '允許已登入使用者從 Moodle 直接檢視 EdFast 報告，無需額外驗證';
$string['jwt_secret'] = 'JWT 密鑰';
$string['jwt_secret_desc'] = '用於簽署 JWT Token 的密鑰。請產生一組強度高的隨機金鑰，妥善保存且切勿分享！';
$string['token_expiration_minutes'] = 'Token 有效期限（分鐘）';
$string['token_expiration_minutes_desc'] = 'Token 的有效時間（5–120 分鐘，預設：30 分鐘）';
$string['view_full_report'] = '查看完整報告';
$string['auto_login_note'] = '您已自動登入';
