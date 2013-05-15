<?php
/**
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . "/course/lib.php");
// for listpages...
require_once($CFG->dirroot . "/lib/resourcelib.php");
require_once($CFG->dirroot . "/mod/page/lib.php");

// Classes d'équivalence des diplômes pour les catégories
function equivalent_diplomas() {

    $diplomaEqv = array(
        'Licences' => 'L1,L2,L3,DP',
        'Masters' => 'M1,E1,M2,E2,30',
        'Doctorats' => '40',
        'Autres' => 'U2,U3,U4,U5,U6,PG,PC,PA,P1'
    );

    foreach ($diplomaEqv as $eqv => $strdiplomas) {
        $diplomas = explode(',', $strdiplomas);
        foreach ($diplomas as $diploma) {
            $idxEqv[$diploma] = $eqv;
        }
    }
    return $idxEqv;
}

function high_level_categories() {
    return
        array(
            array('name' => 'Année 2012-2013', 'idnumber' => '1:2012-2013'),
            array('name' => 'Paris 1', 'idnumber' => '2:UP1'),
        );
}

function create_rof_categories($verb=0) {
    global $DB;

    $dipOrdre = array('Licences', 'Masters', 'Doctorats', 'Autres');
    $idxEqv = equivalent_diplomas();
    $hlCategories = high_level_categories();
    $parentid=0;

    // Crée les deux niveaux supérieurs
    foreach ($hlCategories as $hlcat) {
        $newcategory = new stdClass();
        $newcategory->name = $hlcat['name'];
        $newcategory->idnumber = $hlcat['idnumber'];
        $newcategory->parent = $parentid;

        $category = create_course_category($newcategory);
        $parentid = $category->id;
        fix_course_sortorder();
     }

    $rofRootId = $parentid;

    // Crée les niveaux issus du ROF : composantes (3) et types-diplômes simplifiés (4)
    $components = $DB->get_records('rof_component');
    foreach ($components as $component) {
        if ($verb > 0) echo "\n$component->number $component->name \n";
        $newcategory = new stdClass();
        $newcategory->name = $component->name;
        $newcategory->idnumber = '3:' . $component->number;
        $newcategory->parent = $rofRootId;
        $category = create_course_category($newcategory);
        $compCatId = $category->id;
        fix_course_sortorder();
        $sql = 'SELECT * FROM {rof_program} WHERE rofid IN ' . serialized_to_sql($component->sub);
        $programs = $DB->get_records_sql($sql);

        $diplomeCat = array();
        foreach ($programs as $program) {
            if ($verb >= 1) echo '.';
            if ($verb >= 2) echo " $program->rofid ";
            $typesimple = type_simplifie($program->typedip, $idxEqv);
            $diplomeCat[$typesimple] = TRUE;
        } // $programs

        foreach ($dipOrdre as $classeDiplome) {
            if ( isset($diplomeCat[$classeDiplome]) ) {
                $newcategory = new stdClass();
                $newcategory->name = $classeDiplome;
                $newcategory->idnumber = '4:' . $component->number .'/'. $classeDiplome;
                $newcategory->parent = $compCatId;
                if ($verb >= 1) echo " $classeDiplome";
                $category = create_course_category($newcategory);
                // $progCatId = $category->id;
                fix_course_sortorder();
            }
        } // $dipOrdre
        if ($verb >= 2) echo "\n";
    } // $components

}

/**
 * turns a serialized list into one suitable for SQL IN request, ex.
 * "A,B,C" -> "'A','B','C'"
 */
function serialized_to_sql($serial) {
    return "('" . implode("', '", explode(",", $serial)) ."')";
}

/**
 * returns a simplified category for the diploma, ex. 'L2' -> 'Licences'
 * @param string $typedip
 */
function type_simplifie($typedip, $idxEqv) {
    if (array_key_exists($typedip, $idxEqv)) {
        return $idxEqv[$typedip];
    } else {
        return 'Autres';
    }
}




