<?php

/**
 * renvoie l'identifiant du custom_info_field 'up1avalider'
 */
function getIdTovalidate () {
	global $DB;
	return $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'up1avalider'));
}

/**
 * renvoie l'identifiant du custom_info_field 'up1datevalid'
 */
function getIdvalidatedate () {
	global $DB;
	return $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'up1datevalid'));
}

/**
 * renvoie l'identifiant du custom_info_field 'validator'
 */
function getIdValidator() {
	global $DB;
	return $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'validator'));
}

/**
 * renvoie l'identifiant du custom_info_field 'validatorid'
 */
function getIdValidatorid() {
	global $DB;
	return $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'validatorid'));
}

/**
 * liste des cours créé à valider
 * tovalidate=1 AND validatedate!=0
 */
function getIdCourseValidated () {
	global $DB;
	$listeId = '';
	$idTovalidate = getIdTovalidate();
	$idValidatedate = getIdvalidatedate();
	if ($idTovalidate && $idValidatedate) {

		$sql = "SELECT distinct c1.objectid FROM custom_info_data c1 JOIN custom_info_data c2 ON (c1.objectid=c2.objectid) "
			. "WHERE c1.fieldid=".$idTovalidate." AND c1.data=1 and c2.fieldid=".$idValidatedate." and c2.data=0";
		$tabIdCourse = $DB->get_records_sql($sql);
		if (count($tabIdCourse)) {
			foreach ($tabIdCourse as $cid) {
				$listeId .= "'".trim($cid->objectid)."',";
			}
			$listeId = substr($listeId, 0, -1);
		}
		return $listeId;
	}
}
/**
 * modification de la fonction get_courses()
 * renvoie le tableau des cours à valider
 * @param string|int $categoryid Either a category id or 'all' for everything
 * @param string $sort A field and direction to sort by
 * @param string $fields The additional fields to return
 * @return array Array of courses
 */
function get_courses_to_validate ($categoryid="all", $sort="c.sortorder ASC", $fields="c.*") {
	global $DB;

	$listIdCourses = getIdCourseValidated();
	$inCourse = '';
	if ($listIdCourses == '') {
		$listIdCourses = "'0'";
	}

	$params = array();

	 if ($categoryid !== "all" && is_numeric($categoryid)) {
        $categoryselect = " WHERE c.category = :catid";
        $params['catid'] = $categoryid;
		$inCourse = " AND c.id IN (".$listIdCourses.") ";
    } else {
        $categoryselect = "";
        $inCourse = " WHERE c.id IN (".$listIdCourses.") ";
    }


    if (empty($sort)) {
        $sortstatement = "";
    } else {
        $sortstatement = "ORDER BY $sort";
    }

	$visiblecourses = array();

	list($ccselect, $ccjoin) = context_instance_preload_sql('c.id', CONTEXT_COURSE, 'ctx');

	$sql = "SELECT $fields $ccselect
              FROM {course} c
           $ccjoin
              $categoryselect
              $inCourse
              $sortstatement";

      // pull out all course matching the cat
    if ($courses = $DB->get_records_sql($sql, $params)) {

        // loop throught them
        foreach ($courses as $course) {
            context_instance_preload($course);
            if (isset($course->visible) && $course->visible <= 0) {
                // for hidden courses, require visibility check
                if (has_capability('moodle/course:viewhiddencourses', get_context_instance(CONTEXT_COURSE, $course->id))) {
                    $visiblecourses [$course->id] = $course;
                }
            } else {
                $visiblecourses [$course->id] = $course;
            }
        }
    }
    return $visiblecourses;
}

/**
 * Modification de la fonction print_course()
 * affiche la liste des cours à valider
 * @param object $course the course object.
 * @param string $highlightterms (optional) some search terms that should be highlighted in the display.
 */
function print_course_tovalidate($course, $highlightterms = '') {
global $OUTPUT;
	$baseurl = new moodle_url('/local/course_validated/index.php');
	$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

	echo html_writer::start_tag('div', array('class'=>'coursebox clearfix'));
    echo html_writer::start_tag('div', array('class'=>'info'));
    echo html_writer::start_tag('h3', array('class'=>'name'));
	$linkhref = new moodle_url('/course/view.php', array('id'=>$course->id));
	$coursename = get_course_display_name_for_list($course);
    $linktext = highlight($highlightterms, format_string($coursename));
    $linkparams = array('title'=>get_string('entercourse'));
    if (empty($course->visible)) {
        $linkparams['class'] = 'dimmed';
    }
    echo html_writer::link($linkhref, $linktext, $linkparams);
    echo html_writer::end_tag('h3');

	echo html_writer::start_tag('div', array('class'=>'action'));
    if (has_capability('moodle/course:update', $coursecontext)) {
		$url = new moodle_url('/course/edit.php', array('id' => $course->id, 'category' => $course->category, 'returnto' => 'category'));
		echo $OUTPUT->action_icon($url, new pix_icon('t/edit', get_string('settings')));
		echo '&nbsp;';
    }

    if (can_delete_course($course->id)) {
		$url = new moodle_url('/course/delete.php', array('id' => $course->id));
        echo $OUTPUT->action_icon($url, new pix_icon('t/delete', get_string('delete')));
        echo '&nbsp;';
    }

	// MDL-8885, users with no capability to view hidden courses, should not be able to lock themselves out
    if (has_capability('moodle/course:visibility', $coursecontext) && has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
		if (!empty($course->visible)) {
			$url = new moodle_url($baseurl, array('hide' => $course->id));
            echo $OUTPUT->action_icon($url, new pix_icon('t/hide', get_string('hide')));
        } else {
			$url = new moodle_url($baseurl, array('show' => $course->id));
            echo $OUTPUT->action_icon($url, new pix_icon('t/show', get_string('show')));
        }
        echo '&nbsp;';
    }
    // si capability : valider un cours : validatedate
	$url = new moodle_url($baseurl, array('validate' => $course->id));
	echo $OUTPUT->action_icon($url, new pix_icon('i/tick_green_small', 'valider'));

	echo html_writer::end_tag('div');

    echo html_writer::end_tag('div'); // End of summary div
    echo html_writer::end_tag('div'); // End of coursebox div
}

/**
 * Fonction de rendre visible/non-visible le cours d'identifiant $show/$hide
 * @param int $show identifiant du cours que l'on veut rendre visible
 * @param int $hide identifiant du cours que l'on veut rendre invisible
 */
function show_or_hide($show, $hide) {
	global $DB;
	if (!empty($hide)) {
		$course = $DB->get_record('course', array('id' => $hide));
        $visible = 0;
    } else {
		$course = $DB->get_record('course', array('id' => $show));
        $visible = 1;
	}

    if ($course) {
		$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:visibility', $coursecontext);
        // Set the visibility of the course. we set the old flag when user manually changes visibility of course.
        $DB->update_record('course', array('id' => $course->id, 'visible' => $visible, 'visibleold' => $visible, 'timemodified' => time()));
    }
}
/**
 * valide un cours (fixe le champ custom_info_data.validatedate à time()
 * @param int $validate identifiant du cours à valider
 */
function validate_course ($validate) {
	global $DB;
	$idValidatedate = getIdvalidatedate();
	$idCidDatevalidate = $DB->get_field('custom_info_data', 'id', array('objectname' => 'course', 'fieldid' => $idValidatedate, 'objectid' => $validate));
	if ($idCidDatevalidate) {
		$DB->update_record('custom_info_data', array('id' => $idCidDatevalidate, 'data' => time()));
	}
}



