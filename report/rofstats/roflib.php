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
require_once(__DIR__ . '/../../local/rof_categories/locallib.php');

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
            $pathname[] = $record->name;
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
function get_combined_path($rofidpath) {
    global $DB;
    $res = array();
    foreach ($rofidpath as $rofid) {
        list($record, $ignore) = rof_get_record($rofid);
        $res[$rofid] = $record->name;
    }
    return $res;
}

/**
 * returns table from rofid
 * @param string $rofid
 * @return string table name
 */
function rof_get_table($rofid) {
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
function rof_get_record($rofid) {
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
function rof_format_path($pathArray, $format='rofid', $roflink=false) {
    $formats = array('rofid', 'name', 'combined', 'ul');
    $ret = '';
    foreach ($pathArray as $rofid => $name) {
        $linkrofid = ($roflink ? rof_rofid_link($rofid) : $rofid);
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
function rof_rofid_link($rofid) {
    $url = new moodle_url('/report/rofstats/view.php', array('rofid' => $rofid));
    return html_writer::link($url, $rofid);
}

/**
 * returns an ordered list for typedip, to use in SQL FIND_IN_SET()
 */
function rof_typeDiplome_ordered_list() {
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
    list($dbprog, $stop) = rof_get_record($rofid);
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


/**
 * turn rof information from a rofobject into loosely formatted (up1) course metadata
 * @global type $DB
 * @param mixed $rofobject = rofid (string) OR rofidpath (array(rofid)) OR combined path (array(rofid => rofname))
 * @return array
 */
function rof_get_metadata($rofobject) {
    global $DB;
    $res = array('Identification' => array(),
                 'Indexation' => array(),
                 'Diplome' => array(),
                 'Cycle de vie - création' => array()
        );
    if ($rofobject === FALSE) {
        return $res;
    }

    // $rofobject conversion into $rofnamepath + $rofidpath
    if ( is_array($rofobject) ) {
        $keys = array_keys($rofobject);
        $values = array_values($rofobject);
        if ($keys[0] == '0') { // simple array
            $rofidpath = $values;
            $combinedpath = get_combined_path($values);
            $rofnamepath = array_values($combinedpath);
        } else {    //associative array
            $rofnamepath = array_values($rofobject);
            $rofidpath = array_keys($rofobject);
        }
    } else {
        $combinedpath = rof_get_course_first_path($rofobject);
        $rofnamepath = array_values($combinedpath);
        $rofidpath = array_keys($combinedpath);
    }

    $program = $DB->get_record('rof_program', array('rofid' => $rofidpath[1])); //diplome (en général)
    $res['Diplome']['up1diplome'] = $program->name;
    $res['Diplome']['up1acronyme'] = $program->acronyme;
    $res['Diplome']['up1mention'] = $program->mention;
    $res['Diplome']['up1specialite'] = $program->specialite;
    if ( preg_match('/^.* parcours (.*)$/', $program->name, $matches) ) {
        $res['Diplome']['up1parcours'] = $matches[1];
    }
    $res['Diplome']['up1type']    = rof_constant_metadata('typeDiplome', $program->typedip);
    $res['Diplome']['up1domaine'] = rof_constant_metadata('domaineDiplome', $program->domainedip);
    $res['Diplome']['up1nature']  = rof_constant_metadata('natureDiplome', $program->naturedip);
    $res['Diplome']['up1cycle']   = rof_constant_metadata('cycleDiplome', $program->cycledip);
    $res['Diplome']['up1rythme']  = rof_constant_metadata('publicDiplome', $program->rythmedip);
    $res['Diplome']['up1langue']  = rof_constant_metadata('langueDiplome', $program->languedip);

    $res['Indexation']['up1subprogram'] = $rofnamepath[2]; //valeur de subprogram
    $res['Indexation']['up1semestre'] = rof_guess_semester($rofnamepath[2]);
    $res['Indexation']['up1niveauannee'] = rof_guess_year($res['Indexation']['up1semestre'], $program->typedip);

    $elp = array_pop($rofidpath);
    $course = $DB->get_record('rof_course', array('rofid' => $elp));
    $res['Indexation']['up1composition'] = $course->composition;
    $res['Identification']['up1complement'] = $course->composition;
    $res['Identification']['up1nom'] = $course->name;
    $res['Identification']['up1rofid'] = $course->rofid;
    $res['Identification']['up1code'] = $course->code;
    $res['Identification']['up1nomnorme'] = $course->code .' - '. $course->name .' - '. $course->composition;
    $res['Identification']['up1abregenorme'] = $course->code .' - '. $course->composition;
    $res['Cycle de vie - création']['up1responsable'] = $course->refperson;

    return $res;
}

/**
 * return "human-readable" value as : (code) readable-name
 * @global type $DB
 * @param type $element from the rof_constant table
 * @param type $rawdata reference to the rof_constant table, column dataimport
 * @return string
 */
function rof_constant_metadata($element, $rawdata) {
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

function rof_format_metadata($metadata) {
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

function rof_insert_paths_statistics($verb=0) {
    global $DB;

    $courses = $DB->get_records('rof_course', array(), '', 'id,rofid');
    foreach ($courses as $course) {
        $paths = count(rof_get_course_all_paths($course->rofid));
        if ($verb >=1 ) { echo '.'; }
        $dbcourse = new stdClass();
        $dbcourse->id = $course->id;
        $dbcourse->pathsnb = $paths;
        $DB->update_record('rof_course', $dbcourse, true); //bulk=true
    }
}


/**
 * WARNING ONLY for 2012-2013 / UP1 /... categories
 * Prerequisite: standard categories have been created from ROF by local/rof_categories
 * @global type $DB
 * @param array $rofpath ROF path as usual ('02', 'UP1-PROG12345', 'UP1-PROG45678', 'UP1-C98765' ...)
 * @return int (category id) OR false if an error occurred
 * @throws coding_exception
 */
function rof_rofpath_to_category($rofpath) {
    global $DB;

    if ( count($rofpath) < 2 || ! is_array($rofpath) ) {
        throw new coding_exception('rofpath trop court : 2 niveaux minimum');
        return false;
    }
    if ( ! preg_match('/^\d\d/', $rofpath[0]) ) { //composante = code numérique, 2 chiffres
        throw new coding_exception('composante non conforme' . $rofpath[0]);
        return false;
    }
    $typedip = $DB->get_field('rof_program', 'typedip', array('rofid' => $rofpath[1]), MUST_EXIST);
    if ($typedip) {
        $eqvDiplomas = equivalentDiplomas();
        $catcode = '4:' . $rofpath[0] .'/'. $eqvDiplomas[$typedip];

        $res = $DB->get_field('course_categories', 'id', array('idnumber' => $catcode));
        return $res;
    }
    return false;
}


function rof_get_code_or_rofid($rofid) {
    global $DB;

    if ( preg_match('/^UP1-C\d+/', $rofid) ) {
        $res = $DB->get_field('rof_course', 'code', array('rofid' => $rofid));
        if ($res) {
            return $res;
        }
    }
    return $rofid;
}