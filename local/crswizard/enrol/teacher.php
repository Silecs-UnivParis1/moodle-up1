<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once('../lib_wizard.php');
require_once('../libaccess.php');

require_login();

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);

wizard_require_permission('creator', $USER->id);

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/crswizard/index.php');
$PAGE->set_title($SESSION->wizard['form_step2']['fullname'] . ': ' . get_string('teacher', 'local_crswizard'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_teachersel/teachersel.js'), true);
$PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('wizardcourse', 'local_crswizard'), 'titlecrswizard');
echo $OUTPUT->box(get_string('enrolteachers', 'local_crswizard'), 'titlecrswizard');
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
            echo '<option value="' . s($r['shortname']) . '">' . format_string(get_string($label, 'local_crswizard')) . '</option>';
        }
        ?>
	</select>
</div>
<br/>
<div id="user-select">
    <div class="widgetselect-panel-left">
        <h3><?php echo get_string('findteacher', 'local_crswizard'); ?></h3>
        <input type="text" class="user-selector" name="something" data-inputname="teacher" size="50"
               placeholder="<?php echo s(get_string('teachername', 'local_crswizard')); ?>" />
    </div>
    <div class="widgetselect-panel-right">
        <h3><?php echo get_string('selectedteacher', 'local_crswizard'); ?></h3>
        <div class="users-selected"></div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    $('#user-select').autocompleteUser({
        urlUsers: '../../mwsgroups/service-users.php',
        wsParams: { affiliation: 1, maxRows: 50 },
        preSelected: <?php echo wizard_preselected_users(); ?>
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
require __DIR__ . '/footer.php';
