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
 * @package    moodlecore
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('../lib.php');
require_once('lib_wizard.php');
require_once('step1_form.php');
require_once('step2_form.php');
require_once('step3_form.php');
require_once('step_confirm.php');

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/course/wizard/index.php');

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
has_capability('moodle/course:request', $systemcontext);


if (!isset($_POST['stepin'])) {
	$stepin = optional_param('stepin', 1, PARAM_INT);
	$stepgo = $stepin;
	if (isset($SESSION->wizard)) {
	    unset($SESSION->wizard);
    }
} else {
	$stepin = $_POST['stepin'];
	$stepgo = get_stepgo($stepin, $_POST);

}

if (isset($stepgo)) {
    $SESSION->wizard['form_step'.$stepin] = $_POST;
    switch ($stepgo) {
		case 1 :
		    $steptitle = 'Etape 1 - Pour quel enseignement souhaitez-vous ouvrir un espace sur la plateforme ?';
		    $step1form = step1_form();

		    break;
		case 2 :
		    $steptitle = 'Etape 2 - Identification de l\'espace de cours';
		    $course = null;
		    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
		    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
		    $editform = new course_wizard_step2_form(NULL, array('editoroptions'=>$editoroptions));
		    break;
		case 3 :
		    $data = $SESSION->wizard['form_step2'];
		    $errors = validation_shortname($data['shortname']);
		    if (count($errors)) {
				$data['erreurs'] = $errors;
				$SESSION->wizard['form_step2'] = $data;
				$editform = new course_wizard_step2_form(NULL);
				$steptitle = 'Etape 2 - Identification de l\'espace de cours';
			} else {
		        $steptitle = 'Etape 3 - Description de l\'espace de cours';
		        $editform = new course_wizard_step3_form();
		        if (isset($SESSION->wizard['form_step3'])) {
		            $editform->set_data((object)$SESSION->wizard['form_step3']);
		        }
		    }
		    break;
		case 4 :
		    $steptitle = 'Etape 4 : création du cours + inscription profs';
		    $date = $SESSION->wizard['form_step2']['startdate'];
		    $startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);

            $datamerge = array_merge($SESSION->wizard['form_step2'], $SESSION->wizard['form_step3']);
		    $mydata = (object) $datamerge;
		    $mydata->startdate = $startdate;
            $course = create_course($mydata);
            // save custom fields data
            $mydata->id = $course->id;
            $custominfo_data = custominfo_data::type('course');
            $custominfo_data->save_data($mydata);

            // tester si le cours existe bien ?
            $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

            redirect(new moodle_url('/course/wizard/enrol/users.php', array('id'=>$course->id)));
		    break;
		case 5 :
		    echo ' inscription cohortes';
		    break;
		case 6 :
		    echo ' si inscription avec clef';
		    break;

		case 7 :
		    // on vient de inscription et on va à la fin
		    $steptitle = 'Etape 6 - Confirmation de la demande d\'espace de cours';
		    $editform = new course_wizard_step_confirm();
		    break;
	    case 8 :
		    // envoi message
		    $message = $SESSION->wizard['form_step7']['message'];
		    if (isset($SESSION->wizard['form_step7']['remarques']) && $SESSION->wizard['form_step7']['remarques'] != '') {
				$message .= '<p>La demande est accompagnée de la remarque suivante : <div>'
				    . $SESSION->wizard['form_step7']['remarques'] . '</div></p>';
			}
			$res = send_course_request($message);
		    unset($SESSION->wizard);
		    redirect(new moodle_url('/'));
		    break;
	}
}
$site = get_site();

$streditcoursesettings = get_string("editcoursesettings");
$straddnewcourse = get_string("addnewcourse");
$stradministration = get_string("administration");
$strcategories = get_string("categories");

$PAGE->navbar->add($stradministration, new moodle_url('/admin/index.php'));
$PAGE->navbar->add($strcategories, new moodle_url('/course/index.php'));
$PAGE->navbar->add($straddnewcourse);
$title = "$site->shortname: $straddnewcourse";
$fullname = $site->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading("Assistant ouverture/paramétrage coursMoodle");
echo $OUTPUT->heading($steptitle);
if (isset($editform)) {
    $editform->display();
}elseif(isset($step1form)) {
	echo $step1form;
} else {
	echo '<p>Pas de formulaires</p>';
}

echo $OUTPUT->footer();
