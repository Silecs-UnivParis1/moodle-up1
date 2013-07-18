<?php
/* @var $DB moodle_database */

defined('MOODLE_INTERNAL') || die;

/* @var $DB moodle_database */

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

        if (!empty($this->_customdata['fieldset'])) {
            $mform->addElement('header', 'main_settings', format_string($this->_customdata['fieldset']));
        }

        $mform->addElement('text', 'search', get_string('searchcourses'), 'maxlength="254" size="50"');
        $mform->addElement('text', 'enrolled', get_string('defaultcourseteacher'), 'maxlength="254" size="50"');
        $mform->addElement('hidden', 'enrolledroles');

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
            $fields_by_cat = self::getFieldsFromCategories(array());
        } else {
            if (isset($this->_customdata['fields'][0]) && strncmp($this->_customdata['fields'][0], 'up1', 3) === 0) {
                $fields_by_cat = self::getFieldsFromNames($this->_customdata['fields']);
            } else {
                $fields_by_cat = self::getFieldsFromCategories($this->_customdata['fields']);
            }
       }

        if ($fields_by_cat) {
            foreach ($fields_by_cat as $catid => $fields) {
                $catname = $fields['name'];
                unset($fields['name']);
                // display the header and the fields
                $mform->addElement('header', 'category_'.$catid, format_string($catname));
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

        $this->add_action_buttons(false, get_string('go'));

        $mform->addElement('hidden', 'topcategory');
        $mform->addElement('hidden', 'node');
        if (!empty($this->_customdata['fields'])) {
            $mform->addElement('hidden', 'fieldsjson');
            $mform->setDefault('fieldsjson', json_encode($this->_customdata['fields']));
        }
    }

    private static function getFieldsFromCategories($cats) {
        global $DB;
        $cond = array();
        $params = array();
        foreach ($cats as $catname => $fieldnames) {
            if (empty($fieldnames) || $fieldnames === '*') {
                $cond[] = 'c.name = ?';
                $params[] = $catname;
            } else {
                list ($sqlin, $sqlparams) = $DB->get_in_or_equal($fieldnames);
                $cond[] = "f.shortname " . $sqlin;
                $params = array_merge($params, $sqlparams);
            }
        }
        return self::getFieldsFrom(join(' OR ', $cond), $params);
    }

    private static function getFieldsFromNames($fieldnames) {
        global $DB;
        list ($sqlin, $sqlparams) = $DB->get_in_or_equal($fieldnames);
        return self::getFieldsFrom("f.shortname " . $sqlin, $sqlparams);
    }

    private static function getFieldsFrom($cond, $params) {
        global $DB;
        $sql = "SELECT f.*, c.name AS catname "
                . "FROM {custom_info_field} f "
                . "JOIN {custom_info_category} c on f.categoryid = c.id "
                . "WHERE f.objectname = 'course' " . ($cond ? "AND ($cond) " : "")
                . "ORDER BY c.sortorder, f.sortorder";
        $fields = $DB->get_records_sql($sql, $params);
        $by_categoryId = array();
        foreach ($fields as $f) {
            if (isset($by_categoryId[$f->categoryid])) {
                $by_categoryId[$f->categoryid][] = $f;
            } else {
                $catname = $f->catname;
                unset($f->catname);
                $by_categoryId[$f->categoryid] = array('name' => $catname, $f);
            }
        }
        return $by_categoryId;
    }

    /// perform some extra moodle validation
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}

