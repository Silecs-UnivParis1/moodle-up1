$(document).ready(function() {
    $('<style type="text/css">\n\
.updatable { cursor: pointer; }\n\
.generaltable .cell.status-success { background-color: #E0FFE0; }\n\
.generaltable .cell.status-failure { background-color: #FFE0E0; }\n\
</style>').appendTo("head");
    $('td.updatable:not(.editing)').on('click', function(){
        var $td = $(this);
        if ($td.hasClass('editing')) {
            return true;
        }
        $td.addClass('editing').removeClass('status-success status-failure');
        var content = $td.text();
        $td.html(updatable_create_form(content, $td.data('structure')));
        $('form [name="new"]', $td).focus();
        $('form', $td).on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                type: "POST",
                url: 'ajax-update.php',
                dataType: 'json',
                data: {
                    courseid: $td.data('courseid'),
                    fieldshortname: $td.data('fieldshortname'),
                    "old": $('input[name="old"]', $td).val(),
                    "new": $('[name="new"]', $td).val()
                },
                success: function(data){
                    if (typeof data !== 'object') {
                        console.log("Error with ajax-update.php");
                    }
                    $td.addClass('status-' + data.status);
                    if ('value' in data) {
                        $td.html(data.value);
                    } else {
                        $td.html(content);
                    }
                    if ('message' in data) {
                        $td.attr('title', data.message);
                    }
                    $td.removeClass('editing');
                }
            });
            return false;
        });
        return false;
    });

    function updatable_create_form(value, structure) {
        var form = $('<form action=""><input type="hidden" name="old"><button type="submit">OK</button></form>');
        form.children('input[name="old"]').val(value);
        var field;
        console.log(structure);
        if (structure && structure.type == 'list') {
            field = $('<select name="new"></select>');
            for (var displayName in structure.options) {
                var option = $('<option></option>');
                option.text(displayName);
                option.val(structure.options[displayName]);
                field.append(option);
            }
        } else {
            field = $('<input type="text" name="new">');
        }
        field.val(value);
        form.children('input').after(field);
        return form;
    }
});
