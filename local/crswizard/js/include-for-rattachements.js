$(document).ready(function() {
    var config = {
        separator: / \/ /,
        labels: ["Composante :", "Type de diplôme :"],
        required: false
    };

    var more = $('.transformRattachements').first().parent().parent().clone();
    more.removeAttr('id');
    more.children('div').removeAttr('id');
    more.find('label').remove();
    var button = $('<button type="button">Ajouter un champ supplémentaire de rattachement</button>');
    button.click(function() {
        var num = $('.transformRattachements').size();
        var inserted = more.clone();
        var select = inserted.find('select').first();
        //select.attr('name', select.attr('name').replace('[0]', '[' + num + ']'));
        select.attr('id', select.attr('id').replace(/_$/, '_' + num));
        $('.transformRattachements:last').parent().parent().after(inserted);
        $('select.transformRattachements:last').transformIntoSubselects(config)
    });

    $('select.transformRattachements').transformIntoSubselects(config)
        .after(button);
});
