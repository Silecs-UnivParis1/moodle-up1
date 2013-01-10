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

        $user_name = fullname($USER);
        $textehtml = '';
        $textehtml .= '<div>' . get_string('username', 'local_crswizard') . $user_name . ' </div>';
        $textehtml .= '<div>' . get_string('courserequestdate', 'local_crswizard') . date('d-m-Y') . ' </div>';

        $idcat = $SESSION->wizard['form_step2']['category'];
        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist);
        $textehtml .= '<div>' . get_string('category') . ' : ' . $displaylist[$idcat] . ' </div>';

        $textehtml .= '<div>' . get_string('up1composante', 'local_crswizard') .
            $SESSION->wizard['form_step3']['profile_field_up1composante'] . ' </div>';
        $textehtml .= '<div>' . get_string('up1niveau', 'local_crswizard') .
            $SESSION->wizard['form_step3']['profile_field_up1niveau'] . ' </div>';

        $fullname = $SESSION->wizard['form_step2']['fullname'];
        $textehtml .= '<div>' .  get_string('fullnamecourse', 'local_crswizard') . $fullname . ' </div>';

        $shortname = $SESSION->wizard['form_step2']['shortname'];
        $textehtml .= '<div>' .  get_string('shortnamecourse', 'local_crswizard') . $shortname . ' </div>';

        /** @todo display the summary correctly, with Moodle's conversion functions */
        $textehtml .= '<div>'
                . get_string('coursesummary', 'local_crswizard')
                . $SESSION->wizard['form_step2']['summary_editor']['text'] . '</div>';

        $startdate = $SESSION->wizard['form_step2']['startdate'];
        $textehtml .= '<div>' .  get_string('coursestartdate', 'local_crswizard') . date('d-m-Y', $startdate) . ' </div>';

        $datefermeture = 'up1datefermeture';
        $enddate = $SESSION->wizard['form_step2'][$datefermeture];
        $textehtml .= '<div>' .  get_string('up1datefermeture', 'local_crswizard') . date('d-m-Y', $enddate) . ' </div>';

        /**
        $mform->addElement('date_selector', 'startdate', get_string('coursestartdate', 'local_crswizard'));
        $mform->addElement('date_selector', 'up1datefermeture', get_string('up1datefermeture', 'local_crswizard'));
        **/

        $textehtml .= '<div>' .  get_string('teachers', 'local_crswizard') . ' : ';
        if (isset($SESSION->wizard['form_step4']['all-users']) && is_array($SESSION->wizard['form_step4']['all-users'])) {
            $allusers = $SESSION->wizard['form_step4']['all-users'];
            $labels = $myconfig->role_teachers;
            foreach ($allusers as $role => $users) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                $textehtml .= '<ul>';
                foreach ($users as $id => $user) {
                    $textehtml .= '<li>' .  ($first ? $label : '') . ' ' . fullname($user) . '</li>';
                    $first = false;
                }
                $textehtml .= '</ul>';
            }
        }else {
            $textehtml .= 'aucun';
        }
        $textehtml .= '</div>';

        $textehtml .= '<div>' .  get_string('cohorts', 'local_crswizard') . ' : ';
        if (!empty($SESSION->wizard['form_step5']['all-cohorts'])) {
            $groupsbyrole = $SESSION->wizard['form_step5']['all-cohorts'];
            $labels = $myconfig->role_cohort;
            foreach ($groupsbyrole as $role => $groups) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                 $textehtml .= '<ul>';
                foreach ($groups as $id => $group) {
                    $textehtml .= '<li>' .  ($first ? $label : '') . ' ' . $group->name . " ({$group->size})" . '</li>';
                    $first = false;
                }
                 $textehtml .= '<ul>';
            }
        } else {
            $textehtml .= 'aucun';
        }
        $textehtml .= '</div>';

        $textehtml .= '<div>' .  get_string('enrolkey', 'local_crswizard') . ' : ';
        /** @todo Do not set the values here, share the code that parses the forms data */
        $clefs = wizard_list_clef();
        if (count($clefs)) {
            foreach ($clefs as $type => $clef) {
                $textehtml .= '<div><b>' . $type . ' : </b>';
                $textehtml .= ' valeur cachée ';
                $c = $clef['code'];
                if (isset($clef['enrolstartdate'])) {
                    $textehtml .= '<div>' . get_string('enrolstartdate', 'enrol_self') . ' : ' .
                        ($clef['enrolstartdate']==0?'aucune':date('d-m-Y', $clef['enrolstartdate'])) . '</div>';
                }
                if (isset($clef['enrolenddate'])) {
                    $textehtml .= '<div>' . get_string('enrolenddate', 'enrol_self') . ' : ' .
                        ($clef['enrolenddate']==0?'aucune':date('d-m-Y', $clef['enrolenddate'])) . '</div>';
                }
            }
        } else {
            $textehtml .= 'aucune';
        }
        $textehtml .= '</div>';

        $mform->addElement('header', 'blocrecapitulatif', get_string('summaryof', 'local_crswizard'));
        $mform->addElement('html', html_writer::tag('div', $textehtml, array('class' => 'blocrecapitulatif cache')));


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

        $urlCategory = new moodle_url('/course/category.php', array('id' => $idcat, 'edit' => 'on'));
        $messagehtml = '<div>Ce message concerne la demande de création de cours ' . $fullname . ' ( ' . $shortname . ' )'
                . ' faite par ' . $user_name . '.</div><div>Vous pouvez valider ou supprimer ce cours : '
                . html_writer::link($urlCategory, $urlCategory)
                . '</div>';
        $message = 'Ce message concerne la demande de création de cours ' . $fullname . ' ( ' . $shortname . ' )'
                . ' faite par ' . $user_name . '. Vous pouvez valider ou supprimer ce cours : ' . $urlCategory;
        $mform->addElement('hidden', 'messagehtml', null);
        $mform->setType('messagehtml', PARAM_RAW);
        $mform->setConstant('messagehtml', $messagehtml);

        $mform->addElement('hidden', 'message', null);
        $mform->setType('message', PARAM_RAW);
        $mform->setConstant('message', $message);

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

        $mform->hardFreezeAllVisibleExcept(array('buttonar'));
    }

}
