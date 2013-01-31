$(document).ready(function() {
    var config = {
        separator: / \/ /,
        labels: ["Composante :", "Type de diplôme :"],
        required: false
    };
    var more = $('.transformRattachements').first().clone();
    var button = $('<button type="button">Ajouter un champ supplémentaire de rattachement</button>');
    button.click(function() {
        $('.transformRattachements:last').after(more);
        $('select.transformRattachements:last').transformIntoSubselects(config)
    });
    $('select.transformRattachements').transformIntoSubselects(config)
        .after(button);
});
