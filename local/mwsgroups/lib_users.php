<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage mwsgroups
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $DB moodle_database */

/**
 * search users according to filters.
 * ** MySQL ONLY **
 */
class mws_search_users {
    /** @var int max num of results */
    public $maxrows = MWS_SEARCH_MAXROWS;

    /** @var string 'no' (no students) | 'only' | 'both' (default) */
    public $filterstudent = 'both';

    /** @var boolean Add a field "affiliation" to each user returned */
    public $affiliation = false;

    /** @var boolean Add a field "supannEntiteAffectation" to each user returned */
    public $affectation = true;

    /** @var array Exclude users with these usernames */
    public $exclude = array();

    /** @var array Restrict to the following cohorts names */
    public $cohorts = array();

    /** @var boolean If a cohorts filter is active, add users that have the site capacity */
    public $addValidators = true;

    const affiliationFieldName = 'up1edupersonprimaryaffiliation';
    const validatorCapacity = 'local/crswizard:validator';

    private $exclude_map = array();

    /**
     * Checks that the parameters are valid, and ints some helper properties.
     */
    private function init() {
        if (!is_array($this->exclude) || !is_string($this->filterstudent) || !is_array($this->cohorts)) {
            throw new Exception('Invalid arg type for mws_search_users');
        }

        $this->maxrows = (int) $this->maxrows;
        if (!$this->maxrows || $this->maxrows > MWS_SEARCH_MAXROWS) {
            $this->maxrows = MWS_SEARCH_MAXROWS;
        }

        $this->exclude_map = array();
        foreach ($this->exclude as $name) {
            $this->exclude_map[$name] = 1;
        }
    }

    /**
     * search users according to filters.
     * ** MySQL ONLY **
     * @global moodle_database $DB
     * @param string $token to search in user table
     * @return array
     */
    function search($token) {
        global $DB;
        $this->init();
        $ptoken = $DB->sql_like_escape($token) . '%';
        $sql = $this->buildSql();

        $records = $DB->get_records_sql($sql, array($token, $ptoken, $ptoken, $ptoken, $ptoken), 0, $this->maxrows);
        $users = array();
        $sqlbyuser = "SELECT c.idnumber, c.name FROM {cohort} c JOIN {cohort_members} cm ON (c.id = cm.cohortid) "
             . "WHERE c.idnumber LIKE 'structures-%' AND cm.userid = ? ";
        foreach ($records as $record) {
            if (isset($this->exclude_map[$record->username])) {
                continue;
            }
            $user = array(
                    'uid' => $record->username,
                    'displayName' => $record->firstname . ' ' . $record->lastname,
            );
            if ($this->affectation) {
                $res = $DB->get_records_sql_menu($sqlbyuser, array($record->id));
                $user['supannEntiteAffectation'] = array_unique(
                        array_map(array('self', 'groupNameToShortname'), array_values($res))
                );
            }
            if ($this->affiliation) {
                $user['affiliation'] = $record->affiliation;
            }
            $users[] = $user;
        }
        return $users;
    }

    /**
     * Build the SQL from the object properties.
     *
     * @return string
     */
    private function buildSql() {
        $select = "SELECT u.id, u.username, u.firstname, u.lastname";
        $from =  "FROM {user} u";
        $where = "WHERE ( (u.mnethostid = 1 AND u.username = ?)  OR  u.firstname LIKE ? OR u.lastname LIKE ? "
                . "OR  CONCAT(u.firstname, ' ', u.lastname) LIKE ?  OR  CONCAT(u.lastname, ' ', u.firstname) LIKE ? )";
        if ($this->filterstudent == 'no') {
            $where = " AND u.idnumber = '' ";
        } else if ($this->filterstudent == 'only') {
            $where = " AND u.idnumber != '' ";
        }
        if ($this->affiliation) {
            $fieldId = $this->getAffiliationFieldId();
            $select .= ", d1.data AS affiliation ";
            $from .= " LEFT JOIN custom_info_data d1 ON (d1.fieldid = $fieldId AND d1.objectid = u.id) ";
        }
        if ($this->cohorts) {
            $cohortsId = $this->cohortsNamesToId($this->cohorts);
            if ($cohortsId) {
                $roleIds = $this->getValidatorsRolesIds();
                $from .= " LEFT JOIN {cohort_members} cm ON (cm.userid = u.id AND cm.cohortid IN ($cohortsId)) ";
                if ($roleIds) {
                    $from .= " LEFT JOIN {role_assignments} ra "
                            . "ON (ra.userid = u.id AND ra.contextid = 1 AND ra.roleid IN ($roleIds)) ";
                    $where .= " AND (cm.cohortid IS NOT NULL OR ra.roleid IS NOT NULL) ";
                } else {
                    $where .= " AND cm.cohortid IS NOT NULL ";
                }
                $where .= ' GROUP BY u.id';
            }
        }
        return "$select $from $where ORDER BY lastname ASC, firstname ASC";
    }

    /**
     * Converts a list of cohort names into a string list of cohort IDs.
     *
     * @global moodle_database $DB
     * @param array $names
     * @return string list of integers separated by commas
     */
    private function cohortsNamesToId($names) {
        global $DB;
        if (empty($names)) {
            return '';
        }
        $rs = $DB->get_recordset_list('cohort', 'name', $names, '', 'id');
        $ids = array();
        foreach ($rs as $row) {
            $ids[] = $row->id;
        }
        return join(',', $ids);
    }

    /**
     * Returns the string list of the IDs of the roles that can validate a course creation.
     *
     * @global moodle_database $DB
     * @return string list of integers, separated by commas
     */
    private function getValidatorsRolesIds() {
        global $DB;
        if (!$this->addValidators) {
            return '';
        }
        $rs = $DB->get_recordset(
                'role_capabilities',
                array('capability' => self::validatorCapacity, 'permission' => 1),
                '', // sort
                'roleid'
        );
        $ids = array();
        foreach ($rs as $row) {
            $ids[] = $row->roleid;
        }
        return join(',', $ids);
    }

    /**
     * function provided by Pascal Rigaux, cf http://tickets.silecs.info/mantis/view.php?id=1642 (5082)
     * @param string $name group/cohort name for a "structures-.*" group/cohort
     * @return string short name, ex. 'UFR 05'
     */
    private static function groupNameToShortname($name) {
        if (preg_match('/(.*?)\s*:/', $name, $matches))
          return $matches[1];
        else
          return $name;
    }

    /**
     * Returns the ID of the custom_info_field used for affiliation.
     *
     * @global moodle_database $DB
     * @return integer ID of the custom_info_field
     */
    private function getAffiliationFieldId() {
        global $DB;
        static $fieldId = null;
        if (!isset($fieldId)) {
            $fieldId = (int) $DB->get_field(
                    'custom_info_field',
                    'id',
                    array('objectname' => 'user', 'shortname' => self::affiliationFieldName)
            );
        }
        return (int) $fieldId;
    }

}
