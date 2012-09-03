<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $OUTPUT, $PAGE;

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/widget_coursesearch/coursesearch-demo.php');
$PAGE->set_title('Démo de la recherche de cours');

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'));
$PAGE->requires->js(new moodle_url('/local/widget_coursesearch/coursesearch.js'));

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo de la recherche de cours');

?>
<div class="widget-coursesearch">
</div>
<?php

echo $OUTPUT->footer();
