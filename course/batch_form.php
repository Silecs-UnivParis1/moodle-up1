<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

class course_batch_search_form extends moodleform {
    /**
     * @var custominfo_form_extension
     */
    protected $custominfo;

    function definition() {
        $mform    = $this->_form;

        $systemcontext   = get_context_instance(CONTEXT_SYSTEM);

        $mform->addElement('text', 'search', get_string('searchcourses'), 'maxlength="254" size="50"');

        $mform->addElement('date_selector', 'startdateafter', get_string('startdate'));
        $mform->setDefault('startdateafter', mktime(12, 0, 0, 1, 1, 2010));
        $mform->addElement('date_selector', 'startdatebefore', get_string('startdate'));
        $mform->setDefault('startdatebefore', time() + 3600 * 24);

        // Next the customisable fields
        $this->custominfo = new custominfo_form_extension('course');
        $canviewall = has_capability('moodle/course:update', $systemcontext);
        $this->custominfo->definition($mform, $canviewall);

        $this->add_action_buttons(false, get_string('go'));
    }

    /// perform some extra moodle validation
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}

