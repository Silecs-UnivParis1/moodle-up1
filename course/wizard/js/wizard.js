jQuery(function () {
    $('#etapep').click(function(event) {
		$('input[name=idenrolment]').val('manual');
		var stepout = $('input[name=stepgo-manual]').val();
		$('input[name=stepgo]').val(stepout);
		$('input[name=stepgo]').val('5');
	});

	$('#etapes').click(function(event) {
		$('input[name=idenrolment]').val('cohort');
		var stepout = $('input[name=stepgo-cohort]').val();
		$('input[name=stepgo]').val(stepout);
	});

});
