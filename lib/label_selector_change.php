<?php
	header("Content-Type: text/plain"); // Utilisation d'un header pour spécifier le type de contenu de la page. Ici, il s'agit juste de texte brut (text/plain). 
	
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();
 
	
	require_once("db_functions.php");
	require_once("../dev/service/utils.php");
	require_once("Logger.class.php");
	
	$logger->log('debug', 'label_selector_change', "in file", Logger::GRAN_MONTH);
	
	if (!isset($logger))
		$logger = new Logger('../logs/');
	
	/*if (!isset($logger))
		$logger = new Logger('logs/');*/

	function doLog($message)
	{
		global $logger;
		
		if (isset($logger))
			$logger->log('debug', 'label_selector_change', $message, Logger::GRAN_MONTH);
	}
	
	$selection = (isset($_GET["selection"])) ? $_GET["selection"] : NULL;
	$is_franchise = FALSE;
	
	$logger->log('debug', 'label_selector_change', "Selection=" . $selection, Logger::GRAN_MONTH);
	
	// Check if label = "franchise"
	function doSelectorChange()
	{
		global $selection, $logger;
		
		$tbl_label = "label";
		
		$sqlGetLabel = "SELECT * FROM $tbl_label WHERE `nom`='"
			. mysql_real_escape_string($selection)
			. "'";
			
		$result = mysql_query($sqlGetLabel);
		
		if ($result && ($row = mysql_fetch_array($result)))
		{
			if ($row['type'] == "franchise")
				return TRUE;
		}
		
		return FALSE;
	}
	
	doLog("SELECTION:" . $selection);
	
	if (isset($selection) && !empty($selection))
		$is_franchise = doSelectorChange();

	if ($selection && $is_franchise) 
	{
		$_SESSION['current_label_selection'] = $selection;
		echo "OK";
	} 
	else 
	{
		echo "FAIL";
	}
?>
