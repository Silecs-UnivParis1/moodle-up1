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
        $td.html('<form action=""><input type="hidden" name="old"><input type="text" name="new"><button type="submit">OK</button></form>');
        $('input[name="old"]', $td).val(content);
        $('input[name="new"]', $td).val(content).focus();
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
                    "new": $('input[name="new"]', $td).val()
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
});
