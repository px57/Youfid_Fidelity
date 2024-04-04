function onClickUpdateMarchand()
{
	$("#update_result").text("Veuillez patienter...");
	
	var pin_code = $("#pin_code").val();
	
	if (pin_code == "" || isNaN(pin_code) == false)
	{
		$("#pin_code").removeClass("error");
		
		$.ajax({
			type: 'POST',
			url: 'lib/merchant_moncompte_update.php',
			data: $("#merchant_update").serialize(),
			success: function(data)
			{
				$("#update_result").text(data);
			}
		});
	}
	else
	{
		$("#pin_code").addClass("error");
		$("#update_result").text("Le champ [code PIN] doit être vide si vous ne souhaitez pas en définir un ou bien ne contenir que des chiffres.");
	}
}
