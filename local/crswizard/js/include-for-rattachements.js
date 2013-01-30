$(document).ready(function() {
    $('select.transformRattachements').transformIntoSubselects({
        separator: / \/ /,
        labels: ["Composante :", "Type de diplôme :"],
        required: false,
    });
});
