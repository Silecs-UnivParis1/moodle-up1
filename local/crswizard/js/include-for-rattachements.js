$(document).ready(function() {
    var config = {
        separator: / \/ /,
        labels: ["Composante :", "Type de diplôme :"],
        required: false
    };

    $('select.transformRattachements').transformIntoSubselects(config);
});

$(document).ready(function() {
    var config = {
        separator: / \/ /,
        labels: ["Niveau année :"],
        required: false,
        labelButton: 'Ajouter un Niveau année'
    };

    $('select.niveauanneeRattachements').transformIntoSubselects(config);
});

$(document).ready(function() {
    var config = {
        separator: / \/ /,
        labels: ["Semestre :"],
        required: false,
        labelButton: 'Ajouter un semestre'
    };

    $('select.semestreRattachements').transformIntoSubselects(config);
});

$(document).ready(function() {
    var config = {
        separator: / \/ /,
        labels: ["Niveau :"],
        required: false,
        labelButton: 'Ajouter un niveau'
    };

    $('select.niveauRattachements').transformIntoSubselects(config);
});
