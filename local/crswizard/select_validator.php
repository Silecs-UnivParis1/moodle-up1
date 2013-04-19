<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('lib_wizard.php');
require_once('libaccess.php');

require_login();

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);

wizard_require_permission('creator', $USER->id);

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/crswizard/index.php');
$PAGE->set_title('Select validator');
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_teachersel/teachersel.js'), true);
$PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('wizardcourse', 'local_crswizard'), 'titlecrswizard');
echo $OUTPUT->box(get_string('selectvalidator', 'local_crswizard'), 'titlecrswizard');
echo $OUTPUT->box(get_string('bockhelpE3validator', 'local_crswizard'), '');


echo '<form action="' . $CFG->wwwroot . '/local/crswizard/index.php" method="post" class="mform">';
?>

<div class="fitem">
<fieldset class="clearfix" id="categoryheader">
    <legend class="ftoggler" ><?php echo get_string('selectedvalidator', 'local_crswizard');?></legend>
    <div class="fcontainer clearfix">
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
    </div>
</fieldset>
</div>

<?php
$tabinfo = array();
$tabinfo['username'] = fullname($USER);
$tabinfo['userlogin'] = $USER->username;
$tabinfo['courserequestdate'] = date('d-m-Y');
?>

<div class="fitem">
<fieldset class="clearfix" id="categoryheader">
    <legend class="ftoggler" ><?php echo get_string('managecourseblock', 'local_crswizard');?></legend>
    <div class="fcontainer clearfix">
        <?php
            foreach ($tabinfo as $key => $value) {
                echo '<div class="fitem">';
                echo '<div class="fitemtitle">';
                echo '<div class="fstaticlabel">';
                echo '<label>' . get_string($key, 'local_crswizard') . '</label>';
                echo '</div>';
                echo '</div>';
                echo '<div class="felement fstatic">'.$value.'</div>';
                echo '</div>';
            }
        ?>
    </div>
</fieldset>
</div>

<?php
$listCohort = trim(get_config('local_crswizard', 'cohorts_cap_validator'));
$listCohort = preg_replace('/\s+,\s+/', ',', $listCohort);
?>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    $('#user-select').autocompleteUser({
        urlUsers: '../mwsgroups/service-users.php',
        labelDetails: 'approbateur',
        maxSelected: 1,
        wsParams: { exclude: '<?php echo $USER->username;?>',
            cohorts: '<?php echo $listCohort;?>',
            affiliation: 1,
            maxRows: 50 },
        preSelected: <?php echo wizard_preselected_validators();?>
    });
});
//]]>
</script>

<?php
require __DIR__ . '/enrol/footer.php';
