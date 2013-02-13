<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $OUTPUT, $PAGE;

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/rof_browser/rofselected-demo.php');
$PAGE->set_title('Démo sélection d\'élément(s) pédagogique(s)');
$PAGE->requires->css(new moodle_url('/local/rof_browser/browser.css'));

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/rof_browser/selected.js'), true);

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo sélection d\'élément(s) pédagogique(s)');
?>

<div>
<div class="by-widget">
	<h3>Rechercher un élément pédagogique</h3>
	<div class="item-select" id="choose-item-select"> <!-- ajout class readonly : pas de sélection possible -->
	</div>
</div>
<div class="block-item-selected">
	<h3>Éléments pédagogiques sélectionnés</h3>
	<div id="items-selected">
        <div id="items-selected1"><span>Rattachement de référence</span></div>
        <div id="items-selected2"><span>Rattachement(s) secondaire(s)</span>
        </div>
    </div>
</div>

</div>

<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function () {
        $('#items-selected').autocompleteRof({
         // readonly: true, //par défaut false, interdit la suppression des éléments presélectionnés
         //preSelected: [{"label": "Licence Administration publique", "value": "UP1-PROG35376", "path": "UP1-PROG35376", "nature": "p"}]
        });
    });
    //]]>
</script>

<?php
echo $OUTPUT->footer();
