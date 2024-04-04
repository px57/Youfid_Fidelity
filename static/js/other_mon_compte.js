function validateEmail(email) { 
	var reg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return reg.test(email);
}

function is_number_valid(nombre)
{ 
	var chiffres = new String(nombre);
	 
	// Enlever tous les charactères sauf les chiffres
	chiffres = chiffres.replace(/[^0-9]/g, '');
	 
	// Le champs est vide
	if (nombre == "")
		return false;
	 
	// Nombre de chiffres
	var compteur = chiffres.length;
	 
	if (compteur<8)
		return false; 
	return true; 
}

function check_form_monCompte()
{
	var phone = $("#phone").val();
	var email_bo = $("#email_bo").val();
	
	var is_form_valid = true;
	
	if (!validateEmail(email_bo))
	{
		$("#email_bo").addClass("error");
		is_form_valid = false;
	}
	else
		$("#email_bo").removeClass("error");
	
	if (!is_number_valid(phone))
	{
		$("#phone").addClass("error");
		is_form_valid = false;
	}
	else
		$("#phone").removeClass("error");
	
	return is_form_valid;
}

function onClickFormMonCompte()
{
	var is_valid = check_form_monCompte();
	
	if (is_valid == false)
		$("#check_form_result").text("Une erreur est surevenue lors de la validation du formulaire. Veuillez vérifier les champs en rouge.");
	else
		 document.forms["new_merchant_form"].submit();
}

function check_form_monCompte_master()
{
	var phone = $("#phone").val();
	var email_bo = $("#email_bo").val();
	var pin_code = $("#pin_code").val();
	
	var is_form_valid = true;
	
	if (pin_code == "" || isNaN(pin_code) == false)
		$("#pin_code").removeClass("error");
	else
	{
		$("#pin_code").addClass("error");
		is_form_valid = false;
	}
	
	if (!validateEmail(email_bo))
	{
		$("#email_bo").addClass("error");
		is_form_valid = false;
	}
	else
		$("#email_bo").removeClass("error");
	
	if (!is_number_valid(phone))
	{
		$("#phone").addClass("error");
		is_form_valid = false;
	}
	else
		$("#phone").removeClass("error");
	
	return is_form_valid;
}

function onClickFormMonCompte_master()
{
	var is_valid = check_form_monCompte_master();
	
	if (is_valid == false)
		$("#check_form_result").text("Une erreur est surevenue lors de la validation du formulaire. Veuillez vérifier les champs en rouge.");
	else
		 document.forms["new_merchant_form"].submit();
}
