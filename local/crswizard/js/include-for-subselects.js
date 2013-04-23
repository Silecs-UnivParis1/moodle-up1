$(document).ready(function() {
    $('select.transformIntoSubselects').transformIntoSubselects({
        separator: / \/ /,
        labels: ["Période :", "Établissement :", "Composante :", "Type de diplôme :"]
    });
});
