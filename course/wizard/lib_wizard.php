<?php

function get_stepgo($stepin, $post) {
	switch ($stepin) {
		case 5:
			if (array_key_exists('stepgo_6', $post)) {
				$stepgo = 6;
			}
			if (array_key_exists('stepgo_7', $post)) {
				$stepgo = 7;
			}
			break;
		default :
			$stepgo = $stepin + 1;
			$stepretour = $stepin - 1;
			$clefr = 'stepgo_' . $stepretour;
			if (array_key_exists($clefr, $post)) {
				$stepgo = $stepretour;
			}
	}
    return $stepgo;
}

function validation_shortname($shortname) {
    global $DB;

    $errors = array();
    $foundcourses = $DB->get_records('course', array('shortname' => $shortname));
    if ($foundcourses) {
        foreach ($foundcourses as $foundcourse) {
            $foundcoursenames[] = $foundcourse->fullname;
        }
        $foundcoursenamestring = implode(',', $foundcoursenames);
        $errors['shortname'] = get_string('shortnametaken', '', $foundcoursenamestring);
    }
    return $errors;
}

function send_course_request($message, $messagehtml) {
    global $DB, $USER;

    $result = $DB->get_records('user', array('username' => 'admin')); //** @todo on envoie à qui ? plusieurs ?
    //** @todo maybe replace all this by a call to course/lib.php course_request::notify +4394
    $eventdata = new object();
    $eventdata->component = 'moodle';
    $eventdata->name = 'courserequested';
    $eventdata->userfrom = $USER;
    $eventdata->subject = '[CourseWizardRequest]'; //** @todo get_string()
    $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
    $eventdata->fullmessage = $message;
    $eventdata->fullmessagehtml = $messagehtml;
    $eventdata->smallmessage = $message; // USED BY DEFAULT !
    // documentation : http://docs.moodle.org/dev/Messaging_2.0#Message_dispatching
    foreach ($result as $userto) {
        $eventdata->userto = $userto;
        $res = message_send($eventdata);
        if (!$res) {
            /** @todo Handle messaging errors */
        }
    }
}

/**
 * Convertit les champs custom_info_field de type datetime en timestamp
 * @param object $data
 * @return object $data
 */
function customfields_wash($data) {
    global $DB;

    $fields = $DB->get_records('custom_info_field', array('objectname' => 'course', 'datatype' => 'datetime'));
    if ($fields) {
        foreach ($fields as $field) {
            $nomc = 'profile_field_' . $field->shortname;
            if (isset($data->$nomc) && is_array($data->$nomc)) {
                $tab = $data->$nomc;
                $hour = 0;
                $minute = 0;
                if (isset($tab['hour'])) {
                    $hour = $tab['hour'];
                }
                if (isset($tab['minute'])) {
                    $minute = $tab['minute'];
                }
                $data->$nomc = mktime($hour, $minute, 0, $tab['month'], $tab['day'], $tab['year']);
            }
        }
    }
    return $data;
}

function myenrol_cohort($idcourse, $tabGroup) {
    global $DB, $CFG;
    if ($idcourse == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    $error = array();
    $enrol = 'cohort';
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
    $status = 0;   //ENROL_INSTANCE_ENABLED
    foreach ($tabGroup as $idgroup) {
        $cohort = $DB->get_record('cohort', array('idnumber' => $idgroup));
        if ($cohort) {
            if (!$DB->record_exists('enrol', array('enrol' => $enrol, 'courseid' => $idcourse, 'customint1' => $cohort->id))) {
                $instance = new stdClass();
                $instance->enrol = $enrol;
                $instance->status = $status;
                $instance->courseid = $idcourse;
                $instance->customint1 = $cohort->id;
                $instance->roleid = $roleid;
                $instance->enrolstartdate = 0;
                $instance->enrolenddate = 0;
                $instance->timemodified = time();
                $instance->timecreated = $instance->timemodified;
                $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $idcourse));
                $DB->insert_record('enrol', $instance);
            }
        } else {
            $error[] = 'groupe "' . $idgroup . '" n\'existe pas dans la base';
        }
    }
    require_once("$CFG->dirroot/enrol/cohort/locallib.php");
    enrol_cohort_sync($idcourse);
    return $error;
}

function affiche_error_enrolcohort($erreurs) {
    $message = '';
    $message .= '<div><h3>Messages </h3>';
    $message .= '<p>Des erreurs sont survenues lors de l\'inscription des groupes :</p><ul>';
    foreach ($erreurs as $e) {
        $message .= '<li>' . $e . '</li>';
    }
    $message .= '</ul></div>';
    return $message;
}

