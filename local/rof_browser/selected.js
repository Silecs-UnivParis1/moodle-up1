jQuery(function () {

	var rootUrl = $('script[src$="/selected.js"]').attr('src').replace('/selected.js', '/');
    $('div.item-select').load(rootUrl + 'ajax.php');

    $('div.item-select').on("click", ".collapse", function(event) {
		var niv = $(this).attr('data_deep');
		var codeid = $(this).attr('id');
		var rofid = $(this).attr('data_rofid');
		var path = $(this).attr('data_path');

		var plus = $(this).text();
		$(this).empty();
		if (plus=='[+] ') {
			plus = '[-] ';
		} else {
			plus = '[+] ';
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
		var intitule = $(this).text();

		var reg=new RegExp("_", "gi");
		path = path.replace(reg, "/");

		var present = $('#items-selected > div#select_'+rofid).size();
		if (present) {
			alert(rofid+' fait déjà parti de la sélection.');
		} else {
			var elem = '<div class="item-selected" id="select_'+rofid+'">'
				+'<div class="selected-remove" title="Supprimer">&#10799;</div>'
				+'<div class="intitule-selected">'+path+' : '+intitule+'</div>'
				+'<input type="hidden" name="item[]" value="'+rofid+'"/>'
				+'</div>';
			$("#items-selected").append(elem);
		}

	});

	$("#items-selected").on("click", ".selected-remove", function(event) {
		if (confirm('Supprimez cet élément de la sélection ?')) {
			$(this).parent('div.item-selected').remove();
		}
	});

});
