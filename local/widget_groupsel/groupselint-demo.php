<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/widget_groupsel/groupsel-demo.php');
$PAGE->set_title('Démo du sélecteur de groupes');

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_groupsel/groupsel.js'), true);

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo du sélecteur de cohortes Moodle (interne)');

?>
<div id="group-select">
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Rechercher un groupe d'étudiants</h3>
        <input type="text" class="group-selector" name="something" size="50" placeholder="Libellé de groupe ou nom d'étudiant" />
    </div>
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Groupes sélectionnés</h3>
        <div class="group-selected"></div>
    </div>
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    $('#group-select').autocompleteGroup({
        urlGroups: '../mwsgroups/service-search.php',
        urlUserToGroups: '<?php echo new moodle_url('/local/mwsgroups/service-userGroups.php'); ?>',
        minLength: 4,
        wsParams: { maxRows: 10, filter: 'none' }
    });
});
//]]>
</script>
<?php

echo $OUTPUT->footer();
