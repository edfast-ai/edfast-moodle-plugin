<?php
/**
 * EdFast Plagiarism Plugin - Event Observers
 *
 * Registers direct event observers for Moodle's legacy event system.
 * This is used as the primary dispatch mechanism since it works on all
 * Moodle 4.x / 5.x builds regardless of whether the 'hooks' API has
 * assignsubmission_file hook classes registered.
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // File submitted (new submission)
    [
        'eventname' => '\assignsubmission_file\event\assessable_uploaded',
        'callback'  => '\plagiarism_edfast\event_observers::assessable_uploaded',
        'priority'  => 200,
    ],
    // File submission created
    [
        'eventname' => '\assignsubmission_file\event\submission_created',
        'callback'  => '\plagiarism_edfast\event_observers::submission_created',
        'priority'  => 200,
    ],
    // File submission updated (resubmission)
    [
        'eventname' => '\assignsubmission_file\event\submission_updated',
        'callback'  => '\plagiarism_edfast\event_observers::submission_updated',
        'priority'  => 200,
    ],
];
