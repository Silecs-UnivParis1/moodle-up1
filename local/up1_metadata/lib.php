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
 * @param bool $error : if set, throw an exception if $field isn't found ; otherwise return an empty string
 */
function up1_meta_get_text($courseid, $field, $error=false) {
    global $DB;

    $prefix = 'up1';
    if ( substr($field, 0, 3) !== 'up1' ) {
        $field = $prefix . $field;
    }
    $sql = "SELECT data FROM {custom_info_field} cf "
         . "JOIN {custom_info_data} cd ON (cf.id = cd.fieldid) "
         . "WHERE cf.objectname='course' AND cd.objectname='course' AND cf.shortname=? AND cd.objectid=?";
    $res = $DB->get_field_sql($sql, array($field, $courseid));
    if ( $error && ! $res ) {
        throw new coding_exception('Erreur ! champ "' . $field . '" absent');
        return '';
    }
    if ( ! $res ) {
        return '';
    }
    return $res;
}

/**
 * return a metadata up1 as date
 * @global type $DB
 * @param int $courseid
 * @param type $field UP1 metadata date, ex. datedemande
 */
function up1_meta_get_date($courseid, $field) {

    $dtime = up1_meta_get_text($courseid, $field);
    if ($dtime == 0) {
        return array('date' => '', 'datetime' => '');
    }
    return  array(
        'date' => userdate($dtime, '%Y-%m-%d'),
        'datetime' => userdate($dtime, '%Y-%m-%d %H:%M:%S'),
        );
}

/**
 * return a metadata up1 as (id, name) assoc. array
 * @global type $DB
 * @param int $courseid
 * @param string $field UP1 metadata userid, among (demandeurid, approbateurpropid, approbateureffid)
 * @param bool $username : if set, append the username after the fullname
 * @return array('id' => ..., 'name' => ...)
 */
function up1_meta_get_user($courseid, $field, $username=true) {
    global $DB;

    $userid = up1_meta_get_text($courseid, $field);
    if ($userid) {
        $dbuser = $DB->get_record('user', array('id' => $userid));
        if ($dbuser) {
            $fullname = $dbuser->firstname .' '. $dbuser->lastname . ($username ? ' ('.$dbuser->username. ')' : '');
            return array('id' => $userid, 'name' => $fullname);
        } else {
            return array('id' => $userid, 'name' => '(id=' . $userid . ')');
        }
    }
    else {
        return array('id' => false, 'name' => '');
    }
}

/**
 * get the id in table custom_info_data for a given (course id, field shortname)
 * @global type $DB
 * @param in $courseid
 * @param string $field (shortname)
 * @return type
 */
function up1_meta_get_id($courseid, $field) {
    global $DB;

    $prefix = 'up1';
    if ( substr($field, 0, 3) !== 'up1' ) {
        $field = $prefix . $field;
    }
    $sql = "SELECT cd.id FROM {custom_info_data} cd "
         . " JOIN {custom_info_field} cf ON (cd.fieldid = cf.id AND cd.objectname='course' AND cf.objectname='course') "
         . " WHERE cf.shortname=? AND cd.objectid=?";
	$id = $DB->get_field_sql($sql, array($field, $courseid), MUST_EXIST);

    //echo $sql ."\n -> $id\n";
    return $id;
}