<?php

defined('MOODLE_INTERNAL') || die;

function step1_form() {
	global $OUTPUT;
	$formstep1 ='';
    $formstep1 .= $OUTPUT->single_button(
           new moodle_url('',
                    array('step' => 2, 'wizardcase' => 1)),
                'Un élément pédagogique dans lequel j\'enseigne',
                'post'
            );
    $formstep1 .= $OUTPUT->single_button(
           new moodle_url('',
                    array('step' => 2, 'wizardcase' => 2)),
                'Un autre élément pédagogique de l\'offre de formation',
                'post'
            );
	$formstep1 .= $OUTPUT->single_button(
           new moodle_url('/course/wizard/index.php',
                    array('stepin' => 1, 'stepgo_2' => 2,'wizardcase' => 3)),
                'Un autre besoin',
                'post'
            );
    return $formstep1;
}


