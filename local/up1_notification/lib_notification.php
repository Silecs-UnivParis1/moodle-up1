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
    $message->body = str_replace('[[nom_cours]]', $params['course_name'], $message->body);
    $message->body = str_replace('[[nbr_rep]]', $params['nbr_rep'], $message->body);
    $message->body = str_replace('[[nbr_non_rep]]', $params['nbr_non_rep'], $message->body);

    return $message;
}

/**
 * Envoi une notification au user ayant déjà répondu au feedback
 * @param array $responses
 * @param object $msg
 * @return string : message interface
 */
function send_notification_complete_users($responses, $msg) {
    global $DB;
    $ids = '';
    foreach ($responses as $response) {
        $ids .= $response->userid . ',';
    }
    $ids = substr($ids, 0, -1);
    return notification_send_all_email($ids, $msg);
}

/**
 * Envoi une notification au user n'ayant pas encore répondu au feedback
 * @param array $idusers
 * @param object $msg
 * @return string : message interface
 */
function send_notification_incomplete_users($idusers, $msg) {
    global $DB;
    $ids = '';
    foreach ($idusers as $id) {
        $ids .= $id . ',';
    }
    $ids = substr($ids, 0, -1);
    return notification_send_all_email($ids, $msg);
}

/**
 * Envoi une notification au user dont l'identifiant apparait dans $ids
 * @param string $ids : liste des identifiants user (format : id1,id2,id3
 * @param object $msg
 * @return string : message interface
 */
function notification_send_all_email($ids, $msg) {
    global $DB;
    $sql = "SELECT firstname, lastname, email FROM {user} WHERE id IN ({$ids})";
    $users = $DB->get_records_sql($sql);
    $nb = 0;
    foreach ($users as $user) {
        $res = notification_send_email($user->email, $msg->subject, $msg->body);
        if ($res) {
            ++$nb;
        }
    }
    return get_result_action($nb, $msg->info);
}

/**
 * construit le message d'interface après l'envoi groupé de notification
 * @param int $nb : nombre de notification envoyé
 * @param string $type : type de notification envoyée
 * @return string message interface
 */
function get_result_action($nb, $type) {
    $s = '';
    if ($nb == 0) {
        return get_string('nopostssend', 'local_up1_notification');
    } elseif ($nb > 1) {
        $s = 's';
    }
    return $nb . ' message' . $s . ' de type "' . $type . '" envoyé' . $s;
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
