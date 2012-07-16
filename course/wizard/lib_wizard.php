<?php
function get_stepgo($stepin, $POST){
	switch ($stepin){
	    case 1 :
	        $stepgo = 2;
	        break;
	    case 2 :
	        $stepgo = 3;
            if (array_key_exists('stepgo_1', $POST)) {
				$stepgo = 1;
			}
	        break;
	    case 3 :
	        $stepgo = 4;
            if (array_key_exists('stepgo_2', $POST)) {
				$stepgo = 2;
			}
	        break;
	    case 5 :
	        $stepgo = 6;
	        break;
	   case 6 :
	        $stepgo = 7;
	         break;
	   case 7 :
	        $stepgo = 8;
	         break;
	 }
	return $stepgo;
}

function validation_shortname($shortname) {
    global $DB, $CFG;
    $errors = array();
    if ($foundcourses = $DB->get_records('course', array('shortname'=>$shortname))) {
        if (!empty($foundcourses)) {
            foreach ($foundcourses as $foundcourse) {
                $foundcoursenames[] = $foundcourse->fullname;
            }
            $foundcoursenamestring = implode(',', $foundcoursenames);
            $errors['shortname']= get_string('shortnametaken', '', $foundcoursenamestring);
        }
    }
    return $errors;
}

function send_course_request($message) {
	global $DB, $USER;

	$result = $DB->get_records('user', array('username' => 'admin')); //** @todo on envoie à qui ? plusieurs ?

	//** @todo maybe replace all this by a call to course/lib.php course_request::notify +4394
    $eventdata = new object();
    $eventdata->component         = 'moodle';
    $eventdata->name              = 'courserequested';
    $eventdata->userfrom          = $USER;
    $eventdata->subject           = '[CourseWizardRequest]'; //** @todo get_string()
    $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
    $eventdata->fullmessage       = $message;
    $eventdata->fullmessagehtml   = $message;
  //  $eventdata->smallmessage      = $message; // USED BY DEFAULT !

    // documentation : http://docs.moodle.org/dev/Messaging_2.0#Message_dispatching
	$count = array('err' => 0, 'ok' => 0);
    foreach ($result as $userto) {
        $eventdata->userto = $userto;
        $res = message_send($eventdata);
        if ($res) {
            $count['ok']++;
        } else {
            $count['err']++;
        }
    }
    return $count;
}
