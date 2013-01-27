$(document).ready(function() {
    $('div.item-select').on("click", ".element", function(event) {
        var intsel = $(this).prevAll('span.collapse').attr('data_rofid');
        var select = $("#items-selected1").children('div[class=item-selected]');
        var intitule = select.children('div[class=intitule-selected]').text();

        var roffirst = select.children('input:first').val();
        if (intsel == roffirst) {
            var comp = $(this).prevAll('span.comp').text();
            $('#id_complement').val(comp);
        }

        $('#fullname').val(intitule);
        $('#fullnamelab').empty();
        $('#fullnamelab').text(intitule);
    });

    $("#items-selected").on("click", ".selected-remove", function(event) {
        var select = $("#items-selected1").children('div[class=item-selected]');
        var intitule = select.children('div[class=intitule-selected]').text();
        if (intitule == '') {
            $('#id_complement').val(intitule);
        }
        $('#fullname').val(intitule);
        $('#fullnamelab').empty();
        $('#fullnamelab').text(intitule);
    });

    $('#mform1').submit(function(event){
        var ret = true;
        var select = $("#items-selected1").children('div[class=item-selected]');
        if (select.size()==0) {
            ret = false;
            var textm = 'Vous devez sélectionner un élément pédagogique comme rattachement de référence de votre espace de cours avant de passer à l\'étape suivante.';
            $('#mgerrorrof').empty();
            $('#mgerrorrof').append('<div class="felement fselect error"><span class="error">'+textm+'</span></div>');
            event.preventDefault();
            $('html,body').animate({scrollTop: $('#mgerrorrof').offset().top}, 'slow');
        }
        return ret;
    });

});
