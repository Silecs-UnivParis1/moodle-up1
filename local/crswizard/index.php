<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit course settings
 *
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or laters
 */
require_once('../../config.php');
require_once('../../course/lib.php');
require_once(__DIR__ . '/lib_wizard.php');
require_once(__DIR__ . '/libaccess.php');
require_once(__DIR__ . '/step1_form.php');
require_once(__DIR__ . '/step_model.php');
require_once(__DIR__ . '/step2_form.php');
require_once(__DIR__ . '/step2_rof_form.php');
require_once(__DIR__ . '/step3_form.php');
require_once(__DIR__ . '/step_confirm.php');
require_once(__DIR__ . '/step_cle.php');

require_once(__DIR__ . '/update/lib_update_wizard.php');

global $DB, $CFG, $PAGE, $OUTPUT, $SESSION, $USER;

require_login();

$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_url('/local/crswizard/index.php');
$PAGE->set_context($systemcontext);
$PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

wizard_require_permission('creator', $USER->id);

$stepin = optional_param('stepin', 0, PARAM_INT);
if (!$stepin) {
    // new wizard process
    $stepin = 0;
    $stepgo = 0;
    if (isset($SESSION->wizard)) {
        unset($SESSION->wizard);
    }
} else {
    $stepgo = $stepin + 1;
}
wizard_navigation($stepin);

$wizardcase = optional_param('wizardcase', 0, PARAM_INT);
if ($wizardcase) {
    $SESSION->wizard['wizardcase'] = $wizardcase;
} elseif(isset($SESSION->wizard['wizardcase'])) {
    $wizardcase = $SESSION->wizard['wizardcase'];
}

