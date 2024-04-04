<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
        require_once(dirname(__FILE__) . "/../include/session.class.php");
        $session = new Session();
 

	require_once("Logger.class.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');
	
	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'recover_password', $message, Logger::GRAN_MONTH);
	}
	
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Ressource/email_content.php");
	
	$tbl_bo_usr = "backoffice_usr";
	$tbl_marchands = "marchand";
	
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	$error = recover_password();
	
	if ($error == TRUE)
		echo("true");
	else
		echo("false");
	
	function recover_password()
	{
		global $tbl_bo_usr, $tbl_marchands, $email_recover_password_subject, $email_recover_password_content, $email_recover_password_content2, $logger;
		
		if (isset($_POST['login']) && !empty($_POST['login']))
		{	
			$login = $_POST['login'];
			unset($_POST['login']);
		}
		else
			return FALSE;
		
		$sqlGetBoUsr = "SELECT * FROM $tbl_bo_usr WHERE `login`='"
			. mysql_real_escape_string($login)
			. "'";
			
		$result = mysql_query($sqlGetBoUsr);
		
		if ($row = mysql_fetch_array($result))
		{
			$password = $row['password'];
			
			$sqlGetBoUsr = "SELECT * FROM $tbl_marchands WHERE `id`='"
				. mysql_real_escape_string($row['id_marchand'])
				. "'";
			
			$result = mysql_query($sqlGetBoUsr);
			
			if ($merchant_row = mysql_fetch_array($result))
			{
				$email_address = $merchant_row['email_backoffice'];
				
			    /////////////////////////////////
				/// Email definition
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
				$headers .=	'Content-Transfer-Encoding: 8bit' . "\r\n";
				$headers .= 'From: Admin YouFid <admin@youfid.fr>' . "\r\n";
				
				$email_content = $email_recover_password_content . $password . $email_recover_password_content2;
				
				//mail($email_address, $email_recover_password_subject, utf8_decode($email_content), $headers) or die ("Couldn't send mail!" );
				mail_youfid($email_address, $email_recover_password_subject, $email_content);
				return TRUE;
			}
		}
		return FALSE;
	}


?>
