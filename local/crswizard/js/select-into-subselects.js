(function ($) {

var is_string = function (v){
    return typeof(v) === 'string';
};

var config = {};

var defaultConfig = {
    separator: " / ",
    labelOnEmpty: "Aucun",
    // labels: ["l1", "l2"],
    required: true
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
                var tmp = current[e];
                current[e] = {};
                current[e][config.labelOnEmpty] = tmp;
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
    //console.log(tree);
    $.each(tree, function (pathElem, subtree) {
        var option = $('<option>').text(pathElem);
        subselect.append(option);
    });
    return subselect;
};

var addDefaultSubselects = function (selectsDiv, onchange, tree, depth) {
    while (!is_string(tree)) {
        var subselect = createOneSubselect(onchange, tree, depth);
        selectsDiv.append(buildSelectLine(subselect, depth));
        depth++;
        tree = tree[subselect.val()]; // use default value (ie first value)
    }
    return tree; // selected value
};

var buildSelectLine = function(subselect, depth) {
    var line = $(
        '<div class="fitem ' + (config.required ? 'required ' : '') + 'fitem_fselect" data-depth="' + depth + '">'
    );
    if ('labels' in config && config.labels && depth in config.labels) {
        line.append(
            $('<div class="fitemtitle">').append($('<label>').text(config.labels[depth]+(config.required ? ' *' : '')))
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

        try {
            var selectedVal = addDefaultSubselects(selectsDiv, onchange, subtree, depth+1);
            theSelect.val(selectedVal);
        } catch (e) {
            theSelect.val(0);
        }
    };
    return onchange;
};

var transformIntoSubselects = function (theSelect) {
    theSelect.hide(0);
    var root = getTree(theSelect.find('option'));

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
    config = $.extend(true, {}, defaultConfig, cfg || {});
    $(this).each(function () {
        var theSelect = $(this);
        var valuesSelected = theSelect.val();
        if (theSelect.attr('multiple')) {
            if (!valuesSelected) {
                valuesSelected = [0];
            }
            theSelect.removeAttr('multiple').val(valuesSelected[0]);
            var duplicator = build_duplicator(theSelect, config);

            var button = $('<button type="button">Ajouter un champ supplémentaire de rattachement</button>');
            button.click(duplicator);
            theSelect.after(button);
        }
        transformIntoSubselects(theSelect);
        if (typeof valuesSelected != 'string' && valuesSelected.length > 1) {
            for (var i=1; i < valuesSelected.length; i++) {
                duplicator.apply(this, [valuesSelected[i]]);
            }
        }
        if (theSelect.attr('id')) {
            $('label[for="' + theSelect.attr('id') + '"]').hide(0);
        }
    });
    return $(this);
}

var build_duplicator = function (sel, config) {
    var root = sel.parent().parent();
    var more = root.clone();
    var num = 1;
    more.removeAttr('id');
    more.children('div').removeAttr('id');
    more.find('label').remove();
    return function(val) {
        var inserted = more.clone();
        var select = inserted.find('select').first();
        select.val(val);
        num++;
        //select.attr('name', select.attr('name').replace('[0]', '[' + num + ']'));
        select.attr('id', select.attr('id') + '_' + num);
        root.after(inserted);
        inserted.find('select').first().transformIntoSubselects(config)
    }
}

})(jQuery);
