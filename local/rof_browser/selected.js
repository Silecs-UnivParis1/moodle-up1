jQuery(function () {

    $('div.component-tree').on("click", ".collapse", function(event) {
		var niv = $(this).attr('data_deep');
		var codeid = $(this).attr('id');
		var rofid = $(this).attr('data_rofid');

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
			$.get('roffinal.php', {niveau: niv, rofid: rofid, selected: 1},  function(data){
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


	$('div.component-tree').on("click", ".element", function(event) {
		var rofid = $(this).prevAll('span.collapse').attr('data_rofid');
		var present = $('#liste-selected > div#select_'+rofid).size();
		if (present) {
			alert(rofid+' fait déjà parti de la la sélection.');
		} else {
			var elem = ' <div class="elemSel" id="select_'+rofid+'" title="Supprimer">'+rofid+'</div>';
			$("#liste-selected").append(elem);
		}

	});

	$("#liste-selected").on("click", ".elemSel", function(event) {
		if (confirm('Supprimez cet élément de la sélection ?')) {
			$(this).remove();
		}
	});

});
