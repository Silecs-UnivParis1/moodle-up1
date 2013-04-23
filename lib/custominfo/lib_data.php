<?php
/**
 * This file contains 2 classes that can be used to put/read data in the custom fields of some object.
 */

/**
 * Contains some methods for reading and writing the data in the custom fields.
 */
class custominfo_data extends custominfo_data_abstract {
    /**
     * Builds a new instance.
     * Syntaxic sugar to replace (new custominfo_data('user'))->...
     * @param string $objectname
     * @return custominfo_data new instance
     */
    public static function type($objectname) {
        return new self($objectname);
    }

    /**
     * Add a property to the object for each field submitted in the form
     * @global object $DB
     * @param object $object
     */
    public function load_data($object) {
        global $DB;

        if (empty($object)) {
            return;
        }
        $fields = $this->getFields();
        if ($fields) {
            foreach ($fields as $field) {
                $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $object->id);
                $formfield->edit_load_object_data($object);
            }
        }
    }

    /**
     * Write in the DB the data for each field submitted in the form
     * @global object $DB
     * @param object $newobject
     */
    public function save_data($newobject) {
        global $DB;

        $fields = $this->getFields();
        if ($fields) {
            foreach ($fields as $field) {
                $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $newobject->id);
                $formfield->edit_save_data($newobject);
            }
        }
    }

    /**
     * Display a list of the visible field names and values.
     * This is currently used by "user" pages with a simple header: <table class="list" summary="">
     * @global object $DB
     * @param integer $objectid
     */
    public function display_fields($objectid, $categories=null) {
        global $DB;

        $categories = $this->getCategories();
        if ($categories) {
            foreach ($categories as $category) {
                $fields = $DB->get_records('custom_info_field', array('categoryid' => $category->id), 'sortorder ASC');
                if ($fields) {
                    foreach ($fields as $field) {
                        $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $objectid);
                        if ($formfield->is_visible() and !$formfield->is_empty()) {
                            printf(
                                    "\n<tr><td class=\"label c0\">%s</td><td class=\"info c1\">%s</td></tr>\n",
                                    format_string($formfield->field->name.':'),
                                    $formfield->display_data()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns a structured list (array(array)) of categories, fields names and values
     * @global object $DB
     * @param integer $objectid
     * @param integer $allfields : if set, all fields are returned; otherwise only not empty ones
     */
    public function get_structured_fields($objectid, $allfields=false) {
        global $DB;

        $res = array();
        $categories = $this->getCategories();
        if ($categories) {
            foreach ($categories as $category) {
                $res[$category->name] = array();
                $fields = $DB->get_records('custom_info_field', array('categoryid' => $category->id), 'sortorder ASC');
                if ($fields) {
                    foreach ($fields as $field) {
                        $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $objectid);
                        if ($formfield->is_visible() && ($allfields || ! $formfield->is_empty()) )  {
                            $res[$category->name][$formfield->field->name] = $formfield->display_data();
                        }
                    }
                }
            }
        }
        return $res;
    }

    /**
     * Returns a structured list (array(array(array))) of categories, fields shortnames, names and values
     * @global object $DB
     * @param integer $objectid
     * @param integer $allfields : if set, all fields are returned; otherwise only not empty ones
     */
    public function get_structured_fields_short($objectid, $allfields=false) {
        global $DB;

        $res = array();
        $categories = $this->getCategories();
        if ($categories) {
            foreach ($categories as $category) {
                $res[$category->name] = array();
                $fields = $DB->get_records('custom_info_field', array('categoryid' => $category->id), 'sortorder ASC');
                if ($fields) {
                    foreach ($fields as $field) {
                        $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $objectid);
                        if ($formfield->is_visible() && ($allfields || ! $formfield->is_empty()) )  {
                            $res[$category->name][$formfield->field->shortname]['name'] = $formfield->field->name;
                            $res[$category->name][$formfield->field->shortname]['data'] = $formfield->display_data();
                        }
                    }
                }
            }
        }
        return $res;
    }


    /**
     * Returns an object with the custom fields set for the given object
     * @param  integer  $objectid
     * @return  object
     */
    public function get_record($objectid) {
        global $DB;

        $customfields = new stdClass();

        $fields = $DB->get_records('custom_info_field', array('objectname' => $this->objectname));
        if ($fields) {
            foreach ($fields as $field) {
                $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $objectid);
                if ($formfield->is_object_data()) {
                    $customfields->{$field->shortname} = $formfield->data;
                }
            }
        }

        return $customfields;
    }

    /**
     * Deletes all the custom data of the given object
     * @param  integer  $objectid
     * @return  boolean success?
     */
    public function delete($objectid) {
        global $DB;

        if (!$objectid) {
            return false;
        }
        $DB->delete_records('custom_info_data', array('objectname' => $this->objectname, 'objectid' => $objectid));
        return true;
    }
}

/**
 * Provide methods that allow to embed the custom info fields into an existing form.
 *
 * Each public method should be called from within an homonym moodle_form method.
 */
class custominfo_form_extension extends custominfo_data_abstract {
    protected $objectid;

    /**
     * Constructor
     * @param string $objectname E.g. user, course
     * @param integer $objectid (opt)
     */
    public function __construct($objectname, $objectid=null) {
        $this->objectname = $objectname;
        $this->set_objectid($objectid);
    }

    /**
     * Setter for objectid
     * @param integer $objectid
     */
    public function set_objectid($objectid) {
        $this->objectid = ($objectid < 0) ? 0 : (int)$objectid;
    }

    /**
     * Declare the customisable categories and fields on a form
     * @param object $mform     instance of the moodleform class
     * @param bool $canviewall  (opt) if true, force the visibility of all fields
     */
    public function definition($mform, $canviewall=false) {
        global $DB;

        $categories = $this->getCategories();
        if ($categories) {
            $allFields = $this->getFields(true);
            foreach ($categories as $category) {
                if (isset($allFields[$category->id])) {
                    $fields = $allFields[$category->id];
                    // check first if *any* fields will be displayed
                    $display = false;
                    if (!$canviewall) {
                        foreach ($fields as $field) {
                            if ($field->visible != CUSTOMINFO_VISIBLE_NONE) {
                                $display = true;
                            }
                        }
                    }

                    // display the header and the fields
                    if ($display or $canviewall) {
                        $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                        foreach ($fields as $field) {
                            $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $this->objectid);
                            $formfield->edit_field($mform);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @global object $DB
     * @param object  $mform
     */
    public function definition_after_data($mform) {
        global $DB;

        $fields = $this->getFields();
        if ($fields) {
            foreach ($fields as $field) {
                $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $this->objectid);
                $formfield->edit_after_data($mform);
            }
        }
    }

    /**
     * Validates the custom fields and return an array of errors
     * @global object $DB
     * @param object $objectnew  Object to validate
     * @param array $files
     * @return array of errors
     */
    public function validation($objectnew, $files) {
        global $DB;

        $err = array();
        $fields = $this->getFields();
        if ($fields) {
            foreach ($fields as $field) {
                $formfield = custominfo_field_factory($this->objectname, $field->datatype, $field->id, $objectnew->id);
                $err += $formfield->edit_validate_field($objectnew, $files);
            }
        }
        return $err;
    }
}

/**
 * Abstract class that handles the process of filtering categories.
 */
abstract class custominfo_data_abstract {
    protected $objectname;

    /**
     * @var array of "custom_info_category" records to use instead of all of them.
     */
    protected $categories;

    /**
     * @var boolean
     */
    protected $filteredCategories = false;

    /**
     * Constructor
     * @param string $objectname E.g. user, course
     */
    public function __construct($objectname) {
        $this->objectname = $objectname;
    }

    /**
     * Limits the categories used by the other methods.
     * @param array $categories of "custom_info_category" records.
     */
    public function setCategories($categories) {
        $this->categories = $categories;
        $this->filteredCategories = !empty($categories);
    }

    /**
     * Limits the categories used by the other methods to some given names.
     * @todo Find a better way to build a portable SQL IN() from string values.
     * @param array $categoriesNames Array of strings.
     */
    public function setCategoriesByNames($categoriesNames) {
        global $DB;
        $this->categories = $DB->get_records_select(
                'custom_info_category',
                "objectname = ? AND name IN ('" . join("','", array_map('addslashes', $categoriesNames)) . "')",
                array($this->objectname),
                'sortorder ASC'
        );
        $this->filteredCategories = true;
    }

    /**
     * Return the categories selected, or all of them if no selection was made.
     * @return array of objects.
     */
    public function getCategories() {
        global $DB;
        if (!$this->categories && !$this->filteredCategories) {
            $this->categories = $DB->get_records('custom_info_category', array('objectname' => $this->objectname), 'sortorder ASC');
        }
        return $this->categories;
    }

    /**
     * Return the categories selected, or all of them if no selection was made.
     * @return array of integers.
     */
    public function getCategoriesIds() {
        $ids = array();
        foreach ($this->getCategories() as $c) {
            $ids[] = (int) $c->id;
        }
        return $ids;
    }

    /**
     * Return all the custominfo fields, eventually restricted to the categories selected.
     * @param boolean $byCategoriesIds (opt) If true, the first level will be the categories ID, instead of a flat array.
     * @return array
     */
    public function getFields($byCategoriesIds=false) {
        global $DB;
        $categoryCond = '';
        if ($this->filteredCategories) {
            if (!$this->getCategoriesIds()) {
                return array();
            }
            $categoryCond =  " AND categoryid IN (" . join(',', $this->getCategoriesIds()) . ")";
        }
        $fields = $DB->get_records_select(
                'custom_info_field',
                "objectname = ?" . $categoryCond,
                array($this->objectname),
                'categoryid ASC, sortorder ASC'
        );
        if (!$byCategoriesIds || !$fields) {
            return $fields;
        }
        $byCat = array();
        foreach ($fields as $f) {
            if (!isset($byCat[$f->categoryid])) {
                $byCat[$f->categoryid] = array();
            }
            $byCat[$f->categoryid][] = $f;
        }
        return $byCat;
    }
}
