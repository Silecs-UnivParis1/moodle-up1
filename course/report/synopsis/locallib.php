<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains functions used by the outline reports
 *
 * @package    coursereport
 * @subpackage synopsis
 * @copyright  2012 Silecs {@link http://www.silecs.info}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from package report_outline
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/course/lib.php');

/**
 * Returns list of cohorts enrolled into course/context.
 * @todo this function should be moved to  enrol/cohort/locallib.php
 * OR lib/accesslib.php (next to get_enrolled_users)
 *
 * @param context $context (optional)
 * @param course id $courseid (optional)
 * @param array(role_id, ...) $roleids
 * @return array of cohort records
 */

function get_enrolled_cohorts($courseid, $roleids=null) {
    global $DB;

	    $sql = "SELECT c.id, c.name, c.idnumber, c.description
              FROM {cohort} c
              JOIN {enrol} e ON (e.enrol='cohort' AND e.customint1=c.id) ";
//			  JOIN {role} r ON (r.id = e.roleid) //** @todo bugfix DML read exception; don't know why
		$sql .= " WHERE e.courseid = ? ";
	if ( isset($roleids) ) {
		$sql .= "AND roleid IN (". implode(',', $roleids) .")";
	}
	$sql .= " ORDER BY c.name ASC";

    return $DB->get_records_sql($sql, array($courseid));
}


function report_outline_print_row($mod, $instance, $result) {
    global $OUTPUT, $CFG;

    $image = "<img src=\"" . $OUTPUT->pix_url('icon', $mod->modname) . "\" class=\"icon\" alt=\"$mod->modfullname\" />";

    echo "<tr>";
    echo "<td valign=\"top\">$image</td>";
    echo "<td valign=\"top\" style=\"width:300\">";
    echo "   <a title=\"$mod->modfullname\"";
    echo "   href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">".format_string($instance->name,true)."</a></td>";
    echo "<td>&nbsp;&nbsp;&nbsp;</td>";
    echo "<td valign=\"top\">";
    if (isset($result->info)) {
        echo "$result->info";
    } else {
        echo "<p style=\"text-align:center\">-</p>";
    }
    echo "</td>";
    echo "<td>&nbsp;&nbsp;&nbsp;</td>";
    if (!empty($result->time)) {
        $timeago = format_time(time() - $result->time);
        echo "<td valign=\"top\" style=\"white-space: nowrap\">".userdate($result->time)." ($timeago)</td>";
    }
    echo "</tr>";
}
