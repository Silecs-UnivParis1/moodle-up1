<?php

require_once('../../../config.php');

require_login();

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
has_capability('moodle/course:request', $systemcontext);

$PAGE->set_url('/course/wizard/enrol/teacher.php');

$PAGE->set_title($SESSION->wizard['form_step2']['fullname'] . ': '.'enseignant');
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'));
$PAGE->requires->js(new moodle_url('/local/widget_teachersel/teachersel.js'));
$PAGE->requires->js(new moodle_url('/course/wizard/js/wizard.js'));

echo $OUTPUT->header();
echo $OUTPUT->heading(' Inscrire des enseignants');
echo '<form action="' . $CFG->wwwroot . '/course/wizard/index.php" method="post">';
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

$stepin = $SESSION->wizard['navigation']['stepin'];
$suite = $SESSION->wizard['navigation']['suite'];
$retour = $SESSION->wizard['navigation']['retour'];

echo '<div align="center" style="margin:50px; clear:both"><div class="buttons">';
echo '<input type="hidden" name="stepin" value="'.$stepin.'"/>';

echo '<input type="hidden" name="stepgo-retour" value="'.$retour.'"/>';
echo '<input type="hidden" name="stepgo-suite" value="'.$suite.'"/>';
echo '<input type="hidden" id="stepgo" name="stepgo_" value=""/>';

echo '<input type="hidden" name="sesskey" value="'.sesskey().'"/>';
echo '<button type="submit" id="etaper" value="retour">Etape précédente</button>';
echo '<button type="submit" id="etapes" value="suivant">Etape suivante</button>';

echo '</div>';
echo '</div>';
echo '</form>';

echo $OUTPUT->footer();
