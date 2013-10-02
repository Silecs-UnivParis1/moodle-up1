<?php
/**
 * functions specific to manage rof paths : formatting, conversion, computing
 *
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Get the first path for a course or a program
 * @param string $rofid Course ROFid, ex. UP1-C20867 or Program ROFid, ex. UP1-PROG18336
 */
function rof_get_course_first_path($rofid) {
    global $DB;
    $currofid = $rofid;
    $rofpath = array();
    $namepath = array();

    do {
        list($record, $stop) = rof_get_record($currofid);
        // $table = rofGetTable($currofid);
        // $record = $DB->get_record($table, array('rofid' => $currofid), '*', IGNORE_MISSING);
        $rofpath[] = $currofid;
        $namepath[] = rof_combined_name($record->localname, $record->name);
        if ( ! $stop ) {
            $parents = explode(',', $record->parents);
            $currofid = $parents[0];
        }
    } while( ! $stop );

    $rofpath = array_reverse($rofpath);
    $namepath = array_reverse($namepath);
    return array_combine($rofpath, $namepath);
}

/**
 * Get all paths leading to a course or a program
 * @param string $rofid
 */
function rof_get_course_all_paths($rofid) {
    global $DB;
    $prevlist = array(array($rofid));
    $round = 0;

    do { // loop on the levels
        $stop = true;
        $round++;
        $newlist = array();
        foreach ($prevlist as $path) {
            $tail = $path[count($path) - 1]; //dernier élément ; pop interdit car effet de bord
            list($record, $locstop) = rof_get_record($tail);

            // $record = $DB->get_record($table, array('rofid' => $tail), '*', IGNORE_MISSING);
            if ($locstop) {
                // $stop = $stop && true;
                $newlist[] = $path; // on ne change rien (à une équipe qui gagne)
            } else {
                $stop = false;
                $parents = explode(',', $record->parents);
                foreach ($parents as $parent) {
                    $npath = $path; // y'a pas moyen d'écrire ça en une ligne ? //** @todo
                    $npath[] = $parent;
                    $newlist[] = $npath;
                }
            }
        } // foreach ($prevlist)
        $prevlist = $newlist;
    }
    while ( ! $stop );

    $res = array();
    foreach ($prevlist as $path) {
        $res[] = array_reverse($path);
    }
    return $res;
}


/**
 * take the result from rof_get_course_all_paths (previous) and turns into a combined (rofid =>rofname) array
 * @global type $DB
 * @param array(array(string)) $pathlist
 */
function rof_get_course_all_pathnames($pathlist) {
    global $DB;
    $res = array();
    foreach ($pathlist as $path) {
        $pathname = array();
        foreach ($path as $rofid) {
            list($record, $stop) = rof_get_record($rofid);
            $pathname[] = rof_combined_name($record->localname, $record->name);
        }
        $res[] = array_combine($path, $pathname);
    }
    return $res;
}


/**
 * returns combined path (with names) from rofid path
 * @global type $DB
 * @param array $rofidpath
 * @return associative array (rofid => rofname)
 */
function rof_get_combined_path($rofidpath) {
    global $DB;
    $res = array();
    foreach ($rofidpath as $rofid) {
        list($record, $ignore) = rof_get_record($rofid);
        if ( $record == false ) {
            $res[$rofid] = '<b>' . $rofid. ' Référence cassée';
        } else {
            $res[$rofid] = rof_combined_name($record->localname, $record->name);
        }
    }
    return $res;
}


/**
 * returns a formatted string with the result of getCourseFirstPath (or other)
 * @param associative array $pathArray : combined path
 * @param enum $format
 * @param bool $roflink : if set, rofid links to view.php
 * @param string $separator
 * @return string
 */
function rof_format_path($pathArray, $format='rofid', $roflink=false, $separator=' / ') {
    $formats = array('rofid', 'name', 'combined', 'ul');
    $ret = '';
    foreach ($pathArray as $rofid => $name) {
        $linkrofid = ($roflink ? rof_rofid_link($rofid, false, $name) : $rofid);
        switch($format) {
            case 'rofid':
                $ret .= $separator . $linkrofid;
                break;
            case 'name':
                if ($roflink) {
                    $ret .= $separator . rof_rofid_link($rofid, $name);
                } else {
                    $ret .= $separator . $name;
                }
                break;
            case 'combined':
                $ret .= $separator . '[' . $linkrofid . '] ' . $name;
                break;
            case 'ul':
                $ret .= '<ul><li>' . '[' . $linkrofid . '] ' . $name .'</li>';
        }
    }
    if ($format == 'ul') {
        $ret .= str_repeat('</ul>', count($pathArray));
    }
    return $ret;
}

