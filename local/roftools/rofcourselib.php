<?php

/**
 * Functions to manage the links between Moodle courses and ROF references (check broken refs...)
 *
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(dirname(__DIR__)).'/config.php'); // global moodle config file.
require_once(__DIR__ . '/locallib.php');



/**
 * display a table with broken ROF references (courses referring a non-existing ROF object)
 */
function rof_check_courses_references() {
    $referencedrof = rof_get_referenced_objects();
    $brokenrefs = rof_get_broken_references($referencedrof);

    // table output
}

/**
 * get all referenced ROF objects (components + programs + courses) by actual courses
 * @return array($referencenb, $referencefirst), both associative arrays with ROFids as keys
 */
function rof_get_referenced_objects() {
    global $DB;

    $referencenb = array();
    $referencefirst = array();
	$rpiid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1rofpathid', 'objectname' => 'course'), MUST_EXIST);
    $rofpathidset = $DB->get_records_menu('custom_info_data', array('fieldid' => $rpiid), '', 'objectid, data');

    foreach ($rofpathidset as $courseid => $rofpathids) {
        $tabrofpathids = explode(';', $rofpathids);
        foreach ($tabrofpathids as $rofpathid) {
            $tabrofobjects = array_filter(explode('/', $rofpathid));

            foreach ($tabrofobjects as $rofobject) {
                if (isset ($referencenb[$rofobject])) {
                    $referencenb[$rofobject]++;
                } else {
                    $referencenb[$rofobject] = 1;
                    $referencefirst[$rofobject] = $courseid;
                }
            }       // foreach ($tabrofobjects)
        }           // foreach ($tabrofpathids)
    }               // foreach ($rofpathidset)
    return array($referencenb, $referencefirst);
}

/**
 * find all broken references among all ROF references
 * @param array $referencedrof as output by rof_get_referenced_objects
 * @return filtered input
 */
function rof_get_broken_references($referencedrof) {
    $referencenb = $referencedrof[0];
    $referencefirst = $referencedrof[1];
    $brokenrefnb = array();
    $brokenreffirst = array();

}