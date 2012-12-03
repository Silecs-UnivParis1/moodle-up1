<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $OUTPUT, $PAGE;

require_login();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/widget_coursesearch/courselist-demo.php');
$PAGE->set_title('Démo de la liste de cours par critères');

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_coursesearch/courselist.js'), true);

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo de la liste de cours par critères');

?>
<div id="widget-courselist">
</div>
<script type="text/javascript">
//<!--
$("#widget-courselist").courselist({
        "search": "",
        "startdateafter": '2008-09-01',
        "custom": {
            "up1diplome": "Maths"
        }
});
// -->
</script>
<?php

echo $OUTPUT->footer();
