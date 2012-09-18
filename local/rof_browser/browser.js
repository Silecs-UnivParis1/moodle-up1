jQuery(function () {

    $('.selected-deep2').click(function(event) {
		var niv = $(this).attr('data_deep');
		var path = $(this).attr('data_path');
		var codeid = $(this).attr('id');
		var rofid = $(this).attr('data_rofid');
		var cf = '.cont-deep'+niv;

		var fr = $(this).siblings().size();
		if (fr == 0) {
			$(cf).addClass('hidden');
			$.get('roffinal.php', {niveau: niv, rofid: rofid},  function(data){
				$("#"+codeid).after(data);
			}, 'html');
		}
		else {
			var ch = $("#"+codeid).siblings(cf).hasClass('hidden');
			$(cf).addClass('hidden');
			if (ch) {
				$("#"+codeid).siblings(cf).removeClass('hidden');
			}
		}
	});

	$("div.component-tree").on("click", ".selected-deep3"
	 , function(event) {
		var fr = $(this).siblings().size();
		if (fr == 0) {
			var codeid = $(this).attr('id');
		    var niv = $(this).attr('data_deep');
		    var rofid = $(this).attr('data_rofid');

			var titre = '<div class="suprog-'+codeid+'">'+$(this).text()+'</div>';
			$("#arbreprog").empty();
			$(titre).appendTo("#arbreprog");
			$('<div class="suprog-tree"></div>').load('roffinal.php', {niveau: niv, rofid: rofid}).appendTo("#arbreprog");
		}
    });

	 $("div.detail-tree").on("click", ".selected-deep4, .selected-deep5, .selected-deep6, .selected-deep7, .selected-deep8, .selected-deep9, .selected-deep10"
	 , function(event) {
		var codeid = $(this).attr('id');
		var niv = $(this).attr('data_deep');
		var rofid = $(this).attr('data_rofid');

		var fr = $(this).siblings().size();
		if (fr == 0) {
			$.get('roffinal.php', {niveau: niv, rofid: rofid},  function(data){
				$("#"+codeid).after(data);
			}, 'html');
		} else {
			var cf = '.per'+rofid;
			$("#"+codeid).siblings(cf).toggleClass('hidden');
		}
    });

});
