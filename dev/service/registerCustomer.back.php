<?php

	require_once('Logger.class.php');

	if (!isset($logger))
		$logger = new Logger('logs/');

	function doLog($message)
	{
		global $logger;

		if (isset($logger))
			$logger->log('debug', 'registerCustomer', $message, Logger::GRAN_MONTH);
	}

	// Import du fichier utils (generatePassword...)
	require_once('utils.php');

	/// table name
	$tbl_name="mobileuser";
	require_once('dbLogInfo.php');

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$physique_email_content = "Confirmation message";
	$facebook_email_content = "Bonjour, votre mdp est";

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	if(isset($jsonArray->supermarchand_id)) {
		define('SUPERMARCHAND_ID', $jsonArray->supermarchand_id);
	} else {
		define('SUPERMARCHAND_ID', 0);
	}

	doLog("REQUEST::" . $json);

	$error = false;
	$errorMsg = "";

	/// Genere un qr_code et verifie si il est unique
	function generate_qr_code($prefixe)
	{
		global $tbl_name;

		$loop = TRUE;
		$qr_code = "";

		while($loop)
		{
			$qr_code = $prefixe . gen_yfid();

			$sqlGetUser = "SELECT * FROM $tbl_name WHERE `qr_code`='"
				. mysql_real_escape_string($qr_code)
				. "'";

			$result = mysql_query($sqlGetUser);

			if (!mysql_fetch_array($result))
				$loop = FALSE;
		}

		return $qr_code;
	}

	function gen_mobileuser_uuid() {
		global $tbl_name;
		$uid = null;
		do {
			$uid = gen_uuid();
			$check_qry = mysql_query("SELECT id FROM $tbl_name WHERE public_id like '$uid'");
			$numRows = mysql_num_rows($check_qry);
		} while($numRows > 0);

		return $uid;
	}

	/// Verifie si l'email est present en db
	function checkEmail($mail) {
		global $tbl_name;

		$sqlGetUser = "SELECT * FROM $tbl_name WHERE `mail`='"
			. mysql_real_escape_string($mail)
			. "'";

		$result = mysql_query($sqlGetUser);

		if (!mysql_fetch_array($result))
			return TRUE;

		return FALSE;

	}

	/////////////////////////////////

	/// Email definition
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$headers .=	'Content-Transfer-Encoding: 8bit' . "\r\n";
	if(SUPERMARCHAND_ID == 1839) {
		$headers .= "From: Les Commerces de l'Arche <admin@youfid.fr>" . "\r\n";
	} else {
		$headers .= 'From: Admin YouFid <admin@youfid.fr>' . "\r\n";
	}

	function get_email_message($firstname, $name, $mail, $password, $qr_code) {
		if(SUPERMARCHAND_ID == 1839) {
			$message .= "Nous vous remercions pour votre pré-inscription au service de fidélité des Commerces de l'Arche.<br/>";
		} else {
			$message .= "Nous vous remercions pour votre pré-inscription au service YouFID.<br/>";
		}
		$message .= "Vos identifiants sont :<br/>";
		$message .= "Email : " . $mail . "<br/>";
		$message .= "Mot de Passe : " . $password . "<br/><br/>";

		//"<a href='http://backoffice.youfid.fr/change_password.php'> http://backoffice.youfid.fr/change_password.php </a>"
		$url = "http://backoffice.youfid.fr/activateAccount2.php?qr_code=" . $qr_code;
		$url = "<a href='" . $url . "'> Activer mon compte </a>";

		$message .= "Pour finaliser la création de votre compte, veuillez cliquer sur le lient suivant: " . $url . "<br/>";
		$message .= "Pensez à télécharger l'application si vous ne l'avez pas encore fait :<br/>";
		$message .= "<a href='http://android.youfid.fr'> http://android.youfid.fr </a><br/>";
		$message .= "<a href='http://ios.youfid.fr'> http://ios.youfid.fr </a><br/>";

		//$message .= "Pour finaliser la création de votre compte, veuillez cliquer sur le lient suivant afin de « activer mon compte » {Il s’agit d’un mail automatique, merci de ne pas y répondre}
		#$message .= "<br/><br/>L'Equipe YouFID";
		return $message;
	}

	function get_email_message_facebook($firstname, $name, $mail, $password) {
		if(SUPERMARCHAND_ID == 1839) {
			$message = "Nous vous remercions pour votre inscription au service des Commerces de l'Arche.<br/>";
		} else {
			$message = "Nous vous remercions pour votre inscription au service YouFID.<br/>";
		}
		$message .= "Vos identifiants sont :<br/>";
		$message .= "Email : " . $mail . "<br/>";
		$message .= "Mot de Passe : " . $password . "<br/><br/>";

		$message .= "Pensez à télécharger l'application si vous ne l'avez pas encore fait :<br/>";
		$message .= "<a href='http://android.youfid.fr'> http://android.youfid.fr </a><br/>";
		$message .= "<a href='http://ios.youfid.fr'> http://ios.youfid.fr </a><br/>";
		//$message .= "Pour finaliser la création de votre compte, veuillez cliquer sur le lient suivant:";
		//Pour finaliser la création de votre compte, veuillez cliquer sur le lient suivant afin de « activer mon compte » {Il s’agit d’un mail automatique, merci de ne pas y répondre}
		#$message .= "L'Equipe YouFID";

		return $message;
	}

	function get_email_physique_message($qr_code) {
		if(SUPERMARCHAND_ID == 1839) {
			$message = "Nous vous remercions pour votre pré-inscription au service des Commerces de l'Arche.<br/>";
		} else {
			$message = "Nous vous remercions pour votre pré-inscription au service YouFID.<br/>";
		}

		$url = "http://backoffice.youfid.fr/activateAccount.php?qr_code=" . $qr_code;
		$url = "<a href='" . $url . "'> Activer mon compte </a>";

		$message .= "Pour finaliser la création de votre compte, veuillez cliquer sur le lient suivant:" . $url . "<br/>";
		$message .= "Pensez à télécharger l'application si vous ne l'avez pas encore fait :<br/>";
		$message .= "<a href='http://android.youfid.fr'> http://android.youfid.fr </a><br/>";
		$message .= "<a href='http://ios.youfid.fr'> http://ios.youfid.fr </a><br/>";

		return $message;
	}

	/// App Cliente
	if (isset($jsonArray->source) && $jsonArray->source == 'youfid') {
		if (!isset($jsonArray->email) || !isset($jsonArray->password) || !isset($jsonArray->first_name) || !isset($jsonArray->last_name)) {
			$error = true;
			$errorMsg = "Bad parameters...";
		} else {
			$public_id = gen_mobileuser_uuid();
			//$qr_code = "1" . generate_qr_code();
			$qr_code = generate_qr_code("1");

			$mailVerif = verifyEmail($jsonArray->email);
			if ($mailVerif->status !== 'Ok') {
				$error = true;
				$errorMsg = "L'e-mail que vous avez saisi ne semble pas valide";
			} else if (checkEmail($jsonArray->email) == TRUE) {

				//$sqlApp = "INsSERT INTO $tbl_name VALUES ('' , '$jsonArray->last_name', '$jsonArray->first_name', '$jsonArray->email', '$jsonArray->password', '', '', '', '', '', '', '')";
				$sqlApp = "INSERT INTO $tbl_name SET `public_id`='"
				 	. mysql_real_escape_string($public_id)
					. "', `nom`='"
					. mysql_real_escape_string($jsonArray->last_name)
					. "', `prenom`='"
					. mysql_real_escape_string($jsonArray->first_name)
					. "', `mail`='"
				 	. mysql_real_escape_string($jsonArray->email)
				 	. "', `password`=PASSWORD('"
				 	. mysql_real_escape_string($jsonArray->password)
				 	. "'), `status`='"
				 	. mysql_real_escape_string(1)
					. "', `qr_code`='"
				 	. mysql_real_escape_string($qr_code)
				 	. "', `date_inscription`=NOW()"
				 	. ", `validation` = MD5(CONCAT(1, '"
				 	. mysql_real_escape_string($jsonArray->email)
				 	. "'))";

				 	/* ADD ALEX TO CHECK IF GEOLOC / PUSH ACCEPTED */
				 	if(isset($jsonArray->accept_push)) if($jsonArray->accept_push == "oui") $sqlApp = $sqlApp. ", `accept_push`=NOW()";
				 	if(isset($jsonArray->accept_geoloc)) if($jsonArray->accept_geoloc == "oui") $sqlApp = $sqlApp. ", `accept_geoloc`=NOW()";

				 	/* ADD ALEX FOR BIRTHDATE */
				 	if(isset($jsonArray->birthdate)) $sqlApp = $sqlApp. ", `birthdate`='". mysql_real_escape_string($jsonArray->birthdate) . "'";


				$result=mysql_query($sqlApp);

				if ($result == FALSE) {
					$error = true;
					//$errorMsg = "Error with the db::" . $sqlApp;
					$errorMsg = "Erreur : cet e-mail est déjà utilisé. Si vous avez oublié votre mot de passe, merci de vous rendre à cette adresse : http://www.youfid.fr/Account/RecoverPassword";
				} else {
					$email_content = get_email_message($jsonArray->first_name, $jsonArray->last_name, $jsonArray->email, $jsonArray->password, $qr_code);
					//mail($jsonArray->email, "Votre inscription YouFid", utf8_decode($email_content), $headers) or die ("Couldn't send mail!" );
					mail_youfid($jsonArray->email, $jsonArray->first_name . " " . $jsonArray->last_name, "Votre inscription", $email_content, 'youfid', 'preinscription_appcliente');
				}
			} else {
				$error = true;
				$errorMsg = "Error: There already is an user with the email::" . $jsonArray->email;
			}
		}
	}

	/// Carte physique
	if (isset($jsonArray->source) && $jsonArray->source == 'physique')
	{
		if (!isset($jsonArray->email) || !isset($jsonArray->qr_code))
		{
			$error = true;
			$errorMsg = "Bad parameters...";
		}
		else
		{
			$public_id = gen_mobileuser_uuid();
			$mailVerif = verifyEmail($jsonArray->email);
			if ($mailVerif->status !== 'Ok') {
				$error = true;
				$errorMsg = "L'e-mail que vous avez saisi ne semble pas valide";
			} else if (checkEmail($jsonArray->email) == TRUE) {
				//$sqlCarte = "INSERT INTO $tbl_name VALUES ('', '', '', '$jsonArray->email', '', '$jsonArray->qr_code', '', '', '', '', '', '')";
				$sqlCarte = "INSERT INTO $tbl_name SET `public_id`='"
					. mysql_real_escape_string($public_id)
					."', `mail`='"
					. mysql_real_escape_string($jsonArray->email)
					. "', `status`='"
				 	. mysql_real_escape_string(2)
					. "', `qr_code`='"
					. mysql_real_escape_string($jsonArray->qr_code)
					. "', `date_inscription`=NOW()"
				 	. ", `validation` = MD5(CONCAT(2, '"
				 	. mysql_real_escape_string($jsonArray->email)
				 	. "'))";

				$result=mysql_query($sqlCarte);
				//$logger->log('debug', 'registerCustomer', "physique query = " . $sqlCarte, Logger::GRAN_MONTH);

				if ($result == FALSE)
				{

					$error = true;
					//$errorMsg = "Error with the db";
					$errorMsg = "Erreur : cet e-mail est déjà utilisé. Si vous avez oublié votre mot de passe, merci de vous rendre à cette adresse : http://www.youfid.fr/Account/RecoverPassword";

				}
				else {

					$email_content = get_email_physique_message($jsonArray->qr_code);
					//mail($jsonArray->email, "Votre inscription YouFid", utf8_decode($email_content), $headers) or die ("Couldn't send mail!" );
					mail_youfid($jsonArray->email, '', "Votre inscription", $email_content, 'youfid', 'preinscription_cartephysique');
				}
			}
			else {
				$error = true;
				$errorMsg = "Error: There already is an user with the email::" . $jsonArray->email;
			}

		}

	}

	function checkFb_id($fb_id) {

		global $tbl_name;

		$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `fb_id`='" . mysql_real_escape_string($fb_id) . "'";
		$result = mysql_query($sqlGetCustomer);

		if (!mysql_fetch_array($result))
			return TRUE;

		return FALSE;

	}



	/// Facebook

	if (isset($jsonArray->source) && $jsonArray->source == 'facebook')
	{

		if (!isset($jsonArray->email) || !isset($jsonArray->first_name) || !isset($jsonArray->last_name) ||
			!isset($jsonArray->fb_id) || !isset($jsonArray->token))
		{
			$error = true;
			$errorMsg = "Bad parameters...";
		}
		else
		{

			if (checkFb_id($jsonArray->fb_id) && checkEmail($jsonArray->email))
			{
				$public_id = gen_mobileuser_uuid();
				//$qr_code = "1" . generate_qr_code();
				$qr_code = generate_qr_code("1");

				$facebook_picture = 'https://graph.facebook.com/' . $jsonArray->fb_id . '/picture';
				$facebook_password = generatePassword();

				//$sqlFacebook = "INSERT INTO $tbl_name VALUES ('' , '$jsonArray->last_name', '$jsonArray->first_name', '$jsonArray->email', '$facebook_password', '', '$jsonArray->fb_id', '$jsonArray->token', '$facebook_picture', '', '', '')";

				$sqlFacebook = "INSERT INTO $tbl_name SET `public_id`='"
				 	. mysql_real_escape_string($public_id)
					. "', `nom`='"
					. mysql_real_escape_string($jsonArray->last_name)
					. "', `prenom`='"
					. mysql_real_escape_string($jsonArray->first_name)
					. "', `mail`='"
				 	. mysql_real_escape_string($jsonArray->email)
				 	. "', `password`=PASSWORD('"
				 	. mysql_real_escape_string($facebook_password)
				 	. "'), `fb_id`='"
				 	. mysql_real_escape_string($jsonArray->fb_id)
					. "', `token`='"
				 	. mysql_real_escape_string($jsonArray->token)
					. "', `photo`='"
				 	. mysql_real_escape_string($facebook_picture)
				 	. "', `status`='"
				 	. mysql_real_escape_string(1)
				 	. "', `qr_code`='"
			 		. mysql_real_escape_string($qr_code)
				 	. "', `date_inscription`=NOW()"
				 	. ", `validation` = MD5(CONCAT(1, '"
				 	. mysql_real_escape_string($jsonArray->email)
				 	. "'))";

				 	/* ADD ALEX TO CHECK IF GEOLOC / PUSH ACCEPTED */
				 	if(isset($jsonArray->accept_push)) if($jsonArray->accept_push == "oui") $sqlFacebook = $sqlFacebook. ", `accept_push`=NOW()";
				 	if(isset($jsonArray->accept_geoloc)) if($jsonArray->accept_geoloc == "oui") $sqlFacebook = $sqlFacebook. ", `accept_geoloc`=NOW()";

				 //$logger->log('debug', 'registerCustomer', "facebook query = " . $sqlFacebook, Logger::GRAN_MONTH);
				//echo($sqlFacebook);
				$result=mysql_query($sqlFacebook);

				if ($result == FALSE)
				{
					$error = true;
					$errorMsg = "Error with the DB.";
				}
				else
				{
					$email_content = get_email_message_facebook($jsonArray->first_name, $jsonArray->last_name, $jsonArray->email, $facebook_password);
					//mail($jsonArray->email, "Votre inscription YouFid", utf8_decode($email_content), $headers) or die ("Couldn't send mail!" );
					mail_youfid($jsonArray->email, $jsonArray->first_name." ".$jsonArray->last_name, "Votre inscription", $email_content, 'youfid', 'preinscription_facebook');
				}
			}
			else
			{
				$error = true;
				$errorMsg = "There already a user with the fb_id:" . $jsonArray->fb_id . " or the same email.";
			}
		}
	}

	/// Update
	if (isset($jsonArray->source) && $jsonArray->source == 'update')
	{
		if (!isset($jsonArray->usr_id))
		{
			$error = true;
			$errorMsg = "Bad parameters...";
		}
		else
		{
			// `nom`='$jsonArray->last_name',`prenom`='$jsonArray->first_name',`password`='$jsonArray->password' WHERE `idclient`='$jsonArray->usr_id'";
			$sqlUpdate= "UPDATE $tbl_name SET ";
			$isFirst = true;

			if (isset($jsonArray->password) and !empty($jsonArray->password))
			{

				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';
				$isFirst = false;

				$sqlUpdate = $sqlUpdate . "`password`=PASSWORD('" . mysql_real_escape_string($jsonArray->password) . "')";
			}

			if (isset($jsonArray->first_name))
			{
				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';
				$isFirst = false;

				$sqlUpdate = $sqlUpdate . "`prenom`='" . mysql_real_escape_string($jsonArray->first_name) . "'";
			}

			if (isset($jsonArray->last_name))
			{
				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';
				$isFirst = false;

				$sqlUpdate = $sqlUpdate . "`nom`='" . mysql_real_escape_string($jsonArray->last_name) . "'";
			}

			if (isset($jsonArray->pin_code))
			{
				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';

				$isFirst = false;
				$sqlUpdate = $sqlUpdate . "`pin_code`='" . mysql_real_escape_string($jsonArray->pin_code) . "'";
			}

			if (isset($jsonArray->is_pin_active))
			{
				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';
				$isFirst = false;

				$sqlUpdate = $sqlUpdate . "`is_pin_active`='" . mysql_real_escape_string($jsonArray->is_pin_active) . "'";
			}

			/* ALEX */
			if (isset($jsonArray->email))
			{
				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';
				$isFirst = false;

				$sqlUpdate = $sqlUpdate . "`mail`='" . mysql_real_escape_string($jsonArray->email) . "'";
			}

			if (isset($jsonArray->birthdate))
			{
				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';
				$isFirst = false;

				$sqlUpdate = $sqlUpdate . "`birthdate`='" . mysql_real_escape_string($jsonArray->birthdate) . "'";
			}

			if (isset($jsonArray->phone))
			{
				if ($isFirst == false)
					$sqlUpdate = $sqlUpdate . ', ';
				$isFirst = false;

				$sqlUpdate = $sqlUpdate . "`phone`='" . mysql_real_escape_string($jsonArray->phone) . "'";
			}

			///
			$sqlUpdate = $sqlUpdate . " WHERE `id`='" . mysql_real_escape_string($jsonArray->usr_id) . "'";

			//echo($sqlUpdate);
			if ($isFirst == false)
			{
				$result=mysql_query($sqlUpdate);
				/*ALEX*/
				mysql_query("UPDATE $tbl_name SET `status` = 1 WHERE `id`='"
				. mysql_real_escape_string($jsonArray->usr_id) ."'");
			}
			else
				$result = false;

			if ($result == FALSE)
			{
				$error = true;
				$errorMsg = "Error with the db";

				if ($isFirst == true)
					$errorMsg = "No parameters";
			}
		}
	}

	/// Gestion d'erreur
	if ($error == true)
		$status = "error";

	else
	{
		$status = "ok";

		if (isset($jsonArray->email))
		{
			$sqlGetId = "SELECT * FROM $tbl_name WHERE `mail` = '"
				. mysql_real_escape_string($jsonArray->email)
				. "'";

			//echo($sqlGetId);
			$result = mysql_query($sqlGetId);

			$row = mysql_fetch_array($result);
		}
		//echo($row['first_name']);
	}

	$jsonResult['status'] = $status;
	$jsonResult['message'] = $errorMsg;
	if (isset($row['id']) && $jsonArray->source != 'update')
		$jsonResult['usr_id'] = $row['id'];

	//$jsonResult['usr_id'] = '0';
	echo(json_encode(array_map_utf8_encode($jsonResult)));
	doLog("RESPONSE::" . json_encode(array_map_utf8_encode($jsonResult)));

