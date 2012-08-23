jQuery(function () {

    $('.selected-niv2').click(function(event) {
		var codeid = $(this).attr('id');
		var i = codeid.lastIndexOf('_') + 1;
		var id = codeid.substring(i);
		var niv = 2;
		var cf = '.cont-niv'+niv;

		var fr = $(this).siblings().size();
		if (fr == 0) {
			$(cf).addClass('hidden');
			$.get('roffinal.php?id='+id+'&niveau='+niv,  function(data){
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

	$("div.component-tree").on("click", ".selected-niv3"
	 , function(event) {
		var fr = $(this).siblings().size();
		if (fr == 0) {
			var codeid = $(this).attr('id');
			var i = codeid.lastIndexOf('_') + 1;
			var id = codeid.substring(i);
			var niv = codeid.substring(i-2, i-1);
			$("#arbreprog").load('roffinal.php?id='+id+'&niveau='+niv);
		}
    });

	 $("div.detail-tree").on("click", ".selected-niv4, .selected-niv5, .selected-niv6"
	 , function(event) {
		var fr = $(this).siblings().size();
		if (fr == 0) {
			var codeid = $(this).attr('id');
			var i = codeid.lastIndexOf('_') + 1;
			var id = codeid.substring(i);
			var niv = codeid.substring(i-2, i-1);
			$.get('roffinal.php?id='+id+'&niveau='+niv,  function(data){
				$("#"+codeid).after(data);
			}, 'html');
		}
    });

});
