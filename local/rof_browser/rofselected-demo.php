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
?>
<div>
<div class="by-widget">
	<h3>Rechercher un élément pédagogique</h3>
	<div class="item-select">
	</div>
</div>
<div class="block-item-selected">
	<h3>Éléments pédagogiques sélectionnés</h3>
	<div id="items-selected"></div>
</div>

</div>
<?php
echo $OUTPUT->footer();
