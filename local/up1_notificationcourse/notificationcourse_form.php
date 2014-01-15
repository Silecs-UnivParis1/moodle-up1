<?php
/**
 * @package    local
 * @subpackage up1_notificationcourse
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//It must be included from a Moodle page
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class local_up1_notificationcourse_notificationcourse_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;

        // hidden elements
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'mod');
        $mform->setType('mod', PARAM_ALPHA);


        $mform->addElement('text', 'msgsubject', get_string('subject', 'local_up1_notificationcourse'), 'maxlength="254" size="50" class="obligatoire"');
        $mform->setType('msgsubject', PARAM_MULTILANG);
        $mform->setDefault('msgsubject', get_string('msgsubject', 'local_up1_notificationcourse'));

        $mform->addElement('textarea', 'msgbody', get_string('body', 'local_up1_notificationcourse'), array('rows' => 15,
            'cols' => 80));
        $mform->setType('msgbody', PARAM_TEXT);
        $mform->setDefault('msgbody', get_string('msgbody', 'local_up1_notificationcourse'));

        //-------------------------------------------------------------------------------
        // buttons
      //  $this->add_action_buttons(true, 'Envoyer');

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('label_envoyer', 'local_up1_notificationcourse'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');


    }

     public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['msgsubject'])) {
            $errors['msgsubject'] = get_string('noemptysubject', 'local_up1_notificationcourse');
        }
        if (empty($data['msgbody'])) {
            $errors['msgbody'] = get_string('noemptybody', 'local_up1_notificationcourse');
        }

        return $errors;
    }
}

