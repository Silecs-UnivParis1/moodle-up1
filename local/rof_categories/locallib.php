<?php

require_once($CFG->dirroot . "/course/lib.php");

// Classes d'équivalence des diplômes pour les catégories
function equivalentDiplomas() {

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

function highLevelCategories() {
    return
        array(
            array('name' => 'Année 2012-2013', 'idnumber' => '1:2012-2013'),
            array('name' => 'Paris 1', 'idnumber' => '2:UP1'),
        );
}

function createRofCategories($verb=0) {
    global $DB;

    $dipOrdre = array('Licences', 'Masters', 'Doctorats', 'Autres');
    $idxEqv = equivalentDiplomas();
    $hlCategories = highLevelCategories();
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
        $sql = 'SELECT * FROM {rof_program} WHERE rofid IN ' . serializedToSql($component->sub);
        $programs = $DB->get_records_sql($sql);

        $diplomeCat = array();
        foreach ($programs as $program) {
            if ($verb >= 1) echo '.';
            if ($verb >= 2) echo " $program->rofid ";
            $typesimple = typeSimplifie($program->typedip, $idxEqv);
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
function serializedToSql($serial) {
    return "('" . implode("', '", explode(",", $serial)) ."')";
}

/**
 * returns a simplified category for the diploma, ex. 'L2' -> 'Licences'
 * @param string $typedip
 */
function typeSimplifie($typedip, $idxEqv) {
    if (array_key_exists($typedip, $idxEqv)) {
        return $idxEqv[$typedip];
    } else {
        return 'Autres';
    }
}
