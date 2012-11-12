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
require_once($CFG->libdir.'/custominfo/lib.php');
require_once('lib_wizard.php');

class course_wizard_step_confirm extends moodleform {

    function definition() {
        global $USER, $DB, $SESSION;

		$myconfig = new my_elements_config();

        $tabfreeze = array();
        $mform    = $this->_form;

        $mform->addElement('header','confirmation', get_string('confirmation', 'local_crswizard'));

        $mform->addElement('html', html_writer::tag('p', get_string('confirmationblock', 'local_crswizard') . ' :'));
        $mform->addElement('html', html_writer::tag('p', '[Prénom Nom du modérateur et/ou membres du service TICE]'));
        $mform->addElement('textarea', 'remarques', get_string('noticeconfirmation', 'local_crswizard'), array('rows'=>15, 'cols'=>80));
        $mform->setType('content', PARAM_RAW);

        $user_name = $USER->firstname . ' '. $USER->lastname;
        $mform->addElement('header','resume', get_string('summaryof', 'local_crswizard'));
        $mform->addElement('text', 'user_name', get_string('username', 'local_crswizard'), 'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', $user_name);
        $tabfreeze[] = 'user_name';
        $mform->addElement('date_selector', 'requestdate', get_string('courserequestdate', 'local_crswizard'));
        $mform->setDefault('requestdate', time());
        $tabfreeze[] = 'requestdate';

        $idcat = $SESSION->wizard['form_step2']['category'];
        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist);
        $mform->addElement('select', 'category', get_string('category'), $displaylist);
        $mform->setConstant('category',$idcat);
        $tabfreeze[] = 'category';

        $fullname = $SESSION->wizard['form_step2']['fullname'];
        $mform->addElement('text','fullname', get_string('fullnamecourse', 'local_crswizard'),'maxlength="254" size="50"');
        $mform->setConstant('fullname', $fullname);
        $tabfreeze[] = 'fullname';

        $shortname = $SESSION->wizard['form_step2']['shortname'];
        $mform->addElement('text', 'shortname', get_string('shortnamecourse', 'local_crswizard'), 'maxlength="100" size="20"');
        $mform->setConstant('shortname', $shortname);
        $tabfreeze[] = 'shortname';

        $htmlsummary = '<div class="fitemtitle"><div class="fstaticlabel"><label>'
            . get_string('coursesummary', 'local_crswizard') . '</label></div></div>'
            . '<div class="felement fstatic">' . $SESSION->wizard['form_step2']['summary_editor']['text'] . '</div>';
        $mform->addElement('html', html_writer::tag('div', $htmlsummary, array('class' => 'fitem')));

        $date = $SESSION->wizard['form_step2']['startdate'];
        $startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
        $mform->addElement('date_selector', 'startdate', get_string('coursestartdate', 'local_crswizard'));
        $mform->setConstant('startdate', $startdate);
        $tabfreeze[] = 'startdate';

        if (isset($SESSION->wizard['form_step4']['all-users']) && count($SESSION->wizard['form_step4']['all-users'])) {
            $allusers = $SESSION->wizard['form_step4']['all-users'];
            $mform->addElement('header', 'enseignants', get_string('teachers', 'local_crswizard'));
            $labels = $myconfig->role_teachers;
            foreach ($allusers as $role => $users) {
                if (array_key_exists($role, $labels)) {
					$label = $labels[$role];
                    $mform->addElement('html', html_writer::tag('h4', get_string($label, 'local_crswizard')));
				}
                foreach ($users as $id => $user) {
                    $identite = $user->firstname . ' ' . $user->lastname;
                    $mform->addElement('html', html_writer::tag('div', $identite, array('class' => 'fitem')));
                }
            }
        }

        if (isset($SESSION->wizard['form_step5']['all-cohorts']) && count($SESSION->wizard['form_step5']['all-cohorts'])) {
            $allgroupes = $SESSION->wizard['form_step5']['all-cohorts'];
            $mform->addElement('header','groupes', get_string('cohorts', 'local_crswizard'));
            $labels = $myconfig->role_cohort;
            foreach ($allgroupes as $role => $groupes) {
                if (array_key_exists($role, $labels)) {
					$label = $labels[$role];
                    $mform->addElement('html', html_writer::tag('h4', get_string($label, 'local_crswizard')));
				}
                foreach ($groupes as $id => $group) {
                    $mform->addElement('html', html_writer::tag('div', $group->name, array('class' => 'fitem')));
                }
            }
        }

		$clefs = wizard_list_clef();
		if (count($clefs)) {
			$mform->addElement('header','clefs', get_string('enrolkey', 'local_crswizard'));
			foreach ($clefs as $type => $clef) {
				$mform->addElement('html', html_writer::tag('h4', $type . ' : '));
				$c = $clef['code'];
				if (isset($clef['enrolstartdate'])) {
					$date = $clef['enrolstartdate'];
					$startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
					$mform->addElement('date_selector', 'enrolstartdate' . $c, get_string('enrolstartdate', 'enrol_self'));
					$mform->setConstant('enrolstartdate' . $c, $startdate);
					$tabfreeze[] = 'enrolstartdate' . $c;
				}
				if (isset($clef['enrolenddate'])) {
					$date = $clef['enrolenddate'];
					$startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
					$mform->addElement('date_selector', 'enrolenddate' . $c, get_string('enrolenddate', 'enrol_self'));
					$mform->setConstant('enrolenddate' . $c, $startdate);
					$tabfreeze[] = 'enrolenddate' . $c;
				}
			}
		}

        //--------------------------------------------------------------------------------
        if (isset($SESSION->wizard['idcourse'])) {
            $idcourse = (int) $SESSION->wizard['idcourse'];
            $custominfo_data = custominfo_data::type('course');
            $cinfos = $custominfo_data->get_record($idcourse);

            foreach ($cinfos as $label=>$info) {
                $htmlinfo = '<div class="fitemtitle"><div class="fstaticlabel"><label>'
                    . $label . '</label></div></div>'
                    . '<div class="felement fstatic">' . $info . '</div>';
                $mform->addElement('html', html_writer::tag('div', $htmlinfo, array('class' => 'fitem')));
            }
        }
//--------------------------------------------------------------------------------

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 7);

        $urlCategory = new moodle_url('/course/category.php', array('id' => $idcat, 'edit' => 'on' ));
        $messagehtml = '<div>Ce message concerne la demande de création de cours '. $fullname . ' ( ' . $shortname . ' )'
            .' faite par ' . $user_name . '.</div><div>Vous pouvez valider ou supprimer ce cours : '
            . html_writer::link($urlCategory, $urlCategory)
            . '</div>';
        $message = 'Ce message concerne la demande de création de cours '. $fullname . ' ( ' . $shortname . ' )'
            .' faite par ' . $user_name . '. Vous pouvez valider ou supprimer ce cours : ' . $urlCategory;
        $mform->addElement('hidden', 'messagehtml', null);
        $mform->setType('messagehtml', PARAM_RAW);
        $mform->setConstant('messagehtml', $messagehtml);

        $mform->addElement('hidden', 'message', null);
        $mform->setType('message', PARAM_RAW);
        $mform->setConstant('message', $message);

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_8', get_string('finish', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->hardFreeze($tabfreeze);
    }

}
