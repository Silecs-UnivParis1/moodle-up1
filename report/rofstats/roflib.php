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
 * Get the first path for a course or a program
 * @param string $rofid Course ROFid, ex. UP1-C20867 or Program ROFid, ex. UP1-PROG18336
 */
function getCourseFirstPath($rofid) {
    global $DB;
    $currofid = $rofid;
    $rofpath = array();
    $namepath = array();

    do {
        list($record, $stop) = rofGetRecord($currofid);
        // $table = rofGetTable($currofid);
        // $record = $DB->get_record($table, array('rofid' => $currofid), '*', IGNORE_MISSING);
        $rofpath[] = $currofid;
        $namepath[] = $record->name;
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
 * @param type $rofid
 */
function getCourseAllPaths($rofid) {
    global $DB;
    $prevlist = array(array($rofid));
    $round = 0;

    do { // loop on the levels
        $stop = true;
        $round++;
        $newlist = array();
        foreach ($prevlist as $path) {
            $tail = $path[count($path) - 1]; //dernier élément ; pop interdit car effet de bord
            list($record, $locstop) = rofGetRecord($tail);

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
 * take the result from getCourseAllPaths (previous) and turns into a combined (rofid,name) array
 * @global type $DB
 * @param array(array(string)) $pathlist
 */
function getCourseAllPathnames($pathlist) {
    global $DB;
    $res = array();
    foreach ($pathlist as $path) {
        $pathname = array();
        foreach ($path as $rofid) {
            list($record, $stop) = rofGetRecord($rofid);
            $pathname[] = $record->name;
        }
        $res[] = array_combine($path, $pathname);
    }
    return $res;
}

/**
 * returns table from rofid
 * @param string $rofid
 * @return string table name
 */
function rofGetTable($rofid) {
    if (preg_match('/UP1-PROG/', $rofid)) {
        $table = 'rof_program';
    } elseif (preg_match('/UP1-C/', $rofid)) {
        $table = 'rof_course';
    } elseif (preg_match('/[0-9][0-9]/', $rofid)) {
        $table = 'rof_component';
    } elseif (preg_match('/UP1-OU/', $rofid)) {
        $table = 'rof_component';
    } elseif (preg_match('/UP1-PERS/', $rofid)) {
        $table = 'rof_person';
    } else {
        $table = false;
    }
    return $table;
}

/**
 * returns record from rofid ; requests from component, program, course and person tables
 * @param string $rofid
 * @return DB record
 */
function rofGetRecord($rofid) {
    global $DB;
    if (preg_match('/UP1-PROG/', $rofid)) {
        $table = 'rof_program';
        $field = 'rofid';
        $stop = false;
    } elseif (preg_match('/UP1-C/', $rofid)) {
        $table = 'rof_course';
        $field = 'rofid';
        $stop = false;
    } elseif (preg_match('/[0-9][0-9]/', $rofid)) {
        $table = 'rof_component';
        $field = 'number';
        $stop = true;
    } elseif (preg_match('/UP1-OU/', $rofid)) {
        $table = 'rof_component';
        $field = 'rofid';
        $stop = true;
        // WARNING non renseigné pour l'instant
    } elseif (preg_match('/UP1-PERS/', $rofid)) {
        $table = 'rof_person';
        $field = 'rofid';
        $stop = false;
    } else {
        return array(false, true);
    }
    $record = $DB->get_record($table, array($field => $rofid), '*', IGNORE_MISSING);
    return array($record, $stop);
}



/**
 * returns a formatted string with the result of getCourseFirstPath (or other)
 * @param associative array $pathArray
 * @param enum $format
 * @param bool $roflink : if set, rofid links to view.php
 * @return string
 */
function fmtPath($pathArray, $format='rofid', $roflink=false) {
    $formats = array('rofid', 'name', 'combined', 'ul');
    $ret = '';
    foreach ($pathArray as $rofid => $name) {
        $url = new moodle_url('/report/rofstats/view.php', array('rofid' => $rofid));
        $linkrofid = ($roflink ? html_writer::link($url, $rofid) : $rofid);
        switch($format) {
            case 'rofid':
                $ret .= ' / ' . $linkrofid;
                break;
            case 'name':
                $ret .= ' / ' . $name;
                break;
            case 'combined':
                $ret .= ' / ' . '[' . $linkrofid . '] ' . $name;
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

/**
 * returns an ordered list for typedip, to use in SQL FIND_IN_SET()
 */
function typeDiplomeOrderedList() {
    global $DB;
    $sql = "SELECT GROUP_CONCAT(dataimport) AS list FROM {rof_constant} WHERE element LIKE 'typeDiplome' ORDER BY id";
    $res = $DB->get_record_sql($sql)->list;

    return ($res.',MA,CF,LI,41,77,80,02,00'); // on complète avec les valeurs non présentes dans les constants mais utilisées
}

/**
 * displays a table from a ROF record (component, program, course, or person)
 * @param type $rofid
 * @return type
 */
function rof_view_record($rofid) {

    $res = array();
    list($dbprog, $stop) = rofGetRecord($rofid);
    if ( ! $dbprog ) {
        echo "Mauvais identifiant (rofid) : $rofid.";
        return;
    }
    foreach (get_object_vars($dbprog) as $key => $value) {
        $res[] = array($key, $value);
    }
    $table = new html_table();
    $table->head = array('Champ', 'Valeur');
    $table->data = $res;
    echo html_writer::table($table);
    return;
}
