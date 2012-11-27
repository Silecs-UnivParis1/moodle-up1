jQuery(function () {
    var selected = new Array();
    var visited = new Array();
	var rootUrl = $('script[src$="/selected.js"]').attr('src').replace('/selected.js', '/');
    $('div.item-select').load(rootUrl + 'ajax.php');

    $('div.item-select').on("change", ".selectmenu", function(event) {
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

			$.get('roffinal.php', {niveau: niv, rofid: rofid, selected: 1,
				path: path, format: format, typedip: typedip},
				function(data){
				$('#'+id).after(data);
			}, 'html');
		}
	});

    $('div.item-select').on("click", ".collapse", function(event) {
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

		var fr = $(this).siblings('ul').size();
		if (fr == 0) {
		// creation liste ul
			$.get('roffinal.php', {niveau: niv, rofid: rofid, selected: 1, path: path},  function(data){
				$("#"+codeid+'-elem').after(data);
			}, 'html');
		}
		else {
			if (niv==2) {
				var ulEnf = '.cont-deep'+niv;
				$(this).siblings(ulEnf).toggleClass('hidden');
			} else {
				var ulEnf = '.per'+rofid;
				$(ulEnf).toggleClass('hidden');
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

        var regtab=new RegExp("[ ,_]+", "g");
        var tableau=path.split(regtab);
        var chemin = new String();
        for (var i=0; i<tableau.length; i++) {
            var c = tableau[i];
            if (typeof visited[c] != 'undefined') {
                chemin = chemin+' > '+visited[c];
            }
        }
        chemin = chemin.substr(3);
        /**
		var reg=new RegExp("_", "gi");
		path = path.replace(reg, "/");
        **/
        var name = 'select_'+rofid;
        if (typeof selected[name] != 'undefined' && selected[name] == 1) {
			alert('"'+intitule+'" fait déjà partie de la sélection.');
		} else {
            selected[name] = 1;
			var elem = '<div class="item-selected" id="select_'+rofid+'">'
				+'<div class="selected-remove" title="Supprimer la sélection">&#10799;</div>'
				+'<div class="intitule-selected" title="'+chemin+'">'+intitule+'</div>'
				+'<input type="hidden" name="item[]" value="'+rofid+'"/>'
				+'</div>';
			$("#items-selected").append(elem);
		}

	});

	$("#items-selected").on("click", ".selected-remove", function(event) {
		if (confirm('Confirmez-vous la suppression de cet élément de la sélection ?')) {
            var rofid = $(this).siblings('input[type=hidden]').val();
            selected['select_'+rofid] = 0;
			$(this).parent('div.item-selected').remove();
		}
	});

});
