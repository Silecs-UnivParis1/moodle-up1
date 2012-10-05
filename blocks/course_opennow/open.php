<?php
    require_once('../../config.php');
	global $CFG, $DB;
	$courseid = required_param('courseid',PARAM_INT);
	$returnurl = $_SERVER['HTTP_REFERER'];

    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    if ($data = data_submitted() and confirm_sesskey()) {
        $context = get_context_instance(CONTEXT_COURSE, $data->courseid);
        if (has_capability('moodle/course:update', $context)) {
            if (!$course = $DB->get_record('course', array('id' =>$data->courseid))) {
                    error('Course ID was incorrect');
            } else {
				$date = date('d-m-Y');
				$tabDate = explode('-', $date);
				$datetime = mktime(0, 0, 0, $tabDate[1], $tabDate[0], $tabDate[2]);
                $course->startdate = $datetime;
                $course->timemodified = time();
				if (! $DB->update_record('course', array('id' => $course->id,
					'startdate' => $course->startdate, 'timemodified' => $course->timemodified))) {
					echo 'not updated';
					print_error('coursenotupdated');
				}
            }
		}
    }
    redirect($returnurl);
?>
