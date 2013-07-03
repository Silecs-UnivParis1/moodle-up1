jQuery(function () {
    var selected = new Array();
    var visited = new Array();
    var reference = new Array();
	var rootUrl = $('script[src$="/selected.js"]').attr('src').replace('/selected.js', '/');
    $('div.item-select').load(rootUrl + 'ajax.php');

    $('div.item-select').on("change", ".selectmenu", function(event) {
        var readonly = 0;
        if ( $('#choose-item-select').hasClass("readonly") ) {
            readonly = 1;
        }
		var id = $(this).attr('id');
		$('#'+id+' ~ *').remove();

		var select = $(this).children('option[selected=selected]');
		var codeid = select.attr('id');

		if (codeid) {
			var niv = select.attr('data_deep');
			var rofid = select.attr('data_rofid');
			var path = select.attr('data_path');
			if (niv < 3) {
				var format = 1;
			} else {
				var format = 0;
			}

            var typedip = select.attr('data_typedip');
            if (typeof typedip == 'undefined')  {
                visited[rofid] = select.text();
            }

			$.get(rootUrl + 'roffinal.php', {niveau: niv, rofid: rofid, selected: 1,
				path: path, format: format, typedip: typedip, readonly: readonly},
				function(data){
				$('#'+id).after(data);
			}, 'html');
		}
	});

    $('div.item-select').on("click", ".collapse", function(event) {
        var readonly = 0;
        if ( $('#choose-item-select').hasClass("readonly") ) {
            readonly = 1;
        }
        if ($(this).parent(".dip-sel").size()) {
            return false;
        }
		var niv = $(this).attr('data_deep');
		var codeid = $(this).attr('id');
		var rofid = $(this).attr('data_rofid');
		var path = $(this).attr('data_path');

        if (typeof visited[rofid] == 'undefined') {
            var intitule = $(this).siblings('span.intitule').text();
            visited[rofid] = intitule;
        }

		var plus = $(this).text();
		$(this).empty();
		if (plus==' + ') {
            $(this).removeClass('collapsed');
            $(this).addClass('expanded');
			plus = ' - ';
            $(this).attr("title","Replier");
		} else {
            $(this).removeClass('expanded');
            $(this).addClass('collapsed');
			plus = ' + ';
            $(this).attr("title","Déplier");
		}
		$(this).append(plus);

		var fr = $(this).parent('.elem-li').siblings('ul').size();
		if (fr == 0) {
		// creation liste ul
			$.get(rootUrl + 'roffinal.php', {niveau: niv, rofid: rofid, selected: 1, path: path, readonly: readonly},  function(data){
				$("#"+codeid+'-elem').parent('.elem-li').after(data);
			}, 'html');
		}
		else {
			if (niv==2) {
				var ulEnf = '.cont-deep'+niv;
				$(this).siblings(ulEnf).toggleClass('rof-hidden');
			} else {
				var ulEnf = '.per'+rofid;
				$(ulEnf).toggleClass('rof-hidden');
			}

		}
	});


	$('div.item-select').on("click", ".element", function(event) {
		var rofid = $(this).prevAll('span.collapse').attr('data_rofid');
		var path =  $(this).prevAll('span.collapse').attr('data_path');
		var intitule = $(this).prevAll('span.intitule').text();

        if (typeof visited[rofid] == 'undefined') {
            var intitule = $(this).siblings('span.intitule').text();
            visited[rofid] = intitule;
        }

        var chemin = new String();
        chemin = createChemin(path, visited);
        chemin = chemin.substr(3);

        if (typeof selected['select_'+path] != 'undefined' && selected['select_'+path] == 1) {
			alert('"'+intitule+'" fait déjà partie de la sélection.');
		} else {
            /** patch si uniquement rattachements secondaires (cours hybrides) **/
            if (! $('#items-selected1').size()) {
               reference[0] = 0;
            }
            /** rattachement de reference **/
            var rattachement = '#items-selected2';
            var tabItem = '';
            if (reference.length) {
                tabItem = 'item[s][]';
            } else {
                tabItem = 'item[p][]';
                var rattachement = '#items-selected1';
                reference[0] = 'select_'+path;
            }
            selected['select_'+path] = 1;
			$(rattachement).append(addElem(rofid, chemin, intitule, tabItem, path));
		}

	});

	$("#items-selected").on("click", ".selected-remove", function(event) {
		if (confirm('Confirmez-vous la suppression de cet élément de la sélection ?')) {
            var rofid = $(this).siblings('input[type=hidden]').eq(0).val();
            var rofpath = $(this).siblings('input[type=hidden]').eq(1).val();
            selected['select_'+rofpath] = 0;
            if (reference[0]=='select_'+rofpath) {
                reference.splice(0, 1);
            }
			$(this).parent('div.item-selected').remove();
		}
	});

    function addElem(rofid, chemin, intitule, tabItem, path, readonly) {
        var suppr = '<div class="selected-remove" title="Supprimer la sélection">&#10799;</div>';
        if (readonly==true) {
            suppr = '';
        }
        var elem = '<div class="item-selected" id="select_'+rofid+'">'
				+suppr
				+'<div class="intitule-selected" title="'+chemin+'">'+intitule+'</div>'
				+'<input type="hidden" name="'+tabItem+'" value="'+rofid+'"/>'
                +'<input type="hidden" name="path['+rofid+']" value="'+path+'"/>';
				+'</div>';
        return elem;
    };

    function createChemin(path, visited) {
        var chemin = new String();
        var regtab=new RegExp("[ ,_]+", "g");
        var tableau=path.split(regtab);
        for (var i=0; i<tableau.length; i++) {
            var c = tableau[i];
            if (typeof visited[c] != 'undefined') {
                chemin = chemin+' > '+visited[c];
            }
        }
        return chemin;
    };

    var defaultSettings = {
        readonly: false,
        preSelected: [] // [{"label": "Licence Administration publique", "value": "UP1-PROG35376", "path": "03_UP1-PROG35376", "nature": "p"}, ]
    };

     $.fn.autocompleteRof = function (options) {
        var settings = $.extend(true, {}, defaultSettings, options || {});
        return this.each(function() {
            var $elem = $(this);
            var acg = new autocompleteRof(settings, $elem);
            if (settings.preSelected.length) {
                acg.fillSelection(settings.preSelected, settings.readonly);
            }
        });
     }

    function autocompleteRof(settings, elem) {
        this.settings = settings;
        this.elem = elem;
        return this;
    }

    autocompleteRof.prototype =
    {
        fillSelection: function(items, readonly) {
            var $this = this;
            for (var i=0; i < items.length; i++) {
                var my = items[i];
                buildSelectedBlock(items[i], readonly);
            }
        }
    }

    function buildSelectedBlock(item, readonly) {

        var rofid = item.value;
		var path =  item.path;
		var intitule = item.label;
        var chemin = '';
        if (typeof item.chemin != 'undefined') {
            chemin = item.chemin;
        }
        selected['select_'+path] = 1;

        var rattachement = '#items-selected2';
        var tabItem = '';

        if (item.nature=='s') {
            tabItem = 'item[s][]';
        } else {
            tabItem = 'item[p][]';
            var rattachement = '#items-selected1';
            reference[0] = 'select_'+path;
        }
        selected['select_'+path] = 1;
        $(rattachement).append(addElem(rofid, chemin, intitule, tabItem, path, readonly));
    }

});
