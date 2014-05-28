<?php
/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../locallib.php');

function xmldb_local_cohortsyncup1_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this

    if ($oldversion < 2014052001) {
        $table = new xmldb_table('cohort');
        $field1 = new xmldb_field('up1category', XMLDB_TYPE_CHAR, '100', null, false, false, '');
        // up1category is one of the cohort categories, as defined by the function groupKeyToCategory()
        $field2 = new xmldb_field('up1period', XMLDB_TYPE_CHAR, '100', null, false, false, '');
        if ( ! $dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if ( ! $dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
    }

        if ($oldversion < 2014052200) {
        $table = new xmldb_table('cohort');
        $field1 = new xmldb_field('up1key', XMLDB_TYPE_CHAR, '100', null, false, false, '');
        // up1key is exactly the upstream key if this cohort is to be synced, or '' otherwise
        if ( ! $dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
    }
    return true;
}
