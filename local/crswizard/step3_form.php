<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

class course_wizard_step3_form extends moodleform {

    protected $custominfo;

    function definition() {
        global $USER, $DB, $SESSION;

        $tabfreeze = array();
        $mform    = $this->_form;

        $mform->addElement('header','general', get_string('categoryblockE3', 'local_crswizard'));

		$myconfig = new my_elements_config();
		if (isset($SESSION->wizard['form_step2']['category'])) {
			$idcat = (int) $SESSION->wizard['form_step2']['category'];
            $tabcategories = get_list_category($idcat);
            foreach ($tabcategories as  $key => $nom) {
				$type = $myconfig->categorie_cours[$key];
				$mform->addElement('text', $type, $type, 'maxlength="40" size="20", disabled="disabled"');
				$mform->setConstant($type, $nom);
				$tabfreeze[] = $type;
			}
		}

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);

        $mform->hardFreeze($tabfreeze);

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);

//--------------------------------------------------------------------------------
        // Next the customisable fields
        $this->custominfo = new custominfo_form_extension('course');
        $canviewall = has_capability('moodle/course:update', get_context_instance(CONTEXT_SYSTEM));
        $this->custominfo->definition($mform, $canviewall);

//--------------------------------------------------------------------------------

		$mform->addElement('header','gestion', get_string('managecourseblock', 'local_crswizard'));
        $mform->addElement('text', 'user_name', get_string('username', 'local_crswizard'),
			'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', $USER->firstname . ' '. $USER->lastname);
        $tabfreeze[] = 'user_name';

        $mform->addElement('date_selector', 'requestdate',  get_string('courserequestdate', 'local_crswizard'));
        $mform->setDefault('requestdate', time());
        $tabfreeze[] = 'requestdate';

//---------------------------------------------------------------------------------

        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'stepgo_2', get_string('previousstage', 'local_crswizard'));
        $buttonarray[] = $mform->createElement(
                'submit', 'stepgo_4', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
