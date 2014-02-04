$(document).ready(function() {
    $('select.transformIntoSubselects').transformIntoSubselects({
        separator: / \/ /,
        labels: ["Période :", "Cours :"]
    });

    if (! $("#id_modeletype_selm2").attr("checked")) {
        $("#fitem_id_selm2").addClass('cache');
    }

    var selm1 = $("#id_selm1").val();
    $("#id_selm1").parent('fieldset').attr("title", $("#id_course_summary").children('option[value='+selm1+']').text());

    $("#id_modeletype_selm2").click(
        function() {
            if ($(this).attr("checked")) {
                $("#fitem_id_selm2").removeClass('cache');
            }
    });

    $("#id_modeletype_selm1").click(
        function() {
            if ($(this).attr("checked")) {
                $("#fitem_id_selm2").addClass('cache');
            }
    });

    $("#id_selm1").change(
        function() {
            var sel = this.value;
            var text = $("#id_course_summary").children('option[value='+sel+']').text();
            $(this).parent('fieldset').attr("title", text);
    });
});
