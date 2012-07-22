<?php

/**
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from official auth_ldap
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// The Posix uid and gid of the 'nobody' account and 'nogroup' group.
if (!defined('AUTH_UID_NOBODY')) {
    define('AUTH_UID_NOBODY', -2);
}
if (!defined('AUTH_GID_NOGROUP')) {
    define('AUTH_GID_NOGROUP', -2);
}

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/ldaplib.php');

/**
 * LDAP authentication plugin.
 */
class auth_plugin_ldapup1 extends auth_plugin_base {

    /**
     * Init plugin config from database settings depending on the plugin auth type.
     */
    function init_plugin($authtype) {
        $this->pluginconfig = 'auth/ldapup1';
        $this->config = get_config($this->pluginconfig);
        if (empty($this->config->ldapencoding)) {
            $this->config->ldapencoding = 'utf-8';
        }
        if (empty($this->config->user_type)) {
            $this->config->user_type = 'default';
        }

        $ldap_usertypes = ldap_supported_usertypes();
        $this->config->user_type_name = $ldap_usertypes[$this->config->user_type];
        unset($ldap_usertypes);

        $default = ldap_getdefaults();

        // Use defaults if values not given
        foreach ($default as $key => $value) {
            // watch out - 0, false are correct values too
            if (!isset($this->config->{$key}) or $this->config->{$key} == '') {
                $this->config->{$key} = $value[$this->config->user_type];
            }
        }

        // Hack prefix to objectclass
        if (empty($this->config->objectclass)) {
            // Can't send empty filter
            $this->config->objectclass = '(objectClass=*)';
        } else if (stripos($this->config->objectclass, 'objectClass=') === 0) {
            // Value is 'objectClass=some-string-here', so just add ()
            // around the value (filter _must_ have them).
            $this->config->objectclass = '('.$this->config->objectclass.')';
        } else if (strpos($this->config->objectclass, '(') !== 0) {
            // Value is 'some-string-not-starting-with-left-parentheses',
            // which is assumed to be the objectClass matching value.
            // So build a valid filter with it.
            $this->config->objectclass = '(objectClass='.$this->config->objectclass.')';
        } else {
            // There is an additional possible value
            // '(some-string-here)', that can be used to specify any
            // valid filter string, to select subsets of users based
            // on any criteria. For example, we could select the users
            // whose objectClass is 'user' and have the
            // 'enabledMoodleUser' attribute, with something like:
            //
            //   (&(objectClass=user)(enabledMoodleUser=1))
            //
            // In this particular case we don't need to do anything,
            // so leave $this->config->objectclass as is.
        }
    }

    /**
     * Constructor with initialisation.
     */
    function auth_plugin_ldapup1() {
        $this->authtype = 'ldapup1'; //** @todo really clean ??
        $this->roleauth = 'auth_ldap'; //**@todo keep or change ?
        $this->errorlogtag = '[AUTH LDAP] '; //**@todo keep or change ?
        $this->init_plugin($this->authtype);
    }

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
     * Reads user information from ldap and returns it in array()
     *
     * Function should return all information available. If you are saving
     * this information to moodle user-table you should honor syncronization flags
     *
     * @param string $username username
     *
     * @return mixed array with no magic quotes or false on error
     */
    function get_userinfo($username) {
        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);

        $ldapconnection = $this->ldap_connect();
        if(!($user_dn = $this->ldap_find_userdn($ldapconnection, $extusername))) {
            return false;
        }

