<?php
/**
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function up1_metadata() {

    $res = array(
        'Identification' => array(
            'nomnorme' => array('name' => 'Nom normé', 'datatype' => 'text', 'locked' => 0),
            'abregenorme' => array('name' => 'Nom abrégé normé', 'datatype' => 'text', 'locked' => 0),
            'rofpath' => array('name' => 'Chemin ROF', 'datatype' => 'text', 'locked' => 0),
            'rofpathid' => array('name' => 'Chemin ROFid', 'datatype' => 'text', 'locked' => 0),
            'code' => array('name' => 'Code Apogée', 'datatype' => 'text', 'locked' => 1),
            'rofid' => array('name' => 'RofId', 'datatype' => 'text', 'locked' => 1),
            'rofname' => array('name' => 'Nom ROF', 'datatype' => 'text', 'locked' => 0)
        ),
        'Indexation' => array(
            'periode' => array('name' => 'Période', 'datatype' => 'text', 'locked' => 0),
            'composante' => array('name' => 'Composante', 'datatype' => 'text', 'locked' => 0),
            'semestre' => array('name' => 'Semestre', 'datatype' => 'text', 'locked' => 0),
            'niveau' => array('name' => 'Niveau', 'datatype' => 'text', 'locked' => 0),
            'composition' => array('name' => 'Composition', 'datatype' => 'text', 'locked' => 0)
        ),
        'Diplome' => array(
            'diplome' => array('name' => 'Diplôme', 'datatype' => 'text', 'locked' => 0),
            'domaine' => array('name' => 'Domaine ROF', 'datatype' => 'text', 'locked' => 0),
            'type' => array('name' => 'Type ROF', 'datatype' => 'text', 'locked' => 0),
            'nature' => array('name' => 'Nature ROF', 'datatype' => 'text', 'locked' => 0),
            'cycle' => array('name' => 'Cycle ROF', 'datatype' => 'text', 'locked' => 0),
            'rythme' => array('name' => 'Rythme ROF', 'datatype' => 'text', 'locked' => 0),
            'langue' => array('name' => 'Langue', 'datatype' => 'text', 'locked' => 0),
            'acronyme' => array('name' => 'Acronyme', 'datatype' => 'text', 'locked' => 0),
            'mention' => array('name' => 'Mention', 'datatype' => 'text', 'locked' => 0),
            'specialite' => array('name' => 'Spécialité', 'datatype' => 'text', 'locked' => 0),
            'parcours' => array('name' => 'Parcours', 'datatype' => 'text', 'locked' => 0)
        ),
        'Cycle de vie' => array(
            'demandeur' => array('name' => 'Demandeur', 'datatype' => 'text', 'locked' => 0),
            'responsable' => array('name' => 'Responsable', 'datatype' => 'text', 'locked' => 0),
            'datedemande' => array('name' => 'Date demande', 'datatype' => 'datetime', 'locked' => 0),
            'avalider' => array('name' => 'Attente de validation', 'datatype' => 'checkbox', 'locked' => 0),
            'approbateurid' => array('name' => 'Approbateur Id', 'datatype' => 'text', 'locked' => 0),
            'approbateur' => array('name' => 'Approbateur', 'datatype' => 'text', 'locked' => 0),
            'datevalid' => array('name' => 'Date validation', 'datatype' => 'datetime', 'locked' => 0),
            'datefermeture' => array('name' => 'Date fermeture', 'datatype' => 'datetime', 'locked' => 0),
            'dateprevarchivage' => array('name' => 'Date prévis. archivage', 'datatype' => 'datetime', 'locked' => 0),
            'datearchivage' => array('name' => 'Date archivage', 'datatype' => 'datetime', 'locked' => 0),
            'generateur' => array('name' => 'Générateur', 'datatype' => 'text', 'locked' => 0),
            'modele' => array('name' => 'Modèle', 'datatype' => 'text', 'locked' => 0)
        )
    );
    return $res;
}

function validate_metadata() {
    $metadata = up1_metadata();
    $count = array();
    $res = TRUE;

    // check if a shortname isn't used twice or more (in 2 categories)
    foreach($metadata as $cat => $fields) {
        foreach ($fields as $shortname => $attributes) {
            if (isset($count[$shortname])) {
                $count[$shortname]++;
            } else {
                $count[$shortname] = 1;
            }
        }
    }

    foreach ($count as $shortname => $c) {
        if ($c != 1) {
            $res = FALSE;
            echo "$shortname utilisé $c fois.\n";
        }
    }
    return $res;
}


function insert_metadata_categories() {
    global $DB;
    $metadata = up1_metadata();

    $sql = "SELECT MAX(sortorder) AS maxi FROM {custom_info_category} WHERE objectname='course'";
    $record = $DB->get_record_sql($sql);
    $sortorder = $record->maxi;
    foreach ($metadata as $cat => $drop) {
        if ( $DB->record_exists('custom_info_category', array('objectname'=>'course', 'name'=>$cat)) ) {
            echo "$cat already exists.\n";
        } else {
            $sortorder++;
            echo "inserting $cat.\n";
            $record = new StdClass;
            $record->objectname = 'course';
            $record->name = $cat;
            $record->sortorder = $sortorder;
            $DB->insert_record('custom_info_category', $record);
        }
    }
}


