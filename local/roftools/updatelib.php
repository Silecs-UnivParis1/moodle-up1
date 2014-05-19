<?php
/**
 * @package    local
 * @subpackage roftools
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

function update_categories_idnumber() {
    global $DB;

    $yearcode = get_config('local_roftools', 'rof_year_code');
    $newyearcode = new_idnumber_code($yearcode);
    $etabcode = get_config('local_roftools', 'rof_etab_code');
    $newetabcode = new_idnumber_code($etabcode);

    $sql = "UPDATE {course_categories} SET idnumber=? WHERE depth=2 AND idnumber=?";
    $DB->execute($sql, array('2:' . $newyearcode . '/' . $newetabcode , $etabcode));

    $sql = "UPDATE {course_categories} SET idnumber=REPLACE(idnumber, '3:', ?) "
        . " WHERE depth=3 AND idnumber REGEXP '3:[^/]+$' ";
    $DB->execute($sql, array('3:' . $newyearcode . '/' . $newetabcode . '/' ));

    $sql = "UPDATE {course_categories} SET idnumber=REPLACE(idnumber, '4:', ?) "
        . " WHERE depth=4 AND idnumber REGEXP '4:[^/]+/[^/]+$'";
    $DB->execute($sql, array('4:' . $newyearcode . '/' . $newetabcode . '/' ));

}


function update_rof_settings() {
    $yearcode = get_config('local_roftools', 'rof_year_code');
    $newyearcode = new_idnumber_code($yearcode);
    $res = set_config('rof_year_code', $newyearcode, 'local_roftools');
    if ( ! $res ) {
        die ('unable to update rof_year_code');
    }

    $etabcode = get_config('local_roftools', 'rof_etab_code');
    $newetabcode = new_idnumber_code($etabcode);
    $res = set_config('rof_etab_code', $newetabcode, 'local_roftools');
    if ( ! $res ) {
        die ('unable to update rof_etab_code');
    }
}

/**
 * ex. "1:2012-2013" -> "2012-2013" ; "2:UP1" -> "UP1"
 * @param string $oldcode
 * @return string
 */
function new_idnumber_code ($oldcode) {
    if (preg_match('/\d:/', $oldcode) ) {
        $newcode = substr($oldcode, 2);
    } else {
        $newcode = $oldcode;
    }
    return $newcode;
}