<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	require_once '../mail/lib/swift_required.php';
	
	require_once("../dev/service/dbLogInfo.php");
	$tbl_name = "message";
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	if (isset($_POST['id'])) {
		$getMail = "SELECT * FROM message WHERE id='" . $_POST['id'] . "'";
		$result = mysql_query($getMail);
		$rowMail = mysql_fetch_array($result);
		
		

		// Create the Transport
		$transport = Swift_SmtpTransport::newInstance('localhost', 25);
		$mailer = Swift_Mailer::newInstance($transport);
		
		$getUsers = "SELECT * FROM message_has_mobileuser  WHERE message_id='" . $_POST['id'] . "'";
		$resultUser = mysql_query($getUsers);

		while ($rowUser = mysql_fetch_array($resultUser)) {
			$getUser2 = "Select * from mobileuser where id = '" . $rowUser['mobileuser_id'] . "'";
			$resultUser2 = mysql_query($getUser2);
			$rowUser2 = mysql_fetch_array($resultUser2);
			
			$message = Swift_Message::newInstance($rowMail['message'])
 			 ->setFrom(array(' admin@youfid.fr'))
 			 ->setTo(array($rowUser2['mail']))
 			 ->setBody($rowMail['detail']);

			$result = $mailer->send($message);
		
		}
		
		$updateCadeau = "UPDATE message SET is_validated='1' WHERE id='" . $_POST['id'] . "'";
		$resultUp = mysql_query($updateCadeau);
	}
	header("location:../youfid_master_validationmes.php");
	?>
