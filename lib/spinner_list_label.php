<?php
	
	//////////////////////////////////////////////////////////////////////////////////////////////
	///	renvois une serie de echo(<option>) en vue de remplir la listbox de new_merchant.php
	
	/// Logs
	require_once("lib/Logger.class.php");
	$logger = new Logger('./logs/');

	$logger->log('debug', 'debug_spinner_list_label', "In spinner_list_label", Logger::GRAN_MONTH);
	
	require_once("./dev/service/dbLogInfo.php");
	$tbl_label = "label";
	
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	
	$sqlListlabel = "SELECT * FROM $tbl_label ORDER BY `nom` ASC";
	$resultListlabel = mysql_query($sqlListlabel); 

	if (mysql_num_rows($resultListlabel))
	{
		$selected_value = 0;
		
		if (isset($_SESSION['selector']))
		{
			$tbl_merchant = "marchand";
			$sqlGetMerchant = "SELECT * FROM $tbl_merchant WHERE `id`='"
				. mysql_real_escape_string($_SESSION['selector'])
				. "'";
			
			$result = mysql_query($sqlGetMerchant);
			if (mysql_num_rows($result))
			{
				$merchantRow = mysql_fetch_array($result);
				$selected_value = $merchantRow['label_id'];		
			}
		}
		
		/// Creation de deux tableaux: un pour les categories et un pour les franchises:
		$categorie_array = array();
		$franchise_array = array();
		
		while ($row = mysql_fetch_array($resultListlabel))
		{
			if ($row['supermarchand_id'] == -1)
				array_push($categorie_array, $row);
			else
				array_push($franchise_array, $row);
		}
	
		if ($categorie_array)
			echo('<option disabled>--Catégories:--</option>'); 
		for ($index = 0; isset($categorie_array[$index]); $index += 1)
		{
			//$logger->log('debug', 'debug_spinner_list_label', "label_name=" . $row['nom'] , Logger::GRAN_MONTH);
			
			if (isset($_SESSION['current_label_selection']) && $_SESSION['current_label_selection'] == $categorie_array[$index]['nom'])
				$spinner_str = "<option selected>" . $categorie_array[$index]['nom'] . "</option>"; 
			else if (!isset($_SESSION['current_label_selection']) && $selected_value == $categorie_array[$index]['id'])
				$spinner_str = "<option selected>" . $categorie_array[$index]['nom'] . "</option>";
			else
				$spinner_str = "<option>" . $categorie_array[$index]['nom'] . "</option>";
			
			echo($spinner_str);
		}
		
		if ($franchise_array)
			echo('<option disabled>--Franchises:--</option>');
		for ($index = 0; isset($franchise_array[$index]); $index += 1)
		{
			//$logger->log('debug', 'debug_spinner_list_label', "label_name=" . $row['nom'] , Logger::GRAN_MONTH);
			
			if (isset($_SESSION['current_label_selection']) && $_SESSION['current_label_selection'] == $franchise_array[$index]['nom'])
				$spinner_str = "<option selected>" . $franchise_array[$index]['nom'] . "</option>"; 
			else if (!isset($_SESSION['current_label_selection']) && $selected_value == $franchise_array[$index]['id'])
				$spinner_str = "<option selected>" . $franchise_array[$index]['nom'] . "</option>";
			else
				$spinner_str = "<option>" . $franchise_array[$index]['nom'] . "</option>";
			
			echo($spinner_str);
		}
		
		
		/*
		while ($row = mysql_fetch_array($resultListlabel))
		{
			$logger->log('debug', 'debug_spinner_list_label', "label_name=" . $row['nom'] , Logger::GRAN_MONTH);
			
			if (isset($_SESSION['current_label_selection']) && $_SESSION['current_label_selection'] == $row['nom'])
				$spinner_str = "<option selected>" . $row['nom'] . "</option>"; 
			else if (!isset($_SESSION['current_label_selection']) && $selected_value == $row['id'])
				$spinner_str = "<option selected>" . $row['nom'] . "</option>";
			else
				$spinner_str = "<option>" . $row['nom'] . "</option>";
			
			echo($spinner_str);
		}*/
		
		if (isset($_SESSION['current_label_selection']))
			unset($_SESSION['current_label_selection']);
	}
	else
	{
		$logger->log('debug', 'debug_spinner_list_label', "Error: no label detected", Logger::GRAN_MONTH);
	}
?> 