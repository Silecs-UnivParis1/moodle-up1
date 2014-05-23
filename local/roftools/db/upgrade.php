<?php
/**
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . "/local/roftools/updatelib.php");

/**
 *
 * @param int $oldversion The old version of the assign module
 * @return bool
 */
function xmldb_local_roftools_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014051901) {

        echo "Updating ROFtools settings... <br />\n";
        update_rof_settings();
    }

    return true;
}