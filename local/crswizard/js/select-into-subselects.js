(function ($) {

var is_string = function (v){
    return typeof(v) === 'string';
};

var getTree = function (options, separator) {
    var selected;
    var root = {};
    options.each(function () {
	    var option = $(this);
	    var val = option.val();
	    var pathElems = option.text().split(separator);
	    var lastElem = pathElems.pop();

	    var current = root;
	    $.each(pathElems, function (i, e) {
	        if (is_string(current[e])) {
		        // the tree has nodes that can be selected.
		        // move the node into subselect
		        current[e] = { "Tout": current[e] };
	        }
	        current = current[e] || (current[e] = {})
	    });
	    // handle conflict on leaves text
	    while (current[lastElem]) lastElem += '_';

	    current[lastElem] = val;
    });
    //if (console && console.log) console.log($.toJSON(root)); // jquery-json plugin
    return root;
};

var createOneSubselect = function (onchange, tree, depth) {
    var subselect = $('<select class="required">').change(onchange).data(
	{ depth: depth, tree: tree }
    );
    $.each(tree, function (pathElem, subtree) {
	var option = $('<option>').text(pathElem);
	subselect.append(option);
    });
    return subselect;
};

var addDefaultSubselects = function (selectsDiv, onchange, tree, depth) {
    while (!is_string(tree)) {
	var subselect = createOneSubselect(onchange, tree, depth);
	selectsDiv.append(subselect);
	depth++;
	tree = tree[subselect.val()]; // use default value (ie first value)
    }
    return tree; // selected value
};

var setSubselects = function (selectsDiv, onchange, root, wanted) {
    var tree = root;
    var depth_;
    $.each(wanted, function (depth, e) {
	var subselect = createOneSubselect(onchange, tree, depth);
	subselect.val(e);
	selectsDiv.append(subselect);
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

	selectsDiv.find("select:gt(" + depth + ")").remove(); // remove subselects

	var selectedVal = addDefaultSubselects(selectsDiv, onchange, subtree, depth+1);
	theSelect.val(selectedVal);
    };
    return onchange;
};

var transformIntoSubselects = function (theSelect, separator) {
    var root = getTree(theSelect.find('option'), separator);

    var selectsDiv = $('<div class="subselects">');
    theSelect.after(selectsDiv);

    var onchange = createOnchangeHandler(theSelect, selectsDiv);

    var getAndSetSelectedValue = function () {
	    selectsDiv.empty(); // cleanup
	    var selectedText = theSelect.find(":selected").text();
	    var selected = selectedText.split(separator);
	    setSubselects(selectsDiv, onchange, root, selected);
    };

    theSelect.change(getAndSetSelectedValue);
    getAndSetSelectedValue();
};

$.fn.transformIntoSubselects = function (separator) {
    $(this).each(function () {
	    var theSelect = $(this);
	    transformIntoSubselects(theSelect, separator);
    });
}

})(jQuery);