        $search_attribs = array();
        $attrmap = $this->ldap_attributes();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!in_array($value, $search_attribs)) {
                    array_push($search_attribs, $value);
                }
            }
        }

        if (!$user_info_result = ldap_read($ldapconnection, $user_dn, '(objectClass=*)', $search_attribs)) {
            return false; // error!
        }

        $user_entry = ldap_get_entries_moodle($ldapconnection, $user_info_result);
        if (empty($user_entry)) {
            return false; // entry not found
        }

        $result = array();
        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            $ldapval = NULL;
            foreach ($values as $value) {
                $entry = array_change_key_case($user_entry[0], CASE_LOWER);
                if (($value == 'dn') || ($value == 'distinguishedname')) {
                    $result[$key] = $user_dn;
                    continue;
                }
                if (!array_key_exists($value, $entry)) {
                    continue; // wrong data mapping!
                }
                if (is_array($entry[$value])) {
                    $newval = textlib::convert($entry[$value][0], $this->config->ldapencoding, 'utf-8');
                } else {
                    $newval = textlib::convert($entry[$value], $this->config->ldapencoding, 'utf-8');
                }
                if (!empty($newval)) { // favour ldap entries that are set
                    $ldapval = $newval;
                }
            }
            if (!is_null($ldapval)) {
                $result[$key] = $ldapval;
            }
        }

        $this->ldap_close();
        return $result;
    }

    /**
     * Reads user information from ldap and returns it in an object
     *
     * @param string $username username (with system magic quotes)
     * @return mixed object or false on error
     */
    function get_userinfo_asobj($username) {
        $user_array = $this->get_userinfo($username);
        if ($user_array == false) {
            return false; //error or not found
        }
        $user_array = truncate_userinfo($user_array);
        $user = new stdClass();
        foreach ($user_array as $key=>$value) {
            $user->{$key} = $value;
        }
        return $user;
    }

    /**
     * Returns all usernames from LDAP
     *
     * get_userlist returns all usernames from LDAP
     *
     * @return array
     */
    function get_userlist() {
        return $this->ldap_get_userlist("({$this->config->user_attribute}=*)");
    }

    /**
     * Checks if user exists on LDAP
     *
     * @param string $username
     */
    function user_exists($username) {
        $extusername = textlib::convert($username, 'utf-8', $this->config->ldapencoding);

        // Returns true if given username exists on ldap
        $users = $this->ldap_get_userlist('('.$this->config->user_attribute.'='.ldap_filter_addslashes($extusername).')');
        return count($users);
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
        $extusername = textlib::convert($userobject->username, 'utf-8', $this->config->ldapencoding);
        $extpassword = textlib::convert($plainpass, 'utf-8', $this->config->ldapencoding);

        switch ($this->config->passtype) {
            case 'md5':
                $extpassword = '{MD5}' . base64_encode(pack('H*', md5($extpassword)));
                break;
            case 'sha1':
                $extpassword = '{SHA}' . base64_encode(pack('H*', sha1($extpassword)));
                break;
            case 'plaintext':
            default:
                break; // plaintext
        }

        $ldapconnection = $this->ldap_connect();
        $attrmap = $this->ldap_attributes();

        $newuser = array();

        foreach ($attrmap as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                if (!empty($userobject->$key) ) {
                    $newuser[$value] = textlib::convert($userobject->$key, 'utf-8', $this->config->ldapencoding);
                }
            }
        }

        //Following sets all mandatory and other forced attribute values
        //User should be creted as login disabled untill email confirmation is processed
        //Feel free to add your user type and send patches to paca@sci.fi to add them
        //Moodle distribution

        switch ($this->config->user_type)  {
            case 'edir':
                $newuser['objectClass']   = array('inetOrgPerson', 'organizationalPerson', 'person', 'top');
                $newuser['uniqueId']      = $extusername;
                $newuser['logindisabled'] = 'TRUE';
                $newuser['userpassword']  = $extpassword;
                $uadd = ldap_add($ldapconnection, $this->config->user_attribute.'='.ldap_addslashes($extusername).','.$this->config->create_context, $newuser);
                break;
            case 'rfc2307':
            case 'rfc2307bis':
                // posixAccount object class forces us to specify a uidNumber
                // and a gidNumber. That is quite complicated to generate from
                // Moodle without colliding with existing numbers and without
                // race conditions. As this user is supposed to be only used
                // with Moodle (otherwise the user would exist beforehand) and
                // doesn't need to login into a operating system, we assign the
                // user the uid of user 'nobody' and gid of group 'nogroup'. In
                // addition to that, we need to specify a home directory. We
                // use the root directory ('/') as the home directory, as this
                // is the only one can always be sure exists. Finally, even if
                // it's not mandatory, we specify '/bin/false' as the login
                // shell, to prevent the user from login in at the operating
                // system level (Moodle ignores this).

                $newuser['objectClass']   = array('posixAccount', 'inetOrgPerson', 'organizationalPerson', 'person', 'top');
                $newuser['cn']            = $extusername;
                $newuser['uid']           = $extusername;
                $newuser['uidNumber']     = AUTH_UID_NOBODY;
                $newuser['gidNumber']     = AUTH_GID_NOGROUP;
                $newuser['homeDirectory'] = '/';
                $newuser['loginShell']    = '/bin/false';

                // IMPORTANT:
                // We have to create the account locked, but posixAccount has
                // no attribute to achive this reliably. So we are going to
                // modify the password in a reversable way that we can later
                // revert in user_activate().
                //
                // Beware that this can be defeated by the user if we are not
                // using MD5 or SHA-1 passwords. After all, the source code of
                // Moodle is available, and the user can see the kind of
                // modification we are doing and 'undo' it by hand (but only
                // if we are using plain text passwords).
                //
                // Also bear in mind that you need to use a binding user that
                // can create accounts and has read/write privileges on the
                // 'userPassword' attribute for this to work.

                $newuser['userPassword']  = '*'.$extpassword;
                $uadd = ldap_add($ldapconnection, $this->config->user_attribute.'='.ldap_addslashes($extusername).','.$this->config->create_context, $newuser);
                break;
            case 'ad':
                // User account creation is a two step process with AD. First you
                // create the user object, then you set the password. If you try
                // to set the password while creating the user, the operation
                // fails.

                // Passwords in Active Directory must be encoded as Unicode
                // strings (UCS-2 Little Endian format) and surrounded with
                // double quotes. See http://support.microsoft.com/?kbid=269190
                if (!function_exists('mb_convert_encoding')) {
                    print_error('auth_ldapup1_no_mbstring', 'auth_ldapup1');
                }

                // Check for invalid sAMAccountName characters.
                if (preg_match('#[/\\[\]:;|=,+*?<>@"]#', $extusername)) {
                    print_error ('auth_ldapup1_ad_invalidchars', 'auth_ldapup1');
                }

                // First create the user account, and mark it as disabled.
                $newuser['objectClass'] = array('top', 'person', 'user', 'organizationalPerson');
                $newuser['sAMAccountName'] = $extusername;
                $newuser['userAccountControl'] = AUTH_AD_NORMAL_ACCOUNT |
                                                 AUTH_AD_ACCOUNTDISABLE;
                $userdn = 'cn='.ldap_addslashes($extusername).','.$this->config->create_context;
                if (!ldap_add($ldapconnection, $userdn, $newuser)) {
                    print_error('auth_ldapup1_ad_create_req', 'auth_ldapup1');
                }

                // Now set the password
                unset($newuser);
                $newuser['unicodePwd'] = mb_convert_encoding('"' . $extpassword . '"',
                                                             'UCS-2LE', 'UTF-8');
                if(!ldap_modify($ldapconnection, $userdn, $newuser)) {
                    // Something went wrong: delete the user account and error out
                    ldap_delete ($ldapconnection, $userdn);
                    print_error('auth_ldapup1_ad_create_req', 'auth_ldapup1');
                }
                $uadd = true;
                break;
            default:
               print_error('auth_ldapup1_unsupportedusertype', 'auth_ldapup1', '', $this->config->user_type_name);
        }
        $this->ldap_close();
        return $uadd;
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
     * Syncronizes user fron external LDAP server to moodle user table
     *
     * Sync is now using username attribute.
     *
     * Syncing users removes or suspends users that dont exists anymore in external LDAP.
     * Creates new users and updates coursecreator status of users.
     *
     * @param bool $do_updates will do pull in data updates from LDAP if relevant
     */
    function sync_users($do_updates=true) {
        global $CFG, $DB;

        print_string('connectingldap', 'auth_ldapup1');
        $ldapconnection = $this->ldap_connect();

        $dbman = $DB->get_manager();

    /// Define table user to be created
        $table = new xmldb_table('tmp_extuser');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mnethostid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('username', XMLDB_INDEX_UNIQUE, array('mnethostid', 'username'));

        print_string('creatingtemptable', 'auth_ldapup1', 'tmp_extuser');
        $dbman->create_temp_table($table);

// var_dump($this->config);

        ////
        //// get user's list from ldap to sql in a scalable fashion
        ////
        // prepare some data we'll need
        $filter = '(&('.$this->config->user_attribute.'=*)'.$this->config->objectclass.')';

        $contexts = explode(';', $this->config->contexts);

        if (!empty($this->config->create_context)) {
              array_push($contexts, $this->config->create_context);
        }

        $fresult = array();
        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }
            if ($this->config->search_sub) {
                //use ldap_search to find first user from subtree
                $ldap_result = ldap_search($ldapconnection, $context,
                                           $filter,
                                           array($this->config->user_attribute));
            } else {
                //search only in this context
                $ldap_result = ldap_list($ldapconnection, $context,
                                         $filter,
                                         array($this->config->user_attribute));
            }

            if(!$ldap_result) {
                continue;
            }

            if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
                do {
                    $value = ldap_get_values_len($ldapconnection, $entry, $this->config->user_attribute);
                    $value = textlib::convert($value[0], $this->config->ldapencoding, 'utf-8');
                    $this->ldap_bulk_insert($value);
                } while ($entry = ldap_next_entry($ldapconnection, $entry));
            }
            unset($ldap_result); // free mem
        }

        /// preserve our user database
        /// if the temp table is empty, it probably means that something went wrong, exit
        /// so as to avoid mass deletion of users; which is hard to undo
        $count = $DB->count_records_sql('SELECT COUNT(username) AS count, 1 FROM {tmp_extuser}');
        if ($count < 1) {
            print_string('didntgetusersfromldap', 'auth_ldapup1');
            exit;
        } else {
            print_string('gotcountrecordsfromldap', 'auth_ldapup1', $count);
        }


