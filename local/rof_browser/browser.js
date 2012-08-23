jQuery(function () {

    $('.selected-niv2').click(function(event) {
		var codeid = $(this).attr('id');
		var i = codeid.lastIndexOf('_') + 1;
		var id = codeid.substring(i);

		$("#arbreprog").load('roffinal.php?id='+id+'&niveau=2');

	});

	 $("div.detail-tree").on("click", ".selected-niv3, .selected-niv4, .selected-niv5, .selected-niv6"
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
