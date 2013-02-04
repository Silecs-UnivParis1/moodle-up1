$(document).ready(function() {
    var config = {
        separator: / \/ /,
        labels: ["Composante :", "Type de diplôme :"],
        required: false
    };

    $('select.transformRattachements').transformIntoSubselects(config);
});
