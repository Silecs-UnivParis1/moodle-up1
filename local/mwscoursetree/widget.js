/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */
(function() {

var rootUrl = findScriptUrl('widget.js');

if (window.jQuery === undefined) {
    loadJs(rootUrl + "../jquery/jquery.js");
    loadJs(rootUrl + "assets/tree.jquery.js", true);
} else if (window.jQuery.fn.tree === undefined) {
    loadJs(rootUrl + "assets/tree.jquery.js", true);
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
        $('head').append('<link rel="stylesheet" href="' + rootUrl + 'assets/jqtree.css">');
        $('.coursetree').each(function(){
            var rootNode = $(this).data('root');
            if (rootNode) {
                rootNode = '?node=' + rootNode;
            } else {
                rootNode = '';
            }
            $(this).tree({
                dataUrl: rootUrl + 'service-children.php' + rootNode,
                autoEscape: false, // allow HTML labels
                autoOpen: false,
                slide: false, // turn off the animation
                dragAndDrop: false
           });
        });
    });
}

})();
