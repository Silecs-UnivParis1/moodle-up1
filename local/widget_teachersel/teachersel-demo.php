<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/widget_groupsel/teachersel-demo.php');
$PAGE->set_title('Démo du sélecteur d\'utilisateurs');

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'));
$PAGE->requires->js(new moodle_url('/local/widget_teachersel/teachersel.js'));

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
<div class="by-widget teacher-select">
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Rechercher un enseignant</h3>
        <input type="text" class="teacher-selector" name="something" data-inputname="teacher" size="50" placeholder="Libellé de nom d'utilisateur" />
    </div>
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Enseignants sélectionnés</h3>
        <div class="teachers-selected"></div>
    </div>
</div>
<?php

echo $OUTPUT->footer();
