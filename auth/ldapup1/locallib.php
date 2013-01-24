<?php
/**
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

function up1_user_metadata() {

    $res = array(
        'Ldap' => array(
            'edupersonprimaryaffiliation' => array(
                'name' => 'eduPersonPrimaryAffiliation',
                'datatype' => 'text',
                'locked' => 0,
                'init' => '' // can be null ; initial value, not to be confused with the defaultdata field
                ),
            'supannentiteaffectationprincipale' => array(
                'name' => 'supannEntiteAffectationPrincipale',
                'datatype' => 'text',
                'locked' => 0,
                'init' => '' // can be null ; initial value, not to be confused with the defaultdata field
                ),
        ),
    );
    return $res;
}

/**
 * Insert user metadata categories in the custom_info_category table,
 * @global type $DB
 */
function insert_user_metadata_categories() {
    global $DB;
    $metadata = up1_user_metadata();

    $sql = "SELECT MAX(sortorder) AS maxi FROM {custom_info_category} WHERE objectname='user'";
    $record = $DB->get_record_sql($sql);
    $sortorder = $record->maxi;
    foreach ($metadata as $cat => $drop) {
        if ( $DB->record_exists('custom_info_category', array('objectname'=>'user', 'name'=>$cat)) ) {
            echo "$cat already exists.\n";
        } else {
            $sortorder++;
            echo "inserting $cat.\n";
            $record = new StdClass;
            $record->objectname = 'user';
            $record->name = $cat;
            $record->sortorder = $sortorder;
            $DB->insert_record('custom_info_category', $record);
        }
    }
}

/**
 * Insert user metadata in the custom_info_field table,
 *    and optionally values in the custom_info_table (warning, could be greedy)
 * @global type $DB
 * @param boolean $initialize if set, initialize all the objects to the value given by the $record->init field
 */
function insert_user_metadata_fields($initialize=false) {
    global $DB;
    $metadata = up1_user_metadata();
    $prefix = 'up1';
    $fieldsnb = 0;

    foreach ($metadata as $cat => $fields) {
        $catdb = $DB->get_record('custom_info_category', array('objectname'=>'user', 'name'=>$cat), 'id', MUST_EXIST);
        if ($catdb->id) {
            $sortorder = 0;
            foreach ($fields as $shortname => $cif_fields) {
                if ( $DB->record_exists('custom_info_field', array('objectname'=>'user', 'shortname'=>$prefix.$shortname)) ) {
                    echo "$shortname already exists. Keeping it.<br />\n";
                    continue; // next field
                }
                $sortorder++;
                $fieldsnb++;
                echo "inserting $shortname... ";
                $record = new StdClass;
                $record->objectname = 'user';
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
                echo "OK. id=$id <br />\n";
                if ( ! is_null($cif_fields['init']) ) {
                    initialize_custom_data('user', $id, $cif_fields['init']);
                }
            } // $shortname
        }
    } // $cat
    echo "\n$fieldsnb created fields.\n\n";
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