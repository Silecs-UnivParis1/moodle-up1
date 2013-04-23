/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */
(function() {

var rootUrl = findScriptUrl('coursesearch.js');

if (window.jQuery === undefined) {
    loadJs(rootUrl + "../jquery/jquery.js");
    loadJs(rootUrl + "../jquery/jquery-ui.js", true);
} else if (window.jQuery.fn.accordion === undefined) {
    loadJs(rootUrl + "../jquery/jquery-ui.js", true);
} else {
    onLoad();
}

function findScriptUrl(name) {
    var scripts = document.getElementsByTagName('script');
    for (var i=0; i<scripts.length; i++) {
        if (scripts[i].src.indexOf('/'+name) !== -1) {
            return scripts[i].src.replace('/'+name, '/');
        }
    }
    return false;
}

function loadJs(url, last) {
    var script_tag = document.createElement('script');
    script_tag.setAttribute("type","text/javascript");
    script_tag.setAttribute("src", url);
    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);
    if (last !== undefined) {
        if (script_tag.readyState) {
            script_tag.onreadystatechange = function () {
                if (this.readyState == 'complete' || this.readyState == 'loaded') {
                    onLoad();
                }
            }
        }
    } else {
        script_tag.onload = onLoad;
    }
}

function onLoad() {
    jQuery(function () {
        $('.widget-coursesearch').load(rootUrl + 'ajax.php');
        $('.widget-coursesearch').on('submit', 'form', searchMoodleCourses);
        $('.widget-coursesearch').on('click', 'input[name="submitbutton"]', searchMoodleCourses);
        $('.widget-coursesearch').on('click', '.paging > a', function (event) {
            var data = $(this).attr('href').substr(1);
            $('.widget-coursesearch').load(rootUrl + 'ajax.php?', data);
            return false;
        });
        function searchMoodleCourses (event) {
            var data = $(this).closest('form').serialize();
            $('.widget-coursesearch').load(rootUrl + 'ajax.php?', data);
            return false;
        }
    });
}

})();
