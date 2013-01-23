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
            'edupersonprimaryaffiliation' => array('name' => 'eduPersonPrimaryAffiliation', 'datatype' => 'text', 'locked' => 0),
        ),
    );
    return $res;
}

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

function insert_user_metadata_fields() {
    global $DB;
    $metadata = up1_user_metadata();
    $prefix = 'up1';
    $fieldsnb = 0;

    foreach ($metadata as $cat => $fields) {
        $catdb = $DB->get_record('custom_info_category', array('objectname'=>'user', 'name'=>$cat), 'id', MUST_EXIST);
        if ($catdb->id) {
            $sortorder = 0;
            foreach ($fields as $shortname => $ofields) {
                if ( $DB->record_exists('custom_info_field', array('objectname'=>'user', 'shortname'=>$prefix.$shortname)) ) {
                    echo "$shortname already exists. Keeping it.\n";
                    continue; // next field
                }
                $sortorder++;
                $fieldsnb++;
                echo "inserting $shortname... ";
                $record = new StdClass;
                $record->objectname = 'user';
                $record->shortname = $prefix . $shortname;
                $record->name = $ofields['name'];
                $record->datatype = $ofields['datatype'];
                $record->description = '';
                $record->descriptionformat = 1;
                $record->categoryid = $catdb->id;
                $record->sortorder = $sortorder;
                $record->required = 0;
                $record->locked = $ofields['locked'];
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
            } // $shortname
        }
    } // $cat
    echo "\n$fieldsnb champs créés.\n\n";
}
