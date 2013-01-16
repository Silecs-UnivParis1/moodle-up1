<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('lib_wizard.php');

require_login();

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);

require_capabilities($systemcontext);

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/crswizard/select_validator.php');
$PAGE->set_title('Select validator');
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_teachersel/teachersel.js'), true);
$PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('wizardcourse', 'local_crswizard'));
echo $OUTPUT->heading(get_string('selectvalidator', 'local_crswizard'));
echo $OUTPUT->box(get_string('bockhelpE3validator', 'local_crswizard'), '');


echo '<form action="' . $CFG->wwwroot . '/local/crswizard/index.php" method="post">';
?>

<br/>
<div id="user-select">
    <div class="widgetselect-panel-left">
        <h3><?php echo get_string('findvalidator', 'local_crswizard'); ?></h3>
        <input type="text" class="user-selector" name="something" data-inputname="teacher" size="50"
               placeholder="<?php echo s(get_string('validatorname', 'local_crswizard')); ?>" />
    </div>
    <div class="widgetselect-panel-right">
        <h3><?php echo get_string('selectedvalidator', 'local_crswizard'); ?></h3>
        <div class="users-selected"></div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    $('#user-select').autocompleteUser({
        urlUsers: '../mwsteachers/service-search.php',
        preSelected: <?php echo wizard_preselected_validators();?>
    });
});
//]]>
</script>

<?php
require __DIR__ . '/enrol/footer.php';
