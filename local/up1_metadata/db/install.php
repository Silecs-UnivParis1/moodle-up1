<?php
/**
 * Plugin upgrade code.
 *
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../datalib.php');
require_once(__DIR__ . '/../insertlib.php');

function xmldb_local_up1_metadata_install() {
    global $CFG, $DB;

    $metadata = up1_course_metadata();

    echo "Création des catégories :<br />\n";
    insert_metadata_categories($metadata, 'course');

    echo "<br />\n<br />\n";
    echo "Création des champs :<br />\n";
    insert_metadata_fields($metadata, 'course');


    return true;
}