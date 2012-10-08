/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

jQuery(function () {
    $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: "http://code.jquery.com/ui/1.8.22/themes/base/jquery-ui.css"
    }).appendTo("head");
    $(".by-widget.teacher-select input.teacher-selector").each(function(){
        $(this).autocomplete({
			minLength: 4,
			source: mainSource(),
			select: function (event, ui) {

				if($('#roleteacher').size()) {
					var inputName = $('#roleteacher').val();
				} else {
					var inputName = $(this).attr('data-inputname');
				}
				var widget = $(this).closest('.by-widget.teacher-select');
				if (ui.item.source == 'users') {
					$(".teachers-selected", widget).prepend(buildSelectedBlock(ui.item, inputName));
					$(this).val('');
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
    function mainSource() {
        sourceUrl = $('script[src$="teachersel.js"]').attr('src').replace('/widget_teachersel/teachersel.js', '/mwsteachers/service-search.php');
        return function(request, response) {
        $.ajax({
            url: sourceUrl,
            dataType: "jsonp",
            data: {
                maxRows: 10,
                token: request.term
            },
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

		var present = $('input[type=hidden][value='+item.value+']').size();
		if (present) {
			alert(item.label+' fait déjà partie de la sélection.');
		} else {
			return $('<div class="teacher-item-block"></div>')
				.html('<div class="teacher-item-selected">' + item.label + ' (' + role + ') </div>')
				.prepend('<div class="selected-remove">&#10799;</div>')
				.append('<input type="hidden" name="' + inputName + '[]" value="' + item.value + '" />');
        }
    }
    $(".by-widget.teacher-select .teachers-selected").on("click", ".selected-remove", function(event) {
        $(this).closest(".teacher-item-block").remove();
    });
    // load the custom CSS
    var cssUrl = $('script[src$="teachersel.js"]').attr('src').replace('/teachersel.js', '/teachersel.css');
    $('head').append($('<link rel="stylesheet" type="text/css" href=' + cssUrl + '>'));
});
