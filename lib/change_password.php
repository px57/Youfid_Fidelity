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
	$error = change_password();
	//$error = FALSE;
	
	if ($error == TRUE)
		echo("true");
	else
		echo($error_msg);
	
	function change_password()
	{
		global $error_msg, $tbl_bo_usr;
		
		if ((isset($_POST['login']) && !empty($_POST['login'])) || (isset($_POST['old_password']) && !empty($_POST['old_password'])) ||
			(isset($_POST['new_password']) && !empty($_POST['new_password'])) || (isset($_POST['new_password_bis']) && !empty($_POST['new_password_bis'])))
		{	
			$login = $_POST['login'];
			$old_password = $_POST['old_password'];
			$new_password = $_POST['new_password'];
			unset($_POST);
		}
		else
		{
			$error_msg = "1_Erreur avec le serveur... Contactez un administrateur.";
			return FALSE;
		}
		
		$query = "SELECT * FROM $tbl_bo_usr WHERE `login`='"
			. mysql_real_escape_string($login)
			. "' && `password`='"
			. mysql_real_escape_string($old_password)
			. "'";
			
		$result = mysql_query($query);
		
		if ($row = mysql_fetch_array($result))
		{
			$query = "UPDATE $tbl_bo_usr SET `password`='"
				. mysql_real_escape_string($new_password)
				. "' WHERE `id`='"
				. mysql_real_escape_string($row['id'])
				. "'";
				
			$result = mysql_query($query);
			if ($result)
			{
				$delim = "<br/>";
				$message = "Bonjour," . $delim . $delim;
				$message .= "Votre demande de changement de mot de passe a bien été prise en compte." . $delim;
				$message .= "Voici votre nouveau mot de passe:" . $new_password . $delim;
				$message .= "Vous pouvez accédez à votre back office via notre site internet ( espace marchand) ou bien directement de la tablette." . $delim;
				$message .= "N'hésitez pas à nous contacter en cas de questions." . $delim . $delim;
				$message .= "Cordialement," . $delim . "L' Equipe YouFID" . $delim;
				mail_youfid($row['mail'], 'Votre nouveau mot de passe YouFid', $message);
				
				$error_msg = "";
				return TRUE;
			}
		}
		else {
			$error_msg = "Aucun utilisateur trouvé avec cette combinaison de login / mot de passe ...";
			return FALSE;
		}
		
		$error_msg = "2_Erreur avec le serveur... Contactez un administrateur.";
		return FALSE;
	}
	
?>
