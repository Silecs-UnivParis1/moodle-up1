<?php
/**
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function up1_course_metadata() {
    // nota : 'init' is NOT a custom meta-field ; it is the value which will be initialized (if not null)
    // for all object records (all courses or all users)

    $res = array(
        'Identification' => array(
            'complement' => array('name' => 'Complément intitulé', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'nomnorme' => array('name' => 'Nom normé', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'abregenorme' => array('name' => 'Nom abrégé normé', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'rofpath' => array('name' => 'Chemin ROF', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'rofpathid' => array('name' => 'Chemin ROFid', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'code' => array('name' => 'Code Apogée', 'datatype' => 'text', 'locked' => 1,  'init' => null),
            'rofid' => array('name' => 'RofId', 'datatype' => 'text', 'locked' => 1,  'init' => null),
            'rofname' => array('name' => 'Nom ROF', 'datatype' => 'text', 'locked' => 0,  'init' => null),
        ),
        'Indexation' => array(
            'periode' => array('name' => 'Période', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'composante' => array('name' => 'Composante', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'semestre' => array('name' => 'Semestre', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'niveau' => array('name' => 'Niveau', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'niveaulmda' => array('name' => 'Niveau LMDA', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'niveauannee' => array('name' => 'Niveau année', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'composition' => array('name' => 'Composition', 'datatype' => 'text', 'locked' => 0),
            'categoriesbis' => array('name' => 'Catégories de cours supplémentaires hors ROF', 'datatype' => 'text', 'locked' => 0,  'init' => ''),
            'categoriesbisrof' => array('name' => 'Catégories de cours supplémentaires rattachements ROF', 'datatype' => 'text', 'locked' => 0,  'init' => ''),
        ),
        'Diplome' => array(
            'diplome' => array('name' => 'Diplôme', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'domaine' => array('name' => 'Domaine ROF', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'type' => array('name' => 'Type ROF', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'nature' => array('name' => 'Nature ROF', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'cycle' => array('name' => 'Cycle ROF', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'rythme' => array('name' => 'Rythme ROF', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'langue' => array('name' => 'Langue', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'acronyme' => array('name' => 'Acronyme', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'mention' => array('name' => 'Mention', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'specialite' => array('name' => 'Spécialité', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'parcours' => array('name' => 'Parcours', 'datatype' => 'text', 'locked' => 0)
        ),
        'Cycle de vie - création' => array(
            'avalider' => array('name' => 'Attente de validation', 'datatype' => 'checkbox', 'locked' => 0,  'init' => null),
            'responsable' => array('name' => 'Responsable enseignement (ROF)', 'datatype' => 'text', 'locked' => 0,  'init' => null), // d'après le ROF
            'demandeurid' => array('name' => 'Demandeur Id', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'datedemande' => array('name' => 'Date demande', 'datatype' => 'datetime', 'locked' => 0,  'init' => null),
            'approbateurpropid' => array('name' => 'Approbateur proposé Id', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'approbateureffid' => array('name' => 'Approbateur effectif Id', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'datevalid' => array('name' => 'Date validation', 'datatype' => 'datetime', 'locked' => 0,  'init' => null),
            'commentairecreation' => array('name' => 'Commentaire creation', 'datatype' => 'text', 'locked' => 0,  'init' => null),
        ),
         'Cycle de vie - gestion' => array(
            'datefermeture' => array('name' => 'Date fermeture', 'datatype' => 'datetime', 'locked' => 0,  'init' => null),
            'dateprevarchivage' => array('name' => 'Date prévis. archivage', 'datatype' => 'datetime', 'locked' => 0,  'init' => null),
            'datearchivage' => array('name' => 'Date archivage', 'datatype' => 'datetime', 'locked' => 0,  'init' => null),
        ),
         'Cycle de vie - Informations techniques' => array(
            'generateur' => array('name' => 'Générateur', 'datatype' => 'text', 'locked' => 0,  'init' => null),
            'modele' => array('name' => 'Modèle', 'datatype' => 'text', 'locked' => 0)
        )
    );
    return $res;
}



/**
 * Updates the newly created categoriesbisrof metadata when this field has just benn created
 */
function update_categoriesbisrof() {
    global $DB;

    $dataid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1rofpathid'), MUST_EXIST);
    $rofpathids = $DB->get_records('custom_info_data', array('fieldid' => $dataid));

    $catbisrofid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1categoriesbisrof'), MUST_EXIST);

    foreach ($rofpathids as $rofpathid) {
        // echo $rofpathid->objectid ." => ". $rofpathid->data ."\n" ;
        $rofpaths = explode(';', $rofpathid->data);
        $categoriesbisrof = array();
        if (count($rofpaths) >= 2) { //rattachements ROF secondaires
            echo "course " . $rofpathid->objectid ." => ";
            // echo $rofpathid->data ."\n    " ;
            foreach (array_slice($rofpaths, 1) as $rofpath) {
                $myrofpath = array_values(array_filter(explode('/', $rofpath)));
                $mycat = rof_rofpath_to_category($myrofpath);
                $categoriesbisrof[] = $mycat;
                // echo "cat=" . $mycat . "  ";
            }
            $data = join(';', $categoriesbisrof);
            echo "up1categoriesbisrof = $data <br />\n";
            $record = $DB->get_record('custom_info_data',
                    array('fieldid' => $catbisrofid, 'objectid' => $rofpathid->objectid, 'objectname' => 'course'));
            $record->data = $data;
            $DB->update_record('custom_info_data', $record);
        }
    }
}

