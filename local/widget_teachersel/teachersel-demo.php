<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/widget_groupsel/teachersel-demo.php');
$PAGE->set_title('Démo du sélecteur d\'utilisateurs');

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_teachersel/teachersel.js'), true);

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo du sélecteur d\'utilisateurs');

?>

<div class="role">
<h3>Rôle</h3>
	<select name="role" size="1" id="roleteacher">
		<option value="editingteacher">Enseignant</option>
		<option value="teacher">Enseignant non éditeur</option>
	</select>
</div>
<br/>
<div id="user-select">
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Rechercher un enseignant</h3>
        <input type="text" class="user-selector" name="something" data-inputname="teacher" size="50" placeholder="Libellé de nom d'utilisateur" />
    </div>
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Enseignants sélectionnés</h3>
        <div class="users-selected"></div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    $('#user-select').autocompleteUser({
        urlUsers: '../mwsteachers/service-search.php',
        minLength: 4,
    });

    $('#roleteacher').on('change', function() {
        var sel = $('#roleteacher > option:selected').text();
        $('#user-select').data('autocompleteUser').settings.labelDetails = sel;
    });
    $('#roleteacher').change();
});
//]]>
</script>

<?php

echo $OUTPUT->footer();
