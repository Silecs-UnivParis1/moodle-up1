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
    global $DB;

    $referencedRof = rof_get_referenced_objects();
    $brokenRefs = rof_get_broken_references($referencedRof);

    if (count($brokenRefs[0]) > 0) { //there are broken references
        $row = array();
        foreach ($brokenRefs[0] as $rofid => $brokenNb) {
            $crsid = $brokenRefs[1][$rofid];
            $urlrof = new moodle_url('/report/rofstats/view.php', array('rofid' => $rofid));
            // urlrof = dumb link, as the rof object is listed as non-existent
            $urlcrs = new moodle_url('/course/view.php', array('id' => $crsid));
            $coursename = $DB->get_field('course', 'fullname', array('id' => $crsid), 'MUST_EXIST');
            $row[] = array(html_writer::link($urlrof, $rofid), $brokenNb, html_writer::link($urlcrs, $coursename));
        }
        // table output
        $table = new html_table();
        $table->head = array('ROFid', 'Nb. réfs', 'Premier cours');
        $table->data = $row;
        echo html_writer::table($table);
        return true;
    } else { //No broken references
        echo "<p>Aucune référence cassée.</p>\n";
        return false;
    }
}

/**
 * prepare a list <ul>...</ul> of broken ROF references for a given course
 * @param integer $crsid target course id
 * @return string html list block OR false = none
 */
function rof_check_course_references($crsid) {
    $referencedRof = rof_get_referenced_objects();
    $brokenRefs = rof_get_broken_references($referencedRof);

    if (count($brokenRefs[0]) > 0) { //there are broken references
        $output = "<ul>\n";
        foreach ($brokenRefs[0] as $rofid => $brokenNb) {
            $crsid = $brokenRefs[1][$rofid];
            $urlrof = new moodle_url('/report/rofstats/view.php', array('rofid' => $rofid));
            // urlrof = dumb link, as the rof object is listed as non-existent
            $output .= '<li>' . html_writer::link($urlrof, $rofid) . "</li>\n";
        }
        $output .= "</ul>\n";
        return $output;
    } else { //No broken references
        return false;
    }
}


/**
 * get all referenced ROF objects (components + programs + courses) by actual courses
 * @param integer $crsid for a specific course ; NULL for all courses
 * @return array($referencenb, $referencefirst), both associative arrays with ROFids as keys
 */
function rof_get_referenced_objects($crsid = null) {
    global $DB;

    $referencenb = array();
    $referencefirst = array();
	$rpiid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1rofpathid', 'objectname' => 'course'), MUST_EXIST);

    $conds = array('fieldid' => $rpiid);
    if (isset($crsid) && is_integer($crsid)) {
        $conds['objectid'] = $crsid;
    }
    $rofpathidset = $DB->get_records_menu('custom_info_data', $conds, '', 'objectid, data');

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
    global $DB;

    $referenceNb = $referencedrof[0];
    $referenceFirst = $referencedrof[1];
    $brokenRefNb = array();
    $brokenRefFirst = array();

    foreach ($referenceNb as $rofid => $refs) {
        $res = rof_get_record($rofid);
        if ($res[0] === FALSE) {
            $brokenRefNb[$rofid] = $referenceNb[$rofid];
            $brokenRefFirst[$rofid] = $referenceFirst[$rofid];
        }
    }
    return array($brokenRefNb, $brokenRefFirst);
}

/**
 * return the list of courses referencing a given ROF object
 * @param type $rofid string ROFid
 * @return array( $courseid => $name )
 */
function rof_object_is_referenced_by($rofid) {
    global $DB;

    $rpiid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1rofpathid', 'objectname' => 'course'), MUST_EXIST);
    $sql = "SELECT c.id, c.fullname "
         . "FROM {custom_info_data} cid "
         . "JOIN {course} c ON (cid.objectid = c.id)"
         . "WHERE objectname='course' AND fieldid = ? AND data LIKE '%" . $rofid . "%'" ;
    $courses = $DB->get_records_sql_menu($sql, array($rpiid));
    return $courses;
}


