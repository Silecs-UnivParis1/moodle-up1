<?php

/**
 * Post installation and migration code.
 *
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../locallib.php');

function xmldb_auth_ldapup1_install() {
    global $CFG, $DB;

    echo "Création des catégories :<br />\n";
    insert_user_metadata_categories();

    echo "\nCréation des champs :<br />\n";
    insert_user_metadata_fields(true);

}

// insert into user_sync(id, ref_plugin, timemodified) select id, auth, unix_timestamp() from user;
// update user_sync set ref_plugin='auth_ldapup1' where ref_plugin='ldap' ;
