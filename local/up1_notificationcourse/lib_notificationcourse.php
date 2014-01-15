<?php
/**
 * @package    local
 * @subpackage up1_notificationcourse
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_notificationcourse_message($formdata, $params) {
    $message = new object();
    $sitename = '[' . format_string(get_site()->shortname) . '] ';
    $message->subject = $formdata->msgsubject;
    $message->body = $formdata->msgbody;

    $message->subject = $sitename . $message->subject;
    //interpolation variables si besoin
    $message->subject = str_replace('[[nom_activite]]', $params['nom_activite'], $message->subject);
    $message->body = str_replace('[[lien_activite]]', $params['lien_activite'], $message->body);

    return $message;
}

/**
 * renvoie les identifiants des utilisateurs ayant le rôle 'rolename'
 * dans le cours $course
 * @param object $course $course
 * @param string $rolename shortname du rôle
 * @return array iduser => iduser
 */
function get_users_from_course($course, $rolename) {
    global $DB;
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

    $rolestudent = $DB->get_record('role', array('shortname'=> $rolename));

    $students = get_users_from_role_on_context($rolestudent, $coursecontext);

    $mystudents = array();

    if (isset($students) && count($students)) {
        foreach ($students as $student) {
            $mystudents[$student->userid] = $student->userid;
        }
    }
    return $mystudents;
}



/**
 * Envoi une notification au user
 * @param array $idusers
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function send_notificationcourse($idusers, $msg, $infolog) {
    global $DB;
    $ids = '';
    if ($idusers && count($idusers)) {
        foreach ($idusers as $id) {
            $ids .= $id . ',';
        }
        $ids = substr($ids, 0, -1);
    }
    return notificationcourse_send_all_message($ids, $msg, $infolog);
}

/**
 * Envoi une notification au user dont l'identifiant apparait dans $ids
 * @param string $ids : liste des identifiants user (format : id1,id2,id3
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function notificationcourse_send_all_message($ids, $msg, $infolog) {
    global $DB, $USER;
    $nb = 0;
    if ($ids != '') {
        $sql = "SELECT id, firstname, lastname, email FROM {user} WHERE id IN ({$ids})";
        $users = $DB->get_records_sql($sql);

        foreach ($users as $user) {
            $res = notificationcourse_send_email($user->email, $msg->subject, $msg->body);
            if ($res) {
                ++$nb;
            }
        }
        /** pour messagerie interne cf http://docs.moodle.org/dev/Messaging_2.0#Message_dispatching
        $userfrom = new object();
        static $supportuser = null;
        if (!empty($supportuser)) {
            $userfrom = $supportuser;
        } else {
            $userfrom = $USER;
        }
        $eventdata = new object();
        $eventdata->component = 'moodle';
        $eventdata->name = 'courserequested';
        $eventdata->userfrom = $userfrom;
        $eventdata->subject = $msg->subject; //** @todo get_string()
        $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
        $eventdata->fullmessage = $msg->body;
        $eventdata->fullmessagehtml = '';   //$messagehtml;
        $eventdata->smallmessage = $msg->body; // USED BY DEFAULT !
        foreach ($users as $user) {
            $eventdata->userto = $user;
            $res = message_send($eventdata);
            if ($res) {
                ++$nb;
            }
        }
        **/
    }
    $infolog['nb'] = $nb;

    return get_result_action_notificationcourse($infolog);
}

/**
 * construit le message d'interface après l'envoi groupé de notification
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string message interface
 */
function get_result_action_notificationcourse($infolog) {
    $s = '';

    if ($infolog['nb'] == 0) {
        return get_string('nopostssend', 'local_up1_notificationcourse');
    } elseif ($infolog['nb'] > 1) {
        $s = 's';
    }

    $message = $infolog['nb'] . ' notification' . $s . ' envoyée' . $s;
    //log
    add_to_log($infolog['courseid'], 'up1_notif_course', 'send notification_course',
        $infolog['cmurl'], $message , $infolog['cmid'], $infolog['userid']);

    return $message;
}

/**
 * Envoie un email à l'adresse mail spécifiée
 * @param string $email
 * @param string $subject,
 * @param string $message
 * @return false ou resultat de la fonction email_to_user()
 **/
function notificationcourse_send_email($email, $subject, $message) {
    if (!isset($email) && empty($email)) {
        return false;
    }
    $supportuser = generate_email_supportuser();
    $user = new stdClass();
    $user->email = $email;
    return email_to_user($user, $supportuser, $subject, $message);
}
?>
