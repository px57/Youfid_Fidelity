<?php
	require_once(dirname(__FILE__) . "/../include/database.class.php");
	require_once(dirname(__FILE__) . "/../include/session.class.php");
	$session = new Session();

	//////////////////////////////////////////////////////////////////////////////////////////////
	///	renvois une serie de echo(<option>) en vue de remplir la listbox de header_content.php

	/// Logs
	require_once("lib/Logger.class.php");
	$logger = new Logger('./logs/');

	$logger->log('debug', 'debug_sinner_list_shop', "in spinner_list_shop.php", Logger::GRAN_MONTH);

	require_once("./dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	$tbl_chm = "yfcommercial_have_merchant";

	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");

	/// Recuperation du criteria pour un tri
	$criteria = "";
	if (isset($_POST['shop_find_criteria']) && !empty($_POST['shop_find_criteria']))
	{
		if ($_POST['shop_find_criteria'] != "Rechercher")
			$criteria = $_POST['shop_find_criteria'];
		unset($_POST['shop_find_criteria']);
	}

	/// If Commercial: Recuperation des marchands en cours

	$merchant_id_array = array();
	if ($_SESSION['role'] == "youfid_commerciaux")
	{
		$merchant_id_array = get_commercial_have_merchant($_SESSION['usr_id']);

		$sql_merchant_id = "";
		for($index = 0; isset($merchant_id_array[$index]); $index += 1)
		{
			$sql_merchant_id = $sql_merchant_id . " `id`='" . $merchant_id_array[$index] . "' ";
			if (isset($merchant_id_array[$index + 1]))
				$sql_merchant_id = $sql_merchant_id . "||";
		}
	}

	if ($criteria == "")
	{
		if ($_SESSION['role'] == "youfid_commerciaux")
			$sqlListMarchand = "SELECT * FROM $tbl_marchands WHERE" . $sql_merchant_id . "ORDER BY `name` ASC";

// TEST ALEX
		else if ($_SESSION['login'] == "admin_pagesdor")
			$sqlListMarchand = "SELECT * FROM marchand WHERE `country` = 'Belgique' ORDER BY `name` ASC";
// END TEST
		else
			$sqlListMarchand = "SELECT * FROM $tbl_marchands ORDER BY `name` ASC";//SELECT * FROM ta_table ORDER BY champ ASC

		$logger->log('debug', 'debug_sinner_list_shop', "hello guys", Logger::GRAN_MONTH);
		$logger->log('debug', 'debug_sinner_list_shop', "query=" . $sqlListMarchand, Logger::GRAN_MONTH);

//ob_start();
//var_dump($_SESSION);
//$resultSess = ob_get_clean();
//$logger->log('debug', 'debug_sinner_list_shop', "session=" . $resultSess, Logger::GRAN_MONTH);

		$resultListMarchand = mysql_query($sqlListMarchand);
		$tmp_resultListMarchand = mysql_query($sqlListMarchand);
	}
	else
	{
		if ($_SESSION['role'] == "youfid_commerciaux")
		{
			$sqlListMarchand = "SELECT * FROM $tbl_marchands WHERE `name` LIKE '%"
			. mysql_real_escape_string($criteria)
			. "%' AND(" .  $sql_merchant_id
			. ") ORDER BY `name` ASC";
		}
// TEST ALEX
		else if ($_SESSION['login'] == "admin_pagesdor")
		{
			$sqlListMarchand = "SELECT * FROM marchand WHERE `name` LIKE '%"
			. mysql_real_escape_string($criteria)
			. "%' AND WHERE `country` = 'Belgique' ORDER BY `name` ASC";

					$logger->log('debug', 'debug_sinner_list_shop', "in test alex", Logger::GRAN_MONTH);

		}
// END TEST
		else
		{
			$sqlListMarchand = "SELECT * FROM $tbl_marchands WHERE `name` LIKE '%"
				. mysql_real_escape_string($criteria)
				. "%' ORDER BY `name` ASC";
		}

		$resultListMarchand = mysql_query($sqlListMarchand);
		$tmp_resultListMarchand = mysql_query($sqlListMarchand);

		if (mysql_num_rows($resultListMarchand))
			$_SESSION['shop_find_nbresult'] = mysql_num_rows($resultListMarchand) . " marchands trouvés.";
		else
			$_SESSION['shop_find_nbresult'] = "Aucun marchand trouvé.";
	}
	//$resultListMarchand = mysql_query($sqlListMarchand);

	/// On verifie que la position du selector est set sur un marchand encore présent dans la liste
	if (mysql_num_rows($tmp_resultListMarchand))
	{
		$is_selector_correct = FALSE;

		if (isset($_SESSION['selector']))
			$selected_value = $_SESSION['selector'];
		else
			$selected_value = 0;

		while ($row = mysql_fetch_array($tmp_resultListMarchand))
		{
			if ($row['id'] == $selected_value)
				$is_selector_correct = TRUE;
		}

		if ($is_selector_correct == FALSE)
			$_SESSION['selector'] = "NEW";

		unset($selected_value);
	}

	if (mysql_num_rows($resultListMarchand))
	{
		//
		//$logger->log('debug', 'debug_sinner_list_shop', "result = true", Logger::GRAN_MONTH);
		if (isset($_SESSION['selector']))
			$selected_value = $_SESSION['selector'];
		else
			$selected_value = 0;

		//$logger->log('debug', 'debug_sinner_list_shop', "selected_id=" . $selected_value, Logger::GRAN_MONTH);

		while ($row = mysql_fetch_array($resultListMarchand))
		{
			$is_valid_query = "SELECT * FROM $tbl_chm WHERE `merchant_id`='"
				. mysql_real_escape_string($row['id'])
				. "'";

			$is_valid_result = mysql_query($is_valid_query);

			$is_valid = TRUE;
			if (mysql_num_rows($is_valid_result))
				$is_valid = FALSE;

			if ($row['id'] == $selected_value)
			{
				/// Set selected
				if ($is_valid == TRUE)
					$spinner_str = "<option value=". $row['id'] . " selected>" . $row['name'] . "</option>";
				else
					$spinner_str = "<option style=\"color:#FF0000;\" value=". $row['id'] . " selected>" . $row['name'] . "</option>";
				$logger->log('debug', 'debug_sinner_list_shop', "Selected index is => ". $row['id'], Logger::GRAN_MONTH);
			}
			else
			{
				if ($is_valid == TRUE)
					$spinner_str = "<option value=". $row['id'] . ">" . $row['name'] . "</option>";
				else
					$spinner_str = "<option style=\"color:#FF0000;\" value=". $row['id'] . ">" . $row['name'] . "</option>";
			}
			echo($spinner_str);
		}
	}
	/// Aucun Marchand trouvé
	else
	{
		$_SESSION['selector'] = "NEW";
		//$logger->log('debug', 'debug_sinner_list_shop', "Error: no marchand detected", Logger::GRAN_MONTH);
	}

	function get_commercial_have_merchant($user_id)
	{
		global $tbl_marchands, $tbl_chm, $logger;

		$merchant_id_array = array();

		$query = "SELECT * FROM $tbl_chm WHERE `user_id`='"
			. mysql_real_escape_string($user_id)
			. "'";

		$logger->log('debug', 'debug_sinner_list_shop', "getcommercial::query=" . $query, Logger::GRAN_MONTH);
		$result = mysql_query($query);

		while ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_marchands WHERE `id`='"
				. mysql_real_escape_string($row['merchant_id'])
				. "'";

			$logger->log('debug', 'debug_sinner_list_shop', "getcommercial::query2=" . $query, Logger::GRAN_MONTH);
			$merchant_result = mysql_query($query);
			if (($merchantrow = mysql_fetch_array($merchant_result)) && ($merchantrow['is_active'] == 0))
				array_push($merchant_id_array, $row['merchant_id']);
		}
		return $merchant_id_array;
	}
?>
