$(document).ready(function() {
    var selected = new Array();
    var reference = new Array();
    $('div.item-select').on("click", ".element", function(event) {
        var rofid = $(this).prevAll('span.collapse').attr('data_rofid');
        var name = 'select_'+rofid;
        if (typeof selected[name] == 'undefined' ||  selected[name] == 0) {
            selected[name] = 1;
            if (reference.length==0) {
                reference[0] = name;
                var intitule = $(this).prevAll('span.intitule').text();
                $('#id_fullname').val(intitule);
            }
        }

    });

    $("#items-selected").on("click", ".selected-remove", function(event) {
        var rofid = $(this).siblings('input[type=hidden]').val();
        selected['select_'+rofid] = 0;
        if (reference[0]=='select_'+rofid) {
            reference.splice(0, 1);
            $('#id_fullname').val('');
        }
    });

    $('#mform1').submit(function(){
        var ret = true;
        if (reference.length==0) {
            ret = false;
            $('#categoryheader').after('<div class="felement fselect error"><span class="error">Il manque le rattachement de référence</span></div>');
        }
        return ret;
    });

});
