<?php

require_once($CFG->dirroot . "/course/lib.php");

function highLevelCategories() {
    return
        array(
            array('name' => 'Année 2012-2013', 'idnumber' => '1:2012-2013'),
            array('name' => 'Paris 1', 'idnumber' => '2:UP1'),
        );
}

function createRofCategories($verb=0) {
    global $DB;

    $hlCategories = highLevelCategories();
    $parentid=0;

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

        foreach ($programs as $program) {
            if ($verb > 1) echo "  $program->rofid";
            $newcategory = new stdClass();
            $newcategory->name = $program->name;
            $newcategory->idnumber = '4:' . $component->number .'/'. $program->rofid;
            $newcategory->parent = $compCatId;
            $category = create_course_category($newcategory);
            $progCatId = $category->id;
            fix_course_sortorder();
            $sql = 'SELECT * FROM {rof_program} WHERE rofid IN ' . serializedToSql($program->sub);
            $subPrograms = $DB->get_records_sql($sql);

            foreach ($subPrograms as $subProgram) {
                if ($verb > 2) echo ".";
                $newcategory = new stdClass();
                $newcategory->name = $subProgram->name;
                $newcategory->idnumber = '5:' . $component->number .'/'. $program->rofid .'/'. $subProgram->rofid ;
                $newcategory->parent = $progCatId;
                $category = create_course_category($newcategory);
                fix_course_sortorder();

            } // $subprograms

        } // $programs

    } // $components

}

/*
 * turns a serialized list into one suitable for SQL IN request, ex.
 * "A,B,C" -> "'A','B','C'"
 */
function serializedToSql($serial) {
    return "('" . implode("', '", explode(",", $serial)) ."')";
}
