<?php
/* @var $DB moodle_database */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

class course_batch_search_form extends moodleform {
    /**
     * @var custominfo_form_extension
     */
    protected $custominfo;

    function definition() {
        global $DB;

        $mform    = $this->_form;

        $systemcontext   = get_context_instance(CONTEXT_SYSTEM);

        if (!empty($this->_customdata['fieldset'])) {
            $mform->addElement('header', 'main_settings', format_string($this->_customdata['fieldset']));
        }

        $mform->addElement('text', 'search', get_string('searchcourses'), 'maxlength="254" size="50"');
        $mform->addElement('text', 'enrolled', get_string('defaultcourseteacher'), 'maxlength="254" size="50"');

        $mform->addElement('date_selector', 'startdateafter', get_string('startdate') . ' &gt;');
        $mform->setDefault('startdateafter', mktime(12, 0, 0, 1, 1, date('Y') - 1));
        $mform->addElement('date_selector', 'startdatebefore', get_string('startdate') . ' &lt;');
        $mform->setDefault('startdatebefore', time() + 3600 * 24);

        $mform->addElement('date_selector', 'createdafter', get_string('createdon', 'search') . ' &gt;');
        $mform->setDefault('createdafter', mktime(12, 0, 0, 1, 1, date('Y') - 1));
        $mform->addElement('date_selector', 'createdbefore', get_string('createdon', 'search') . ' &lt;');
        $mform->setDefault('createdbefore', time() + 3600 * 24);

        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist);
        $displaylist = array_merge(array('' => ''), $displaylist);
        $mform->addElement('select', 'category', get_string('category'), $displaylist);

        // Next the customisable fields
        $this->custominfo = new custominfo_form_extension('course');
        if (empty($this->_customdata['fields']) || $this->_customdata['fields'] === '*') {
            $categories = $DB->get_records('custom_info_category', array('objectname' => 'course'), 'sortorder ASC');
        } else {
            list ($sqlin, $sqlparams) = $DB->get_in_or_equal(array_keys($this->_customdata['fields']));
            if ($sqlin) {
                $categories = $DB->get_records_select(
                        'custom_info_category', "objectname = 'course' AND name " . $sqlin, $sqlparams, 'sortorder ASC'
                );
            } else {
                $categories = array();
            }
        }
        if ($categories) {
            foreach ($categories as $category) {
                if (isset($this->_customdata['fields'][$category->name]) && $this->_customdata['fields'][$category->name] !== '*') {
                    $fields = array();
                    if (!empty($this->_customdata['fields'][$category->name])) {
                        list ($sqlin, $sqlparams) = $DB->get_in_or_equal($this->_customdata['fields'][$category->name]);
                        if ($sqlin) {
                            $sqlparams[] = $category->id;
                            $fields = $DB->get_records_select(
                                    'custom_info_field', "shortname $sqlin AND categoryid = ?", $sqlparams, 'sortorder ASC'
                            );
                        }
                    }
                } else {
                    $fields = $DB->get_records('custom_info_field', array('categoryid' => $category->id), 'sortorder ASC');
                }
                if ($fields) {
                    // display the header and the fields
                    $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                    foreach ($fields as $field) {
                        // do not display the normal field when a search criteria is expected
                        if (in_array($field->datatype, array('datetime', 'textarea'))) {
                            $field->param1 = '';
                            $field->param2 = '';
                            $field->param3 = '';
                            $field->datatype = 'text';
                        }
                        //var_dump($field); die();
                        // add the custom field
                        $formfield = custominfo_field_factory('course', $field->datatype, $field->id);
                        $formfield->load_data($field);
                        $formfield->options[''] = '';
                        $formfield->edit_field($mform);
                        $mform->setDefault($formfield->inputname, '');
                    }
                }
            }
        }

        $this->add_action_buttons(false, get_string('go'));

        $mform->addElement('hidden', 'topcategory');
        $mform->addElement('hidden', 'topnode');
        if (!empty($this->_customdata['fields'])) {
            $mform->addElement('hidden', 'fieldsjson');
            $mform->setDefault('fields', $this->_customdata['fields']);
        }
    }

    /// perform some extra moodle validation
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}

