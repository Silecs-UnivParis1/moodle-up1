/**
 * Group Selector, AKA autocompleteGroup
 *
 * @license http://opensource.org/licenses/mit-license.php MIT/X11 License
 */

(function($){
    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "http://code.jquery.com/ui/1.8.22/themes/base/jquery-ui.css"
    }).appendTo("head");

    // load the custom CSS
    var cssUrl = $('script[src$="groupsel.js"]').attr('src').replace('/groupsel.js', '/groupsel.css');
    $('head').append($('<link rel="stylesheet" type="text/css" href=' + cssUrl + '>'));

    $.fn.autocompleteGroupPlugin = {
        defaultSettings: {
            urlGroups: 'http://wsgroups.univ-paris1.fr/search',
            urlUserToGroups: 'http://wsgroups.univ-paris1.fr/userGroupsId',
            minLength: 4,
            wsParams: { maxRows : 10 }
        }
    };

    $.fn.autocompleteGroup = function (options) {
        var settings = $.extend(true, {}, $.fn.autocompleteGroupPlugin.defaultSettings, options || {});

        return this.each(function() {
            var $elem = $(this);
            var $input = $elem.find('input.group-selector').first();
            $elem.addClass('autocomplete-group-select');
            $input.on('click', function () {
                $(this).autocomplete("search");
            });
            $input.autocomplete({
                source: mainSource(settings),
                select: function (event, ui) {
                    var inputName = $(this).attr('data-inputname');
                    if (ui.item.source == 'groups') {
                        $(".group-selected", $elem).prepend(buildSelectedBlock(ui.item, inputName));
                        $input.val('');
                    } else if (ui.item.source == 'users') {
                        $input.val(ui.item.label);
                        setGroupsbyUser(ui.item.value, $input, settings);
                    }
                    return false;
                },
                open: function () {},
                close: function () {},
                minLength: settings.minLength
            }).data("autocomplete")._renderItem = customRenderItem;

            $(".group-selected", $(this)).on("click", ".selected-remove", function(event) {
                $(this).closest(".group-item-block").remove();
            });
        });
    }

    function sortByCategory (items) {
        return items.sort(function (a, b) {
            return a.category == b.category ? 0 : a.category > b.category ? 1 : -1;
        });
    }

    function transformItems(items) {
      var category;
      $.each(items, function ( i, item ) {
	    if (category != item.category) {
		category = item.category;
		item.pre = category || "";
	    }
      });
    }

    function mainSource(settings) {
        return function(request, response) {
            var wsParams = $.extend({}, settings.wsParams);
            wsParams.token = request.term;
            $.ajax({
                url: settings.urlGroups,
                dataType: "jsonp",
                data: wsParams,
                success: function (data) {
                    var groups = sortByCategory(data.groups);
                    transformItems(groups);
                    response($.merge(
                        $.merge(
                            (groups.length === 0 ? [] : [{ label: "Groupes", source: "title" }]),
                            $.map(groups, function (item) {
                                return { label: groupItemToLabel(item), value: item.key, source: 'groups', pre: item.pre };
                        })),
                        $.merge(
                            (data.users.length === 0 ? [] : [{ label: "Personnes", source: "title" }]),
                            $.map(data.users, function (item) {
                                return { label: item.displayName, value: item.uid, source: 'users' };
                        }))
                    ));
                }
            });
        }
    }

    function setGroupsbyUser(uid, ac, settings) {
        $.ajax({
            url: settings.urlUserToGroups,
            dataType: "jsonp",
            data: {
                uid: uid
            },
            success: function (data) {
                var items = $.merge(
                    [{ label: "est membre des groupes :", source: "title" }],
                    $.map(data, function (item) {
                       return {
                           label: groupItemToLabel(item),
                           value: item.key,
                           source: 'groups'
                       };
                    })
                );
                ac.data("autocomplete")._suggest(items);
            }
        });
    }

    function groupItemToLabel(item) {
        var $s = '<b>' + item.name + '</b>';
        var description = '';
        if ('description' in item && item.name !== item.description) {
            description = item.description;
        }
        if ('size' in item) {
            description += ' (' + item.size + ')';
        }
        if (description) {
            $s += '<div>' + description + '</div>';
        }
        return $s;
    }

    function buildSelectedBlock(item, inputName) {
        return $('<div class="group-item-block"></div>')
            .html('<div class="group-item-selected">' + item.label + '</div>')
            .prepend('<div class="selected-remove" title="Supprimer la sélection">&#10799;</div>')
            .append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
    }

    function customRenderItem(ul, item) {
        if (item.source == 'title') {
            return $("<li><strong>" + item.label + "</strong></li>").appendTo(ul);
        }
        if (item.pre) {
            $('<li class="kind"><span>' + item.pre + "</span></li>").appendTo(ul);
        }

        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a><span>&oplus;</span>" + item.label + '</a>')
            .appendTo(ul);
    }
})(jQuery);
