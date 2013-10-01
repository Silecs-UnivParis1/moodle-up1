<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class course_wizard_step1_form {
    public function display() {
        global $OUTPUT;
        $url = '/local/crswizard/index.php';

        ?>
<div style="margin:20px;">
    <?php echo get_string('blocHelloS1', 'local_crswizard');?>
</div>
<div align="center" style="margin:50px;">
    <div style="margin:5px;">
        <?php
        /**
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 2, 'wizardcase' => 1)), get_string('wizardcase1', 'local_crswizard'),
                    'get', array('disabled' => 'disabled')
        );
        **/
        ?>
    </div>
    <div style="margin:5px;">
        <?php
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 1, 'wizardcase' => 2)), get_string('wizardcase2', 'local_crswizard'), 'get'
        );
        ?>
    </div>
    <div style="margin:5px;">
        <?php
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 1, 'wizardcase' => 3)), get_string('wizardcase3', 'local_crswizard'), 'get'
        );
        ?>
    </div>
</div>
    <?php
    }
}
