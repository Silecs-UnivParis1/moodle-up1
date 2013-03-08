<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(__DIR__ . '/lib.php');

$node = optional_param('node', '/cat0', PARAM_RAW);

/* @var $PAGE page_base */
global $PAGE, $OUTPUT;

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/mwscoursetree/coursetable-demo.php');
$PAGE->set_title("Démo table et liste des cours");
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading("Démo table et liste des cours");

if ($node == '/cat0') {
    echo $OUTPUT->box("Vous devriez indiquer en fin d'URL un paramètre node. "
    . "Par exemple : .../coursetable-demo.php?node=/cat2060/02/UP1-PROG26751");
}
echo "<p></p>";

$coursetree = new course_tree();
$rofcourselist = new rof_tools($coursetree);

echo "<h2>Table des cours</h2>";
$table  = $rofcourselist->html_course_table($node);
echo $table;

echo "<h2>Liste des cours</h2>";
$list = $rofcourselist->html_course_list($node);
echo $list;


echo $OUTPUT->footer();
