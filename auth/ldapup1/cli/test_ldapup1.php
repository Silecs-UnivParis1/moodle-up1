<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions



// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

if (!is_enabled_auth('ldapup1')) {
    die;
}

$ldapauth = get_auth_plugin('ldapup1');


echo "Liste les attributs LDAP lus d'après le formulaire de config du module\n";
echo "array ( moodle field => ldap attribute )\n";
$attrs = $ldapauth->ldap_attributes();
var_dump($attrs);



