<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

/* @var $PAGE page_base */
global $PAGE, $OUTPUT;

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/mwscoursetree/widget-demo.php');
$PAGE->set_title("Démo de l'arbre des cours");

$PAGE->requires->js(new moodle_url('/local/mwscoursetree/widget.js'), true);

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading("Démo de l'arbre des cours");

?>
<style type="text/css">
.jqtree-hidden {
    display: inherit;
}
</style>
<script type="text/javascript" src="<?php echo new moodle_url('/local/mwscoursetree/widget.js'); ?>"></script>
<div class="coursetree" data-root="/0" data-title="1"></div>
<?php

echo $OUTPUT->footer();
