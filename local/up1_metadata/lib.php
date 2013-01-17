<?php
/**
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * return a metadata up1 as text
 * @global type $DB
 * @param int $courseid
 * @param string $field UP1 metadata text, ex. complement
 */
function up1_meta_get_text($courseid, $field) {
    global $DB;

    $prefix = 'up1';
    if ( substr($field, 0, 3) !== 'up1' ) {
        $field = $prefix . $field;
    }
    $sql = "SELECT data FROM {custom_info_field} cf "
         . "JOIN {custom_info_data} cd ON (cf.id = cd.fieldid) "
         . "WHERE cf.objectname='course' AND cd.objectname='course' AND cf.shortname=? AND cd.objectid=?";
    return $DB->get_field_sql($sql, array($field, $courseid), MUST_EXIST);
}

/**
 * return a metadata up1 as date
 * @global type $DB
 * @param int $courseid
 * @param type $field UP1 metadata date, ex. datedemande
 */
function up1_meta_get_date($courseid, $field) {

    $dtime = up1_meta_get_text($courseid, $field);
    return  userdate($dtime);
}

/**
 * return a metadata up1 as (id, name) assoc. array
 * @global type $DB
 * @param int $courseid
 * @param type $field UP1 metadata userid, among (demandeurid, approbateurpropid, approbateureffid)
 * @return array('id' => ..., 'name' => ...)
 */
function up1_meta_get_user($courseid, $field) {
    global $DB;

    $userid = up1_meta_get_text($courseid, $field);
    if ($userid) {
        $dbuser = $DB->get_record('user', array('id' => $userid));
        return array(
            'id' => $userid,
            'name' => $dbuser->firstname .' '. $dbuser->lastname .' '. $dbuser->username
            );
    }
    else {
        return array('id' => false, 'name' => '');
    }
}