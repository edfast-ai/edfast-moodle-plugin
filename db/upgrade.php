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
 * EdFast Moodle 4/5 Plagiarism Plugin - Database Upgrade Handler
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function called when plugin version changes
 *
 * @param int $oldversion Previous plugin version
 * @return bool
 */
function xmldb_plagiarism_edfast_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // v1.4.0 — add file_contenthash to detect re-submissions with changed content
    // when moodle_file_id stays the same (Moodle reuses the file record on in-place replace).
    if ($oldversion < 2026022701) {
        $table = new \xmldb_table('plagiarism_edfast_submissions');
        $field = new \xmldb_field('file_contenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'moodle_file_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026022701, 'plagiarism', 'edfast');
    }

    // v1.4.5 — replace mtrace() with error_log() to silence browser output during web requests
    if ($oldversion < 2026030304) {
        upgrade_plugin_savepoint(true, 2026030304, 'plagiarism', 'edfast');
    }

    return true;
}
