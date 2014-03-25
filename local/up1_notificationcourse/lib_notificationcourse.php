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
        . '<br/><a href="' . $msgbodyinfo['urlactivite']
        . '">' . $msgbodyinfo['urlactivite'] . '</a></p>';
    $message->bodytext .= "\n\n" . $msgbodyinfo['coursepath']
        . "\n" . $msgbodyinfo['urlactivite'];
    return $message;
}

/**
 * construit le messsage d'interface du nombre et de la qualité des
 * destinataires du message
 * @param int $nbdest
 * @param int $groupingid
 * @param array $msgbodyinfo
 * @return string $label
 */
function get_label_destinataire($nbdest, $groupingid, $msgbodyinfo) {
    $label = '';
    if ($nbdest == 0) {
        return get_string('norecipient', 'local_up1_notificationcourse');
    }
    $x = 'à l\'';
    $s = '';
    if ($nbdest > 1) {
        $x = 'aux ' . $nbdest . ' ';
        $s = 's';
    }
    $label = 'Le message suivant sera transmis ' . $x
        . 'utilisateur' . $s;
    if ($groupingid == 0) {
        $label .= ' inscrit' . $s . ' à cet espace.';
    } else {
        $label .= ' concerné' . $s . ' par <a href="' . $msgbodyinfo['urlactivite']
            . '">' . $msgbodyinfo['nomactivite'] . '.</a>';
    }
    return $label;
}

/**
 * renvoie les utilisateurs ayant le rôle 'rolename'
 * dans le cours $course
 * @param object $course $course
 * @param string $rolename shortname du rôle
 * @return array de $user
 */
function get_users_from_course($course, $rolename) {
    global $DB;
    $coursecontext = context_course::instance($course->id);
    $rolestudent = $DB->get_record('role', array('shortname'=> $rolename));
    $studentcontext = get_users_from_role_on_context($rolestudent, $coursecontext);

    if ($studentcontext == 0) {
        return $studentcontext;
    }
    $ids = '';
    foreach ($studentcontext as $sc) {
        $ids .= $sc->userid . ',';
    }
    $ids = substr($ids, 0, -1);
    $sql = "SELECT id, firstname, lastname, email, mailformat FROM {user} WHERE id IN ({$ids})";
    $students = $DB->get_records_sql($sql);

    return $students;
}

/**
 * Envoi une notification aux $users + copie à $USER
 * @param array $idusers
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function send_notificationcourse($users, $msg, $infolog) {
    global $USER;
    $nb = 0;
    foreach ($users as $user) {
        $res = notificationcourse_send_email($user, $msg);
        if ($res) {
            ++$nb;
        }
    }
    notificationcourse_send_email($USER, $msg);
    $infolog['nb'] = $nb;
    return get_result_action_notificationcourse($infolog);
}

/**
 * construit le message d'interface après l'envoi groupé de notification
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string message interface
 */
function get_result_action_notificationcourse($infolog) {
    if ($infolog['nb'] == 0) {
        return get_string('nomessagesend', 'local_up1_notificationcourse');
    }
    $message = get_string('numbernotification', 'local_up1_notificationcourse', $infolog['nb']);
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
function notificationcourse_send_email($user, $msg) {
    if (!isset($user->email) && empty($user->email)) {
        return false;
    }
    $emailform = $msg->from;
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
    $subject .='['. $siteshortname . '] '. get_string('notification', 'local_up1_notificationcourse')
        . $courseshortname . ' - ' . $activitename;
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
    $coursename = $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'];
    $a = new stdClass();
    $a->sender = $msgbodyinfo['user'];
    $a->linkactivity = $msgbodyinfo['nomactivite'];
    $a->linkcourse = $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'];
    if ($type == 'html') {
        $a->linkactivity = html_Writer::link($msgbodyinfo['urlactivite'], $msgbodyinfo['nomactivite']);
        $a->linkcourse = html_Writer::link($msgbodyinfo['urlcourse'], $coursename);
    }
    $res .= get_string('msgsender', 'local_up1_notificationcourse', $a);
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
