<?php

/**
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from official auth_ldap
 * this file contains all the trivial methods (ie which returns TRUE or FALSE),
 * simplified from the original auth.php, which are necessary to the Moodle auth_plugin interface
 */


abstract class auth_plugin_trivial extends auth_plugin_base {

    /**
     * Returns true if the username and password work => DISABLED
     * @param string $username The username (without system magic quotes)
     * @param string $password The password (without system magic quotes)
     *
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
        return false;
    }


    /**
     * Creates a new user on LDAP.
     * By using information in userobject
     * Use user_exists to prevent duplicate usernames
     *
     * @param mixed $userobject  Moodle userobject
     * @param mixed $plainpass   Plaintext password
     */
    function user_create($userobject, $plainpass) {
        return false;
    }

    /**
     * Returns true if plugin allows resetting of password from moodle.
     *
     * @return bool
     */
    function can_reset_password() {
        return false;
    }

    /**
     * Returns true if plugin allows signup and user creation.
     *
     * @return bool
     */
    function can_signup() {
        return false;
    }

    /**
     * Sign up a new user ready for confirmation. => DISABLED
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    function user_signup($user, $notify=true) {
        return false;
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    function can_confirm() {
        return false;
    }

    /**
     * Confirm the new user as registered. => DISABLED
     * @param string $username
     * @param string $confirmsecret
     */
    function user_confirm($username, $confirmsecret) {
            return AUTH_CONFIRM_ERROR;
    }

    /**
     * Return number of days to user password expires => DISABLED
     * @param mixed $username username
     * @return integer
     */
    function password_expire($username) {
        return 0;
    }


    /**
     * Activates (enables) user in external LDAP so user can login => DISABLED
     *
     * @param mixed $username
     * @return boolean result
     */
    function user_activate($username) {
        return true;
    }

    /**
     * Returns true if user should be coursecreator => DISABLED
     *
     * @param mixed $username    username (without system magic quotes)
     * @return mixed result      null if course creators is not configured, boolean otherwise.
     */
    function iscreator($username) {
        return null;
    }

    /**
     * Called when the user record is updated => DISABLED
     * @param mixed $olduser     Userobject before modifications    (without system magic quotes)
     * @param mixed $newuser     Userobject new modified userobject (without system magic quotes)
     * @return boolean result
     *
     */
    function user_update($olduser, $newuser) {
        return false;
    }

    /**
     * Changes userpassword in LDAP => DISABLED
     * @param  object  $user        User table object
     * @param  string  $newpassword Plaintext password (not crypted/md5'ed)
     * @return boolean result
     *
     */
    function user_update_password($user, $newpassword) {
        return false;
    }



    /**
     * Indicates if password hashes should be stored in local moodle database.
     *
     * @return bool true means flag 'not_cached' stored instead of password hash
     */
    function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's password, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    function change_password_url() {
        return null;
    }


    /**
     * Sync roles for this user => DISABLED
     *
     * @param $user object user object (without system magic quotes)
     */
    function sync_roles($user) {
        return false;
    }

}