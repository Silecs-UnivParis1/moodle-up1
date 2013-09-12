<?php

class block_course_opennow extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_course_opennow');
    }

    function get_content() {
        global $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }

        $this->content = new stdClass();
        $context = get_context_instance(CONTEXT_COURSE, $this->page->course->id);

		if (has_capability('moodle/course:update', $context)) {
			$startDate = date('d-m-Y', $this->page->course->startdate);
			$open = $this->page->course->visible;
			$this->content->text = '<div class="">' . get_string('startdate', 'block_course_opennow');
			$this->content->text .= ' : '. $startDate;
            $buttonname = get_string('opencourse', 'block_course_opennow');
            $message = get_string('close', 'block_course_opennow');
			if ($open) {
                $message = get_string('open', 'block_course_opennow');
                $buttonname = get_string('closecourse', 'block_course_opennow');
			}
            $this->content->text .= '<div>' . $message . '</div>';
            $this->content->text .= '<form action="' . $CFG->wwwroot . '/blocks/course_opennow/open.php" method="post">'
                . '<input type="hidden" value="'.$this->page->course->id.'" name="courseid" />'
                . '<input type="hidden" value="'.sesskey().'" name="sesskey" />'
                . '<input type="hidden" value="'.$open.'" name="visible" />'
                . '<button type="submit" name="datenow" value="open">'
                . $buttonname . '</button>'
                .'</form>';
			$this->content->text .= '</div>';
		}
        $this->content->footer = '';

        return $this->content;
    }

    function hide_header() {
        return true;
    }

    function preferred_width() {
        return 210;
    }

     function applicable_formats() {
        return array('course' => true, 'mod' => false, 'my' => false, 'admin' => false,
                     'tag' => false);
    }

}


