<?php
/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/local/up1_courselist/courselist_tools.php');

foreach (array(2052, 2053, 2054, 2055, 2056, 2057, 2058) as $cat) {
    echo " catid = $cat   component = ";
    var_dump(courselist_cattools::get_component_from_category($cat));
}

$rofpathid = '/02/UP1-PROG3456/UP1-C12345';
var_dump(courselist_roftools::rofpath_match_component($rofpathid, '02'));
var_dump(courselist_roftools::rofpath_match_component($rofpathid, '06'));
$rofpathid = '/AAA/UP1-PROG3456/UP1-C12345';
var_dump(courselist_roftools::rofpath_match_component($rofpathid, '02'));
var_dump(courselist_roftools::rofpath_match_component($rofpathid, '06'));

var_dump(courselist_cattools::get_descendant_courses(2121));
