<?php
/**
 * EdFast Moodle 4/5 Plagiarism Plugin - Language Strings (Simplified Chinese)
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// 插件名称与描述
$string['pluginname'] = 'EdFast 抄袭检测器';
$string['pluginname_desc'] = 'EdFast 为 Moodle 作业提供 AI 驱动的抄袭与 AI 生成内容检测，整合 EdFast 云端服务进行全面文件分析。';

// 插件启用／停用
$string['plugin_heading'] = 'EdFast 插件设置';
$string['plugin_heading_desc'] = '启用或停用 EdFast 抄袭检测插件';
$string['enabled'] = '启用 EdFast 插件';
$string['enabled_desc'] = '启用后，可在作业中使用 EdFast 抄袭检查功能';

// API 设置
$string['api_heading'] = 'EdFast API 设置';
$string['api_heading_desc'] = '设置与 EdFast 云端服务的连接';
$string['apikey'] = 'API 密钥';
$string['apikey_desc'] = '您所在机构的 EdFast API 密钥。请妥善保管！';
$string['apikey_id'] = 'API 密钥 ID';
$string['apikey_id_desc'] = 'API 密钥的 UUID（在 EdFast 生成后提供），访问报告时必须填写。';
$string['serverurl'] = 'EdFast 服务器 URL';
$string['serverurl_desc'] = 'EdFast API 的基础 URL（例如：https://api.edfast.ai/api/v1）';
$string['frontendurl'] = 'EdFast 前端 URL';
$string['frontendurl_desc'] = '用于报告链接的 EdFast 网页应用程序基础 URL（例如：https://edfast.ai），须与教师及学生访问 EdFast 报告的 URL 一致。';
$string['webhooksecret'] = 'Webhook 密钥';
$string['webhooksecret_desc'] = '用于验证 Webhook 的密钥（选填，可提高安全性）';

// 检测设置
$string['detection_heading'] = '检测设置';
$string['detection_heading_desc'] = '设置抄袭与 AI 检测参数';
$string['plagiarism_enabled'] = '启用抄袭检测';
$string['plagiarism_enabled_desc'] = '将学生提交内容与网络及其他学生作业进行比对';
$string['similarity_threshold'] = '相似度阈值（%）';
$string['similarity_threshold_desc'] = '标记相似度超过此百分比的提交内容（0–100）';
$string['ai_enabled'] = '启用 AI 检测';
$string['ai_enabled_desc'] = '检测提交内容是否含有 AI 生成的文字';
$string['ai_threshold'] = 'AI 检测阈值（%）';
$string['ai_threshold_desc'] = '标记 AI 比例超过此阈值的提交内容（0–100）';

// 高级设置
$string['advanced_heading'] = '高级设置';
$string['advanced_heading_desc'] = '供高级用户使用，请谨慎修改';
$string['cross_batch_analysis'] = '启用跨批次分析';
$string['cross_batch_analysis_desc'] = '跨所有批次／作业比对提交内容，以达到最全面的抄袭检测';
$string['max_file_size'] = '最大文件大小（MB）';
$string['max_file_size_desc'] = '可接受分析的最大文件大小（默认：20MB）';
$string['webhook_timeout'] = 'Webhook 超时（秒）';
$string['webhook_timeout_desc'] = '等待 Webhook 回调的最长时间（默认：30 秒）';
$string['debug_mode'] = '调试模式';
$string['debug_mode_desc'] = '启用额外日志以协助排查问题（正式环境请停用）';

// LTI 1.3 集成
$string['lti_platform_id'] = 'LTI 1.3 平台 ID';
$string['lti_platform_id_desc'] = '选填。设置后，报告链接将使用 LTI 1.3 会话而非旧版 JWT 查看 Token。请粘贴 EdFast 机构设置 → LTI 平台中的平台 UUID。若留空则继续使用旧版 JWT 报告链接。';

// 报告显示
$string['similarity'] = '相似度';
$string['ai_percentage'] = 'AI 比例';
$string['essay_score'] = '文章评分';
$string['word_count'] = '字数';
$string['detected_language'] = '语言';
$string['readability'] = '可读性';
$string['not_analyzed'] = '尚未分析';
$string['analyzing'] = '分析中…';
$string['analysis_failed'] = '分析失败';
$string['resubmit'] = '重新提交分析';

// 状态消息
$string['pending'] = '等待分析';
$string['processing'] = '处理中…';
$string['completed'] = '分析完成';
$string['error'] = '分析错误';

// 权限
$string['edfast:viewreport'] = '查看 EdFast 抄袭报告';
$string['edfast:checkfile'] = '提交文件进行抄袭检查';

// 错误消息
$string['error_api_key_missing'] = 'EdFast API 密钥尚未设置，请联系管理员。';
$string['error_api_connection'] = '无法连接至 EdFast 服务，请稍后再试。';
$string['error_file_too_large'] = '文件超过最大大小限制（{$a}MB）';
$string['error_unsupported_file'] = '不支持此文件类型进行分析';
$string['error_webhook_failed'] = '无法处理 Webhook 响应';

// 成功消息
$string['submission_received'] = '提交内容已收到，正在进行分析';
$string['analysis_started'] = '分析已启动，结果将于稍后显示';

// 无缝访问（基于 JWT 的深度链接）
$string['seamless_access_heading'] = '无缝访问设置';
$string['seamless_access_heading_desc'] = '设置基于 JWT 的验证，让用户无需重新登录即可直接访问报告';
$string['enable_seamless_access'] = '启用无缝访问';
$string['enable_seamless_access_desc'] = '允许已登录用户从 Moodle 直接查看 EdFast 报告，无需额外验证';
$string['jwt_secret'] = 'JWT 密钥';
$string['jwt_secret_desc'] = '用于签署 JWT Token 的密钥。请生成一组强度高的随机密钥，妥善保存且切勿分享！';
$string['token_expiration_minutes'] = 'Token 有效期限（分钟）';
$string['token_expiration_minutes_desc'] = 'Token 的有效时间（5–120 分钟，默认：30 分钟）';
$string['view_full_report'] = '查看完整报告';
$string['auto_login_note'] = '您已自动登录';
