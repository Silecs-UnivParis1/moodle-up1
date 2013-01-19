$(document).ready(function() {
    $('div#bockhelpE7').after('<button type="button" id="request-details-toggle">Afficher/masquer le récapitulatif de la demande</button>');
    $("#request-details-toggle").click(requestDetailsToggle);
    function requestDetailsToggle() {
        $("#request-details-toggle").nextAll('fieldset.clearfix[id!=confirmation]').toggle();
    }
    requestDetailsToggle();
});