function wizard_navigation ($stepin) {
	global $SESSION;
	$SESSION->wizard['navigation']['stepin'] = $stepin;
	$SESSION->wizard['navigation']['suite'] = $stepin + 1;
	$SESSION->wizard['navigation']['retour'] = $stepin - 1;
}

function wizard_role_teacher($token) {
	global $DB;
	$ptoken = '%' . $token . '%';
	$sql = "SELECT * FROM {role} WHERE "
        . "shortname LIKE ?" ;
    $records = $DB->get_records_sql($sql, array($ptoken));
    $rolet = array();
    foreach ($records as $record) {
        $rolet[] = array(
            'shortname' => $record->shortname,
            'name' => $record->name,
            'id' => $record->id
        );
    }
    return $rolet;
}

function myenrol_teacher($courseid, $tabTeachers, $roleid) {
	global $DB, $CFG;
	require_once("$CFG->dirroot/lib/enrollib.php");
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }

	foreach ($tabTeachers as $user) {
		$userid = $DB->get_field('user', 'id', array('username' => $user));
		if ($userid) {
			enrol_try_internal_enrol($courseid, $userid, $roleid);
		}
	}
}

/**
 * renvoie un tableau des groupes à afficher dans étape confirmation
 * utilise $SESSION->wizard['form_step5']
 * @retun array $list
 */
function wizard_list_enrolement_group()
{
	global $DB, $SESSION;
	$list = array();
	$form5 = $SESSION->wizard['form_step5'];
	if (array_key_exists('group', $form5)) {
		foreach ($form5['group'] as $group) {
			$cohort = $DB->get_field('cohort', 'name', array('idnumber' => $group));
			if ($cohort) {
				$list[] = $cohort;
			}
		}
	}
	return $list;
}

/**
 * renvoie un tableau d'enseignants à afficher dans étape confirmation
 * utilise $SESSION->wizard['form_step4']
 * @retun array $list
 */
function wizard_list_enrolement_enseignants()
{
	global $DB, $SESSION;
	$list = array();
	$roles = wizard_role_teacher('teacher');
	$form4 = $SESSION->wizard['form_step4'];
	foreach ($roles as $r) {
		$code = $r['shortname'];
		if (array_key_exists($code, $form4)) {
			foreach ($form4[$code] as $u) {
				$user = $DB->get_record('user', array('username' => $u));
				if ($user) {
					$list[$code][] = $user->firstname .' '. $user->lastname;
				}
			}
		}
	}
	return $list;
}

class core_wizard {

	function create_course_to_validate () {
		global $SESSION, $DB, $CFG;
		// créer cours
		$mydata = $this->prepare_course_to_validate();
		$course = create_course($mydata);
		// save custom fields data
		$mydata->id = $course->id;
		$custominfo_data = custominfo_data::type('course');

		$mydata = customfields_wash($mydata);

		$custominfo_data->save_data($mydata);
		$SESSION->wizard['idcourse'] = $course->id;
		$SESSION->wizard['idenrolment'] = 'manual';
		// tester si le cours existe bien ?
        //$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

		// inscrire des enseignants
		$form4 = $SESSION->wizard['form_step4'];
		$roles = wizard_role_teacher('teacher');
		foreach ($roles as $r) {
			$code = $r['shortname'];
			if (array_key_exists($code, $form4)) {
				myenrol_teacher($course->id, $form4[$code], $r['id']);
			}
		}
		// inscrire des cohortes
		if (isset($SESSION->wizard['form_step5']['group']) && count($SESSION->wizard['form_step5']['group'])) {
			$tabGroup = $SESSION->wizard['form_step5']['group'];
			$erreurs = myenrol_cohort($course->id, $tabGroup);
			if (count($erreurs)) {
				$SESSION->wizard['form_step5']['cohorterreur'] = $erreurs;
				$messageInterface = affiche_error_enrolcohort($erreurs);
			}
		}

	}

	function prepare_course_to_validate () {
		global $SESSION;
		$date = $SESSION->wizard['form_step2']['startdate'];
		$startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);

		$datamerge = array_merge($SESSION->wizard['form_step2'], $SESSION->wizard['form_step3']);
		$mydata = (object) $datamerge;
		$mydata->startdate = $startdate;
		// cours doit être validé
		$mydata->profile_field_tovalidate = 1;
		$mydata->profile_field_validatedate = 0;

		return $mydata;
	}
}
