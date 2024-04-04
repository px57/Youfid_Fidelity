<?php

	////////////////////////////////////////
	// Emails definition

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$headers .= 'From: Admin YouFid <admin@youfid.fr>' . "\r\n";
	// To commerciaux
	$msg_commerciaux_1 = "Une nouvelle demande d'ajout d'un compte marchand vient d'être effectuée:";
	$msg_commerciaux_1 .= "<br/><br/>";
	// To Merchant
	$msg_merchant = "Bienvenue,<br/><br/>";
	$msg_merchant .= "Vous souhaitez rejoindre YouFID pour fidéliser d’avantage vos clients.<br/>";
	$msg_merchant .= "Vous serez contacté sous 24h par un membre de notre équipe afin de vous présenter tous<br/> les avantages YouFID<br/><br/>";
	$msg_merchant .= "Nous vous accompagnons a mettre en place un programme de fidélité à votre image et vous recevrez<br/> le kit youFID pour commencer le plus rapidement à récompenser vos clients fidèles.<br/><br/>";
	$msg_merchant .= "A très bientôt,<br/>";
	$msg_merchant .= "L’Equipe YouFID<br/>";

	////////////////////////////////////////
	// DataBase Properties
	$tbl_name="marchand";
	require_once('dbLogInfo.php');
	require_once 'utils.php';

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');
	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	$error = false;
	$errorMsg = "";

	if (!isset($jsonArray->contact_name) || !isset($jsonArray->contact_mail) || !isset($jsonArray->name) ||
			!isset($jsonArray->phone) || !isset($jsonArray->address)|| !isset($jsonArray->zip_code) ||
			!isset($jsonArray->city) || !isset($jsonArray->country))
		{
			$error = true;
			$errorMsg = "Bad parameters...";
		}
		else
		{
			/*$sqlFacebook = "INSERT INTO $tbl_name SET contact='"
			 	. mysql_real_escape_string($jsonArray->contact_name . '  ' .  $jsonArray->contact_mail)
				. "', name='"
				. mysql_real_escape_string($jsonArray->name)
				. "', phone='"
				. mysql_real_escape_string($jsonArray->phone)
				. "', address='"
				. mysql_real_escape_string($jsonArray->address)
				. "', zip_code='"
				. mysql_real_escape_string($jsonArray->zip_code)
				. "', city='"
				. mysql_real_escape_string($jsonArray->city)
				. "', country='"
				. mysql_real_escape_string($jsonArray->country)
				. "'";

			$result=mysql_query($sqlFacebook);

			if ($result == FALSE)
			{
				$error = true;
				$errorMsg = "Error with the db";
			}*/

			/// Mail to Merchant
			//mail($jsonArray->contact_mail, 'Inscription YouFid', utf8_decode($msg_merchant), $headers) or die ("Couldn't send mail!" );
			mail_youfid($jsonArray->contact_mail, '', 'Inscription YouFid', $msg_merchant, 'youfid', 'register_merchant');

			$msg_commerciaux_1 .= "Nom de la boutique:" . $jsonArray->name . "<br/>";
			$msg_commerciaux_1 .= "Nom de contact:" . $jsonArray->contact_name . "<br/>";
			$msg_commerciaux_1 .= "Email de contact:" . $jsonArray->contact_mail . "<br/>";
			$msg_commerciaux_1 .= "Téléphone:" . $jsonArray->phone . "<br/>";
			$msg_commerciaux_1 .= "Adresse:" . $jsonArray->address . "<br/>";
			$msg_commerciaux_1 .= "Code Postal:" . $jsonArray->zip_code . "<br/>";
			$msg_commerciaux_1 .= "Ville:" . $jsonArray->city . "<br/>";
			$msg_commerciaux_1 .= "Pays:" . $jsonArray->country . "<br/>";

			///Mail to Commerciaux
			//mail('contact@youfid.fr', "[MARCHAND] Demande d'inscription", utf8_decode($msg_commerciaux_1), $headers) or die ("Couldn't send mail!" );
			mail_youfid('contact@youfid.fr', '', "[MARCHAND] Demande d'inscription", $msg_commerciaux_1, 'youfid', 'register_merchant_yf');
		}



	if ($error == true)
		$status = "error";
	else
		$status = "ok";

	$jsonResult['status'] = $status;
	$jsonResult['message'] = $errorMsg;
	echo(json_encode(array_map_utf8_encode($jsonResult)));
