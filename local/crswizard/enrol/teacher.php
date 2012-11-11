<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once('../lib_wizard.php');

require_login();

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);

//has_capability('moodle/course:request', $systemcontext);
$capcreate = use_crswizard($systemcontext);

$PAGE->set_url('/local/crswizard/enrol/teacher.php');

$PAGE->set_title($SESSION->wizard['form_step2']['fullname'] . ': ' . get_string('teacher', 'local_crswizard'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_teachersel/teachersel.js'), true);
$PAGE->requires->js(new moodle_url('/local/crswizard/js/wizard.js'), true);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('wizardcourse', 'local_crswizard'));
echo $OUTPUT->heading(get_string('enrolteachers', 'local_crswizard'));
echo $OUTPUT->heading(get_string('blocktitleE4', 'local_crswizard'), 4, '');
echo $OUTPUT->box(get_string('bockhelpE4', 'local_crswizard'), '');

echo '<form action="' . $CFG->wwwroot . '/local/crswizard/index.php" method="post">';
?>

<div class="role">
<h3><?php echo get_string('role', 'local_crswizard');?></h3>
	<select name="role" size="1" id="roleteacher">
	<?php
		$myconfig = new my_elements_config();
		$labels = $myconfig->role_teachers;
		$roles = wizard_role($labels);
		foreach ($roles as $r) {
			$label = $r['name'];
			if (array_key_exists($r['shortname'], $labels)) {
				$label = $labels[$r['shortname']];
			}
			echo '<option value="' . $r['shortname'] . '">' . get_string($label, 'local_crswizard') . '</option>';
		}
	?>
	</select>
</div>
<br/>
<div id="user-select">
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3><?php echo get_string('findteacher', 'local_crswizard');?></h3>
        <input type="text" class="user-selector" name="something" data-inputname="teacher" size="50" placeholder="<?php echo get_string('teachername', 'local_crswizard');?>" />
    </div>
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3><?php echo get_string('selectedteacher', 'local_crswizard');?></h3>
        <div class="users-selected"></div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    $('#user-select').autocompleteUser({
        urlUsers: '../../mwsteachers/service-search.php',
        preSelected: [<?php echo wizard_preselected_users(); ?>]
    });

    $('#roleteacher').on('change', function() {
        var sel = $(this).val();
        var sellabel = $('#roleteacher > option:selected').text();
        $('#user-select').data('autocompleteUser').settings.fieldName = 'user[' + sel + ']';
        $('#user-select').data('autocompleteUser').settings.labelDetails = sellabel;
    });
    $('#roleteacher').change();
});
//]]>
</script>

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
echo '<button type="submit" id="etaper" value="retour">'
	. get_string('previousstage', 'local_crswizard') . '</button>';
echo '<button type="submit" id="etapes" value="suivant">'
	. get_string('nextstage', 'local_crswizard') . '</button>';

echo '</div>';
echo '</div>';
echo '</form>';

echo $OUTPUT->footer();
