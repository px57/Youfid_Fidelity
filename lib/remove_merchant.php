<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	//require_once("db_functions.php");
	//require_once("Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
	require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/db_functions.php");
	
	if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . '/logs/');
	
	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'remove merchant', $message, Logger::GRAN_MONTH);
	}
	
	$error = FALSE;
	
	if (isset($_SESSION['selector']) && !empty($_SESSION['selector']))
	{
		$merchand_id = strval($_SESSION['selector']);
		
		doLog("merchant-id=" . $merchand_id);
		
		$error = delete_marchand($merchand_id);
		if ($error == TRUE)
			$_SESSION['user_error'] = "Le marchand a été supprimé avec succes!";
		else
			$_SESSION['user_error'] = "Erreur lors de la suppression du marchand!";
	}

	$_SESSION['selector'] = "NEW";
	header("location:../" . $_SESSION['selector_current_location']);
?>
