<?php
/**
 * @package    local
 * @subpackage up1_notification
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//It must be included from a Moodle page
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class local_up1_notification_notification_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;

        // hidden elements
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'destinataire', '', get_string('item_repondant', 'local_up1_notification'), 1);
        $radioarray[] = $mform->createElement('radio', 'destinataire', '', get_string('item_nonrepondant', 'local_up1_notification'), 0);
        $mform->addGroup($radioarray, 'radioar', get_string('label_destinataire', 'local_up1_notification'), array(' ', ' '), false);

        $msgarray = array();
        $msgarray[] = $mform->createElement('radio', 'message', '', get_string('item_relance', 'local_up1_notification'), 1);
        $msgarray[] = $mform->createElement('radio', 'message', '', get_string('item_invitation', 'local_up1_notification') , 0);
        $mform->addGroup($msgarray, 'msgar', get_string('label_message', 'local_up1_notification'), array(' ', ' '), false);

        $mform->addElement('text', 'msginvitationsubject', get_string('subject', 'local_up1_notification'), 'maxlength="254" size="50" class="obligatoire"');
        $mform->setType('msginvitationsubject', PARAM_MULTILANG);
        $mform->setDefault('msginvitationsubject', get_string('msginvitationsubject', 'local_up1_notification'));

        $mform->addElement('textarea', 'msginvitationbody', get_string('body', 'local_up1_notification'), array('rows' => 15,
            'cols' => 80));
        $mform->setType('msginvitationbody', PARAM_TEXT);
        $mform->setDefault('msginvitationbody', get_string('msginvitationbody', 'local_up1_notification'));


        $mform->addElement('text', 'msgrelancesubject', get_string('subject', 'local_up1_notification'), 'maxlength="254" size="50"');
        $mform->setType('msgrelancesubject', PARAM_MULTILANG);
        $mform->setDefault('msgrelancesubject', get_string('msgrelancesubject', 'local_up1_notification'));

        $mform->addElement('textarea', 'msgrelancebody', get_string('body', 'local_up1_notification'), array('rows' => 15,
            'cols' => 80));
        $mform->setType('msgrelancebody', PARAM_TEXT);
        $mform->setDefault('msgrelancebody', get_string('msgrelancebody', 'local_up1_notification'));

        $mform->addElement('checkbox', 'copie', get_string('label_copie', 'local_up1_notification'));

        //-------------------------------------------------------------------------------
        // buttons
      //  $this->add_action_buttons(true, 'Envoyer');

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('label_envoyer', 'local_up1_notification'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');


    }

     public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['message']==0 && empty($data['msginvitationsubject'])) {
            $errors['msginvitationsubject'] = get_string('noemptysubject', 'local_up1_notification');
        }
        if ($data['message']==0 && empty($data['msginvitationbody'])) {
            $errors['msginvitationbody'] = get_string('noemptybody', 'local_up1_notification');
        }
        if ($data['message']==1 && empty($data['msgrelancesubject'])) {
            $errors['msgrelancesubject'] = get_string('noemptysubject', 'local_up1_notification');
        }
        if ($data['message']==1 && empty($data['msgrelancebody'])) {
            $errors['msgrelancebody'] = get_string('noemptybody', 'local_up1_notification');
        }

        return $errors;
    }
}

