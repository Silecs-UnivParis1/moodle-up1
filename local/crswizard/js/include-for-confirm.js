$(document).ready(function() {
    $('fieldset#confirmation').after('<button type="button" id="request-details-toggle">Afficher/masquer le récapitulatif de la demande</button>');
    $("#request-details-toggle").click(requestDetailsToggle);
    function requestDetailsToggle() {
        $("#request-details-toggle").nextAll('fieldset.clearfix').toggle();
    }
    requestDetailsToggle();
});
