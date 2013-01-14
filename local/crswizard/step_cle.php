<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

class course_wizard_step_cle extends moodleform {

    function definition() {
        global $SESSION, $OUTPUT;

        $mform = $this->_form;

//--------------------------------------------------------------------------------
        $message = get_string('messagekeyblock1', 'local_crswizard');
        $messagecle = get_string('messagekeyblock2', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $message, array('class' => 'fitem')));

        $tabCle = array('u' => get_string('studentkey', 'local_crswizard'),
            'v' => get_string('guestkey', 'local_crswizard')
        );

        foreach ($tabCle as $c => $label) {

            $mform->addElement('header', 'general' . $c, $label);
            $mform->addElement('html', html_writer::tag('div', $messagecle, array('class' => 'fitem')));
            $mform->addElement('passwordunmask', 'password' . $c, get_string('enrolkey', 'local_crswizard'));
            $mform->addHelpButton('password' . $c, 'password', 'enrol_self');

            $mform->addElement('date_selector', 'enrolstartdate' . $c, get_string('enrolstartdate', 'enrol_self'), array('optional' => true));
            $mform->addHelpButton('enrolstartdate' . $c, 'enrolstartdate', 'enrol_self');
            $mform->setDefault('enrolstartdate' . $c, time() + 3600 * 24);

            $mform->addElement('date_selector', 'enrolenddate' . $c, get_string('enrolenddate', 'enrol_self'), array('optional' => true));
            $mform->addHelpButton('enrolenddate' . $c, 'enrolenddate', 'enrol_self');
            $mform->setDefault('enrolenddate' . $c, 0);
        }

//--------------------------------------------------------------------------------
        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 6);

//--------------------------------------------------------------------------------

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url('/local/crswizard/index.php', array('stepin' => 5)),
            get_string('previousstage', 'local_crswizard'), array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement('submit', 'stepgo_7', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

}
