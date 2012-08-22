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
                        $.merge(
                            ["Groupes d'étudiants"],
                            $.map(data.groups, function (item) {
                                return { label: item.description, value: item.key, source: 'groups' };
                        })),
                        $.merge(
                            ["Étudiants"],
                            $.map(data.users, function (item) {
                                return { label: item.displayName, value: item.uid, source: 'users' };
                        }))
                    ));
                }
            });
        },
        select: function (event, ui) {
            var inputName = $(this).attr('data-inputname');
            var widget = $(this).closest('.by-widget.group-select');
            if (ui.item.source == 'groups') {
                $(".group-selected", widget).prepend(buildSelectedBlock(ui.item, inputName));
            }
            return false;
        },
        open: function () {},
        close: function () {}
    }).data("autocomplete")._renderItem = function(ul, item) {
        if (item.value == item.label) {
            return $("<li><strong>" + item.label + "</strong></li>").appendTo(ul);
        }
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a>" + item.label + "</a>")
            .appendTo(ul);
    };
    function buildSelectedBlock(item, inputName) {
        return $('<div class="group-item-block"></div>').text(item.label)
        .append('<span style="float: right;" class="selected-remove">&times;</span>')
        .append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
    }
    $(".by-widget.group-select .group-selected").on("click", "span.selected-remove", function(event) {
        $(this).closest(".group-item-block").remove();
    });
});
