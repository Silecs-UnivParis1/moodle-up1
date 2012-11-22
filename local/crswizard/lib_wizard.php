<?php
/**
 * Vérifie si l'utilisateur à le droit de créer un cours,
 * sinon, vérifie si il a le droit de demander la création
 * d'un cours
 * @param $context $systemcontext
 * @return bool ou error
 */
function use_crswizard($systemcontext){
	//si capacité créer un cours (moodle/course:create)
	$create = has_capability('moodle/course:create', $systemcontext);
	if (!$create) {
		// si capacité demander création d'un cours (moodle/course:request)
		require_capability('moodle/course:request', $systemcontext);
	}
	return $create;
}

function get_stepgo($stepin, $post) {
	switch ($stepin) {
		case 5:
			if (array_key_exists('stepgo_4', $post)) {
				$stepgo = 4;
				break;
			}
			if (array_key_exists('stepgo_6', $post)) {
				$stepgo = 6;
				break;
			}
			if (array_key_exists('stepgo_7', $post)) {
				$stepgo = 7;
				break;
			}

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

function validation_categorie($idcategory) {
     global $DB;

    $errors = array();
    $category = $DB->get_record('course_categories', array('id' => $idcategory));
    if ($category) {
        if ($category->depth < 4 ) {
           $errors['category'] = get_string('categoryerrormsg1', 'local_crswizard');
        }
    } else {
        $errors['category'] = get_string('categoryerrormsg2', 'local_crswizard');
    }
    return $errors;
}

function get_list_category($idcategory) {
	global $DB;
	$categories = array();
	$selected = $DB->get_record('course_categories', array('id' => $idcategory));
	$tabidpath = explode('/', $selected->path);
	$tabcategory = array();
	foreach ($tabidpath as $id) {
		if ($id) {
			$name = $DB->get_field('course_categories', 'name', array('id' => $id));
			if ($name) {
				$tabcategory[] = $name;
			}
		}
	}
	return $tabcategory;
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
    $status = 0;   //ENROL_INSTANCE_ENABLED
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

    foreach ($tabGroup as $role => $groupes) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($groupes as $idgroup) {
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

/**
 * renvoie les rôles permis pour une inscription
 * @param $labels array
 * @return array object role
 */
function wizard_role($labels) {
	global $DB;
	$roles = array();
    foreach ($labels as $key => $label) {
        $sql = "SELECT * FROM {role} WHERE "
        . "shortname = ?" ;
        $record = $DB->get_record_sql($sql, array($key));
        $roles[] = array(
            'shortname' => $record->shortname,
            'name' => $record->name,
            'id' => $record->id
        );
    }
    return $roles;
}

/**
 * Enscrit des utilisateurs à un cours sous le rôle sélectionné
 * @param int $courseid identifiant du cours
 * @param array $tabUsers array[rolename]=>array(iduser)
 */
function myenrol_teacher($courseid, $tabUsers) {
	global $DB, $CFG;
	require_once("$CFG->dirroot/lib/enrollib.php");
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    foreach ($tabUsers as $role => $users) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($users as $user) {
            $userid = $DB->get_field('user', 'id', array('username' => $user));
            if ($userid) {
                enrol_try_internal_enrol($courseid, $userid, $roleid);
            }
        }
	}
}

/**
 * Construit le tableau des groupes sélectionnés et les sauvegrade dans la
 * variable de session $SESSION->wizard['form_step5']['all-cohorts']
 */
function wizard_get_enrolement_cohorts()
{
	global $DB, $SESSION;
	$list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
	$roles = wizard_role($labels);
    if (!isset($SESSION->wizard['form_step5']['group'])) {
        return false;
    }
	$form5g = $SESSION->wizard['form_step5']['group'];

	foreach ($roles as $r) {
		$code = $r['shortname'];
		if (array_key_exists($code, $form5g)) {
			foreach ($form5g[$code] as $g) {
				$group = $DB->get_record('cohort', array('idnumber' => $g));
				if ($group) {
                    $size = $DB->count_records('cohort_members', array('cohortid' => $group->id));
                    $group->size = $size;
					$list[$code][$group->idnumber] = $group;
				}
			}
		}
	}
	$SESSION->wizard['form_step5']['all-cohorts'] = $list;
}

/**
 * Construit le tableau des enseignants sélectionnés et les sauvegrade dans la
 * variable de session $SESSION->wizard['form_step4']['all-users']
 */
function wizard_get_enrolement_users()
{
    global $DB, $SESSION;
	$list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_teachers;
	$roles = wizard_role($labels);;

    if (!isset($SESSION->wizard['form_step4']['user'])) {
        return false;
    }
	$form4u = $SESSION->wizard['form_step4']['user'];

	foreach ($roles as $r) {
		$code = $r['shortname'];
		if (array_key_exists($code, $form4u)) {
			foreach ($form4u[$code] as $u) {
				$user = $DB->get_record('user', array('username' => $u));
				if ($user) {
					$list[$code][$user->username] = $user;
				}
			}
		}
	}
	$SESSION->wizard['form_step4']['all-users'] = $list;
}

/*
 * construit la liste des groupes sélectionnés encodé en json
 * @return string
 */
function wizard_preselected_cohort()
{
    global $SESSION;
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
    $liste = '';
    if (isset($SESSION->wizard['form_step5']['all-cohorts'])) {
        foreach ($SESSION->wizard['form_step5']['all-cohorts'] as $role => $groups) {
            $labelrole = '';
            if (array_key_exists($role, $labels)) {
				$label = $labels[$role];
                $labelrole = get_string($label, 'local_crswizard');
			}

            foreach ($groups as $id => $group) {
                $desc = '';
                if ($group->description != '') {
                    $desc .= strip_tags($group->description);
                }
                if (isset($group->size) && $group->size != '') {
                    $desc .=  ' (' . $group->size . ' inscrits)';
                }
                if ($desc != '') {
                    $desc = '<div>' . $desc . '</div>';
                }
                $liste .= '{"label":"<b>' . $group->name . '</b>'
                    . $desc .  $labelrole . '", "value": "'
                    . $id . '", "fieldName" : "group[' . $role . ']"},';
            }
        }
    }
    return $liste;
}

/*
 * construit la liste des enseignants sélectionnés encodé en json
 * @return string
 */
function wizard_preselected_users()
{
    global $SESSION;
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_teachers;
    $liste = '';
    if (isset($SESSION->wizard['form_step4']['all-users'])) {
        foreach ($SESSION->wizard['form_step4']['all-users'] as $role => $users) {
            $labelrole = '';
            if (array_key_exists($role, $labels)) {
				$label = $labels[$role];
                $labelrole = get_string($label, 'local_crswizard');
			}

            foreach ($users as $id => $user) {
                $desc = $user->firstname . ' ' . $user->lastname;
                $desc .= ' (' . $labelrole . ')';
                $liste .= '{"label":"' . $desc . '", "value": "'
                    . $id . '", "fieldName" : "user[' . $role . ']"},';
            }
        }
    }
    return $liste;
}

function wizard_list_clef() {
	global $SESSION;
	$list = array();
	$tabCle = array('u' => 'Etudiante', 'v' => 'Visiteur');

	if (isset($SESSION->wizard['form_step6'])) {
		$form6 = $SESSION->wizard['form_step6'];

		foreach ($tabCle as $c => $type) {
			$password = 'password' . $c;
			$enrolstartdate = 'enrolstartdate' . $c;
			$enrolenddate = 'enrolenddate' . $c;
			if (isset($form6[$password])) {
				$pass = trim($form6[$password]);
				if ($pass !='') {
					$list[$type]['code'] = $c;
					$list[$type]['password'] = $pass;
					if (isset($form6[$enrolstartdate])) {
						$list[$type]['enrolstartdate'] = $form6[$enrolstartdate];
					}
					if (isset($form6[$enrolenddate])) {
						$list[$type]['enrolenddate'] = $form6[$enrolenddate];
					}
				}
			}
		}
	}
	return $list;
}

function myenrol_clef($idcourse, $tabClefs){
	global $DB;
    if ($idcourse == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    // traitement des données
    foreach ($tabClefs as $type => $tabClef) {
		$name = 'clef '. $type;

		if ($type == 'Etudiante') {
			$enrol = 'self';
			$roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
		} elseif ($type == 'Visiteur') {
			$enrol = 'guest';
			$roleid = 0;
		}
		$status = 0;   //0 pour auto-inscription
		if (isset($tabClef['enrolstartdate'])) {
			$date = $tabClef['enrolstartdate'];
			$startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
		} else {
			$startdate = 0;
		}
		if (isset($tabClef['enrolenddate'])) {
			$date = $tabClef['enrolenddate'];
			$enddate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
		} else {
			$enddate = 0;
		}

		$instance = new stdClass();
		$instance->enrol = $enrol;
		$instance->status = $status;
		$instance->courseid = $idcourse;
		$instance->roleid = $roleid;
		$instance->name = $name;
		$instance->password = $tabClef['password'];
		$instance->customint1 = 0; // clef d'inscription groupe ?
		$instance->customint2 = 0;
		$instance->customint3 = 0;
		$instance->customint4 = 0; // envoie d'un message

		$instance->enrolstartdate = $startdate;
		$instance->enrolenddate = $enddate;
		$instance->timemodified = time();
		$instance->timecreated = $instance->timemodified;
		$instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $idcourse));
		$DB->insert_record('enrol', $instance);
	}

}

/**
 * Reconstruit le tableau $displaylist pour le plugin jquery select-into-subselects.js
 * @retun array() $mydisplaylist
 **/
 /**
function wizard_get_mydisplaylist($displaylist)
{
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist);
    $myconfig = new my_elements_config();
    $labels = $myconfig->categorie_deph;
    $label0 = implode(' * / ', $labels);
    $label0 .= ' * ';
    $mydisplaylist = array(0 => $label0);

    foreach ($displaylist as $id => $label) {
        $tab = explode('/', $label);
        $nb = count($tab);
        for ($i = $nb; $i < 4; ++$i) {
            $j = $i +1;
            if ($i == $nb) {
                $tab[$j] = ' ... ';
            } else {
                $tab[$j] = $labels[$j] . ' * ';
            }
        }
        $mydisplaylist[$id] = implode('/', $tab);
    }
    return $mydisplaylist;
}
**/

/**
 * Reconstruit le tableau $displaylist pour le plugin jquery select-into-subselects.js
 * @retun array() $mydisplaylist
 **/
function wizard_get_mydisplaylist()
{
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist);
    $myconfig = new my_elements_config();
    $labels = $myconfig->categorie_deph;
    $label0 = implode(' * / ', $labels);
    $label0 .= ' * ';
    $mydisplaylist = array(0 => $label0);

    foreach ($displaylist as $id => $label) {
        if (array_key_exists($id, $parentlist) && count($parentlist[$id])==3) {
            $mydisplaylist[$id] = $label;
        }
    }
    return $mydisplaylist;
}

function call_jquery_select_into_subselects()
{
    $script = "\n" . '<script type="text/javascript">' . "\n"
        . '//<![CDATA[' . "\n";
     $script .= '$(document).ready(function() {'
        . 'var separator = / *\/ */;'
        . "$('select.transformIntoSubselects').transformIntoSubselects(separator);"
        . '});' . "\n";
    $script .= '//]]>'."\n".'</script>';
    return $script;
}

/**
 * Renvoie le nom du Course custom fields de nom abrégé $shortname
 * @param string $shortname nom abrégé du champ
 * @return string $name nom du champ
 */
function get_custom_info_field_label($shortname)
{
    global $DB;
    $name = $DB->get_field('custom_info_field', 'name', array('objectname' => 'course', 'shortname' => $shortname));
    return $name;
}

class core_wizard {

	function create_course_to_validate () {
		global $SESSION, $DB, $CFG;
		// créer cours
		$mydata = $this->prepare_course_to_validate();
		$course = create_course($mydata);
		// fonction addhoc - on supprime les enrols par défaut
		$this->delete_default_enrol_course($course->id);
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
		if (isset($SESSION->wizard['form_step4']['user']) && count($SESSION->wizard['form_step4']['user'])) {
            $tabUser = $SESSION->wizard['form_step4']['user'];
            myenrol_teacher($course->id, $tabUser);
        }

		// inscrire des cohortes
		if (isset($SESSION->wizard['form_step5']['group']) && count($SESSION->wizard['form_step5']['group'])) {
			$tabGroup = $SESSION->wizard['form_step5']['group'];
			$erreurs = myenrol_cohort($course->id, $tabGroup);
			if (count($erreurs)) {
				$SESSION->wizard['form_step5']['cohorterreur'] = $erreurs;
				$messageInterface = affiche_error_enrolcohort($erreurs);
			}
		} else {
			// inscrire des clefs
			$clefs = wizard_list_clef();
			if (count($clefs)) {
				myenrol_clef($course->id, $clefs);
			}
		}
	}

	function prepare_course_to_validate () {
		global $SESSION;
		$date = $SESSION->wizard['form_step2']['startdate'];
		$startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);

        $date2 = $SESSION->wizard['form_step2']['up1datefermeture'];
        $enddate = mktime(0, 0, 0, $date2['month'], $date2['day'], $date2['year']);

		$datamerge = array_merge($SESSION->wizard['form_step2'], $SESSION->wizard['form_step3']);
		$mydata = (object) $datamerge;
		$mydata->startdate = $startdate;

        //step3 custominfo_field
        // compoante
        $up1composante = trim($mydata->profile_field_up1composante);
        if ($up1composante != '' && substr($up1composante, -1) != ';') {
            $mydata->profile_field_up1composante .= ';';
        }
        $mydata->profile_field_up1composante .= $SESSION->wizard['form_step3']['composante'];

        // niveau
        $up1niveau = trim($mydata->profile_field_up1niveau);
        if ($up1niveau != '' && substr($up1niveau, -1) != ';') {
            $mydata->profile_field_up1niveau .= ';';
        }
        $mydata->profile_field_up1niveau .= $SESSION->wizard['form_step3']['niveau'];

		// cours doit être validé
		$mydata->profile_field_up1avalider = 1;
		$mydata->profile_field_up1datevalid = 0;

        $mydata->profile_field_up1datefermeture = $enddate;

		return $mydata;
	}

	// methode ad hoc : supprime les méthodes d'inscriptions guest et self
	function delete_default_enrol_course ($courseid) {
		global $DB;
		$DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'self'));
		$DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'guest'));
	}
}

class my_elements_config {
	public $categorie_cours = array('Période', 'Etablissement',
		'Composante','Niveau'
	);

	public $role_teachers = array('editingteacher' => 'editingteacher',
		'teacher' => 'noeditingteacher'
	);

    public $role_cohort = array('student' => 'student', 'guest' => 'guest');

    public $categorie_deph = array('1' => 'Période', '2' => 'Etablissement',
        '3' => 'Composante', '4' => 'Niveau');
}
