		function onClickExportMarchand()
		{
			//$("#calculer_result").text("Veuillez patienter...");
	 		
	 		var error_form = checkForm();
	 		
	 		if (error_form == false)
	 		{
		 		document.forms["stats_form"].submit();
			}
			else
				$("#calculer_result").text("Erreur avec le formulaire. Veuillez verifier les dates saisies. Au moins l'une des deux checkbox doit être cochée.");
		}
		
		function checkForm()
		{
			var date_start_val = $("#date_start").val();
	 		var date_end_val = $("#date_end").val();
	 		
	 		var date_start_len = date_start_val.length;
	 		var date_end_len = date_end_val.length;
	 		
	 		var error_form = false;
	 		
	 		if (date_start_len == 0)
	 		{
	 			$("#date_start").addClass("error");
	 			error_form = true;
	 		}
	 		else
	 			$("#date_start").removeClass("error");
	 		
	 		if (date_end_len == 0)
	 		{
	 			$("#date_end").addClass("error");
	 			error_form = true;
	 		}
	 		else
	 			$("#date_end").removeClass("error");
	 			
	 		var checked = false;
	 		if (document.getElementsByName("is_app").item(0).checked)
	 			checked = true;
	 		if (document.getElementsByName("is_physique").item(0).checked)
	 			checked = true;
	 			
			if (checked == false)
				error_form = true;
			
	 		return error_form;
		}
		
		function checkAdminForm()
		{
			var date_start_val = $("#date_start").val();
	 		var date_end_val = $("#date_end").val();
	 		
	 		var date_start_len = date_start_val.length;
	 		var date_end_len = date_end_val.length;
	 		
	 		var error_form = false;
	 		
	 		if (date_start_len == 0)
	 		{
	 			$("#date_start").addClass("error");
	 			error_form = true;
	 		}
	 		else
	 			$("#date_start").removeClass("error");
	 		
	 		if (date_end_len == 0)
	 		{
	 			$("#date_end").addClass("error");
	 			error_form = true;
	 		}
	 		else
	 			$("#date_end").removeClass("error");
	 		return error_form;
		}
		
	 	function onClickCalculer()
	 	{
	 		$("#calculer_result").text("Veuillez patienter...");
	 		
	 		var error_form = checkAdminForm();
	 		
	 		if (error_form == false)
	 		{
		 		$.ajax({
					type: 'POST',
					url: 'lib/stats_calculer.php',
					data: $("#stats_form").serialize(),
					success: function(data)
					{
						$("#calculer_result").text(data);
					}
				});
			}
			else
				$("#calculer_result").text("Erreur avec le formulaire. Veuillez verifier les dates saisies. Au moins l'une des deux checkbox doit être cochée.");
	 	}
	 	
	 	function onClickCalculerMarchand()
	 	{
	 		$("#calculer_result").text("Veuillez patienter...");
	 		
	 		var error_form = checkForm();
	 		
	 		if (error_form == false)
	 		{
		 		$.ajax({
					type: 'POST',
					url: 'lib/stats_merchant_calculer.php',
					data: $("#stats_form").serialize(),
					success: function(data)
					{
						$("#calculer_result").text(data);
					}
				});
			}
			else
				$("#calculer_result").text("Erreur avec le formulaire. Veuillez verifier les dates saisies. Au moins l'une des deux checkbox doit être cochée.");
	 	}
	 
	 	/* Script DATE */
	 	$(document).ready(function() {
	    	$("#date_start").datepicker();
	    	$("#date_end").datepicker();
	  	});