function listpages_templates() {
    $tpl['name'] = 'Espaces de cours de {compname} ({vue})';
    $tpl['intro'] = '<p>L\'espace que vous cherchez n\'est pas listé sur cette page ? ' .
        'Avez-vous pensé à le trouver du côté des ' .
        '“<a title="EPI" href="http://epi.univ-paris1.fr">anciens EPI</a>” ?</p>';

    $tpl['contenttab'] = '<div class="tabtree">'
        . '<ul class="tabrow0">'
        . '<li class="first onerow here selected"><a class="nolink"><span>{vue}</span></a>'
        . '<div class="tabrow1 empty"></div>'
        . '</li>'
        . '<li>{sisterpagelink}</li>'
        . '</ul>'
        . '</div>';
    $tpl['contentmain'] = '<h3>Espaces de cours de {niveaulmda}</h3>'
        . '<p>[courselist format={format} node={node}]</p>'
        . '<p> </p>';
    $tpl['contentfoot'] = ''
        . '<p><span style="font-size: x-small;">Les Espaces pédagogiques interactifs proposent des informations et des ressources pédagogiques en accompagnement des cours. Les enseignants les publient à l’intention des étudiants inscrits aux enseignements concernés pour guider leur travail personnel, approfondir certaines questions, préparer les travaux et devoirs ou encore réviser les examens.</span></p>'
        . '<p><span style="font-size: small;"><span style="font-size: x-small;">Les documents, quelle que soit leur nature, publiés dans les Espaces pédagogiques interactifs de l\'Université Paris 1 Panthéon-Sorbonne, sont protégés par le <a title="Code de la propriété intellectuelle - Legifrance" href="http://www.legifrance.gouv.fr/affichCode.do?cidTexte=LEGITEXT000006069414">Code de la propriété intellectuelle</a> (Article L 111-1). Toute reproduction partielle ou totale sans autorisation écrite de l\'auteur est interdite, sauf celles prévues à l\'article L 122-5 du <a title="Code de la propriété intellectuelle - Legifrance" href="http://www.legifrance.gouv.fr/affichCode.do?cidTexte=LEGITEXT000006069414">Code de la propriété intellectuelle</a>.</span><a href="http://www.celog.fr/cpi/lv1_tt2.htm"><br /> </a></span></p>';
    return $tpl;

}

function listpages_create() {
    global $DB;

    $rootcat = $DB->get_field('course_categories', 'id', array('idnumber' => '2:UP1', 'depth' => 2), MUST_EXIST);

    $itercategories = $DB->get_records('course_categories', array('visible' => 1, 'parent' => $rootcat));
    foreach ($itercategories as $category) {
        echo "Creating page for " . $category->name . "\n";
        $created = listpages_create_for($category);
    }
}

function listpages_create_for($category) {
    global $DB;

    $vues = array(
        'tableau' => array('name' => 'vue tableau', 'format' => 'table', 'sister' => +1),
        'arborescence' => array('name' => 'vue arborescence', 'format' => 'tree', 'sister' => -1),
    );

    $template = listpages_templates();
    $catniveaux = $DB->get_records('course_categories', array('parent' => $category->id));

    foreach ($vues as $vue) {
        echo "    " . $vue['name'] . "\n";

        $pagedata = new stdClass();
        $pagedata->course = 1;
        $pagedata->introformat = FORMAT_MOODLE; //1
        $pagedata->legacyfiles = 0;
        $pagedata->display = RESOURCELIB_DISPLAY_AUTO; //5
        $pagedata->revision = 1;
        // $pagedata->timemodified = time();

        $pagedata->name = str_replace('{compname}', $category->name, $template['name']);
        $pagedata->name = str_replace('{vue}', $vue['name'], $pagedata->name);
        $pagedata->intro = $template['intro'];

        $pagedata->content = str_replace('{vue}', $vue['name'], $template['contenttab']);
        //todo sisterpagelink

        foreach ($catniveaux as $niveaulmda) {
            $node = '/cat' . $niveaulmda->id . '/' . substr($category->idnumber, 2);
            $nivcontent = str_replace('{niveaulmda}', $category->name, $template['contentmain']);
            $nivcontent = str_replace('{node}', $node, $nivcontent);
            $nivcontent = str_replace('{node}', $node, $nivcontent);
            $pagedata->content .= $nivcontent;
        }
        $pagedata->content .= $template['contentfoot'];

        $pageid = page_add_instance($pagedata);
    }
}

