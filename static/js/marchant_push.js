function setSelectValue(selectId, value)
{
	/*Récupération du select*/
	var elmt = document.getElementById(selectId);
	/*On parcourt les options du select*/
	for (var i = 0; i < elmt.options.length; i++)
	{
		/*Si l'élément à la bonne valeur on le sélectionne*/
		if(elmt.options[i].value == value)
		{
			elmt.selectedIndex = i;
			return true;
		}
	}
	/*On a pas trouvé la valeur on retourne faux*/
	return false;
}

function fill_time_select(push_array)
{
	setSelectValue("l_start", push_array[0].date_debut);
	setSelectValue("l_end", push_array[0].date_fin);
	if (push_array[0].is_active == "1")
		document.getElementById("is_lundi").checked = true;
	
	
	setSelectValue("ma_start", push_array[1].date_debut);
	setSelectValue("ma_end", push_array[1].date_fin);
	if (push_array[1].is_active == "1")
		document.getElementById("is_mardi").checked = true;
	
	setSelectValue("me_start", push_array[2].date_debut);
	setSelectValue("me_end", push_array[2].date_fin);
	if (push_array[2].is_active == "1")
		document.getElementById("is_mercredi").checked = true;
	
	setSelectValue("j_start", push_array[3].date_debut);
	setSelectValue("j_end", push_array[3].date_fin);
	if (push_array[3].is_active == "1")
		document.getElementById("is_jeudi").checked = true;
	
	setSelectValue("v_start", push_array[4].date_debut);
	setSelectValue("v_end", push_array[4].date_fin);
	if (push_array[4].is_active == "1")
		document.getElementById("is_vendredi").checked = true;
	
	setSelectValue("s_start", push_array[5].date_debut);
	setSelectValue("s_end", push_array[5].date_fin);
	if (push_array[5].is_active == "1")
		document.getElementById("is_samedi").checked = true;
	
	setSelectValue("d_start", push_array[6].date_debut);
	setSelectValue("d_end", push_array[6].date_fin);
	if (push_array[6].is_active == "1")
		document.getElementById("is_dimanche").checked = true;
	
	//document.getElementById('push_title').value = push_array[6].titre;
	$("#push_msg").text(push_array[6].message);
	
	/*var is_active = false;
	for (var index = 0; index < 7; index += 1)
	{
		if (push_array[index].is_active == "1")
			is_active = true;
	}
	if (is_active == true)
	{
		document.getElementById("is_active").checked = true;
	}*/
}

function check_merchant_push_form()
{
	var titleval = $("#push_title").val();
	var msgval = $("#push_msg").val();
	
	var titlelen = titleval.length;
	var msglen = msgval.length;
	
	var error_form = true;
	
	if (titlelen <= 0 || titlelen > 40)
	{
		error_form = false;
		$("#push_title").addClass("error");
	}
	else
		$("#push_title").removeClass("error");
		
	if (msglen <= 0 || msglen > 150)
	{
		error_form = false;
		$("#push_msg").addClass("error");
	}
	else
		$("#push_msg").removeClass("error");
		
	return error_form;
}

function onClickMarchandPush()
{
	$("#form_result").text("Veuillez patienter...");
	var error_form = check_merchant_push_form();
	
	if (error_form == true)
	{
		$.ajax({
				type: 'POST',
				url: 'lib/merchant_push.php',
				data: $("#marchand_push").serialize(),
				success: function(data)
				{
					$("#form_result").text(data);
				}
			});
		alert('Votre modification a bien été prise en compte.')
	}
	else
		$("#form_result").text("Erreur avec le formulaire. Veuillez vérifier les champs en rouge");
}

$(document).ready(function() 
{
	$("#form_result").text("Veuillez patienter...");
	
	$.ajax({
		type: 'POST',
		url: 'lib/get_merchant_push.php',
		/*data: $("#marchand_push").serialize(),*/
		success: function(data)
		{
			$("#form_result").text("");
			fill_time_select(JSON.parse(data));
		}
	});
});