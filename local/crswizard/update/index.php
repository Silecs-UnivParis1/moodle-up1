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

require_once('../../../config.php');
require_once('../../../course/lib.php');
require_once(__DIR__ . '/../lib_wizard.php');
require_once(__DIR__ . '/../libaccess.php');
require_once(__DIR__ . '/../step2_form.php');
require_once(__DIR__ . '/../step2_rof_form.php');
require_once(__DIR__ . '/../step3_form.php');
require_once(__DIR__ . '/../step_cle.php');
require_once(__DIR__ . '/confirm.php');


require_once(__DIR__ . '/lib_update_wizard.php');

global $CFG, $PAGE, $OUTPUT, $SESSION, $USER;

require_login();

$id = optional_param('id', 0, PARAM_INT);
if (empty($id)) {
    if (isset($SESSION->wizard['init_course']['id'])) {
        $id = $SESSION->wizard['init_course']['id'];
    } else {
        print_error('invalidcourseid');
    }
}
$pageparams = array('id'=>$id);
$PAGE->set_url('/local/crswizard/update/index.php', $pageparams);
$PAGE->requires->css(new moodle_url('../local/crswizard/css/crswizard.css'));

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
require_login($course);
$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
$PAGE->set_context($coursecontext);

$stepin = optional_param('stepin', 0, PARAM_INT);

if (!$stepin) {
    $stepin = 2;
    $stepgo = 2;
    if (isset($SESSION->wizard)) {
        unset($SESSION->wizard);
    }
    // recupérer les données du cours
    wizard_get_course($id);
    wizard_require_update_permission($id, $USER->id);

    $SESSION->wizard['wizardurl'] = '/local/crswizard/update/index.php';
    $SESSION->wizard['idcourse'] = $id;

} else {
    $stepgo = $stepin + 1;
}

wizard_navigation($stepin);
$wizardcase = $SESSION->wizard['wizardcase'];
// si $wizardcase == 0 faire quelque chose

switch ($stepin) {
    case 1:
        $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$id));
        redirect($url);
        break;
    case 2:
        $steptitle = get_string('upcoursedefinition', 'local_crswizard');
        $editoroptions = array(
            'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true
        );
        $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
        if ($wizardcase == 3) {
            $editform = new course_wizard_step2_form(NULL, array('editoroptions' => $editoroptions));
        } elseif ($wizardcase == 2) {
            $PAGE->requires->css(new moodle_url('/local/rof_browser/browser.css'));
            $PAGE->requires->js(new moodle_url('/local/rof_browser/selected.js'), true);
            $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/../js/include-for-rofform.js'), true);
            $editform = new course_wizard_step2_rof_form(NULL, array('editoroptions' => $editoroptions));
        }

        $data = $editform->get_data();
        if ($data){
            $SESSION->wizard['form_step' . $stepin] = (array) $data;
            if ($wizardcase == 2) {
                 $SESSION->wizard['form_step2']['item'] = $_POST['item'];
                 $SESSION->wizard['form_step2']['path'] = $_POST['path'];
                 $SESSION->wizard['form_step2']['all-rof'] = wizard_get_rof();
                 $SESSION->wizard['form_step2']['complement'] = $_POST['complement'];
            }
            redirect($CFG->wwwroot . '/local/crswizard/update/index.php?stepin=' . $stepgo);
        } else {
            $PAGE->requires->js(new moodle_url('/local/crswizard/js/select-into-subselects.js'), true);
            $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/../js/include-for-subselects.js'));
        }
        break;
    case 3:
        if ($wizardcase == 3) {
            $editform = new course_wizard_step3_form();

            $data = $editform->get_data();
            if ($data){
                $data->user_name = $SESSION->wizard['form_step3']['user_name'];
                $data->user_login = $SESSION->wizard['form_step3']['user_login'];
                $data->requestdate = $SESSION->wizard['form_step3']['requestdate'];

                $data->rattachements = array_unique(array_filter($data->rattachements));
                $SESSION->wizard['form_step' . $stepin] = (array) $data;
                redirect($CFG->wwwroot . '/local/crswizard/update/index.php?stepin=' . $stepgo);
            }
            $steptitle = get_string('upcoursedescription', 'local_crswizard');
            $PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
            $PAGE->requires->js(new moodle_url('/local/crswizard/js/select-into-subselects.js'), true);
            $PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/../js/include-for-rattachements.js'));
        } elseif ($wizardcase == 2) {
            $SESSION->wizard['navigation']['stepin'] = 5;
            $SESSION->wizard['navigation']['suite'] = 6;
            $SESSION->wizard['navigation']['retour'] = 3;
            redirect(new moodle_url('/local/crswizard/enrol/cohort.php'));
        }
        break;
    case 4:
        $SESSION->wizard['navigation']['stepin'] = 5;
        $SESSION->wizard['navigation']['suite'] = 6;
        $SESSION->wizard['navigation']['retour'] = 3;
        redirect(new moodle_url('/local/crswizard/enrol/cohort.php'));
        break;
    case 5:
        if (isset($_POST['step'])) {
            //* @todo Validate cohort list
            $SESSION->wizard['form_step' . $stepin] = $_POST;
            $SESSION->wizard['form_step5']['all-cohorts'] = wizard_get_enrolement_cohorts();
            redirect($CFG->wwwroot . '/local/crswizard/update/index.php?stepin=' . $stepgo);
        }
        redirect(new moodle_url('/local/crswizard/enrol/cohort.php'));
        break;
    case 6:
        $steptitle = get_string('upstepkeycase2', 'local_crswizard');
        if ($wizardcase == 3) {
            $steptitle = get_string('upstepkeycase3', 'local_crswizard');
        }
        $editform = new course_wizard_step_cle();
        $data = $editform->get_data();
        if ($data){
            $SESSION->wizard['form_step' . $stepin] = (array) $data;
            redirect($CFG->wwwroot . '/local/crswizard/update/index.php?stepin=' . $stepgo);
        }
        break;
    case 7:
        $steptitle = get_string('updatetitle', 'local_crswizard');
        $corewizard = new core_wizard($SESSION->wizard, $USER);
        $formdata = $corewizard->prepare_update_course();
        $editform = new course_wizard_confirm();
        $editform->set_data($formdata);

        $data = $editform->get_data();
        if ($data){
            $SESSION->wizard['form_step' . $stepin] = (array) $data;
            redirect($CFG->wwwroot . '/local/crswizard/update/index.php?stepin=' . $stepgo);
        }
        break;
    case 8:
        $corewizard = new core_wizard($SESSION->wizard, $USER);
        $errorMsg = $corewizard->update_course();
        $urlcourse = new moodle_url('/course/view.php',array('id' => $id));
        unset($SESSION->wizard);
        redirect($urlcourse);
        break;
}

$streditcoursesettings = get_string("editcoursesettings");
$PAGE->navbar->add($streditcoursesettings);

$site = get_site();
$PAGE->set_title("$site->shortname: $streditcoursesettings");
$PAGE->set_heading($site->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('upwizardcourse', 'local_crswizard'));
echo $OUTPUT->heading($steptitle);

if (isset($editform)) {
    if (isset($SESSION->wizard['form_step' . $stepin])) {
        $editform->set_data($SESSION->wizard['form_step' . $stepin]);
    }
    $editform->display();
} else {
    echo '<p>Pas de formulaires</p>';
}

echo $OUTPUT->footer();
