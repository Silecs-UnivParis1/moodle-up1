$(document).ready(function() {

    if ($("#id_message_0").attr("checked")) {
        $("#fitem_id_msgrelancesubject").addClass('cache');
        $("#fitem_id_msgrelancebody").addClass('cache');
    }
    if ($("#id_message_1").attr("checked")) {
        $("#fitem_id_msginvitationsubject").addClass('cache');
        $("#fitem_id_msginvitationbody").addClass('cache');
    }

    $("#id_message_1").click(
        function() {
            $("#fitem_id_msgrelancesubject").removeClass('cache');
            $("#fitem_id_msgrelancebody").removeClass('cache');
            $("#fitem_id_msginvitationsubject").addClass('cache');
            $("#fitem_id_msginvitationbody").addClass('cache');

    });

    $("#id_message_0").click(
        function() {
            $("#fitem_id_msginvitationsubject").removeClass('cache');
            $("#fitem_id_msginvitationbody").removeClass('cache');
            $("#fitem_id_msgrelancesubject").addClass('cache');
            $("#fitem_id_msgrelancebody").addClass('cache');
    });

});
