<?php
/**
 * @package    local
 * @subpackage up1_notification
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_notification_message($formdata, $params) {
    $message = new object();
    $message->type = $formdata->message;
    $sitename = '[' . format_string(get_site()->shortname) . '] ';
    if ($formdata->message && $formdata->message==1) {
        //relance
        $message->info = get_string('word_relance', 'local_up1_notification');
        $message->subject = $formdata->msgrelancesubject;
        $message->body = $formdata->msgrelancebody;
    } else {
        //invitation
        $message->info = get_string('word_invitation', 'local_up1_notification');
        $message->subject = $formdata->msginvitationsubject;
        $message->body = $formdata->msginvitationbody;
    }

    $message->subject = $sitename . $message->subject;
    //interpolation variables si besoin
    $message->subject = str_replace('[[nom_feedback]]', $params['nom_feedback'], $message->subject);
    $message->body = str_replace('[[nbr_rep]]', $params['nbr_rep'], $message->body);
    $message->body = str_replace('[[nbr_non_rep]]', $params['nbr_non_rep'], $message->body);
    $message->body = str_replace('[[lien_feedback]]', $params['lien_feedback'], $message->body);

    return $message;
}

/**
 * Envoi une notification au user ayant déjà répondu au feedback
 * @param array $responses
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function send_notification_complete_users($responses, $msg, $infolog) {
    global $DB;
    $ids = '';
    if ($responses && count($responses)) {
        foreach ($responses as $response) {
            $ids .= $response->userid . ',';
        }
        $ids = substr($ids, 0, -1);
    }
    return notification_send_all_email($ids, $msg, $infolog);
}

/**
 * Envoi une notification au user n'ayant pas encore répondu au feedback
 * @param array $idusers
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function send_notification_incomplete_users($idusers, $msg, $infolog) {
    global $DB;
    $ids = '';
    if ($idusers && count($idusers)) {
        foreach ($idusers as $id) {
            $ids .= $id . ',';
        }
        $ids = substr($ids, 0, -1);
    }
    return notification_send_all_email($ids, $msg, $infolog);
}

/**
 * Envoi une notification au user dont l'identifiant apparait dans $ids
 * @param string $ids : liste des identifiants user (format : id1,id2,id3
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function notification_send_all_email($ids, $msg, $infolog) {
    global $DB;
    $nb = 0;
    if ($ids != '') {
        $sql = "SELECT id, firstname, lastname, email FROM {user} WHERE id IN ({$ids})";
        $users = $DB->get_records_sql($sql);

        foreach ($users as $user) {
            $res = notification_send_email($user->email, $msg->subject, $msg->body);
            if ($res) {
                ++$nb;
            }
        }
        //copie USER
        if (isset($infolog['copie']) && isset($infolog['useremail'])) {
            notification_send_email($infolog['useremail'], $msg->subject, $msg->body);
        }
    }
    $infolog['nb'] = $nb;
    $infolog['typemsg'] = $msg->info;

    return get_result_action($infolog);
}

/**
 * construit le message d'interface après l'envoi groupé de notification
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string message interface
 */
function get_result_action($infolog) {
    $s = '';

    if ($infolog['nb'] == 0) {
        return get_string('nopostssend', 'local_up1_notification');
    } elseif ($infolog['nb'] > 1) {
        $s = 's';
    }

    $message = $infolog['nb'] . ' message' . $s . ' de type "' . $infolog['typemsg'] . '" envoyé' . $s;
    if (isset($infolog['copie']) && isset($infolog['useremail'])) {
        $message .= "  " . "+ copie à " . $infolog['userfullname'];
    }
    //log
    add_to_log($infolog['courseid'], 'up1_notification', 'send notification',
        $infolog['cmurl'], $message . ' ('. $infolog['public'] .')', $infolog['cmid'], $infolog['userid']);

    return $message;
}

/**
 * Envoie un email à l'adresse mail spécifiée
 * @param string $email
 * @param string $subject,
 * @param string $message
 * @return false ou resultat de la fonction email_to_user()
 **/
function notification_send_email($email, $subject, $message) {
    if (!isset($email) && empty($email)) {
        return false;
    }
    $supportuser = generate_email_supportuser();
    $user = new stdClass();
    $user->email = $email;
    return email_to_user($user, $supportuser, $subject, $message);
}
?>