/// User removal
        // Find users in DB that aren't in ldap -- to be removed!
        // this is still not as scalable (but how often do we mass delete?)
        if ($this->config->removeuser !== AUTH_REMOVEUSER_KEEP) {
            $sql = 'SELECT u.*
                      FROM {user} u
                      LEFT JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                     WHERE u.auth = ?
                           AND u.deleted = 0
                           AND e.username IS NULL';
            $remove_users = $DB->get_records_sql($sql, array($this->authtype));

            if (!empty($remove_users)) {
                print_string('userentriestoremove', 'auth_ldapup1', count($remove_users));

                foreach ($remove_users as $user) {
                    if ($this->config->removeuser == AUTH_REMOVEUSER_FULLDELETE) {
                        if (delete_user($user)) {
                            echo "\t"; print_string('auth_dbdeleteuser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)); echo "\n";
                        } else {
                            echo "\t"; print_string('auth_dbdeleteusererror', 'auth_db', $user->username); echo "\n";
                        }
                    } else if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                        $updateuser = new stdClass();
                        $updateuser->id = $user->id;
                        $updateuser->auth = 'nologin';
                        $DB->update_record('user', $updateuser);
                        echo "\t"; print_string('auth_dbsuspenduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)); echo "\n";
                    }
                }
            } else {
                print_string('nouserentriestoremove', 'auth_ldapup1');
            }
            unset($remove_users); // free mem!
        }

/// Revive suspended users
        if (!empty($this->config->removeuser) and $this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
            $sql = "SELECT u.id, u.username
                      FROM {user} u
                      JOIN {tmp_extuser} e ON (u.username = e.username AND u.mnethostid = e.mnethostid)
                     WHERE u.auth = 'nologin' AND u.deleted = 0";
            $revive_users = $DB->get_records_sql($sql);

            if (!empty($revive_users)) {
                print_string('userentriestorevive', 'auth_ldapup1', count($revive_users));

                foreach ($revive_users as $user) {
                    $updateuser = new stdClass();
                    $updateuser->id = $user->id;
                    $updateuser->auth = $this->authtype;
                    $DB->update_record('user', $updateuser);
                    echo "\t"; print_string('auth_dbreviveduser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id)); echo "\n";
                }
            } else {
                print_string('nouserentriestorevive', 'auth_ldapup1');
            }

            unset($revive_users);
        }


/// User Updates - time-consuming (optional)
        if ($do_updates) {
            // Narrow down what fields we need to update
            $all_keys = array_keys(get_object_vars($this->config));
            $updatekeys = array();
            foreach ($all_keys as $key) {
                if (preg_match('/^field_updatelocal_(.+)$/', $key, $match)) {
                    // If we have a field to update it from
                    // and it must be updated 'onlogin' we
                    // update it on cron
                    if (!empty($this->config->{'field_map_'.$match[1]})
                         and $this->config->{$match[0]} === 'onlogin') {
                        array_push($updatekeys, $match[1]); // the actual key name
                    }
                }
            }
            unset($all_keys); unset($key);

        } else {
            print_string('noupdatestobedone', 'auth_ldapup1');
        }
        if ($do_updates and !empty($updatekeys)) { // run updates only if relevant
            $users = $DB->get_records_sql('SELECT u.username, u.id
                                             FROM {user} u
                                            WHERE u.deleted = 0 AND u.auth = ? AND u.mnethostid = ?',
                                          array($this->authtype, $CFG->mnet_localhost_id));
            if (!empty($users)) {
                print_string('userentriestoupdate', 'auth_ldapup1', count($users));

                $sitecontext = get_context_instance(CONTEXT_SYSTEM);
                if (!empty($this->config->creators) and !empty($this->config->memberattribute)
                  and $roles = get_archetype_roles('coursecreator')) {
                    $creatorrole = array_shift($roles);      // We can only use one, let's use the first one
                } else {
                    $creatorrole = false;
                }

                $transaction = $DB->start_delegated_transaction();
                $xcount = 0;
                $maxxcount = 100;

                foreach ($users as $user) {
                    echo "\t"; print_string('auth_dbupdatinguser', 'auth_db', array('name'=>$user->username, 'id'=>$user->id));
                    if (!$this->update_user_record($user->username, $updatekeys)) {
                        echo ' - '.get_string('skipped');
                    }
                    echo "\n";
                    $xcount++;

                    // Update course creators if needed
                    if ($creatorrole !== false) {
                        if ($this->iscreator($user->username)) {
                            role_assign($creatorrole->id, $user->id, $sitecontext->id, $this->roleauth);
                        } else {
                            role_unassign($creatorrole->id, $user->id, $sitecontext->id, $this->roleauth);
                        }
                    }
                }
                $transaction->allow_commit();
                unset($users); // free mem
            }
        } else { // end do updates
            print_string('noupdatestobedone', 'auth_ldapup1');
        }

/// User Additions
        // Find users missing in DB that are in LDAP
        // and gives me a nifty object I don't want.
        // note: we do not care about deleted accounts anymore, this feature was replaced by suspending to nologin auth plugin
        $sql = 'SELECT e.id, e.username
                  FROM {tmp_extuser} e
                  LEFT JOIN {user} u ON (e.username = u.username AND e.mnethostid = u.mnethostid)
                 WHERE u.id IS NULL';
        $add_users = $DB->get_records_sql($sql);

        if (!empty($add_users)) {
            print_string('userentriestoadd', 'auth_ldapup1', count($add_users));

            $sitecontext = get_context_instance(CONTEXT_SYSTEM);
            if (!empty($this->config->creators) and !empty($this->config->memberattribute)
              and $roles = get_archetype_roles('coursecreator')) {
                $creatorrole = array_shift($roles);      // We can only use one, let's use the first one
            } else {
                $creatorrole = false;
            }

            $transaction = $DB->start_delegated_transaction();
            foreach ($add_users as $user) {
                $user = $this->get_userinfo_asobj($user->username);

                // Prep a few params
                $user->modified   = time();
                $user->confirmed  = 1;
                $user->auth       = $this->authtype;
                $user->mnethostid = $CFG->mnet_localhost_id;
                // get_userinfo_asobj() might have replaced $user->username with the value
                // from the LDAP server (which can be mixed-case). Make sure it's lowercase
                $user->username = trim(textlib::strtolower($user->username));
                if (empty($user->lang)) {
                    $user->lang = $CFG->lang;
                }

                $id = $DB->insert_record('user', $user);
                echo "\t"; print_string('auth_dbinsertuser', 'auth_db', array('name'=>$user->username, 'id'=>$id)); echo "\n";
                if (!empty($this->config->forcechangepassword)) {
                    set_user_preference('auth_forcepasswordchange', 1, $id);
                }

                // Add course creators if needed
                if ($creatorrole !== false and $this->iscreator($user->username)) {
                    role_assign($creatorrole->id, $id, $sitecontext->id, $this->roleauth);
                }

            }
            $transaction->allow_commit();
            unset($add_users); // free mem
        } else {
            print_string('nouserstobeadded', 'auth_ldapup1');
        }

        $dbman->drop_table($table);
        $this->ldap_close();

        return true;
    }

    /**
     * Update a local user record from an external source.
     * This is a lighter version of the one in moodlelib -- won't do
     * expensive ops such as enrolment.
     *
     * If you don't pass $updatekeys, there is a performance hit and
     * values removed from LDAP won't be removed from moodle.
     *
     * @param string $username username
     * @param boolean $updatekeys true to update the local record with the external LDAP values.
     */

    function update_user_record($username, $updatekeys = false) {
        global $CFG, $DB;

        // Just in case check text case
        $username = trim(textlib::strtolower($username));

        // Get the current user record
        $user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id));
        if (empty($user)) { // trouble
            error_log($this->errorlogtag.get_string('auth_dbusernotexist', 'auth_db', '', $username));
            print_error('auth_dbusernotexist', 'auth_db', '', $username);
            die;
        }

        // Protect the userid from being overwritten
        $userid = $user->id;

        if ($newinfo = $this->get_userinfo($username)) {
            $newinfo = truncate_userinfo($newinfo);

            if (empty($updatekeys)) { // all keys? this does not support removing values
                $updatekeys = array_keys($newinfo);
            }

            foreach ($updatekeys as $key) {
                if (isset($newinfo[$key])) {
                    $value = $newinfo[$key];
                } else {
                    $value = '';
                }

                if (!empty($this->config->{'field_updatelocal_' . $key})) {
                    if ($user->{$key} != $value) { // only update if it's changed
                        $DB->set_field('user', $key, $value, array('id'=>$userid));
                    }
                }
            }
        } else {
            return false;
        }
        return $DB->get_record('user', array('id'=>$userid, 'deleted'=>0));
    }

    /**
     * Bulk insert in SQL's temp table
     */
    function ldap_bulk_insert($username) {
        global $DB, $CFG;

        $username = textlib::strtolower($username); // usernames are __always__ lowercase.
        $DB->insert_record_raw('tmp_extuser', array('username'=>$username,
                                                    'mnethostid'=>$CFG->mnet_localhost_id), false, true);
        echo '.';
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
     * Take expirationtime and return it as unix timestamp in seconds
     *
     * Takes expiration timestamp as read from LDAP and returns it as unix timestamp in seconds
     * Depends on $this->config->user_type variable
     *
     * @param mixed time   Time stamp read from LDAP as it is.
     * @param string $ldapconnection Only needed for Active Directory.
     * @param string $user_dn User distinguished name for the user we are checking password expiration (only needed for Active Directory).
     * @return timestamp
     */
    function ldap_expirationtime2unix ($time, $ldapconnection, $user_dn) {
        $result = false;
        switch ($this->config->user_type) {
            case 'edir':
                $yr=substr($time, 0, 4);
                $mo=substr($time, 4, 2);
                $dt=substr($time, 6, 2);
                $hr=substr($time, 8, 2);
                $min=substr($time, 10, 2);
                $sec=substr($time, 12, 2);
                $result = mktime($hr, $min, $sec, $mo, $dt, $yr);
                break;
            case 'rfc2307':
            case 'rfc2307bis':
                $result = $time * DAYSECS; // The shadowExpire contains the number of DAYS between 01/01/1970 and the actual expiration date
                break;
            case 'ad':
                $result = $this->ldap_get_ad_pwdexpire($time, $ldapconnection, $user_dn);
                break;
            default:
                print_error('auth_ldapup1_usertypeundefined', 'auth_ldapup1');
        }
        return $result;
    }

    /**
     * Takes unix timestamp and returns it formated for storing in LDAP
     *
     * @param integer unix time stamp
     */
    function ldap_unix2expirationtime($time) {
        $result = false;
        switch ($this->config->user_type) {
            case 'edir':
                $result=date('YmdHis', $time).'Z';
                break;
            case 'rfc2307':
            case 'rfc2307bis':
                $result = $time ; // Already in correct format
                break;
            default:
                print_error('auth_ldapup1_usertypeundefined2', 'auth_ldapup1');
        }
        return $result;

    }

    /**
     * Returns user attribute mappings between moodle and LDAP
     *
     * @return array
     */

    function ldap_attributes () {
        $moodleattributes = array();
        foreach ($this->userfields as $field) {
            if (!empty($this->config->{"field_map_$field"})) {
                $moodleattributes[$field] = textlib::strtolower(trim($this->config->{"field_map_$field"}));
                if (preg_match('/,/', $moodleattributes[$field])) {
                    $moodleattributes[$field] = explode(',', $moodleattributes[$field]); // split ?
                }
            }
        }
        $moodleattributes['username'] = textlib::strtolower(trim($this->config->user_attribute));
        return $moodleattributes;
    }

    /**
     * Returns all usernames from LDAP
     *
     * @param $filter An LDAP search filter to select desired users
     * @return array of LDAP user names converted to UTF-8
     */
    function ldap_get_userlist($filter='*') {
        $fresult = array();

        $ldapconnection = $this->ldap_connect();

        if ($filter == '*') {
           $filter = '(&('.$this->config->user_attribute.'=*)'.$this->config->objectclass.')';
        }

        $contexts = explode(';', $this->config->contexts);
        if (!empty($this->config->create_context)) {
              array_push($contexts, $this->config->create_context);
        }

        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            if ($this->config->search_sub) {
                // Use ldap_search to find first user from subtree
                $ldap_result = ldap_search($ldapconnection, $context,
                                           $filter,
                                           array($this->config->user_attribute));
            } else {
                // Search only in this context
                $ldap_result = ldap_list($ldapconnection, $context,
                                         $filter,
                                         array($this->config->user_attribute));
            }

            if(!$ldap_result) {
                continue;
            }

            $users = ldap_get_entries_moodle($ldapconnection, $ldap_result);

            // Add found users to list
            for ($i = 0; $i < count($users); $i++) {
                $extuser = textlib::convert($users[$i][$this->config->user_attribute][0],
                                             $this->config->ldapencoding, 'utf-8');
                array_push($fresult, $extuser);
            }
        }

        $this->ldap_close();
        return $fresult;
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
     * Sync roles for this user
     *
     * @param $user object user object (without system magic quotes)
     */
    function sync_roles($user) {
        $iscreator = $this->iscreator($user->username);
        if ($iscreator === null) {
            return; // Nothing to sync - creators not configured
        }

        if ($roles = get_archetype_roles('coursecreator')) {
            $creatorrole = array_shift($roles);      // We can only use one, let's use the first one
            $systemcontext = get_context_instance(CONTEXT_SYSTEM);

            if ($iscreator) { // Following calls will not create duplicates
                role_assign($creatorrole->id, $user->id, $systemcontext->id, $this->roleauth);
            } else {
                // Unassign only if previously assigned by this plugin!
                role_unassign($creatorrole->id, $user->id, $systemcontext->id, $this->roleauth);
            }
        }
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        global $CFG, $OUTPUT;

        if (!function_exists('ldap_connect')) { // Is php-ldap really there?
            echo $OUTPUT->notification(get_string('auth_ldapup1_noextension', 'auth_ldapup1'));
            return;
        }

        include($CFG->dirroot.'/auth/ldapup1/config.html'); //** @todo clean or not ?
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        // Set to defaults if undefined
        if (!isset($config->host_url)) {
             $config->host_url = '';
        }
        if (empty($config->ldapencoding)) {
         $config->ldapencoding = 'utf-8';
        }
        if (!isset($config->contexts)) {
             $config->contexts = '';
        }
        if (!isset($config->user_type)) {
             $config->user_type = 'default';
        }
        if (!isset($config->user_attribute)) {
             $config->user_attribute = '';
        }
        if (!isset($config->search_sub)) {
             $config->search_sub = '';
        }
        if (!isset($config->opt_deref)) {
             $config->opt_deref = LDAP_DEREF_NEVER;
        }
        if (!isset($config->preventpassindb)) {
             $config->preventpassindb = 0;
        }
        if (!isset($config->bind_dn)) {
            $config->bind_dn = '';
        }
        if (!isset($config->bind_pw)) {
            $config->bind_pw = '';
        }
        if (!isset($config->ldap_version)) {
            $config->ldap_version = '3';
        }
        if (!isset($config->objectclass)) {
            $config->objectclass = '';
        }
        if (!isset($config->memberattribute)) {
            $config->memberattribute = '';
        }
        if (!isset($config->memberattribute_isdn)) {
            $config->memberattribute_isdn = '';
        }
        if (!isset($config->removeuser)) {
            $config->removeuser = AUTH_REMOVEUSER_KEEP;
        }
        if (!isset($config->sync_condition)) {
            $config->sync_condition = '';
        }

        // Try to remove duplicates before storing the contexts (to avoid problems in sync_users()).
        $config->contexts = explode(';', $config->contexts);
        $config->contexts = array_map(create_function('$x', 'return textlib::strtolower(trim($x));'),
                                      $config->contexts);
        $config->contexts = implode(';', array_unique($config->contexts));

        // Save settings
        set_config('host_url', trim($config->host_url), $this->pluginconfig);
        set_config('ldapencoding', trim($config->ldapencoding), $this->pluginconfig);
        set_config('contexts', $config->contexts, $this->pluginconfig);
        set_config('user_type', textlib::strtolower(trim($config->user_type)), $this->pluginconfig);
        set_config('user_attribute', textlib::strtolower(trim($config->user_attribute)), $this->pluginconfig);
        set_config('search_sub', $config->search_sub, $this->pluginconfig);
        set_config('opt_deref', $config->opt_deref, $this->pluginconfig);
        set_config('preventpassindb', $config->preventpassindb, $this->pluginconfig);
        set_config('bind_dn', trim($config->bind_dn), $this->pluginconfig);
        set_config('bind_pw', $config->bind_pw, $this->pluginconfig);
        set_config('ldap_version', $config->ldap_version, $this->pluginconfig);
        set_config('objectclass', trim($config->objectclass), $this->pluginconfig);
        set_config('memberattribute', textlib::strtolower(trim($config->memberattribute)), $this->pluginconfig);
        set_config('memberattribute_isdn', $config->memberattribute_isdn, $this->pluginconfig);
        set_config('removeuser', $config->removeuser, $this->pluginconfig);
        set_config('sync_condition', trim($config->sync_condition), $this->pluginconfig);

        return true;
    }


    /**
     * Connect to the LDAP server, using the plugin configured
     * settings. It's actually a wrapper around ldap_connect_moodle()
     *
     * @return resource A valid LDAP connection (or dies if it can't connect)
     */
    function ldap_connect() {
        // Cache ldap connections. They are expensive to set up
        // and can drain the TCP/IP ressources on the server if we
        // are syncing a lot of users (as we try to open a new connection
        // to get the user details). This is the least invasive way
        // to reuse existing connections without greater code surgery.
        if(!empty($this->ldapconnection)) {
            $this->ldapconns++;
            return $this->ldapconnection;
        }

        if($ldapconnection = ldap_connect_moodle($this->config->host_url, $this->config->ldap_version,
                                                 $this->config->user_type, $this->config->bind_dn,
                                                 $this->config->bind_pw, $this->config->opt_deref,
                                                 $debuginfo)) {
            $this->ldapconns = 1;
            $this->ldapconnection = $ldapconnection;
            return $ldapconnection;
        }

        print_error('auth_ldapup1_noconnect_all', 'auth_ldapup1', '', $debuginfo);
    }

    /**
     * Disconnects from a LDAP server
     *
     */
    function ldap_close() {
        $this->ldapconns--;
        if($this->ldapconns == 0) {
            @ldap_close($this->ldapconnection);
            unset($this->ldapconnection);
        }
    }

    /**
     * Search specified contexts for username and return the user dn
     * like: cn=username,ou=suborg,o=org. It's actually a wrapper
     * around ldap_find_userdn().
     *
     * @param resource $ldapconnection a valid LDAP connection
     * @param string $extusername the username to search (in external LDAP encoding, no db slashes)
     * @return mixed the user dn (external LDAP encoding) or false
     */
    function ldap_find_userdn($ldapconnection, $extusername) {
        $ldap_contexts = explode(';', $this->config->contexts);
        if (!empty($this->config->create_context)) {
            array_push($ldap_contexts, $this->config->create_context);
        }

        return ldap_find_userdn($ldapconnection, $extusername, $ldap_contexts, $this->config->objectclass,
                                $this->config->user_attribute, $this->config->search_sub);
    }

} // End of the class
