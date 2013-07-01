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

/**
 * concatenate informations from multiple rofobjects into a single associative (tree) array
 * by calling repeatedly rof_get_metadata
 * @param type $rofobjects array(mixed)
 */
function rof_get_metadata_concat($rofobjects, $separator=';') {
    if (! $rofobjects) {
        return array();
    }
    if ( count($rofobjects) == 1 ) {
        return rof_get_metadata($rofobjects[0]);
    }
    $rofobject=array_shift($rofobjects);
    $metadata = rof_get_metadata($rofobject);
    
    foreach ($rofobjects as $rofobject) {
        $metadataiter = rof_get_metadata($rofobject);
        foreach ($metadataiter as $catname => $items) {
            foreach ($items as $key => $value) {
                //$concatitem = $metadata[$catname][$key];
                $metadata[$catname][$key] .= $separator . $metadataiter[$catname][$key];
            } // key
        } // catname
    } // rofobject
    return $metadata;
}


/**
 * turn rof information from a rofobject into loosely formatted (up1) course metadata
 * @global type $DB
 * @param mixed $rofobject = rofid (string) OR rofidpath (array(rofid)))
 * @return array
 */
function rof_get_metadata($rofobject) {
    global $DB;

    $refcomponents = $DB->get_records_menu('rof_component', null, '', 'number, name');
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
        $rofidpath = array_values($rofobject); // to ensure reindexation, from 0
        $combinedpath = rof_get_combined_path($rofidpath);
        $rofnamepath = array_values($combinedpath);
    } else { // si on reçoit un ROFid seul, on récupère son premier chemin (arbitraire)
        $combinedpath = rof_get_course_first_path($rofobject);
        $rofnamepath = array_values($combinedpath);
        $rofidpath = array_keys($combinedpath);
    }

    $res['Identification']['up1rofpath'] = '/' . join('/', $rofnamepath);
    $res['Identification']['up1rofpathid'] = '/' . join('/', $rofidpath);

    $program = $DB->get_record('rof_program', array('rofid' => $rofidpath[1])); //diplome (en général)
    $prog_name = rof_get_combined_name($rofidpath[1]);
    $res['Diplome']['up1diplome'] = $prog_name;
    $res['Diplome']['up1acronyme'] = $program->acronyme;
    $res['Diplome']['up1mention'] = $program->mention;
    $res['Diplome']['up1specialite'] = $program->specialite;
    if ( preg_match('/^.* parcours (.*)$/', $prog_name, $matches) ) {
        $res['Diplome']['up1parcours'] = $matches[1];
    } else {
        $res['Diplome']['up1parcours'] = '';
    }
    $res['Diplome']['up1type']    = rof_constant_metadata('typeDiplome', $program->typedip);
    $res['Diplome']['up1domaine'] = rof_constant_metadata('domaineDiplome', $program->domainedip);
    $res['Diplome']['up1nature']  = rof_constant_metadata('natureDiplome', $program->naturedip);
    $res['Diplome']['up1cycle']   = rof_constant_metadata('cycleDiplome', $program->cycledip);
    $res['Diplome']['up1rythme']  = rof_constant_metadata('publicDiplome', $program->rythmedip);
    $res['Diplome']['up1langue']  = rof_constant_metadata('langueDiplome', $program->languedip);

    if (isset($rofnamepath[2])) { // subprogram = semestre
        $res['Indexation']['up1subprogram'] = rof_get_combined_name($rofidpath[2]); //subprogram name
        $res['Indexation']['up1semestre'] = rof_guess_semester($rofnamepath[2]);
        $res['Indexation']['up1niveauannee'] = rof_guess_year($res['Indexation']['up1semestre'], $program->typedip);
        $res['Indexation']['up1niveau'] = rof_guess_level($res['Indexation']['up1niveauannee']);
        $eqvDiplomas = equivalent_diplomas();
        $res['Indexation']['up1niveaulmda'] = $eqvDiplomas[$program->typedip];
        $res['Indexation']['up1composante'] = $refcomponents[$rofidpath[0]];
    } else {
        $res['Indexation']['up1subprogram'] = '';
        $res['Indexation']['up1semestre'] = '';
        $res['Indexation']['up1niveauannee'] = '';
        $res['Indexation']['up1niveau'] = '';
        $res['Indexation']['up1niveaulmda'] = '';
        $res['Indexation']['up1composante'] = '';
    }

    $elp = array_pop($rofidpath);
    $course = $DB->get_record('rof_course', array('rofid' => $elp));
    if ($course) {
        $course_name = rof_get_combined_name($elp);
        $res['Indexation']['up1composition'] = $course->composition;
        $res['Identification']['up1complement'] = $course->composition;
        $res['Identification']['up1nom'] = $course_name;
        $res['Identification']['up1rofid'] = $course->rofid;
        $res['Identification']['up1rofname'] = $course_name;
        $res['Identification']['up1code'] = $course->code;
        $res['Identification']['up1nomnorme'] = $course->code .' - '. $course_name .' - '. $course->composition;
        $res['Identification']['up1abregenorme'] = $course->code .' - '. $course->composition;
        $res['Cycle de vie - création']['up1responsable'] = $course->refperson;
    } else { // Les valeurs du diplôme si pertinent
        $defaultcourse = '';
        $res['Indexation']['up1composition'] = $defaultcourse;
        $res['Identification']['up1complement'] = $defaultcourse;
        $res['Identification']['up1nom'] = $prog_name;
        $res['Identification']['up1rofid'] = $program->rofid;
        $res['Identification']['up1rofname'] = $prog_name;
        $res['Identification']['up1code'] = $defaultcourse;
        $res['Identification']['up1nomnorme'] = $program->rofid .' - '. $prog_name;
        $res['Identification']['up1abregenorme'] = $program->rofid .' - '. $prog_name;
        $res['Cycle de vie - création']['up1responsable'] = $defaultcourse;
    }
    return $res;
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

/**
 * convert a year (1 to 6) into a level code (L1 to D)
 * @param type $year
 * @return type
 */
function rof_guess_level($year) {
    $convertyear = array ('0', 'L1', 'L2', 'L3', 'M1', 'M2', 'D', 'D', 'D');
    $level = ( isset($convertyear[$year]) ? $convertyear[$year] : 'A' );
    return $level;
}