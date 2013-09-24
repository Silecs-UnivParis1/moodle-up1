<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $OUTPUT, $PAGE;

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/local/widget_courselist/courselist-demo.php');
$PAGE->set_title('Démo de la liste de cours par critères');

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_courselist/courselist.js'), true);

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo de la liste de cours par critères');

?>
<div id="widget-courselist">
</div>
<script type="text/javascript">
//<!--
jQuery(document).ready(function () {
    jQuery("#widget-courselist").courselist({
            "search": "",
            "startdatebefore": '2013-09-01',
            //format: "list", // default: "table"
            //enrolled: "Dupont",
            enrolledexact: "admin",
            //enrolledroles: [3],
            custom: {
                // "UP1DemandeurId": "4"
            }
    });
});
// -->
</script>
<?php

echo $OUTPUT->footer();
