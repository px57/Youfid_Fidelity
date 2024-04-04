<?php
	
	//////////////////////////////////////////////////////////////////////////////////////////////
	///	renvois une serie de echo(<option>) en vue de remplir la listbox de new_merchant.php
	
	/// Logs
	require_once("lib/Logger.class.php");
	$logger = new Logger('./logs/');

	$logger->log('debug', 'debug_spinner_list_category', "In spinner_list_category", Logger::GRAN_MONTH);
	
	require_once("./dev/service/dbLogInfo.php");
	$tbl_category = "categorie";
	
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	$sqlListCategory = "SELECT * FROM $tbl_category";
	$resultListCategory = mysql_query($sqlListCategory); 

	if (mysql_num_rows($resultListCategory))
	{
		while ($row = mysql_fetch_array($resultListCategory))
		{
			$logger->log('debug', 'debug_spinner_list_category', "category_name=" . $row['nom'] , Logger::GRAN_MONTH);
			$spinner_str = "<option>" . $row['nom'] . "</option>"; 
			echo($spinner_str);
		}
	}
	else
	{
		$logger->log('debug', 'debug_spinner_list_category', "Error: no category detected", Logger::GRAN_MONTH);
	}
?>