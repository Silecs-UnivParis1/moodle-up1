<?php

function get_stepgo($stepin, $post) {
    $stepgo = $stepin + 1;
    switch ($stepin) {
        case 2:
            if (array_key_exists('stepgo_1', $post)) {
                $stepgo = 1;
            }
            break;
        case 3:
            if (array_key_exists('stepgo_2', $post)) {
                $stepgo = 2;
            }
            break;
        case 5:
            $stepgo = $post['stepgo'];
            break;
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
