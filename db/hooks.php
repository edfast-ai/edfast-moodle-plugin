<?php
/**
 * EdFast Plagiarism Plugin - Moodle 5.x Hook Callbacks
 *
 * Registers EdFast callbacks for Moodle 5.x hooks-based event system.
 * Moodle 5.0 deprecated the old plagiarism_*_assessable_uploaded() functions
 * in favour of hook callbacks defined here.
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    // File uploaded via assignment submission
    [
        'hook'     => \assignsubmission_file\hook\submission_created::class,
        'callback' => \plagiarism_edfast\hook_callbacks::class . '::submission_file_created',
        'priority' => 500,
    ],
    [
        'hook'     => \assignsubmission_file\hook\submission_updated::class,
        'callback' => \plagiarism_edfast\hook_callbacks::class . '::submission_file_updated',
        'priority' => 500,
    ],
    // Online text submitted via assignment
    [
        'hook'     => \assignsubmission_onlinetext\hook\submission_created::class,
        'callback' => \plagiarism_edfast\hook_callbacks::class . '::submission_onlinetext_created',
        'priority' => 500,
    ],
    [
        'hook'     => \assignsubmission_onlinetext\hook\submission_updated::class,
        'callback' => \plagiarism_edfast\hook_callbacks::class . '::submission_onlinetext_updated',
        'priority' => 500,
    ],
];
