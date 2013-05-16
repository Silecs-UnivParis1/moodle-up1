<?php
//***************************************************
// Mail notification
//***************************************************
class eMail {

	function notify( $changelist, $user, $course ){
		$html_message = $this->html_mail( $changelist, $course );
		$text_message = $this->text_mail( $changelist, $course );
		$subject = get_string('mailsubject', 'block_notifications');
		$subject.= ": ".format_string( $course->fullname, true );
		email_to_user( $user,'', $subject, $text_message, $html_message );
	}


	function html_mail( $changelist, $course ) {
		global $CFG;

		$mailbody = '<head>';

		$mailbody .= '</head>';
		$mailbody .= '<body id="email">';
		$mailbody .= '<div class="header">';
		$mailbody .= get_string('mailsubject', 'block_notifications').' ';
		$mailbody .= "&laquo; <a target=\"_blank\" href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->fullname</a> &raquo; ";
		$mailbody .= '</div>';
		$mailbody .= '<div class="content">';
		$mailbody .= '<ul>';

		foreach ( $changelist as $item ) {
			$mailbody .='<li>';
			$mailbody .= get_string( $item->action, 'block_notifications' ).' ';
			$mailbody .= get_string( $item->type, 'block_notifications' )." : ";
			if ( $item->action != "deleted") {
				$mailbody .="<a href=\"$CFG->wwwroot/mod/$item->type/view.php?id=$item->module_id\">$item->name</a>";
			} else {
				$mailbody .="$item->name";
			}
			$mailbody .= '</li>';
		}

		$mailbody .= '</ul>';
		$mailbody .= '</div>';
		$mailbody .= '</body>';

		return $mailbody;
	}

	function text_mail( $changelist, $course ) {
		global $CFG;

		$mailbody = get_string( 'mailsubject', 'block_notifications' ).': '.$course->fullname.' ';
		$mailbody .= $CFG->wwwroot.'/course/view.php?id='.$course->id."\r\n\r\n";

		foreach ( $changelist as $item ) {
			$mailbody .= "\t".get_string( $item->action, 'block_notifications' ).' ';
			$mailbody .= "\t".get_string( $item->type, 'block_notifications' )." : ";
			$mailbody .= $item->name."\r\n";

			if ( $item->action != "deleted") {
				$mailbody .= "\t$CFG->wwwroot/mod/$item->type/view.php?id=$item->module_id\r\n\r\n";
			}
		}
		return $mailbody;
	}
}
?>
