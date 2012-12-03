(function($){
    var rootUrl = $('script[src$="/courselist.js"]').attr('src').replace('/courselist.js', '/');
    jQuery.fn.courselist = function(criteria) {
        var $elem = this;
        $.ajax({
            'url': rootUrl + 'list.php',
            'type': 'GET',
            data: criteria
        }).done(function(html) {
            $elem.html(html);
        });
    }
})(jQuery);
