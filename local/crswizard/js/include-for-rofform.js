$(document).ready(function() {
    $('div.item-select').on("click", ".element", function(event) {
        var select = $("#items-selected1").children('div[class=item-selected]');
        var intitule = select.children('div[class=intitule-selected]').text();
        $('#id_fullname').val(intitule);
    });

    $("#items-selected").on("click", ".selected-remove", function(event) {
        var select = $("#items-selected1").children('div[class=item-selected]');
        var intitule = select.children('div[class=intitule-selected]').text();
        $('#id_fullname').val(intitule);
    });

    $('#mform1').submit(function(){
        var ret = true;
        var select = $("#items-selected1").children('div[class=item-selected]');
        if (select.size()==0) {
            ret = false;
            var textm = 'Vous devez sélectionner un élément pédagogique comme rattachement de référence de votre espace de cours avant de passer à l\'étape suivante.';
            $('#mgerrorrof').empty();
            $('#mgerrorrof').append('<div class="felement fselect error"><span class="error">'+textm+'</span></div>');
        }
        return ret;
    });

});
