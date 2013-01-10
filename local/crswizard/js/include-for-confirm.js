$(document).ready(function() {
    $('fieldset#confirmation').after('<button type="button" id="request-details-toggle">Détails&hellip;</button>');
    $("#request-details-toggle").click(requestDetailsToggle);
    function requestDetailsToggle() {
        $("#request-details-toggle").nextAll('fieldset.clearfix').toggle();
    }
    requestDetailsToggle();
});
