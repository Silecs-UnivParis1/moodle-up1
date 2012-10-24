/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

jQuery(function () {
    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "http://code.jquery.com/ui/1.8.22/themes/base/jquery-ui.css"
    }).appendTo("head");
    $(".by-widget.group-select input.group-selector").each(function(){
        var internal = $(this).closest('.group-select').hasClass('group-select-internal');
        $(this).autocomplete({
        minLength: 4,
        source: mainSource(internal),
        select: function (event, ui) {
            var inputName = $(this).attr('data-inputname');
            var widget = $(this).closest('.by-widget.group-select');
            if (ui.item.source == 'groups') {
                $(".group-selected", widget).prepend(buildSelectedBlock(ui.item, inputName));
                $(this).val('');
            } else if (ui.item.source == 'users') {
                $(this).val(ui.item.label);
                setGroupsbyUser(ui.item.value, $('input.ui-autocomplete-input', widget), internal);
            }
            return false;
        },
        open: function () {},
        close: function () {}
    })}).data("autocomplete")._renderItem = function(ul, item) {
        if (item.source == 'title') {
            return $("<li><strong>" + item.label + "</strong></li>").appendTo(ul);
        }
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a><span>&oplus;</span>" + item.label + '</a>')
            .appendTo(ul);
    };
    function mainSource(internal) {
        var sourceUrl = "http://wsgroups.univ-paris1.fr/search";
        var inscrits = "";
        if (internal) {
            sourceUrl = $('script[src$="groupsel.js"]').attr('src').replace('/widget_groupsel/groupsel.js', '/mwsgroups/service-search.php');
        }
        return function(request, response) {
        $.ajax({
            url: sourceUrl,
            dataType: "jsonp",
            data: {
                maxRows: 10,
                token: request.term
            },
            success: function (data) {
                response($.merge(
                    $.merge(
                        [{ label: "Groupes d'étudiants", source: "title" }],
                        $.map(data.groups, function (item) {
                            if ('size' in item) {
                                inscrits = ' (' + item.size + ' inscrits) ';
                            }
                            return { label: '<b>' + item.name + '</b><div>' + item.description + inscrits + '</div>',
                                value: item.key,
                                source: 'groups' };
                    })),
                    $.merge(
                        [{ label: "Étudiants", source: "title" }],
                        $.map(data.users, function (item) {
                            return { label: item.displayName, value: item.uid, source: 'users' };
                    }))
                ));
            }
        });
        }
    }
    function setGroupsbyUser(uid, ac, internal) {
        var sourceUrl = 'http://wsgroups.univ-paris1.fr/userGroupsId';
        if (internal) {
            sourceUrl = $('script[src$="groupsel.js"]').attr('src').replace('/widget_groupsel/groupsel.js', '/mwsgroups/service-userGroups.php');
        }
        $.ajax({
            url: sourceUrl,
            dataType: "jsonp",
            data: {
                uid: uid
            },
            success: function (data) {
                var items = $.merge(
                    [{ label: "est membre des groupes :", source: "title" }],
                    $.map(data, function (item) {
                       return {
                           label: '<b>' + item.name + '</b><div>' + item.description + '</div>',
                           value: item.key,
                           source: 'groups'
                       };
                    })
                );
                ac.data("autocomplete")._suggest(items);
            }
        });
    }
    function buildSelectedBlock(item, inputName) {
        return $('<div class="group-item-block"></div>')
            .html('<div class="group-item-selected">' + item.label + '</div>')
            .prepend('<div class="selected-remove" title="Supprimer la sélection">&#10799;</div>')
            .append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
    }
    $(".by-widget.group-select .group-selected").on("click", ".selected-remove", function(event) {
        $(this).closest(".group-item-block").remove();
    });
    // load the custom CSS
    var cssUrl = $('script[src$="groupsel.js"]').attr('src').replace('/groupsel.js', '/groupsel.css');
    $('head').append($('<link rel="stylesheet" type="text/css" href=' + cssUrl + '>'));
});
