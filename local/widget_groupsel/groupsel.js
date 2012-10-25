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

    var defaultSettings = {
        urlGroups: 'http://wsgroups.univ-paris1.fr/search',
        urlUserToGroups: 'http://wsgroups.univ-paris1.fr/userGroupsId',
        minLength: 4,
        wsParams: { maxRows : 9 }, // default parameters for the web service
        inputSelector: 'input.group-selector', // class of the input field where completion takes place
        outputSelector: '.group-selected',
        fieldName: 'group' // name of the array (<input type="hidden" name="...[]"/>) for the selected items
    };

    $.fn.autocompleteGroup = function (options) {
        var settings = $.extend(true, {}, defaultSettings, options || {});
        settings.wsParams.maxRows++;

        return this.each(function() {
            var $elem = $(this);
            var $input = $elem.find(settings.inputSelector).first();
            var acg = new AutocompleteGroup(settings, $elem, $input);
            acg.init();
            acg.run();
        });
    }

    function AutocompleteGroup(settings, elem, input) {
        this.settings = settings;
        this.elem = elem;
        this.input = input;
        return this;
    }

    AutocompleteGroup.prototype =
    {
        init: function() {
            this.elem.addClass('autocomplete-group-select');
            this.input.on('click', function () {
                $(this).autocomplete("search");
            });
            $(this.settings.outputSelector, this.elem).on("click", ".selected-remove", function(event) {
                $(this).closest(".group-item-block").remove();
            });
        },

        run: function() {
            var $this = this;
            $this.input.autocomplete({
                source: $this.mainSource($this.settings),
                select: function (event, ui) {
                    if (ui.item.source == 'groups') {
                        $($this.settings.outputSelector, $this.elem)
                            .prepend(buildSelectedBlock(ui.item, $this.settings.fieldName));
                        $this.input.val('');
                    } else if (ui.item.source == 'users') {
                        $this.input.val(ui.item.label);
                        $this.setGroupsbyUser(ui.item.value, $this.input, $this.settings);
                    }
                    return false;
                },
                open: function () {},
                close: function () {},
                minLength: $this.settings.minLength
            }).data("autocomplete")._renderItem = customRenderItem;
        },

        setGroupsbyUser: function(uid, ac) {
            var $this = this;
            $.ajax({
                url: $this.settings.urlUserToGroups,
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
        },

        mainSource: function() {
        var $this = this;
            return function(request, response) {
                var wsParams = $.extend({}, $this.settings.wsParams);
                wsParams.token = request.term;
                $.ajax({
                    url: $this.settings.urlGroups,
                    dataType: "jsonp",
                    data: wsParams,
                    success: function (data) {
                        var groups = sortByCategory(data.groups);
                        transformGroupItems(groups);
                        transformUserItems(data.users);
                        response($.merge(
                            $this.prepareList(groups, 'Groupes', function (item) {
                                    return { label: groupItemToLabel(item), value: item.key, source: 'groups', pre: item.pre };
                            }),
                            $this.prepareList(data.users, 'Personnes', function (item) {
                                    return { label: userItemToLabel(item), value: item.uid, source: 'users' };
                            })
                        ));
                    }
                });
            }
        },

        prepareList: function(list, titleLabel, itemToResponse) {
            var $this = this;
            var len = list.length;
            if (len >= ($this.settings.wsParams.maxRows)) {
                list[len-1] = { source: 'title', label: '…'};
            }
            return $.merge(
                (len === 0 ? [] : [{ label: titleLabel, source: "title" }]),
                $.map(list, function(item) {
                    if ('source' in item && item.source == 'title') {
                        return item;
                    } else {
                        return itemToResponse.apply(this, [item]);
                    }
                })
            );
        }
    }

    function sortByCategory (items) {
        var order = { structures: 5, affiliation: 5, diploma: 1, elp: 2, gpelp: 3, gpetp: 4 };
        return items.sort(function (a, b) {
            return a.category == b.category ? 0 : (order[a.category] > order[b.category] ? 1 : -1);
        });
    }

    function transformGroupItems(items) {
        var category;
        var category2text = {
            structures: 'établissement',
            affiliation: 'établissement',
            diploma: 'étapes',
            elp: 'matières',
            gpelp: 'TD',
            gpetp: 'Groupe étape',
            '': 'autre'
        };
        $.each(items, function ( i, item ) {
            if (item.category in category2text) {
                item.category = category2text[item.category];
            }
            if (category != item.category) {
                category = item.category;
                item.pre = category || "";
            }
        });
    }

    function transformUserItems(items) {
      var previousUserItemName = '';
      if (items.length < 2) {
          return;
      }
      for (var i=0; i < items.length; i++) {
        if (previousUserItemName && previousUserItemName == items[i].displayName) {
            items[i-1].duplicate = true;
            items[i].duplicate = true;
        } else {
            previousUserItemName = items[i].displayName;
        }
      }
    }

    function groupItemToLabel(item) {
        var $s = '';
        if ('name' in item) {
            $s = '<b>' + item.name + '</b>';
        } else {
            $s = '[' + item.key + ']';
        }
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

    function userItemToLabel(item) {
        var $s = item.displayName;
        if ('duplicate' in item && item.duplicate) {
            $s += ' (' + item.uid + ' )';
        }
        if ('supannEntiteAffectation' in item && item.supannEntiteAffectation.length) {
            $s += ' [' + item.supannEntiteAffectation.join(', ') + ']';
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
