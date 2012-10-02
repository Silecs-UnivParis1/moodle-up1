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
 * returns detailed path (with names) from rofid path
 * @global type $DB
 * @param array $path
 * @return associative array (rofid => name)
 */
function getCompletePath($path) {
    global $DB;
    $res = array();
    foreach ($path as $rofid) {
        list($record, $ignore) = rofGetRecord($rofid);
        $res[$rofid] = $record->name;
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
 * @return array(DB $record, bool $top) ; top set on component => stop climbing up the ROF tree
 */
function rofGetRecord($rofid) {
    global $DB;
    if (preg_match('/^UP1-PROG/', $rofid)) {
        $table = 'rof_program';
        $field = 'rofid';
        $top = false;
    } elseif (preg_match('/^UP1-C/', $rofid)) {
        $table = 'rof_course';
        $field = 'rofid';
        $top = false;
    } elseif (preg_match('/^[0-9][0-9]$/', $rofid)) {
        $table = 'rof_component';
        $field = 'number';
        $top = true;
    } elseif (preg_match('/^UP1-OU/', $rofid)) {
        $table = 'rof_component';
        $field = 'rofid';
        $top = true;
        // WARNING non renseigné pour l'instant
    } elseif (preg_match('/^UP1-PERS/', $rofid)) {
        $table = 'rof_person';
        $field = 'rofid';
        $top = false;
    } else {
        return array(false, true);
    }
    $record = $DB->get_record($table, array($field => $rofid), '*', IGNORE_MISSING);
    return array($record, $top);
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
        $linkrofid = ($roflink ? rofid_link($rofid) : $rofid);
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
 * returns link to view rofid
 * @param string $rofid
 * @return string url
 */
function rofid_link($rofid) {
    $url = new moodle_url('/report/rofstats/view.php', array('rofid' => $rofid));
    return html_writer::link($url, $rofid);
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
 * @return bool TRUE if everything is OK, FALSE otherwise
 */
function rof_view_record($rofid) {

    $res = array();
    list($dbprog, $stop) = rofGetRecord($rofid);
    if ( ! $dbprog ) {
        echo "Mauvais identifiant (rofid) : $rofid.";
        return false;
    }
    foreach (get_object_vars($dbprog) as $key => $value) {
        if ($key == 'courses' || $key == 'sub') {
            $links = join(',', array_map('rofid_link', explode(',', $value)));
            $res[] = array($key, $links);
        } else {
            $res[] = array($key, $value);
        }
    }
    $table = new html_table();
    $table->head = array('Champ', 'Valeur');
    $table->data = $res;
    echo html_writer::table($table);
    return true;
}



function rof_get_metadata($rofobject) {
    global $DB;
    $res = array('identification' => array(),
                 'indexation' => array(),
                 'diplome' => array(),
        );
    if (is_array($rofobject) ) {
        $path = $rofobject;
    } else {
        $path = getCourseFirstPath($rofobject);
    }

    $namepath = array_values($path);
    $rofpath = array_keys($path);

    $program = $DB->get_record('rof_program', array('rofid' => $rofpath[1])); //diplome (en général)
    $res['diplome']['diplome'] = $program->name;
    $res['diplome']['acronyme'] = $program->acronyme;
    $res['diplome']['mention'] = $program->mention;
    $res['diplome']['specialite'] = $program->specialite;
    if ( preg_match('/^.* parcours (.*)$/', $program->name, $matches) ) {
        $res['diplome']['parcours'] = $matches[1];
    }
    $res['diplome']['type']    = constant_metadata('typeDiplome', $program->typedip);
    $res['diplome']['domaine'] = constant_metadata('domaineDiplome', $program->domainedip);
    $res['diplome']['nature']  = constant_metadata('natureDiplome', $program->naturedip);
    $res['diplome']['cycle']   = constant_metadata('cycleDiplome', $program->cycledip);
    $res['diplome']['rythme']  = constant_metadata('publicDiplome', $program->rythmedip);
    $res['diplome']['langue']  = constant_metadata('langueDiplome', $program->languedip);

    $res['indexation']['subprogram'] = $namepath[2]; //valeur de subprogram
    $res['indexation']['semestre'] = rof_guess_semester($namepath[2]);
    $res['indexation']['annee'] = rof_guess_year($res['indexation']['semestre'], $program->typedip);

    $elp = array_pop($rofpath);
    $course = $DB->get_record('rof_course', array('rofid' => $elp));
    $res['indexation']['composition'] = $course->composition;
    $res['identification']['nom'] = $course->name;
    $res['identification']['rofid'] = $course->rofid;
    $res['identification']['code'] = $course->code;
    $res['identification']['nom-norme'] = $course->code .' - '. $course->name .' - '. $course->composition;
    $res['identification']['abrege-norme'] = $course->code .' - '. $course->composition;

    return $res;
}

/**
 * return "human-readable" value as : (code) readable-name
 * @global type $DB
 * @param type $element from the rof_constant table
 * @param type $rawdata reference to the rof_constant table, column dataimport
 * @return string
 */
function constant_metadata($element, $rawdata) {
    global $DB;
    return '(' . $rawdata. ') '.
            $DB->get_field('rof_constant', 'value', array('element' => $element, 'dataimport' => $rawdata));
}

/**
 * try to guess (integer) semester number
 * @param type $semester
 * @return string
 */
function rof_guess_semester($semester) {

    if (preg_match('/\bsemestre( )?([1-9])/i', $semester, $match)) {
        $res = $match[2];
    } elseif (preg_match('/\bsem( )?([1-9])/i', $semester, $match)) {
        $res = $match[2];
    } elseif (preg_match('/\bs( )?([1-9])/i', $semester, $match)) {
        $res = $match[2];
    } else {
        $res = '';
    }
    return $res;
}

/**
 * try to guess the year of studies (année d'étude)
 * @param int $semestreint
 * @param string $typedip : one of the constants typeDiplome
 * @return int or empty string
 */
function rof_guess_year($semestreint, $typedip) {
    $cycledip = array(
        'C1' => array('L1','L2','L3','DP','U2','U3'),
        'C2' => array('M1','E1','M2','E2','30','U4','U5'),
        'C3' => array('40', '41', 'U6'),
        );
        // 'Autres' => 'PG,PC,PA,P1'
    if (! is_numeric($semestreint) ) {
        return '';
    }
    if ( in_array($typedip, $cycledip['C1']) ) {
        return 1 + round(($semestreint - 1) / 2, 0, PHP_ROUND_HALF_DOWN);
    } elseif ( in_array($typedip, $cycledip['C2']) ) {
        return 4 + round(($semestreint - 1) / 2, 0, PHP_ROUND_HALF_DOWN);
    } elseif ( in_array($typedip, $cycledip['C3']) ) {
        return 6;
    } else {
        return '';
    }
}

function fmt_rof_metadata($metadata) {
    $output = "<ul>\n";
    foreach ($metadata as $cat => $data) {
        $output .= "  <li>" . $cat . "</li>\n  <ul>\n";
        foreach ($data as $key=>$value) {
            $output .= "    <li>" . $key ." : <b>". $value ."</b></li>\n";
        }
        $output .= "  </ul>\n";
    }
    $output .= "</ul>\n";
    return $output;
}