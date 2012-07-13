<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class course_wizard_step3_form extends moodleform {
    function definition() {
        global $USER, $CFG, $DB;

        $mform    = $this->_form;

        $mform->addElement('header','general', 'Rattachement de l\'espace de cours');

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);


        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_2', 'étape précédente');
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_4', 'étape suivante');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
