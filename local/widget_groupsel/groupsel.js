/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

jQuery(function () {
    var selector = $("input.by-widget#group-selector");
    var groupInputName = selector.attr('data-inputname');
    selector.autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "http://ticetest.univ-paris1.fr/web-service-groups/search",
                dataType: "jsonp",
                data: {
                    maxRows: 10,
                    token: request.term
                },
                success: function (data) {
                    response($.merge(
                    $.map(data.users, function (item) {
                        return {
                            label: item.displayName,
                            value: item.uid
                        }
                    }), $.map(data.groups, function (item) {
                        return {
                            label: item.description,
                            value: item.key
                        }
                    })));
                }
            });
        },
        minLength: 4,
        select: function (event, ui) {
            jQuery(".by-widget#group-selected").prepend(buildSelectedBlock(ui.item));
            return false;
        },
        open: function () {},
        close: function () {}
    });
    function buildSelectedBlock(item) {
        return $('<div class="group-block"></div>').text(item.label)
            .append('<input type="hidden" name="' + groupInputName + '[]" value="' + item.value + '" />');
    }
    /*
    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "/"
    }).appendTo("head");
    */
});
