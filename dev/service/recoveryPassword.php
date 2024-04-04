<?php
	// Import du fichier utils (generatePassword...)

	require_once('utils.php');

	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Ressource/email_content.php");

	$headers  = 'MIME-Version: 1.0' . "\r\n";

	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

	$headers .= 'From: Admin YouFid <admin@youfid.fr>' . "\r\n";

	////////////////////////////////////////// DataBase Properties
	$tbl_name="mobileuser";
	require_once('dbLogInfo.php');

	////////////////////////////////////////

	// Error properties
	$error = false;
	$errorMsg = "";

	////////////////////////////////////////
	// DataBase connection

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	////////////////////////////////////////
	// Getting Json Content

	$json = file_get_contents('php://input');

	$jsonArray = json_decode($json);


	if(isset($jsonArray->supermarchand_id)) {
		define('SUPERMARCHAND_ID', $jsonArray->supermarchand_id);
	} else {
		define('SUPERMARCHAND_ID', 0);
	}

	if (isset($jsonArray->email)){

		$sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mail` = '" . mysql_real_escape_string($jsonArray->email) . "'";
		$result = mysql_query($sqlGetCustomer);

		//echo($sqlGetCustomer);
		if ($result == false){
			$error = true;
			$errorMsg = "Error with DB";
		} else{
			$rowNb = mysql_num_rows($result);
			if ($rowNb) {

				$password = generatePassword();
				$email_recover_password_content =  "Ci-dessous votre nouveau mot de passe : ";
				if(SUPERMARCHAND_ID == 1839)
					$email_recover_password_content =  "Ci-dessous votre nouveau mot de passe pour les Commerces de l'Arche : ";
				$email_recover_password_content2 = "<br/>" . "Vous pouvez le modifier en cliquant sur le lien suivant : <br> <a href='http://youfid.fr/membres/connexion'> http://youfid.fr/membres/connexion </a>" . "<br/><br/>";

				$email_new_bo_usr_subject = "Votre nouveau mot de passe";
				$email_content = $email_recover_password_content . $password . $email_recover_password_content2;

				//mail($jsonArray->email, 'Votre mot de passe YouFid', utf8_decode($email_content), $headers) or die ("Couldn't send mail!" );

				// on charge la queue d'envoi du mail ...
        //        mysql_query("
        //        	INSERT DELAYED INTO sendmail
        //        	VALUES (
        //        		NULL,
        //        		'" . $jsonArray->email . "',
        //        		'',
        //        		'". mysql_real_escape_string($email_new_bo_usr_subject) . "',
        //        		'". mysql_real_escape_string($email_content) . "',
        //        		NOW(),
        //        		'WAITING',
        //        		NULL
        //        	)
        //        ");

				mail_youfid($jsonArray->email, ' ', $email_new_bo_usr_subject, $email_content, 'youfid', 'recoverypassword');
				$sqlUpdate= "UPDATE $tbl_name SET `password`= PASSWORD('" . $password . "') WHERE `mail`='" . mysql_real_escape_string($jsonArray->email) . "'";
				$result=mysql_query($sqlUpdate);

				if ($result == FALSE) {
					$error = true;
					$errorMsg = "Error with the db";
				}
			}	else {
				$error = true;
				$errorMsg = "Email not found";
			}
		}
	} else {
		$error = true;
		$errorMsg = "Bad parameters";
	}

	if ($error) {
		$status = "error";
	} else {
		$status = "ok";
  }

	$jsonResult['status'] = $status;
	$jsonResult['message'] = $errorMsg;
	echo(json_encode(array_map_utf8_encode($jsonResult)));



