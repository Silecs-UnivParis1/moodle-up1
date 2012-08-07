/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

jQuery(function () {
    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "http://code.jquery.com/ui/1.8.22/themes/base/jquery-ui.css"
    }).appendTo("head");
    $(".by-widget.group-select input.group-selector").autocomplete({
        minLength: 4,
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
            var inputName = $(this).attr('data-inputname');
            var widget = $(this).closest('.by-widget.group-select');
            $(".group-selected", widget).prepend(buildSelectedBlock(ui.item, inputName));
            return false;
        },
        open: function () {},
        close: function () {}
    });
    function buildSelectedBlock(item, inputName) {
        return $('<div class="group-item-block"></div>').text(item.label)
            .append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
    }
    /*
    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "/"
    }).appendTo("head");
    */
});
