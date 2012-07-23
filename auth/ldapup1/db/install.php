<?php

/**
 * Post installation and migration code.
 *
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_auth_ldapup1_install() {
    global $CFG, $DB;

}

// insert into user_sync(id, ref_plugin, timemodified) select id, auth, unix_timestamp() from user;
// update user_sync set ref_plugin='auth_ldapup1' where ref_plugin='ldap' ;
