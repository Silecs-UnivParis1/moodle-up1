/*
 * @license http://www.gnu.org/licenses/gpl-3.0.html  GNU GPL v3
 */
(function() {

    var rootUrl = findScriptUrl('widget.js');
    var ieWait = 2; // number of scripts, for IE < 9

    if (window.jQuery === undefined) {
        loadJs(rootUrl + "../jquery/jquery.js");
        loadJs(rootUrl + "assets/tree.jquery.js", true);
    } else if (window.jQuery.fn.tree === undefined) {
        ieWait--;
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
            if (script_tag.readyState) { // IE < 9
                script_tag.onreadystatechange = function () {
                    if (this.readyState === 'complete' || this.readyState === 'loaded') {
                        script_tag.onreadystatechange = null; // bug IE8
                        ieWait--;
                        if (ieWait === 0) {
                            onLoad();
                        }
                    }
                };
            } else {
                ieWait = 0;
                script_tag.onload = onLoad;
            }
        } else {
            if (script_tag.readyState) { // IE < 9, bug on async script loading
                script_tag.onreadystatechange = function () {
                    if (this.readyState === 'complete' || this.readyState === 'loaded') {
                        script_tag.onreadystatechange = null; // bug IE8
                        ieWait--;
                        if (ieWait === 0) {
                            onLoad();
                        }
                    }
                };
            }
        }
    }

    function onLoad() {
        var linkTag = document.createElement('link');
        linkTag.setAttribute("type","text/css");
        linkTag.setAttribute("rel","stylesheet");
        linkTag.setAttribute("href", rootUrl + 'assets/jqtree.css');
        (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(linkTag);

        (function () {
            var $ = this.jQuery;

            var teachersWidth = 0;
            var iconsWidth = 0;
            $('.coursetree').each(function(){
                var $tree = $(this);
                var rootNode = $tree.data('root');
                if (rootNode) {
                    rootNode = '?node=' + rootNode;
                } else {
                    rootNode = '';
                }
                $tree.tree({
                    dataUrl: rootUrl + 'service-children.php' + rootNode,
                    onAppendLi: function(node, $li) {
                        if (!node.load_on_demand && !('is_open' in node) && node.children.length === 0) {
                            var $name = $li.find('.jqtree-title:first').first().find('.coursetree-name').first();
                            setTimeout(function(){ // trick to wait for the CSS to be applied
                                var lineWidth = $li.width();
                                if (teachersWidth === 0) {
                                    teachersWidth = $('.jqtree-title > .coursetree-teachers:first', $li).first().width();
                                    iconsWidth = $('.jqtree-title > .coursetree-icons:first', $li).first().width();
                                }
                                $name.width(function(i,w){
                                    return (lineWidth - teachersWidth - iconsWidth - 20); // 20px margin-right
                                });
                            }, 0);
                        }
                    },
                    autoEscape: false, // allow HTML labels
                    autoOpen: false,
                    openedIcon: "<img style='margin-top: -4px' src='" + M.util.image_url('t/expanded', 'core') + "'>",
                    closedIcon: "<img style='margin-top: -4px' src='" + M.util.image_url('t/collapsed', 'core') + "'>",
                    slide: false, // turn off the animation
                    dragAndDrop: false
                });
            });
            $(window).resize(function () {
                $('.coursetree-name').each(function() {
                    var n = $(this);
                    var w = n.closest('.jqtree-title').parent().width() - n.siblings('.coursetree-teachers').first().width() - n.siblings('.coursetree-icons').first().width() - 20;
                    $(this).width(w);
                });
            });
        })(window);
    }

})();
