/*
 *  @license http://opensource.org/licenses/mit-license.php MIT/X11 License
 */

(function($){

    var selected = new Array();

    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "http://code.jquery.com/ui/1.8.22/themes/base/jquery-ui.css"
    }).appendTo("head");

    // load the custom CSS
    var cssUrl = $('script[src$="teachersel.js"]').attr('src').replace('/teachersel.js', '/teachersel.css');
    $('head').append($('<link rel="stylesheet" type="text/css" href=' + cssUrl + '>'));

    var autocompleteUser = {
        defaultSettings: {
            urlUsers: '../mwsteachers/service-search.php',
            minLength: 4,
            wsParams: { maxRows : 10 }, // default parameters for the web service
            inputSelector: 'input.user-selector', // class of the input field where completion takes place
            outputSelector: '.users-selected',
        }
    };

    $.fn.autocompleteUser = function (options) {
        var settings = $.extend(true, {}, autocompleteUser.defaultSettings, options || {});
        return this.each(function() {
            var $elem = $(this);
            var $input = $elem.find(settings.inputSelector).first();
            $elem.addClass('autocomplete-user-select');
            $input.on('click', function () {
                $(this).autocomplete("search");
            });
            $input.autocomplete({
                source: mainSource(settings),
                select: function (event, ui) {
                    if($('#roleteacher').size()) {
					    var inputName = $('#roleteacher').val();
				    } else {
					    var inputName = $(this).attr('data-inputname');
				    }
                    if (ui.item.source == 'users') {
                        $(settings.outputSelector, $elem).prepend(buildSelectedBlock(ui.item, inputName));
                        $input.val('');
                    }
                    return false;
                },
                open: function () {},
                close: function () {},
                minLength: settings.minLength
            }).data("autocomplete")._renderItem = customRenderItem;

            $(settings.outputSelector, $elem).on("click", ".selected-remove", function(event) {
                var item = $(this).closest(".teacher-item-block").find('input[type="hidden"]').first();
                selected[item.val()] = 0;
                $(this).closest(".teacher-item-block").remove();
            });
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

    function mainSource(settings) {
        return function(request, response) {
            var wsParams = $.extend({}, settings.wsParams);
            wsParams.token = request.term;
            $.ajax({
                url: settings.urlUsers,
                dataType: "jsonp",
                data: wsParams,
                success: function (data) {
                    response(
                        $.merge(
                            [{ label: "Utilisateurs", source: "title" }],
                            $.map(data, function (item) {
                                return { label: item.displayName, value: item.uid, source: 'users' };
                         }))
                    );
                }
            });
        }
    }

    function buildSelectedBlock(item, inputName) {
		var role = inputName;
		if($('#roleteacher').size()) {
			role = $('#roleteacher > option:selected').text();
		}

		if (typeof selected[item.value] != 'undefined' && selected[item.value] == 1) {
			alert(item.label+' fait déjà partie de la sélection.');
		} else {
            selected[item.value] = 1;
			return $('<div class="teacher-item-block"></div>')
				.html('<div class="teacher-item-selected">' + item.label + ' (' + role + ') </div>')
				.prepend('<div class="selected-remove">&#10799;</div>')
				.append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
        }
    }

})(jQuery);
