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
