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
<p style="margin:20px;">
    Bienvenue dans l'assistant d'ouverture l'espace de cours.
    Suite du texte d'aide et de conseil. Suite du texte d'aide et de conseil. Suite du texte d'aide et de conseil.
    Suite du texte d'aide et de conseil. Suite du texte d'aide et de conseil. Suite du texte d'aide et de conseil.
</p>
<div align="center" style="margin:50px;">
    <div style="margin:5px;">
        <?php
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 2, 'wizardcase' => 1)), "Un élément pédagogique dans lequel j'enseigne",
                    'get', array('disabled' => 'disabled')
        );
        ?>
    </div>
    <div style="margin:5px;">
        <?php
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 2, 'wizardcase' => 2)), "Un autre élément pédagogique de l'offre de formation", 'get'
        );
        ?>
    </div>
    <div style="margin:5px;">
        <?php
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 2, 'wizardcase' => 3)), get_string('anotherneed', 'local_crswizard'), 'get'
        );
        ?>
    </div>
</div>
    <?php
    }
}
