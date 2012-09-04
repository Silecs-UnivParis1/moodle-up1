<?php

/**
 * Lib exported functions
 *
 * @package    report
 * @subpackage rofstats
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


/**
 * Get the first path for a course (only from rof_course table)
 * @param string $rofid Course ROFid, ex. UP1-C20867
 */
function getCourseFirstPath($rofid) {
    global $DB;
    $currofid = $rofid;
    $rofpath = array();
    $namepath = array();

    do {
        $course = $DB->get_record('rof_course', array('rofid' => $currofid), '*', IGNORE_MISSING);
        $rofpath[] = $currofid;
        $namepath[] = $course->name;
        $parents = explode(',', $course->parents);
        $currofid = $parents[0];
    } while($course->level > 1);

    $rofpath = array_reverse($rofpath);
    $namepath = array_reverse($namepath);
    return array_combine($rofpath, $namepath);
}

/**
 * returns a formatted string with the result of getCourseFirstPath (or other)
 * @param associative array $pathArray
 * @param enum $format
 * @return string
 */
function fmtPath($pathArray, $format='rofid') {
    $formats = array('rofid', 'name', 'combined');
    $ret = '';
    foreach ($pathArray as $rofid => $name) {
        switch($format) {
            case 'rofid':
                $ret .= '/' . $rofid;
                break;
            case 'name':
                $ret .= '/' . $name;
                break;
            case 'combined':
                $ret .= ' / ' . '[' . $rofid . '] ' . $name;
                break;
        }
    }
    return $ret;
}

/**
 * returns an ordered list for typedip, to use in SQL FIND_IN_SET()
 */
function typeDiplomeOrderedList() {
    global $DB;
    $sql = "SELECT GROUP_CONCAT(dataimport) AS list FROM {rof_constant} WHERE element LIKE 'typeDiplome' ORDER BY id";
    $res = $DB->get_record_sql($sql)->list;

    return ($res.',MA,CF,LI,41,77,80,02,00'); // on complète avec les valeurs non présentes dans les constants mais utilisées
}