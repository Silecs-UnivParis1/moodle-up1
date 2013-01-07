jQuery(function () {
	$('#etaper').click(function(event) {
		var stepout = $('input[name=stepgo-retour]').val();
		$('#stepgo').val(stepout);
		var newname = 'stepgo_'+stepout;
		$('#stepgo').attr('name',newname);
	});

	$('#etapes').click(function(event) {
		var stepout = $('input[name=stepgo-suite]').val();
		$('#stepgo').val(stepout);
		var newname = 'stepgo_'+stepout;
		$('#stepgo').attr('name',newname);
	});
});
