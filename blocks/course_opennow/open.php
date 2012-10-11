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
				$visible = 1;
				if (! $DB->update_record('course', array('id' => $course->id,
					'visible' => $visible, 'visibleold' => $visible, 'timemodified' => time()))) {
					echo 'not updated';
					print_error('coursenotupdated');
				}
            }
		}
    }
    redirect($returnurl);
?>
