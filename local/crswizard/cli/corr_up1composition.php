<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.

global $DB;

$idcomposition = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1composition'));
$idcomplement = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1complement'));

echo "idcomposition : " .$idcomposition . "\n";
echo "idcomplement : " .$idcomplement . "\n";

$sql = "select * from {custom_info_data} where objectname = 'course' and fieldid=" . $idcomposition;
$compositions = $DB->get_records_sql($sql);


foreach ($compositions as $c) {
    $compl = $DB->get_record('custom_info_data',array('objectid' => $c->objectid,
        'objectname' => 'course', 'fieldid' => $idcomplement));
    $complement = trim($compl->data);
    $composition = trim($c->data);

    echo "situation départ " . $c->objectid . ' : ' . $c->data . ' / ' . $compl->data . "\n";
    if ($composition != $complement) {
        $DB->update_record('custom_info_data', array('id' => $compl->id, 'data' => $composition));
        $DB->update_record('custom_info_data', array('id' => $c->id, 'data' => $complement));
        echo "Modif : " . $c->objectid . "\n";
    }
}
?>
