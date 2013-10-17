<?php
/**
 * Plugin upgrade code.
 *
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../locallib.php');

function xmldb_auth_ldapup1_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this

    if ($oldversion < 2012090501) {
        // Add userid field
        $table = new xmldb_table('user_sync');

        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);

        if ( ! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }


    if ($oldversion < 2013012300) {
        echo "Création des catégories :<br />\n";
        insert_user_metadata_categories();

        echo "\nCréation des champs :<br />\n";
        insert_user_metadata_fields(true);
    }

    if ($oldversion < 2013012404) {
        echo "Création des catégories :<br />\n";
        insert_user_metadata_categories();

        echo "\nCréation des champs :<br />\n";
        insert_user_metadata_fields(true);
    }

    if ($oldversion < 2013012404) {
        echo "Ajout index sur la table user_sync :<br />\n";

        $table = new xmldb_table('user_sync');
        if ( ! $dbman->index_exists('user_sync', 'userid') ) {
            $dbman->add_index('user_sync', 'userid');
        }
    }


    return true;
}
