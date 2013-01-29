(function ($) {

var is_string = function (v){
    return typeof(v) === 'string';
};

var labels;

var config = {};

var defaultConfig = {
    separator: " / "
};

var getTree = function (options) {
    var root = {};
    var first_option = true;
    var emptyChoices = [];
    options.each(function () {
	    var option = $(this);
	    var val = option.val();
	    var pathElems = option.text().split(config.separator);
	    var lastElem = pathElems.pop();
        if (first_option) {
            emptyChoices = option.text().split(config.separator);
        }


        var current = root;
	    $.each(pathElems, function (i, e) {
	        if (is_string(current[e])) {
		        // the tree has nodes that can be selected.
		        // move the node into subselect
		        current[e] = { "Aucun": current[e] }; // Aucun au lieu de Tout
	        }
            //current = current[e] || (current[e] = {'-': ''})
            if (!current[e]) {
                current[e] = {};
                current[e][emptyChoices[i+1]] = '';
            }
            current = current[e];
	    });
	    // handle conflict on leaves text
	    while (current[lastElem]) lastElem += '_';

	    current[lastElem] = val;
        first_option = false;
    });
    //if (console && console.log) console.log($.toJSON(root)); // jquery-json plugin
    return root;
};

var createOneSubselect = function (onchange, tree, depth) {
    var subselect = $('<select>').change(onchange).data(
        { depth: depth, tree: tree }
    );
    console.log(tree);
    $.each(tree, function (pathElem, subtree) {
        var option = $('<option>').text(pathElem);
        subselect.append(option);
    });
    return subselect;
};

var addDefaultSubselects = function (selectsDiv, onchange, tree, depth) {
    while (!is_string(tree)) {
        var subselect = createOneSubselect(onchange, tree, depth);
        selectsDiv.append(buildSelectLine(subselect, depth, labels));
        depth++;
        tree = tree[subselect.val()]; // use default value (ie first value)
    }
    return tree; // selected value
};

var buildSelectLine = function(subselect, depth) {
    var line = $('<div class="fitem required fitem_fselect" data-depth="' + depth + '">');
    if (labels && depth in labels) {
        line.append(
            $('<div class="fitemtitle">').append($('<label>').text(labels[depth]+' *'))
        );
    }
    line.append($('<div class="felement fselect">').append(subselect));
    return line;
}

var setSubselects = function (selectsDiv, onchange, root, wanted) {
    var tree = root;
    var depth_;
    $.each(wanted, function (depth, e) {
        var subselect = createOneSubselect(onchange, tree, depth);
        subselect.val(e);
        selectsDiv.append(buildSelectLine(subselect, depth));
        tree = tree[e];
        depth_ = depth;
    });
    addDefaultSubselects(selectsDiv, onchange, tree, depth_+1);
};

var createOnchangeHandler = function (theSelect, selectsDiv) {
    var onchange = function () {
        var subselect = $(this);
        var depth = subselect.data('depth');
        var tree = subselect.data('tree');
        var subtree = tree[subselect.val()];

        selectsDiv.children("div:gt(" + depth + ")").remove(); // remove subselects

        var selectedVal = addDefaultSubselects(selectsDiv, onchange, subtree, depth+1);
        theSelect.val(selectedVal);
    };
    return onchange;
};

var transformIntoSubselects = function (theSelect) {
    var root = getTree(theSelect.find('option'));
    labels = theSelect.data('labels');

    var selectsDiv = $('<div class="subselects">');
    theSelect.parent().after(selectsDiv);

    var onchange = createOnchangeHandler(theSelect, selectsDiv);

    var getAndSetSelectedValue = function () {
	    selectsDiv.empty(); // cleanup
	    var selectedText = theSelect.find(":selected").text();
	    var selected = selectedText.split(config.separator);
	    setSubselects(selectsDiv, onchange, root, selected);
    };

    theSelect.change(getAndSetSelectedValue);
    getAndSetSelectedValue();
};

$.fn.transformIntoSubselects = function (cfg) {
    if (is_string(cfg)) {
        config.separator = cfg;
    } else {
        config = $.extend(true, {}, defaultConfig, cfg || {});
    }
    $(this).each(function () {
	    var theSelect = $(this);
	    transformIntoSubselects(theSelect);
    });
}

})(jQuery);
