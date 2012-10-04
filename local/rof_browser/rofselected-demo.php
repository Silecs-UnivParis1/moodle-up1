<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $OUTPUT, $PAGE;

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/rof_browser/rofselected-demo.php');
$PAGE->set_title('Démo sélection d\'élément(s) pédagogique(s)');
$PAGE->requires->css(new moodle_url('/local/rof_browser/browser.css'));

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'));
$PAGE->requires->js(new moodle_url('/local/rof_browser/selected.js'));

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo sélection d\'élément(s) pédagogique(s)');

echo '<div id="liste-selected"></div>';
echo '<div class="component-tree">';
echo '</div>';

echo $OUTPUT->footer();
