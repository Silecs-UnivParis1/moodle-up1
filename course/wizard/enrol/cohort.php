<?php
//echo "chien";

//require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('../../../config.php');
$id = required_param('id', PARAM_INT); // course id
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

if ($course->id == SITEID) {
    redirect(new moodle_url('/'));
}

require_login();
if(!isset($SESSION->wizard['idcourse']) || $SESSION->wizard['idcourse']!=$id) {
	require_login($course);
	// ou redirect(new moodle_url('/'));
}

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
has_capability('moodle/course:request', $systemcontext);

$PAGE->set_url('/course/wizard/enrol/cohort.php');

$PAGE->set_title($PAGE->course->fullname.': '.'Cohortes');
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'));
$PAGE->requires->js(new moodle_url('/local/widget_groupsel/groupsel.js'));
$PAGE->requires->js(new moodle_url('/course/wizard/js/wizard.js'));

echo $OUTPUT->header();
echo $OUTPUT->heading('Inscrire des groupes');
echo '<form action="' . $CFG->wwwroot . '/course/wizard/index.php" method="post">';
?>

<div class="by-widget group-select group-select-internal">
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Rechercher un groupe d'étudiants</h3>
        <input type="text" class="group-selector" name="something" data-inputname="group" size="50" placeholder="Libellé de groupe ou nom d'étudiant" />
    </div>
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3>Groupes sélectionnés</h3>
        <div class="group-selected"></div>
    </div>
</div>
<?php

$stepin = 5;

echo '<div align="center" style="margin:50px; clear:both"><div class="buttons">';
echo '<input type="hidden" name="courseid" value="'.$id.'"/>';
echo '<input type="hidden" name="stepin" value="'.$stepin.'"/>';

echo '<input type="hidden" name="stepogo-manual" value="5"/>';
echo '<input type="hidden" name="stepgo-cohort" value="7"/>';
echo '<input type="hidden" name="stepgo" value=""/>';

echo '<input type="hidden" name="idenrolment" value=""/>';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'"/>';
echo '<button type="submit" id="etapep" value="open">Etape précédente</button>';
echo '<button type="submit" id="etapes" value="open">Termine l\'inscription des groupes</button>';

echo '</div>';
echo '</div>';
echo '</form>';

echo $OUTPUT->footer();
