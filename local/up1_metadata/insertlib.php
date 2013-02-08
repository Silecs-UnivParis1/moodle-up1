<?php
/**
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Validate UP1 metadata : each shortname is unique between categories
 * @param assoc. array $metadata as provided by up1_course_metadata() OR up1_user_metadata()
 * @return boolean true = OK
 */
function validate_metadata($metadata) {
    $count = array();
    $res = TRUE;

    // check if a shortname isn't used twice or more (in 2 categories)
    foreach($metadata as $cat => $fields) {
        foreach ($fields as $shortname => $attributes) {
            if (isset($count[$shortname])) {
                $count[$shortname]++;
            } else {
                $count[$shortname] = 1;
            }
        }
    }

    foreach ($count as $shortname => $c) {
        if ($c != 1) {
            $res = FALSE;
            echo "$shortname utilisé $c fois.\n";
        }
    }
    return $res;
}

/**
 * Insert metadata categories (if category does not exist)
 * @global type $DB
 * @param assoc. array $metadata as provided by up1_course_metadata() OR up1_user_metadata()
 * @param string $object = 'course' | 'user'
 */
function insert_metadata_categories($metadata, $object) {
    global $DB;

    $sql = "SELECT MAX(sortorder) AS maxi FROM {custom_info_category} WHERE objectname=?";
    $record = $DB->get_record_sql($sql, array($object));
    $sortorder = $record->maxi;
    foreach ($metadata as $cat => $drop) {
        if ( $DB->record_exists('custom_info_category', array('objectname'=>$object, 'name'=>$cat)) ) {
            echo "[$cat] already exists.<br />\n";
        } else {
            $sortorder++;
            echo "inserting [$cat].<br />\n";
            $record = new StdClass;
            $record->objectname = $object;
            $record->name = $cat;
            $record->sortorder = $sortorder;
            $DB->insert_record('custom_info_category', $record);
        }
    }
}

/**
 * Insert metadata fields
 * @global type $DB
 * @param assoc. array $metadata as provided by up1_course_metadata() OR up1_user_metadata()
 * @param string $object = 'course' | 'user'
 */
function insert_metadata_fields($metadata, $object) {
    global $DB;
    $prefix = 'up1';
    $fieldsnb = 0;

    foreach ($metadata as $cat => $fields) {
        $catdb = $DB->get_record('custom_info_category', array('objectname'=>$object, 'name'=>$cat), 'id', MUST_EXIST);
        if ($catdb->id) {
            $sortorder = 0;
            foreach ($fields as $shortname => $cif_fields) {
                if ( $DB->record_exists('custom_info_field', array('objectname'=>$object, 'shortname'=>$prefix.$shortname)) ) {
                    echo "[$shortname] already exists. Keeping it.<br />\n";
                    continue; // next field
                }
                $sortorder++;
                $fieldsnb++;
                echo "inserting $shortname... ";
                $record = new StdClass;
                $record->objectname = $object;
                $record->shortname = $prefix . $shortname;
                $record->name = $cif_fields['name'];
                $record->datatype = $cif_fields['datatype'];
                $record->description = '';
                $record->descriptionformat = 1;
                $record->categoryid = $catdb->id;
                $record->sortorder = $sortorder;
                $record->required = 0;
                $record->locked = $cif_fields['locked'];
                $record->visible = 2;
                $record->forceunique = 0;
                $record->signup = 0;
                if ($record->datatype == 'text') {
                    $record->defaultdata = '';
                    $record->defaultdataformat = 0;
                    $record->param1 = 30;
                    $record->param2 = 2048;
                    $record->param3 = 0;
                } elseif ($record->datatype == 'datetime') {
                    $record->defaultdata = 0;
                    $record->defaultdataformat = 0;
                    $record->param1 = 2010;
                    $record->param2 = 2020;
                    $record->param3 = 1;
                } elseif ($record->datatype == 'checkbox') {
                    $record->defaultdata = 0;
                    $record->defaultdataformat = 0;
                }
                $id = $DB->insert_record('custom_info_field', $record);
                echo "OK. id=$id\n";
                if ( ! is_null($cif_fields['init']) ) {
                    initialize_custom_data($object, $id, $cif_fields['init']);
                }
            } // $shortname
        }
    } // $cat
    echo "<br />\n$fieldsnb champs créés.<br />\n\n";
}

/**
 * Delete metadata fields.
 * @global type $DB
 * @param assoc. array $metadata as provided by up1_course_metadata() OR up1_user_metadata()
 * @param string $object = 'course' | 'user'
 */
function delete_metadata_fields($metadata, $object) {
    global $DB;
    $prefix = 'up1';
    $fieldsnb = 0;

    foreach ($metadata as $cat => $fields) {
        $catdb = $DB->get_record('custom_info_category', array('objectname'=>$object, 'name'=>$cat), 'id', MUST_EXIST);
        if ($catdb->id) {
            $sortorder = 0;
            foreach ($fields as $shortname => $ofields) {
                if ( $DB->record_exists('custom_info_field', array('objectname'=>$object, 'shortname'=>$prefix.$shortname)) ) {
                    echo "$shortname exists. Deleting it.\n";
                    $fieldsnb++;
                    $DB->delete_records('custom_info_field', array('objectname'=>$object, 'shortname'=>$prefix.$shortname));
                } else {
                    echo "$shortname does NOT exist. No change.\n";
                }
            } // $shortname
        }
    } // $cat
    echo "<br />\n$fieldsnb champs supprimés.<br />\n\n";
}


function initialize_custom_data($objectname, $fieldid, $data) {
    global $DB;

    $cnt=0;
    $sql = "SELECT id FROM {".$objectname."} ";
    $objectids = $DB->get_fieldset_sql($sql);
    foreach ($objectids as $objectid) {
        $record = new StdClass;
        $record->objectname = $objectname;
        $record->objectid = $objectid;
        $record->fieldid = $fieldid;
        $record->data = $data;
        $record->dataformat = 0;
        $dataid = $DB->insert_record('custom_info_data', $record, true, true);
        if ($dataid) {
            $cnt++;
        }
    }
    echo "    $cnt objects have been initialized to [". $data ."].<br />\n\n";
}