$(document).ready(function() {
    $('select.transformIntoSubselects').transformIntoSubselects({
        separator: / \/ /,
        labels: ["Période :", "Cours :"]
    });

    if (! $("#id_modeletype_selm2").attr("checked")) {
        $("#fitem_id_selm2").addClass('cache');
    }

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

     $("#id_modeletype_0").click(
        function() {
            if ($(this).attr("checked")) {
                $("#fitem_id_selm2").addClass('cache');
            }
    });

});
