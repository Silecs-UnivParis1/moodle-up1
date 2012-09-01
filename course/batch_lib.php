<?php

/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

/**
 * A list of courses that match a search
 *
 * @global object
 * @global object
 * @param array $criteria An assoc array of search criteria
 * @param string $sort A field and direction to sort by
 * @param int $page The page number to get
 * @param int $recordsperpage The number of records per page
 * @param int $totalcount Passed in by reference.
 * @return object {@link $COURSE} records
 */
function get_courses_batch_search($criteria, $sort='fullname ASC', $page=0, $recordsperpage=50, &$totalcount) {
    global $CFG, $DB;

    $search = trim(strip_tags($criteria->search)); // trim & clean raw searched string
    $searchterms = array();
    if ($search) {
        $searchterms = explode(" ", $search);    // Search for words independently
        foreach ($searchterms as $key => $searchterm) {
            if (strlen($searchterm) < 2) {
                unset($searchterms[$key]);
            }
        }
    }

    if ($DB->sql_regex_supported()) {
        $REGEXP    = $DB->sql_regex(true);
        $NOTREGEXP = $DB->sql_regex(false);
    }

    $searchcond = array();
    $params     = array();
    $i = 0;

    // Thanks Oracle for your non-ansi concat and type limits in coalesce. MDL-29912
    if ($DB->get_dbfamily() == 'oracle') {
        $concat = $DB->sql_concat('c.summary', "' '", 'c.fullname', "' '", 'c.idnumber', "' '", 'c.shortname');
    } else {
        $concat = $DB->sql_concat("COALESCE(c.summary, '". $DB->sql_empty() ."')", "' '", 'c.fullname', "' '", 'c.idnumber', "' '", 'c.shortname');
    }

    foreach ($searchterms as $searchterm) {
        $i++;

        $NOT = false; /// Initially we aren't going to perform NOT LIKE searches, only MSSQL and Oracle
                   /// will use it to simulate the "-" operator with LIKE clause

    /// Under Oracle and MSSQL, trim the + and - operators and perform
    /// simpler LIKE (or NOT LIKE) queries
        if (!$DB->sql_regex_supported()) {
            if (substr($searchterm, 0, 1) == '-') {
                $NOT = true;
            }
            $searchterm = trim($searchterm, '+-');
        }

        // TODO: +- may not work for non latin languages

        if (substr($searchterm,0,1) == '+') {
            $searchterm = trim($searchterm, '+-');
            $searchterm = preg_quote($searchterm, '|');
            $searchcond[] = "$concat $REGEXP :ss$i";
            $params['ss'.$i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";

        } else if (substr($searchterm,0,1) == "-") {
            $searchterm = trim($searchterm, '+-');
            $searchterm = preg_quote($searchterm, '|');
            $searchcond[] = "$concat $NOTREGEXP :ss$i";
            $params['ss'.$i] = "(^|[^a-zA-Z0-9])$searchterm([^a-zA-Z0-9]|$)";

        } else {
            $searchcond[] = $DB->sql_like($concat,":ss$i", false, true, $NOT);
            $params['ss'.$i] = "%$searchterm%";
        }
    }

    // other course settings
    if (!empty($criteria->startdateafter)) {
        $searchcond[] = "c.startdate >= " . ((int) $criteria->startdateafter);
    }
    if (!empty($criteria->startdatebefore)) {
        $searchcond[] = "c.startdate <= " . ((int) $criteria->startdatebefore);
    }
    if (!empty($criteria->createdafter)) {
        $searchcond[] = "c.timecreated >= " . ((int) $criteria->createdafter);
    }
    if (!empty($criteria->createdbefore)) {
        $searchcond[] = "c.timecreated <= " . ((int) $criteria->createdbefore);
    }

    // custominfo fields
    $fields = $DB->get_records('custom_info_field', array('objectname' => 'course'));
    $searchjoin = array();
    if ($fields) {
        $i = 0;
        foreach ($fields as $field) {
            $formfield = custominfo_field_factory('course', $field->datatype, $field->id, null);
            $formfield->edit_data($criteria);
            if (!empty($formfield->data)) {
                $i++;
                $searchjoin[] = "JOIN {custom_info_data} d$i "
                    ."ON (d$i.objectid = c.id AND d$i.objectname='course' AND d$i.fieldid={$field->id})";
                $searchcond[] = $DB->sql_like("d$i.data", ":dd$i", false, true, false);
                $params['dd'.$i] = "%{$formfield->data}%";
            }
        }
    }

    if (empty($searchcond)) {
        $totalcount = 0;
        return array();
    }

    $searchjoin = implode(" ", $searchjoin);
    $searchcond = implode(" AND ", $searchcond);

    $courses = array();
    $c = 0; // counts how many visible courses we've seen

    // Tiki pagination
    $limitfrom = $page * $recordsperpage;
    $limitto   = $limitfrom + $recordsperpage;

    list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');
    $sql = "SELECT c.* $ccselect
              FROM {course} c
           $ccjoin $searchjoin
             WHERE $searchcond AND c.id <> ".SITEID."
          ORDER BY $sort";

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $course) {
        context_instance_preload($course);
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        if ($course->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
            // Don't exit this loop till the end
            // we need to count all the visible courses
            // to update $totalcount
            if ($c >= $limitfrom && $c < $limitto) {
                $courses[$course->id] = $course;
            }
            $c++;
        }
    }
    $rs->close();

    // our caller expects 2 bits of data - our return
    // array, and an updated $totalcount
    $totalcount = $c;
    return $courses;
}
