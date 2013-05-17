<?php

//***************************************************
// SMS notification abstract class
//***************************************************
abstract class AbstractSMS{
    // once the class is extended to SMS class and
    // the methods message and notifications are
    // implemented according to your provider settings
    // the SMS functionality should be available in
    // global, course and user settings

	abstract function message( $changelist, $course );
	abstract function notify( $changelist, $user, $course );
}

?>
