<?php

require_once($CFG->dirroot . "/course/lib.php");

function highLevelCategories() {
    return
        array(
            array('name' => 'Année 2012-2013', 'idnumber' => '1:2012-2013'),
            array('name' => 'Paris 1', 'idnumber' => '2:UP1'),
        );
}

function createRofCategories() {
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
}