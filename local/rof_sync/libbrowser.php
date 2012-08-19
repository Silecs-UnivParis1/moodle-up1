<?php

function getRofComponents() {
    global $DB;
    $components = $DB->get_records('rof_component');
    return $components;
}

function getRofFathersPrograms() {
    global $DB;
    $programs = $DB->get_records('rof_program', array('parentid' => NULL), 'compnumber');
    return $programs;
}

function getArrayRofFathersPrograms() {
	$tabProgams = array();
	$programs = getRofFathersPrograms();
	foreach ($programs as $p) {
		$tabProgams[$p->compnumber][$p->rofid] = $p;
	}
	return $tabProgams;
}
