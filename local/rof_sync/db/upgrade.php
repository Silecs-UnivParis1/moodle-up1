<?php
/**
 * @package    local
 * @subpackage rof_sync
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 *
 * @param int $oldversion The old version of the assign module
 * @return bool
 */
function xmldb_local_rof_sync_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013052903) {

        $table = new xmldb_table('rof_component');
        $field1 = new xmldb_field('localname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timesync');
        $field2 = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'localname');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
            $dbman->add_field($table, $field2);
        }
        $table = new xmldb_table('rof_program');
        $field1 = new xmldb_field('localname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timesync');
        $field2 = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'localname');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
            $dbman->add_field($table, $field2);
        }
        $table = new xmldb_table('rof_course');
        $field1 = new xmldb_field('localname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timesync');
        $field2 = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'localname');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
            $dbman->add_field($table, $field2);
        }


    }

    return true;
}