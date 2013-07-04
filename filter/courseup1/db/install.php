<?php
/**
 * @package    filter
 * @subpackage courseup1
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Plugin install code
 */

defined('MOODLE_INTERNAL') || die;

require_once('./upgrade.php');

function xmldb_filter_courseup1_install() {
    global $CFG, $DB;

    echo "Correction des contenus pages :<br />\n";
    update_coursetree_pages();
    return true;
}
