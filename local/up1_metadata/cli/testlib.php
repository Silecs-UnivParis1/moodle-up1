<?php
/**
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/local/up1_metadata/lib.php');

echo up1_meta_get_text(5, 'complement') . "\n";
echo up1_meta_get_text(5, 'up1complement') . "\n";

echo up1_meta_get_date(5, 'datedemande') . "\n";
echo up1_meta_get_date(5, 'up1datedemande') . "\n";

print_r(up1_meta_get_user(5, 'demandeurid')) . "\n";
print_r(up1_meta_get_user(5, 'up1demandeurid')) . "\n";
