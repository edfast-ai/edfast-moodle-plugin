<?php
/**
 * EdFast Moodle 4/5 Plagiarism Plugin - Events
 *
 * @package    plagiarism_edfast
 * @copyright  2026 EdFast
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_edfast\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when analysis is complete
 */
class analysis_complete extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';  // Created
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'plagiarism_edfast_submissions';
    }

    public static function get_name() {
        return get_string('event_analysis_complete', 'plagiarism_edfast');
    }

    public function get_description() {
        return "Analysis completed for submission " . $this->data['other']['edfast_id'];
    }

    public function get_url() {
        // Return the Moodle admin page for the plugin since there is no standalone report.php.
        return new \moodle_url('/plagiarism/edfast/settings.php');
    }
}
