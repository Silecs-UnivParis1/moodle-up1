<?php
if ($SESSION->wizard['wizardcase'] == 3) {
    $stepback = 3;
} else {
    $stepback = 2;
}
$stepin = $SESSION->wizard['navigation']['stepin'];
$stepnext = $SESSION->wizard['navigation']['suite'];

?>
<div style="margin:50px; clear:both; text-align: center;">
    <input type="hidden" name="stepin" value="<?php echo $stepin; ?>"/>
    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>"/>

    <div class="buttons">
        <span class="previousstage">
            <?php
            echo $OUTPUT->action_link(
                    new moodle_url($SESSION->wizard['wizardurl'], array('stepin' => $stepback)),
                    get_string('previousstage', 'local_crswizard')
            );
            ?>
        </span>
        <button type="submit" id="etapes" name="step" value="">
            <?php echo get_string('nextstage', 'local_crswizard'); ?>
        </button>

    </div>
</div>

</form>
<?php
echo $OUTPUT->footer();
