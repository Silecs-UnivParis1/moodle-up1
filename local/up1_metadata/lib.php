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
 * return an html string <span title="...">...</span> for easy display of multiple metadata values
 * displays the main value, while the title tooltip displays the whole list on mouseover
 * @param int $courseid
 * @param string $field UP1 metadata text, ex. composante
 * @param bool $error : if set, throw an exception if $field isn't found ; otherwise return an empty string
 * @param bool $prefix if set, prefixes each item of the list with the given string
 * @return (html) string
 */
function up1_meta_html_multi($courseid, $field, $error=false, $prefix = '') {
    $text = up1_meta_get_text($courseid, $field, $error);
    $items = array_filter(array_unique(explode(';', $text)));

    if (count($items) == 0) {
        return '—';
    }
    if (count($items) == 1) {
        return '<span>' . $prefix . $items[0] . '</span>';
    }
    $brief = $prefix . $items[0] . ' +';
    $long = $prefix . join(', ' . $prefix, $items);
    return '<span title="' . $long . '">' . $brief . '</span>';
}

/**
 * return a multiple metadata up1 as a formatted list ; ex. "UFR02-... / UFR04-..."
 * identic values are merged
 * @param int $courseid
 * @param string $field UP1 metadata text, ex. composante
 * @param bool $error : if set, throw an exception if $field isn't found ; otherwise return an empty string
 * @param string $separator
 * @param bool $prefix if set, prefixes the list by the field name, ex. "Niveau : L1 / L2"
 */
function up1_meta_get_list($courseid, $field, $error=false, $separator=' / ', $prefix = false) {
    global $DB;

    $text = up1_meta_get_text($courseid, $field, $error);
    $items = array_unique(explode(';', $text));
    $res = join($separator, $items);
    if ( $res ) {
        if ($prefix) {
            $fieldname = $DB->get_field('custom_info_field', 'name', array('shortname' => $field), MUST_EXIST);
            $res = $fieldname . ' : ' . $res;
        }
        return $res;
    }
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
        return array('date' => false, 'datetime' => false, 'datefr' => false);
    }
    return  array(
        'date' => userdate($dtime, '%Y-%m-%d'),
        'datetime' => userdate($dtime, '%Y-%m-%d %H:%M:%S'),
        'datefr' => userdate($dtime, '%d/%m/%Y'),
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

/**
 *
 * @param string $object = 'course' or 'user'
 * @param array(string) $fields ex. array('up1complement', 'up1diplome', 'up1cycle')
 */
function up1_meta_gen_sql_query($object, $fields) {
   global $DB;

   $sql = "SELECT shortname, id FROM {custom_info_field} WHERE objectname = ? AND shortname IN ('"
        . implode("' ,'", $fields) . "')" ;
   $fieldids = $DB->get_records_sql_menu($sql, array($object));

   $select = "SELECT c.id " ;
   $from = "FROM {course} c ";
   foreach ($fields as $field) {
       $fid = $fieldids[$field];
       $table = "cid" . $fid;
       $select = $select . ", ${table}.data AS $field ";
       $from = $from . "\n  JOIN {custom_info_data} AS ${table} "
                    . " ON ( ${table}.fieldid = $fid AND ${table}.objectid = c.id )" ;
   }
   $sql = $select . $from;
   return $sql;
}