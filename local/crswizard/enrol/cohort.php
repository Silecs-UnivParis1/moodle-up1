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

$capcreate = use_crswizard($systemcontext);

$PAGE->set_url('/local/crswizard/enrol/cohort.php');

$PAGE->set_title($SESSION->wizard['form_step2']['fullname'] . ': ' . get_string('cohort', 'local_crswizard'));

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'));
$PAGE->requires->js(new moodle_url('/local/widget_groupsel/groupsel.js'));
$PAGE->requires->js(new moodle_url('/local/crswizard/js/wizard.js'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('wizardcourse', 'local_crswizard'));
echo $OUTPUT->heading( get_string('enrolcohorts', 'local_crswizard'));
echo '<form action="' . $CFG->wwwroot . '/local/crswizard/index.php" method="post">';
?>

<div class="by-widget group-select group-select-internal">
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3><?php echo get_string('findcohort', 'local_crswizard');?></h3>
        <input type="text" class="group-selector" name="something" data-inputname="group" size="50" placeholder="<?php echo get_string('teachername', 'local_crswizard');?>" />
    </div>
    <div style="float: left; width: 45%; height: 60ex; border: 2px solid black; padding: 3px; margin: 2px;">
        <h3><?php echo get_string('selectedcohort', 'local_crswizard');?></h3>
        <div class="group-selected"></div>
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

echo '<input type="hidden" id="cohort" value="1"/>';

echo '<input type="hidden" name="sesskey" value="'.sesskey().'"/>';
echo '<button type="submit" id="etaper" value="retour">'
	. get_string('previousstage', 'local_crswizard') . '</button>';
echo '<button type="submit" id="etapes" value="suivant">'
	. get_string('nextstage', 'local_crswizard') . '</button>';

echo '</div>';
echo '</div>';
echo '</form>';

echo $OUTPUT->footer();
