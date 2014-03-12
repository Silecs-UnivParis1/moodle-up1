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

        $mform->addElement('textarea', 'complement', null, array('rows' => 3,
            'cols' => 80));
        $mform->setType('complement',PARAM_RAW);

        $htmlinfo = '<br/><p class="notificationlabel">' . $this->_customdata['coursepath'] . '<br/>'
            . $this->_customdata['urlactivite'] . '</p>';
        $mform->addElement('html', $htmlinfo);

        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true,  get_string('label_envoyer', 'local_up1_notificationcourse'));
    }
}
