<?php
require_once('../../config.php');
require_once('libbrowser.php');

// limite cette page au compte admin
require_login();

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
has_capability('enrol/cohort:unenrol', $systemcontext);

$PAGE->set_url('/local/rof_browser/rof_browser.php');
$PAGE->set_title('Components browser');


$PAGE->requires->js('/local/jquery/jquery.js');
$PAGE->requires->js('/local/rof_browser/selected.js');

$PAGE->requires->css('/local/rof_browser/browser.css');

echo $OUTPUT->header();

echo '<div id="liste-selected"></div>';
echo '<div class="component-tree">';

//echo treeComponent();
echo afficheArbre();


echo '</div>';



echo $OUTPUT->footer();
