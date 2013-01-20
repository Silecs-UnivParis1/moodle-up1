<?php
/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

function table_course_vs_rof($crsid, $rofdata) {

    $crsfields = custominfo_data::type('course')->get_structured_fields_short($crsid, true);
    echo "<ul>\n";
    // var_dump($rofcourse);

    foreach ($crsfields as $category => $fields) {
        if ($category == 'Other fields') continue;
            echo "<h4>" . $category . "</h4>\n";
            // var_dump($rofdata[$category]);
            // var_dump($fields);

            foreach ($fields as $shortname => $field ) {
                $name = $field['name'];
                $crsvalue = $field['data'];
                $rofvalue = (isset($rofdata[$category][$shortname]) ? $rofdata[$category][$shortname] : '(donnée absente)');
                echo "<li>" . $shortname . ":" . $crsvalue . " VS " . $rofvalue . "</li>";

            }

        echo "</ul>\n";
    }
    echo "</ul>\n";
}