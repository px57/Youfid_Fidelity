<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 

	require_once("Logger.class.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');
	
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Ressource/email_content.php");
	
	$tbl_bo_usr = "backoffice_usr";
	$tbl_marchands = "marchand";
	
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");

	$error_msg = "";	
	$error = register_new_commercial();

	if ($error == TRUE)
		echo("true");
	else
		echo($error_msg);

	function register_new_commercial()
	{
		global $tbl_bo_usr, $error_msg;
		
		if (isset($_POST['c_email']) && !empty($_POST['c_email']))
		{
			$email = $_POST['c_email'];
			unset($_POST);
		}
		else
		{
			$error_msg = "1_Erreur avec le serveur... Contactez un administrateur.";
			return FALSE;
		}
		
		$query = "SELECT * FROM $tbl_bo_usr WHERE `login`='"
			. mysql_real_escape_string($email)
			. "'";
		
		$result = mysql_query($query);
		if ($result == FALSE)
		{
			$error_msg = "2_Erreur avec le serveur... Contactez un administrateur.";
			return FALSE;
		}
		
		if (mysql_num_rows($result))
		{
			$error_msg = "L'utilisateur: " . $email . " existe deja en base de donnée.";
			return FALSE;
		}
		
		$password = generatePassword();
		
		$query = "INSERT INTO $tbl_bo_usr SET `id_role`='"
			. mysql_real_escape_string("3")
			. "', `login`='"
			. mysql_real_escape_string($email)
			. "', `password`='"
			. mysql_real_escape_string($password)
			. "', `id_marchand`='"
			. mysql_real_escape_string("0")
			. "'";
		
		$result = mysql_query($query);
		if ($result)
		{
			/////////////////////////////////
			/// Email definition
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
			$headers .= 'From: Admin YouFid <admin@youfid.fr>' . "\r\n";
			
			$bo_email_content = "Votre compte commercial Youfid vient d'etre activé.<br/>Veuillez trouvez ici vos identifiants:<br/>Login : "
			. $email . "<br/>Mot de passe :" . $password . "<br/><br/> Vous etes libre de changer votre mot de passe en vous rendant a l'addresse suivante: http://backoffice.youfid.fr/change_password.php";
			
			//mail($email, 'Votre mot de passe YouFid - Back Office', utf8_decode($bo_email_content), $headers)or die ("Couldn't send mail!");
			mail_youfid($email, 'Votre mot de passe YouFid - Back Office', $bo_email_content);
			return TRUE;
		}
		$error_msg = "3_Erreur avec le serveur... Contactez un administrateur.";
		return FALSE;
	}
?>
