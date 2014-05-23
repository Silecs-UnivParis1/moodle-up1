<?php
/**
 * @package    local
 * @subpackage roftools
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

/**
 * update field categories.idnumber to replace ambiguous records by explicit records.
 * eg. 2:UP1 -> 2:2013-2014/UP1   or   3:Licences -> 3:2013-2014/UP/Licences
 * @param type $yearcode
 * @param type $etabcode
 */
function update_categories_idnumber($yearcode, $etabcode) {
    global $DB;

    $yearid = $DB->get_field('course_categories', 'id', array('idnumber'=>'1:'.$yearcode, 'depth'=>1), MUST_EXIST);
    $etabid = $DB->get_field('course_categories', 'id', array('idnumber'=>'2:'.$etabcode, 'depth'=>2, 'parent'=>$yearid), MUST_EXIST);
    if ( ! $yearid || ! $etabid ) {
        die ('yearcode ou etabcode n\'existe pas.');
    }
    $catpath = '/' . $yearid . '/' . $etabid . '/%';
    echo "catpath = $catpath \n";

    $sql = "UPDATE {course_categories} SET idnumber=? WHERE depth=2 AND id=?";
    $DB->execute($sql, array('2:' . $yearcode . '/' . $etabcode , $etabid));
    echo "depth=2 OK.\n";

    $sql = "UPDATE {course_categories} SET idnumber=REPLACE(idnumber, '3:', ?) "
        . " WHERE depth=3 AND idnumber REGEXP '3:[^/]+$' AND path LIKE ? ";
    $DB->execute($sql, array('3:' . $yearcode . '/' . $etabcode . '/',  $catpath ));
    echo "depth=3 OK.\n";

    $sql = "UPDATE {course_categories} SET idnumber=REPLACE(idnumber, '4:', ?) "
        . " WHERE depth=4 AND idnumber REGEXP '4:[^/]+/[^/]+$' AND path LIKE ? ";
    $DB->execute($sql, array('4:' . $yearcode . '/' . $etabcode . '/',  $catpath ));
    echo "depth=4 OK.\n";

    return true;
}

/** list all categories with idnumber matching the old scheme
 */
function list_old_idnumber() {
    global $DB;

    $sql3 = "SELECT id, name, idnumber FROM {course_categories} WHERE depth=3 AND idnumber REGEXP '3:[^/]+$' ";
    $sql4 = "SELECT id, name, idnumber FROM {course_categories} WHERE depth=4 AND idnumber REGEXP '4:[^/]+/[^/]+$' ";
    $res1 = $DB->get_records_sql($sql3);
    $res2 = array_merge($res1, $DB->get_records_sql($sql4));
    $count = 0;
    foreach ($res2 as $cat) {
        $count++;
        echo $count . ". " . $cat->idnumber . "  " . $cat->name . "\n";
    }
    if ($count == 0) {
        echo "\nAucune catégorie avec un champ idnumber ancien schéma.\n\n";
    }
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
