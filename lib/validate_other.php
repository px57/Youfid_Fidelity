<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/dbLogInfo.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/dev/service/utils.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');
	
	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'validate_physique', $message, Logger::GRAN_MONTH);
	}
	
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	$tbl_mobileuser = "mobileuser";
	$tbl_transaction = "transaction";
	$tbl_marchands = "marchand";
	
	$tbl_msg_had_mbu = "message_has_mobileuser";
	$tbl_msg = "message";
	$tbl_authentification = "authentification";
	$tbl_push = "histo_push";
	
	if (isset($_SESSION['qr_code']) && !empty($_SESSION['qr_code']))
	{
		$qr_code = $_SESSION['qr_code'];
		unset ($_SESSION['qr_code']);
	}
	doLog("Enter... qr_code=" . $qr_code);

	$error_msg = validate_physique($qr_code);
	echo(trim($error_msg));
	
	function validate_physique($qr_code)
	{
		global $tbl_mobileuser;
		
		$query = "SELECT * FROM $tbl_mobileuser WHERE `qr_code`='"
			. mysql_real_escape_string($qr_code)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE || !mysql_num_rows($result))
		{
			doLog("erreur:query=" . $query);
			return "false";
		}
		
		/*$row = mysql_fetch_array($result);
		if ($row['status'] != 2)
		{
			doLog("erreur:STATUS::query=" . $query);
			return "false";
		}*/
				
		$query = "UPDATE $tbl_mobileuser SET `status`='1' WHERE `qr_code`='"
			. mysql_real_escape_string($qr_code)
			. "'";
			
		$result = mysql_query($query);
		
		if ($result == FALSE)
		{
			doLog("erreur:UPDATEs::query=" . $query);
			return "false";
		}
			
		return "true";
	}
?>
