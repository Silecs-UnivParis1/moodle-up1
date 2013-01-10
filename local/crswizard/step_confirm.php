<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/custominfo/lib.php');
require_once('lib_wizard.php');

class course_wizard_step_confirm extends moodleform {

    function definition() {
        global $USER, $DB, $SESSION, $OUTPUT;

        $myconfig = new my_elements_config();

        $mform = $this->_form;

        $mform->addElement('header', 'confirmation', get_string('confirmation', 'local_crswizard'));

        $mform->addElement('html', html_writer::tag('p', get_string('confirmationblock', 'local_crswizard') . ' :'));
        $mform->addElement('html', html_writer::tag('p', '[Prénom Nom du modérateur et/ou membres du service TICE]'));
        $mform->addElement('textarea', 'remarques', get_string('noticeconfirmation', 'local_crswizard'), array('rows' => 15, 'cols' => 80));
        $mform->setType('content', PARAM_RAW);

        $mform->addElement('header', 'resume', get_string('summaryof', 'local_crswizard'));
        $user_name = fullname($USER);
        $mform->addElement('text', 'user_name', get_string('username', 'local_crswizard'), 'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', $user_name);
        $mform->addElement('date_selector', 'requestdate', get_string('courserequestdate', 'local_crswizard'));
        $mform->setDefault('requestdate', time());

        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist);
        $mform->addElement('select', 'category', get_string('category'), $displaylist);

        $mform->addElement('text', 'fullname', get_string('fullnamecourse', 'local_crswizard'), 'maxlength="254" size="50"');

        $mform->addElement('text', 'shortname', get_string('shortnamecourse', 'local_crswizard'), 'maxlength="100" size="20"');

        /** @todo display the summary correctly, with Moodle's conversion functions */
        $htmlsummary = '<div class="fitemtitle"><div class="fstaticlabel"><label>'
                . get_string('coursesummary', 'local_crswizard') . '</label></div></div>'
                . '<div class="felement fstatic">' . $SESSION->wizard['form_step2']['summary_editor']['text'] . '</div>';
        $mform->addElement('html', html_writer::tag('div', $htmlsummary, array('class' => 'fitem')));

        $mform->addElement('date_selector', 'startdate', get_string('coursestartdate', 'local_crswizard'));

        $mform->addElement('date_selector', 'up1datefermeture', get_string('up1datefermeture', 'local_crswizard'));

        if (isset($SESSION->wizard['form_step4']['all-users']) && count($SESSION->wizard['form_step4']['all-users'])) {
            $allusers = $SESSION->wizard['form_step4']['all-users'];
            $mform->addElement('header', 'teachers', get_string('teachers', 'local_crswizard'));
            $labels = $myconfig->role_teachers;
            foreach ($allusers as $role => $users) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                foreach ($users as $id => $user) {
                    $mform->addElement('text', 'teacher' . $role . $id, ($first ? $label : ''));
                    $mform->setConstant('teacher' . $role . $id, fullname($user));
                    $first = false;
                }
            }
        }

        if (!empty($SESSION->wizard['form_step5']['all-cohorts'])) {
            $groupsbyrole = $SESSION->wizard['form_step5']['all-cohorts'];
            $mform->addElement('header', 'groups', get_string('cohorts', 'local_crswizard'));
            $labels = $myconfig->role_cohort;
            foreach ($groupsbyrole as $role => $groups) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                foreach ($groups as $id => $group) {
                    $mform->addElement('text', 'cohort' . $id, ($first ? $label : ''));
                    $mform->setConstant('cohort' . $id, $group->name . " ({$group->size})");
                    $first = false;
                }
            }
        }

        /** @todo Do not set the values here, share the code that parses the forms data */
        $clefs = wizard_list_clef();
        if (count($clefs)) {
            $mform->addElement('header', 'clefs', get_string('enrolkey', 'local_crswizard'));
            foreach ($clefs as $type => $clef) {
                $mform->addElement('html', html_writer::tag('h4', $type . ' : '));
                $c = $clef['code'];
                if (isset($clef['enrolstartdate'])) {
                    $mform->addElement('date_selector', 'enrolstartdate' . $c, get_string('enrolstartdate', 'enrol_self'));
                    $mform->setConstant('enrolstartdate' . $c, $clef['enrolstartdate']);
                }
                if (isset($clef['enrolenddate'])) {
                    $mform->addElement('date_selector', 'enrolenddate' . $c, get_string('enrolenddate', 'enrol_self'));
                    $mform->setConstant('enrolenddate' . $c, $clef['enrolenddate']);
                }
            }
        }

        //--------------------------------------------------------------------------------
        if (isset($SESSION->wizard['idcourse'])) {
            $idcourse = (int) $SESSION->wizard['idcourse'];
            $custominfo_data = custominfo_data::type('course');
            $cinfos = $custominfo_data->get_record($idcourse);

            foreach ($cinfos as $label => $info) {
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

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
                'html',
                '<div class="previousstage">' . $OUTPUT->action_link(
                    new moodle_url('/local/crswizard/index.php', array('stepin' => 6)),
                    get_string('previousstage', 'local_crswizard')
                ) . '</div>'
        );
        $buttonarray[] = $mform->createElement('submit', 'stepgo_8', get_string('finish', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->hardFreezeAllVisibleExcept(array('remarques', 'buttonar'));
    }

}
