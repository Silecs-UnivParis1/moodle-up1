<?php

/**
 * Lib exported functions
 *
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(dirname(__DIR__)).'/config.php'); // global moodle config file.
require_once(__DIR__ . '/rofpathlib.php');
require_once(__DIR__ . '/rofimportlib.php');
require_once(__DIR__ . '/locallib.php');

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
 * for a ROF object in (component, program, course), returns the localname if defined, otherwise the official name
 * @param type $rofid
 * @return string combined name
 */
function rof_get_combined_name($rofid) {
    list($record, $top) = rof_get_record($rofid);
    if ($record->localname) {
        return $record->localname;
    } else {
        return $record->name;
    }
}

/**
 * returns the localname if defined (not null nor empty), otherwise the reference name
 * @param string $localname
 * @param string $refname
 * @return string
 */
function rof_combined_name($localname, $refname) {
    return ($localname ? $localname : $refname);
}


/**
 * returns link to view rofid
 * @param string $rofid
 * @param string $support link support, or false (in which case the support will be the rofid)
 * @param string $title the title (tooltip) on the link, optional
 * @return string url
 */
function rof_rofid_link($rofid, $support=false, $title='') {
    $url = new moodle_url('/report/rofstats/view.php', array('rofid' => $rofid));
    $attributes = null;
    if ($title) {
        $attributes = array('title' => $title);
    }
    if ($support) {
        $link = html_writer::link($url, $support, $attributes);
    } else {
        $link = html_writer::link($url, $rofid, $attributes);
    }
    return $link;
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
    list($dbrof, $stop) = rof_get_record($rofid);
    if ( ! $dbrof ) {
        echo "Mauvais identifiant (rofid) : $rofid.";
        return false;
    }
    foreach (get_object_vars($dbrof) as $key => $value) {
        if ($key == 'courses' || $key == 'sub') {
            $links = join(',', array_map('rof_rofid_link', explode(',', $value)));
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
 * displays a table of ROF constants for a given $element
 * @param string $element : matches element field (2nd field of rof_constant)
 */
function rof_table_constants($element) {
    global $DB;

    $res = array();
    $constants = $DB->get_records('rof_constant', array('element' => $element));
    if ( ! $constants ) {
        return false;
    }
    $excludes = array('id', 'element', 'timesync');

    foreach ($constants as $constant) {
        $row = array();
        $header=array();
        foreach (get_object_vars($constant) as $field => $value) {
            if ( ! in_array($field, $excludes) ) {
                $header[] = $field; //this could be put out of the loop but doesn't optimize much
                $row[] = $value;
            }
        }
     $res[] = $row;
    }
    $table = new html_table();
    $table->head = $header;
    $table->data = $res;
    echo html_writer::table($table);
    return true;
}

function rof_get_constants() {
    global $DB;

    $sql = 'SELECT DISTINCT element FROM {rof_constant} ORDER BY element';
    return $DB->get_fieldset_sql($sql);
}

function rof_links_constants($baseurl) {
    $constants = rof_get_constants();
    $links = '';
    foreach ($constants as $constant) {
        $url = new moodle_url($baseurl, array('constant' => $constant));
        $links .= html_writer::link($url, $constant) . ' &nbsp ';
    }
    return 'Constantes : ' . $links;
}


/**
 * return "human-readable" value as : [code] readable-name, ex. [DS] Droit, Sciences politique et sociales
 * @global type $DB
 * @param type $element from the rof_constant table
 * @param type $rawdata reference to the rof_constant table, column dataimport
 * @return string
 */
function rof_constant_metadata($element, $rawdata) {
    global $DB;
    return '[' . $rawdata. '] '.
            $DB->get_field('rof_constant', 'value',
                    array('element' => $element, 'dataimport' => $rawdata),
                    IGNORE_MULTIPLE //workaround for bad data, eg multiple (element='typeDiplome', dataImport='U4')
                    );
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
        $eqvDiplomas = equivalent_diplomas();
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


/**
 * returns the possible values for a given ROF constant element
 * as a menu (associative array),
 * @global type $DB
 * @param type $element from rof_constant element field
 * @param type $prefix if true, the value is prefixed by the key. ex. "[DS] Droit, Sciences politique et sociales"
 * @return associative array  ROF identifer => value OR FALSE if no such field
 */
function rof_get_menu_constant($metafield, $prefix=true) {
    global $DB;

    $meta_to_rof = array (
//        'up1composante' => 'composante', too comlicated because of multiple values ?
        'up1domaine' => 'domaineDiplome',
        'up1type' => 'typeDiplome',
        'up1nature' => 'natureDiplome',
        'up1cycle' => 'cycleDiplome',
        'up1rythme' => 'publicDiplome',
        'up1langue' => 'langueDiplome',
    );
    if ( isset($meta_to_rof[$metafield]) ) {
        $element = $meta_to_rof[$metafield];
    } else {
        return false;
    }
    // we don't use this because of some redundancy in "dataimport" fields, which should NOT happen. Bad ROF, change ROF.
    // $records = $DB->get_records_menu('rof_constant', array('element'=>$element), null, 'dataimport, value');
    $sql = "SELECT dataimport, MAX(value) FROM {rof_constant} rc WHERE element = ? GROUP BY dataimport";
    $records = $DB->get_records_sql_menu($sql, array($element));
    if ( ! $prefix ) {
        return $records;
    } else {
        $res = array();
        foreach ($records as $key => $value) {
            $res[$key] = '[' . $key . '] ' . $value;
        }
        return $res;
    }
}


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