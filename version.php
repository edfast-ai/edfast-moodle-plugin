<?php
/**
 * EdFast Moodle 4.0+ Plagiarism Plugin - Version & Metadata
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'plagiarism_edfast';
$plugin->version = 2026031509;                   // 2026-03-15.09 — Fix Undefined property warning: fetch auth field in user record query for OIDC check
$plugin->release = '1.4.13 (Moodle 4.0-5.x)';
$plugin->requires = 2022041900;                  // Moodle 4.0 minimum (released 2022-04-19)
$plugin->requires_php = '8.0';                  // PHP 8.0 minimum for Moodle 4.0+
$plugin->maturity = MATURITY_STABLE;             // Stable release
$plugin->cron = 300;                            // Run cron every 5 minutes
$plugin->supported = [400, 500];                // Moodle 4.0-5.x

$plugin->author = 'EdFast';
$plugin->copyright = '2026 EdFast';
$plugin->license = 'GPL-3.0-or-later';
$plugin->url = 'https://edfast.ai';
