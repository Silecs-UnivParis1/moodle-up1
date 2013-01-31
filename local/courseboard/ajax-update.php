<?php

require('../../config.php');

/* @var $DB moodle_database */
global $DB;

$courseid = required_param('courseid', PARAM_INTEGER);
$fieldshortname = required_param('fieldshortname', PARAM_RAW);
$old = required_param('old', PARAM_RAW);
$new = required_param('new', PARAM_RAW);

$sql = '';
$fieldid = $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => $fieldshortname));
if (!$fieldid) {
    $result = array(
        'status' => 'failure',
        'message' => "Field not found in the DB",
    );
} else {
    $fieldid = (int) $fieldid;
    $sqlfind = "SELECT * FROM {custom_info_data} "
            . "WHERE objectname='course' AND objectid = ? AND fieldid = ?";
    $oldRecord = $DB->get_record_sql($sqlfind, array($courseid, $fieldid));
    if ($oldRecord) {
        if ($oldRecord->data != $old) {
            $result = array(
                'status' => 'failure',
                'message' => "La valeur n'était pas à jour.",
                'value' => $oldRecord->data,
            );
        } else {
            $sql = "UPDATE {custom_info_data} "
                    . "SET data = ?, dataformat = 0 "
                    . "WHERE objectname='course' AND objectid = ? AND fieldid = ?";
            $params = array($new, $courseid, $fieldid);
        }
    } else {
        $sql = "INSERT INTO {custom_info_data} "
                . "SET data = ?, dataformat = 0, objectname='course', objectid = ?, fieldid = ?";
        $params = array($new, $courseid, $fieldid);
    }
}

if ($sql) {
    $success = $DB->execute($sql, $params);
    if ($success) {
        $result = array(
            'status' => 'success',
            'message' => "La nouvelle valeur a été écrite.",
            'value' => $new,
        );
    } else {
        $result = array(
            'status' => 'failure',
            'message' => "La valeur n'a pu être écrite.",
        );
    }
}

header('Content-Type: application/json; charset="UTF-8"');
echo json_encode($result);