switch ($stepin) {
    case 0:
        $SESSION->wizard['wizardurl'] = '/local/crswizard/index.php';
        $steptitle = get_string('selectcourse', 'local_crswizard');
        $editform = new course_wizard_step1_form();
        break;
    case 1:
        $steptitle = "Étape 1 : modalité de création";
        $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
        $editform = new course_wizard_step_model();
        $data = $editform->get_data();
        if ($data){
            $SESSION->wizard['form_step' . $stepin] = (array) $data;
            redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
        } else {
            $PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));
            $PAGE->requires->js(new moodle_url('/local/crswizard/js/select-into-subselects.js'), true);
            $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-subselectsmodel.js'));
        }
        break;
    case 2:
        //vérifier si modele de cours
        get_selected_model();
        //fin vérifier si modele de cours
        wizard_get_metadonnees();

        $steptitle = get_string('coursedefinition', 'local_crswizard');
        $editoroptions = array(
            'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true
        );
        $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
        //$submission = file_prepare_standard_editor(null, 'summary', $editoroptions, null, 'course', 'summary', null);
        if ($wizardcase == 3) {
            $editform = new course_wizard_step2_form(NULL, array('editoroptions' => $editoroptions));
        } elseif ($wizardcase == 2) {
            $PAGE->requires->css(new moodle_url('/local/rof_browser/browser.css'));
            $PAGE->requires->js(new moodle_url('/local/rof_browser/selected.js'), true);
            $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-rofform.js'), true);
            $editform = new course_wizard_step2_rof_form(NULL, array('editoroptions' => $editoroptions));
        }

        $data = $editform->get_data();
        if ($data){
            $SESSION->wizard['form_step' . $stepin] = (array) $data;
            if ($wizardcase == 2) {
                 $SESSION->wizard['form_step2']['item'] = wizard_get_array_item($_POST['item']);
                 $SESSION->wizard['form_step2']['all-rof'] = wizard_get_rof();
                 $SESSION->wizard['form_step2']['complement'] = $_POST['complement'];
            }
            redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
        } else {
            $PAGE->requires->js(new moodle_url('/local/crswizard/js/select-into-subselects.js'), true);
            $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-subselects.js'));
        }
        break;
    case 3:
        if ($wizardcase == 3) {
            $hybridattachment_permission = false;
            $idcourse = 1;
            if (isset($SESSION->wizard['idcourse'])) {
                $idcourse = $SESSION->wizard['idcourse'];
            }
            $hybridattachment_permission = wizard_has_hybridattachment_permission($idcourse, $USER->id);
            $SESSION->wizard['form_step3']['user_name'] = fullname($USER);
            $SESSION->wizard['form_step3']['user_login'] = $USER->username;
            $SESSION->wizard['form_step3']['requestdate'] = time();

            get_selected_etablissement_id();
            $editform = new course_wizard_step3_form();

            $data = $editform->get_data();
            if ($data){
                $data->user_name = $SESSION->wizard['form_step3']['user_name'];
                $data->user_login = $SESSION->wizard['form_step3']['user_login'];
                $data->requestdate = $SESSION->wizard['form_step3']['requestdate'];
                $data->idetab = $SESSION->wizard['form_step3']['idetab'];

                $data->item =  (isset($_POST['item']) ? wizard_get_array_item($_POST['item']) : array());

                $data->rattachements = array_unique(array_filter($data->rattachements));
                $data->up1niveauannee = array_unique(array_filter($data->up1niveauannee));
                $data->up1semestre = array_unique(array_filter($data->up1semestre));
                $data->up1niveau = array_unique(array_filter($data->up1niveau));

                $SESSION->wizard['form_step' . $stepin] = (array) $data;
                $SESSION->wizard['form_step3']['all-rof'] = wizard_get_rof('form_step3');

                redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
            }

            $steptitle = get_string('coursedescription', 'local_crswizard');
            $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
            $PAGE->requires->js(new moodle_url('/local/crswizard/js/select-into-subselects.js'), true);
            $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-rattachements.js'));

            if ($hybridattachment_permission) {
                $PAGE->requires->css(new moodle_url('/local/rof_browser/browser.css'));
                $PAGE->requires->js(new moodle_url('/local/rof_browser/selected.js'), true);
            }

        } elseif ($wizardcase == 2) {
            if (isset($_POST['step'])) {
                $SESSION->wizard['form_step' . $stepin] = $_POST;
                $SESSION->wizard['form_step3']['all-validators'] = wizard_get_validators();
                redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
            }
            redirect(new moodle_url('/local/crswizard/select_validator.php'));
        }
        break;
    case 4:
        if (!isset($SESSION->wizard['form_step4'])) {
            $SESSION->wizard['form_step4']['all-users'] = wizard_enrolement_user();
        }
        if (isset($_POST['step'])) {
            //* @todo Validate user list
            $SESSION->wizard['form_step' . $stepin] = $_POST;
            $SESSION->wizard['form_step4']['all-users'] = wizard_get_enrolement_users();
            redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
        }
        redirect(new moodle_url('/local/crswizard/enrol/teacher.php'));
        break;
    case 5:
        if (isset($_POST['step'])) {
            //* @todo Validate cohort list
            $SESSION->wizard['form_step' . $stepin] = $_POST;
            $SESSION->wizard['form_step5']['all-cohorts'] = wizard_get_enrolement_cohorts();
            redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
        }
        redirect(new moodle_url('/local/crswizard/enrol/cohort.php'));
        break;
    case 6:
        $steptitle = get_string('stepkey', 'local_crswizard');
        $editform = new course_wizard_step_cle();
        $PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));
        $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
        $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-key.js'));

        $data = $editform->get_data();
        if ($data){
            $SESSION->wizard['form_step' . $stepin] = (array) $data;
            redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
        }
        break;
    case 7:
        $steptitle = get_string('confirmationtitle', 'local_crswizard');
        //vérifier si modele de cours
        get_selected_model();
        //fin vérifier si modele de cours

        $corewizard = new core_wizard($SESSION->wizard, $USER);
        $formdata = $corewizard->prepare_course_to_validate();
        $editform = new course_wizard_step_confirm();
        $editform->set_data($formdata);

        $data = $editform->get_data();
        if ($data){
            $SESSION->wizard['form_step' . $stepin] = (array) $data;
            redirect($CFG->wwwroot . '/local/crswizard/index.php?stepin=' . $stepgo);
        }

        $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
        $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-confirm.js'));
        break;
    case 8:
        $corewizard = new core_wizard($SESSION->wizard, $USER);
        $errorMsg = $corewizard->create_course_to_validate();
        // envoi message
        $messages = $corewizard->get_messages();
        $remarques = '';
        if (isset($SESSION->wizard['form_step7']['remarques']) && $SESSION->wizard['form_step7']['remarques'] != '') {
            $remarques  .= "\n\n\n---------------\n"
                . 'La demande est accompagnée de la remarque suivante : ' . "\n\n"
                . strip_tags($SESSION->wizard['form_step7']['remarques']);
            $remarques  .= "\n\n---------------\n\n";
        }

        $recap = $corewizard->get_recapitulatif_demande();
        $record = new stdClass;
        $record->courseid = $corewizard->course->id;
        $record->txt = $recap;
        $record->html = '';
        $DB->insert_record('crswizard_summary', $record, false);

        $messages['mgvalidator'] .= $remarques . $recap;
        $messages['mgcreator'] .= $remarques . $recap;

        if (isset($errorMsg) && $errorMsg!='') {
            $messages['mgvalidator'] .= "\n\n\nErreur lors de la demande :\n" . $errorMsg;
            $messages['mgcreator'] .= "\n\n\nErreur lors de la demande :\n" . $errorMsg;
        }
        // envoi des notification - messagerie interne
        $corewizard->send_message_notification($corewizard->course->id, $messages['mgcreator'], $messages['mgvalidator']);

        unset($SESSION->wizard);
        $msgredirect = get_string('msgredirect', 'local_crswizard');
        $urlredirect = new moodle_url('/');
        if (wizard_has_edit_course($corewizard->course->id, $USER->id)) {
            $urlredirect = new moodle_url('/course/view.php',array('id' => $corewizard->course->id));
        }
        wizard_redirect_creation($urlredirect, $msgredirect, 5);
        break;
}

$straddnewcourse = get_string("addnewcourse");
$PAGE->navbar->add($straddnewcourse);

$site = get_site();
$PAGE->set_title("$site->shortname: $straddnewcourse");
$PAGE->set_heading($site->fullname);

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('wizardcourse', 'local_crswizard'), 'titlecrswizard');
echo $OUTPUT->box($steptitle, 'titlecrswizard');

if (isset($editform)) {
    if (isset($SESSION->wizard['form_step' . $stepin])) {
        $editform->set_data($SESSION->wizard['form_step' . $stepin]);
    }
    $editform->display();
} else {
    echo '<p>Pas de formulaires</p>';
}

echo $OUTPUT->footer();
