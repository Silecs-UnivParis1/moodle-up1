<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * check if user($userid) has $permission via the cohort membership
 * @global type $DB
 * @param string $permission = creator | validator
 * @param int $userid
 * @return boolean
 */
function wizard_has_cohort_permission($permission, $userid) {
    global $DB;

    $valid_permissions = array('creator', 'validator');
    if ( ! in_array($permission, $valid_permissions) ) {
        return false;
    }
    $permit_cohorts = get_config('local_crswizard', 'cohorts_cap_' . $permission);
    $permitted_cohorts = explode(' ', $permit_cohorts);

    $sql = "SELECT c.idnumber FROM {cohort} c JOIN {cohort_members} cm ON (cm.cohortid = c.id) "
         . "WHERE cm.userid = ?";
    $member_of = $DB->get_fieldset_sql($sql, array($userid));

    if ( count(array_intersect($permitted_cohorts, $member_of)) > 0 ) {
        return true;
    }
    return false;
}

/**
 * return the array of all users (userid) who have $permission via the cohort membership
 * @global type $DB
 * @param string $permission = creator | validator
 * @return array(userid)
 */
function wizard_membersof_permitted_cohorts($permission) {
    global $DB;

    $valid_permissions = array('creator', 'validator');
    if ( ! in_array($permission, $valid_permissions) ) {
        return array();
    }
    $permit_cohorts = get_config('local_crswizard', 'cohorts_cap_' . $permission);
    $pcohorts = explode(' ', $permit_cohorts);
    $sqlcohorts = "('" . join("', '", $pcohorts) . "')";

    $sql = "SELECT DISTINCT cm.userid FROM {cohort} c JOIN {cohort_members} cm ON (cm.cohortid = c.id) "
         . "WHERE c.idnumber IN " . $sqlcohorts ;
    $members = $DB->get_fieldset_sql($sql);

    return $members;
}

/**
 * compute permission by aggregating wizard_has_cohort_permission() and has_capability()
 * @param string $permission = creator | validator
 * @param int $userid
 * @return boolean
 */
function wizard_has_permission($permission, $userid) {
    $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    if ( has_capability('local/crswizard:' . $permission, $systemcontext, $userid) ) {
        return true;
    }
    return wizard_has_cohort_permission($permission, $userid);
}

/**
 *  return all users who have $permission via cohort membership OR role assignation (capability)
 * @param string $permission = creator | validator
 * @return array(userid)
 */
function wizard_who_has_permission($permission) {
    $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    $users1 = array_map("__get_id", get_users_by_capability($systemcontext, 'local/crswizard:' . $permission, 'u.id'));
    $users2 = wizard_membersof_permitted_cohorts($permission);
    $users = array_unique(array_merge($users1, $users2));
    return $users;
}

function __get_id($a) {
    return $a->id;
}

/**
 * stop execution if the $permission is not granted to the $userid
 * @param string $permission = creator | validator
 * @param int $userid
 * @throws coding_exception
 */
function wizard_require_permission($permission, $userid) {
    if (! wizard_has_permission($permission, $userid) ) {
        throw new moodle_exception('Vous n\'avez pas la permission d\'accéder à cette page.');
    }
}