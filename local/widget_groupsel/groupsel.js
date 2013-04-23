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
        wsParams: { maxRows: 10 }, // default parameters for the web service (cf userMaxRows, groupMaxRows)
        moreRows: 50, // when requesting more results, larger than maxRows
        dataType: "jsonp",
        labelMaker: function(item) { return item.label; }, // will build the label from the selected item
        inputSelector: 'input.group-selector', // class of the input field where completion takes place
        outputSelector: '.group-selected',
        fieldName: 'group', // name of the array (<input type="hidden" name="...[]"/>) for the selected items
        unique: false, // unicity applies to (fieldname, groupname)
        preSelected: [] // [ {"label": "Titre1", "value": "1234"}, {label: "T2", value: "4", fieldName: "myGroup"} ... ]
    };

    $.fn.autocompleteGroup = function (options) {
        var settings = $.extend(true, {}, defaultSettings, options || {});
        if (!('userMaxRows' in settings.wsParams)) {
            settings.wsParams.userMaxRows = settings.wsParams.maxRows
        }
        if (!('groupMaxRows' in settings.wsParams)) {
            settings.wsParams.groupMaxRows = settings.wsParams.maxRows
        }

        return this.each(function() {
            var $elem = $(this);
            var $input = $elem.find(settings.inputSelector).first();
            var acg = new AutocompleteGroup(settings, $elem, $input);
            acg.init();
            acg.run();
            if (settings.preSelected.length) {
                acg.fillSelection(settings.preSelected);
            }
            $elem.data('autocompleteGroup', acg);
        });
    }

    function AutocompleteGroup(settings, elem, input) {
        this.settings = settings;
        this.elem = elem;
        this.input = input;
        this.selected = {};
        return this;
    }

    AutocompleteGroup.prototype =
    {
        init: function() {
            var $this = this;
            $this.elem.addClass('autocomplete-group-select');
            $this.input.on('click', function () {
                $(this).autocomplete("search");
            });
            $(this.settings.outputSelector, this.elem).on("click", ".selected-remove", function(event) {
                if ($this.settings.unique) {
                    var input = $(this).siblings('input').first();
                    var id = input.attr('name') + " -- " + input.val();
                    delete $this.selected[id];
                }
                $(this).closest(".group-item-block").remove();
            });
            $this.elem.before('<div class="autocomplete-group-messages" style="display:none"></div>');
        },

        run: function() {
            var $this = this;
            $this.input.autocomplete({
                source: $this.mainSource($this.settings),
                select: function (event, ui) {
                    if (ui.item.source == 'groups') {
                        if (ui.item.label === "Afficher plus de réponses") {
                            var category2filters = {
                                'Groupes Établissement': 'affiliation|structures',
                                'Groupes Étapes': 'diploma',
                                'Groupes Matières': 'elp',
                                'Groupes TD': 'gpelp',
                                'Autres types de groupes': 'other'
                            };
                            var localSettings = JSON.parse(JSON.stringify($this.settings));
                            localSettings.wsParams.groupMaxRows = ui.item.maxRows;
                            delete localSettings.wsParams.maxRows;
                            delete localSettings.wsParams.userMaxRows;
                            var ajaxCall = $this.mainSource(localSettings);
                            ajaxCall(
                                {term: $this.input.val(), 'filter_group_category': category2filters[ui.item.category]},
                                function (items) { $this.input.data("autocomplete")._suggest(items); }
                            );
                            return false;
                        } else {
                            if ($this.addSelected($this.settings.fieldName, ui.item.value)) {
                                $($this.settings.outputSelector, $this.elem)
                                    .prepend(buildSelectedBlock(ui.item, $this.settings.fieldName, $this.settings.labelMaker));
                                $this.input.val('');
                            }
                        }
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

        addSelected: function(field, val) {
            $this = this;
            if (!$this.settings.unique) {
                return true;
            }
            var id = field + ' -- ' + val;
            if (id in $this.selected) {
                $this.elem.prev(".autocomplete-group-messages").html("Ce groupe a déjà été ajouté.").show().fadeOut(6000);
                return false;
            }
            $this.selected[id] = 1;
            return true;
        },

        fillSelection: function(items) {
            var $this = this;
            for (var i=0; i < items.length; i++) {
                var fieldName = $this.settings.fieldName;
                if ('fieldName' in items[i]) {
                    fieldName = items[i].fieldName;
                }
                $($this.settings.outputSelector, $this.elem)
                    .append(buildSelectedBlock(items[i], fieldName, defaultSettings.labelMaker));
            }
        },

        setGroupsbyUser: function(uid, ac, settings) {
            var $this = this;
            $.ajax({
                url: settings.urlUserToGroups,
                dataType: settings.dataType,
                data: {
                    uid: uid
                },
                success: function (data) {
                    var groups = sortByCategory(data);
                    transformGroupItems(groups);
                    var items = $this.prepareList(groups, 'est membre des ' + groups.length + ' groupes :', function (item) {
                        return {
                            label: groupItemToLabel(item),
                            value: item.key,
                            source: 'groups',
                            pre: item.pre
                       };
                    }, 0);
                    ac.data("autocomplete")._suggest(items);
                }
            });
        },

        mainSource: function(settings) {
            var $this = this;
            return function(request, response) {
                var wsParams = JSON.parse(JSON.stringify(settings.wsParams));
                wsParams.token = request.term;
                if ('filter_group_category' in request) {
                    wsParams['filter_group_category'] = request['filter_group_category'];
                }
                if ('maxRows' in wsParams) {
                    wsParams.maxRows++;
                }
                if ('userMaxRows' in wsParams) {
                    wsParams.userMaxRows++;
                }
                if ('groupMaxRows' in wsParams) {
                    wsParams.groupMaxRows++;
                }
                $.ajax({
                    url: settings.urlGroups,
                    dataType: settings.dataType,
                    data: wsParams,
                    success: function (data) {
                        var groups = sortByCategory(data.groups);
                        transformGroupItems(groups);
                        transformUserItems(data.users);
                        response($.merge(
                            $this.prepareList(groups, 'Groupes', function (item) {
                                    return { label: groupItemToLabel(item), value: item.key, source: 'groups', pre: item.pre, category: item.category };
                            }, settings.wsParams.groupMaxRows),
                            $this.prepareList(data.users, 'Personnes', function (item) {
                                    return { label: userItemToLabel(item), value: item.uid, source: 'users' };
                            }, settings.wsParams.userMaxRows)
                        ));
                    }
                });
            }
        },

        prepareList: function(list, titleLabel, itemToResponse, maxRows) {
            var $this = this;
            var len = list.length;
            var initMaxRows = ('groupMaxRows' in $this.settings.wsParams ?
                $this.settings.wsParams.groupMaxRows : $this.settings.wsParams.maxRows);
            var r = $.merge(
                (len === 0 ? [] : [{ label: titleLabel, source: "title" }]),
                $.map(list, function(item, index) {
                    if (maxRows && item.num > maxRows) {
                        item = itemToResponse.apply(this, [item]);
                        item.label = 'Afficher plus de réponses';
                        item.maxRows = maxRows + $this.settings.moreRows;
                        return item;
                    } else if ('source' in item && item.source == 'title') {
                        return item;
                    } else {
                        return itemToResponse.apply(this, [item]);
                    }
                })
            );
            if (maxRows > initMaxRows && len < maxRows) {
                r.push({ label: "Toutes les réponses sont listées", source: "title" });
            }
            return r;
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
        var num = 0;
        var category2text = {
            structures: 'Groupes Établissement',
            affiliation: 'Groupes Établissement',
            diploma: 'Groupes Étapes',
            //gpetp: 'Groupes Étapes',
            elp: 'Groupes Matières',
            gpelp: 'Groupes TD'
        };
        $.each(items, function ( i, item ) {
            if (item.category in category2text) {
                item.category = category2text[item.category];
            } else {
                item.category = 'Autres types de groupes';
            }
            if (category !== item.category) {
                category = item.category;
                item.pre = category || "";
                num = 1;
            } else {
                num++;
            }
            item.num = num;
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
            description += ' (' + item.size + ' inscrits)';
        }
        if (description) {
            $s += '<div class="autocompletegroup-descr">' + description + '</div>';
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

    function buildSelectedBlock(item, inputName, labelMaker) {
        return $(
                '<div class="group-item-block"><div class="group-item-selected">'
                + labelMaker(item) + '</div></div>'
            )
            .prepend('<div class="selected-remove" title="Supprimer la sélection">&#10799;</div>')
            .append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
    }

    function customRenderItem(ul, item) {
        if (item.source == 'title') {
            return $("<li><strong>" + item.label + "</strong></li>").appendTo(ul);
        }
        if (item.label == 'Afficher plus de réponses') {
             return $('<li class="autocompletegroup-more"></li>')
                .data("item.autocomplete", item)
                .append('<a>Afficher plus de réponses</a>')
                .appendTo(ul);
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
