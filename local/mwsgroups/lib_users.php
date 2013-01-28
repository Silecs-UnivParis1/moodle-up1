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
    /** @var int */
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

    private $exclude_map = array();

    const affiliationFieldName = 'up1edupersonprimaryaffiliation';

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
        $where = "WHERE ( (mnethostid = 1 AND username = ?)  OR  firstname LIKE ? OR lastname LIKE ? "
                . "OR  CONCAT(firstname, ' ', lastname) LIKE ?  OR  CONCAT(lastname, ' ', firstname) LIKE ? )";
        if ($this->filterstudent == 'no') {
            $where = " AND idnumber = '' ";
        } else if ($this->filterstudent == 'only') {
            $where = " AND idnumber != '' ";
        }
        if ($this->affiliation) {
            $fieldId = $this->getAffiliationFieldId();
            $select .= ", d1.data AS affiliation ";
            $from .= " LEFT JOIN custom_info_data d1 ON (d1.fieldid = $fieldId AND d1.objectid = u.id) ";
        }
        if ($this->cohorts) {
            $cohortsId = $this->cohortsNamesToId($this->cohorts);
            if ($cohortsId) {
                $cohortsId = join(',', $cohortsId);
                $from .= " JOIN {cohort_members} cm ON (cm.userid = u.id AND cm.cohortid IN ($cohortsId))";
                $where .= ' GROUP BY u.id';
            }
        }
        return "$select $from $where ORDER BY lastname ASC, firstname ASC";
    }

    /**
     * Converts a list of cohort names into a list of cohort IDs.
     *
     * @global moodle_database $DB
     * @param array $names
     * @return array of integers
     */
    private function cohortsNamesToId($names) {
        global $DB;
        $rs = $DB->get_recordset_list('cohort', 'name', $names, '', 'id');
        $ids = array();
        foreach ($rs as $row) {
            $ids[] = $row->id;
        }
        return $ids;
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
