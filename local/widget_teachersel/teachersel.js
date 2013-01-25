/*
 *  @license http://opensource.org/licenses/mit-license.php MIT/X11 License
 */

(function($){

    var selected = new Array();
    var nbsel = 0;

    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "http://code.jquery.com/ui/1.8.22/themes/base/jquery-ui.css"
    }).appendTo("head");

    // load the custom CSS
    var cssUrl = $('script[src$="teachersel.js"]').attr('src').replace('/teachersel.js', '/teachersel.css');
    $('head').append($('<link rel="stylesheet" type="text/css" href=' + cssUrl + '>'));

    var defaultSettings = {
            urlUsers: '../mwsgroups/service-users.php',
            minLength: 4,
            labelDetails: '', // will be printed after the selected label
            wsParams: { maxRows : 10 }, // default parameters for the web service
            inputSelector: 'input.user-selector', // class of the input field where completion takes place
            outputSelector: '.users-selected',
            fieldName: 'user', // name of the array (<input type="hidden" name="...[]"/>) for the selected items
            preSelected: [], // [ {"label": "Titre1", "value": "1234"}, {label: "T2", value: "4", fieldName: "myGroup"} ... ]
            maxSelected: 100
    };

    $.fn.autocompleteUser = function (options) {
        var settings = $.extend(true, {}, defaultSettings, options || {});

        return this.each(function() {
            var $elem = $(this);
            var $input = $elem.find(settings.inputSelector).first();
            var acg = new autocompleteUser(settings, $elem, $input);
            acg.init();
            acg.run();
            if (settings.preSelected.length) {
                acg.fillSelection(settings.preSelected);
            }
            $elem.data('autocompleteUser', acg);
        });
    }

    function autocompleteUser(settings, elem, input) {
        this.settings = settings;
        this.elem = elem;
        this.input = input;
        return this;
    }

    autocompleteUser.prototype =
    {
        init: function() {
            this.elem.addClass('autocomplete-user-select');
            this.input.on('click', function () {
                $(this).autocomplete("search");
            });
            $(this.settings.outputSelector, this.elem).on("click", ".selected-remove", function(event) {
                var item = $(this).closest(".teacher-item-block").find('input[type="hidden"]').first();
                selected[item.val()] = 0;
                nbsel = nbsel - 1;
                $(this).closest(".teacher-item-block").remove();
            });
        },

        run: function() {
            var $this = this;
            $this.input.autocomplete({
                source: $this.mainSource($this.settings),
                select: function (event, ui) {
                    if (ui.item.source == 'users') {
                        $($this.settings.outputSelector, $this.elem)
                            .prepend(buildSelectedBlock(ui.item, $this.settings.fieldName,
                                $this.settings.labelDetails, $this.settings.maxSelected));
                    }
                    return false;
                },
                open: function () {},
                close: function () {},
                minLength: $this.settings.minLength
            }).data("autocomplete")._renderItem = customRenderItem;
        },

        fillSelection: function(items) {
            var $this = this;
            for (var i=0; i < items.length; i++) {
                var fieldName = $this.settings.fieldName;
                if ('fieldName' in items[i]) {
                    fieldName = items[i].fieldName;
                }
                $($this.settings.outputSelector, $this.elem)
                    .append(buildSelectedBlock(items[i], fieldName, '', $this.settings.maxSelected));
            }
        },

        mainSource: function() {
            var $this = this;
            return function(request, response) {
                var wsParams = $.extend({}, $this.settings.wsParams);
                wsParams.token = request.term;
                wsParams.maxRows++;
                $.ajax({
                    url: $this.settings.urlUsers,
                    dataType: "jsonp",
                    data: wsParams,
                    success: function (data) {
                        transformUserItems(data);
                        if ('affiliation' in wsParams && wsParams.affiliation) {
                            data.sort(function(a,b){ return a.order - b.order; });
                        }
                        response(buildLabelList(data));
                    }
                });

            }
        }
    }

    function transformUserItems(items) {
        var h = {
            '': "Autre",
            employee: "Autre",
            faculty: "Autre",
            researcher: "Autre",
            staff: "Biatss",
            student: "Étudiants",
            teacher: "Enseignants"
        };
        var categoryRank = {
            "Enseignants": 1,
            "Biatss": 2,
            "Étudiants": 3,
            "Autre": 4
        }
        var previousUserItemName = '';
        for (var i=0; i < items.length; i++) {
            // convert "affiliation" field into "category" field
            if ('affiliation' in items[i]) {
                if (items[i].affiliation && h[items[i].affiliation]) {
                    items[i].category = h[items[i].affiliation];
                } else {
                    items[i].category = h[''];
                }
                items[i].order = categoryRank[items[i].category];
            }
            // mark duplicate fullnames with a "duplicate" field"
            if (previousUserItemName && previousUserItemName == items[i].displayName) {
                items[i-1].duplicate = true;
                items[i].duplicate = true;
            } else {
                previousUserItemName = items[i].displayName;
            }
        }
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

    function buildLabelList(items) {
        var lastCategory = '';
        return $.map(items, function (item) {
            if ('source' in item && item.source == 'title') {
                return item;
            } else if ('category' in item && item.category !== lastCategory) {
                lastCategory = item.category;
                return [
                    { label: item.category, value: '', source: 'title' },
                    { label: userItemToLabel(item), value: item.uid, source: 'users' }
                ];
            } else {
                return { label: userItemToLabel(item), value: item.uid, source: 'users' };
            }
        });
    }

    function customRenderItem(ul, item) {
        if (item.source == 'title') {
            return $("<li><strong>" + item.label + "</strong></li>").appendTo(ul);
        }
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a><span>&oplus;</span>" + item.label + '</a>')
            .appendTo(ul);
    };

    function buildSelectedBlock(item, inputName, details, maxSelected) {
		if (typeof selected[item.value] != 'undefined' && selected[item.value] == 1) {
			alert(item.label+' fait déjà partie de la sélection.');
		} else {
            if (maxSelected > nbsel) {
                selected[item.value] = 1;
                nbsel = nbsel +1;
                var label = item.label.replace(/\s+\(\w+\)\s*$/, '');
                label = label + ' — ' + item.value;
                var labeldetails = '';
                if (details) {
                    labeldetails = ' (' + details + ') ';
                }
                return $('<div class="teacher-item-block"></div>')
                    .html('<div class="teacher-item-selected">' + label + labeldetails + '</div>')
                    .prepend('<div class="selected-remove" title="Supprimer la sélection">&#10799;</div>')
                    .append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
            } else {
                alert('Vous avez sélectionné le nombre maximum d\'élement autorisé ('+maxSelected+').');
            }
        }
    }

})(jQuery);
