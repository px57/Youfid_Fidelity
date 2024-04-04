<?php

require_once('utils.php');

// Si on connait le nombre de qr_codes à générer
if (isset($_GET['email']))
{
	// Si l'email est envoyé dans l'url, on le récupère
	$email = $_GET['email'];

	// On envoie un mail
	// mail_youfid($email, "test envoi mail", "tu veux un café ? à demain hervé");
	// echo "Mail sent !";
	$verif = verifyEmail($email);
	echo json_encode($verif);
} else {
	echo json_encode(array(
		"error" => "Mail is missing"
	));
}

/*
// Si on ne connait pas encore le nombre de qr_codes à générer, on le demande à l'utilisateur
else
{
	echo '
	<form>
	Email: <input type="text" name="email">
	<input type="submit" name="button" id="button" value="Send !">
	</form>
	';
}

curl -i -H "Content-Type: application/json" -d "{ entries: [ { inputData: 'abdoulsalamy@yahoo.fr' } ] }" -u youfid@zalamtech.com:YouFID75012 https://api.verifalia.com/v1.4/email-validations


*/
?>
