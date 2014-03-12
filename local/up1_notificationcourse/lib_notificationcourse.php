<?php
/**
 * @package    local
 * @subpackage up1_notificationcourse
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * construit l'objet $message contenant le sujet et le corps de message version texte et html
 * @param string $subject
 * @return object $message
 */
function get_notificationcourse_message($subject, $msgbodyinfo, $complement) {
    $message = new object();
    $message->subject = $subject;
    $message->from = $msgbodyinfo['shortnamesite'];
    $comhtml = '';
    $comtext = '';
    if (trim($complement) !='') {
        $comhtml .= '<p>' . $complement . '</p>';
        $comtext .= "\n\n" . $complement;
    }
    $message->bodyhtml = '<p>' . get_email_body($msgbodyinfo, 'html') . '</p>' . $comhtml;
    $message->bodytext = get_email_body($msgbodyinfo, 'text') . $comtext;

    $message->bodyhtml .= '<p>' . $msgbodyinfo['coursepath']
        . '<br/>' . $msgbodyinfo['urlactivite'] . '</p>';
    $message->bodytext .= "\n\n" . $msgbodyinfo['coursepath']
        . "\n" . $msgbodyinfo['urlactivite'];
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
    $coursecontext = context_course::instance($course->id);

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
            $res = notificationcourse_send_email($user->email, $msg);
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
 * @param object $msg
 * @return false ou resultat de la fonction email_to_user()
 **/
function notificationcourse_send_email($email, $msg) {

    if (!isset($email) && empty($email)) {
        return false;
    }
    $emailform = $msg->from;
    $user = new stdClass();
    $user->email = $email;
    return email_to_user($user, $emailform, $msg->subject, $msg->bodytext, $msg->bodyhtml);
}

/**
 * construit le sujet du mail envoyé
 * @param string $siteshortname
 * @param string $courseshortname
 * @param string $activitename
 * @return string
 */
function get_email_subject($siteshortname, $courseshortname, $activitename) {
    $subject = '';
    $subject .='['. $siteshortname . '] Notification : ' . $courseshortname
        . ' - ' . $activitename;
    return $subject;
}

/**
 * construit le
 * @param array $msgbodyinfo
 * @param string $type
 * return string
 */
function get_email_body($msgbodyinfo, $type) {
    $res = '';
    if ($type == 'html') {
        $res .= $msgbodyinfo['user'] . ' souhaite attirer votre attention'
            . ' sur l\'élément ' . '<a href="' . $msgbodyinfo['urlactivite'] . '">'
            . $msgbodyinfo['nomactivite'] . '</a> proposé au sein de'
            . ' l\'espace <a href="' . $msgbodyinfo['urlcourse'] . '">'
            . $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'] . '</a>.';
    } else {
        $res .= $msgbodyinfo['user'] . ' souhaite attirer votre attention'
            . ' sur l\'élément ' . $msgbodyinfo['nomactivite'] . ' proposé au sein de'
            . ' l\'espace ' . $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'] . '.';
    }
    return $res;
}

/**
 * Construit le chemin categories > cours
 * @param array $categories tableau de tableaux
 * @param object $course
 * @return string $path
 */
function get_pathcategories_course($categories, $course) {
    $path ='';
    $tabcat = array();
    if (count($categories)) {
        foreach ($categories as $category) {
            $tabcat[$category->depth] = $category->name;
        }
        ksort($tabcat);
        foreach ($tabcat as $cat) {
            $path .= $cat . ' > ';
        }
    }
    $path .= $course->shortname;
    return $path;
}
?>
