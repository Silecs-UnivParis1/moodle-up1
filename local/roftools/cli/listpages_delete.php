<?php

/* @var $DB moodle_database */

define('CLI_SCRIPT', true);

require dirname(dirname(dirname(__DIR__))) . '/config.php';
require __DIR__ . '/../locallib.php';

echo "Delete existing list pages for ROF course categories... \n";

$cms = $DB->get_records('course_modules', array('course' => 1, 'module' => 15));
foreach ($cms as $cm) {
    $DB->delete_records('page', array('id' => $cm->instance));
    delete_course_module($cm->id);
    delete_mod_from_section($cm->id, $cm->section);
}

echo "OK.\n";
