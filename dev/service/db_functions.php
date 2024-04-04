<?php

	/////////////////////////////////////////////////////////////////////////////////
	//// Contient des fonction pour ajouter, supprimer, update des objets en DB

	require_once("Logger.class.php");
	require_once 'utils.php';

	if (!isset($logger))
		$logger = new Logger('../logs/');

	$logger->log('debug', 'db_function', "in file", Logger::GRAN_MONTH);

	require_once("../dev/service/dbLogInfo.php");
	$tbl_marchands = "marchand";
	$tbl_label = "label";
	$tbl_user = "mobileuser";

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	/**
	 * 	getMobileUser(Int)
	 *  Retourne la colonne du mobileuser associe a cet id
	 *
	 *  @param Int $usr_id id du mobileuser
	 */

	function getMobileUser($usr_id)
	{
		global $tbl_user, $logger;

		$sqlGetUser = "SELECT * FROM $tbl_user WHERE `id`='"
			. mysql_real_escape_string($usr_id)
			. "'";

		$result = mysql_query($sqlGetUser);

		if ($row = mysql_fetch_array($result))
			return $row;

		return FALSE;
	}

	/// Renvoi un id si success, ou FALSE en cas d'erreur. Si le label est deja present et de meme $type, renvoie l'id de ce dernier
	function INSERT_LABEL($name, $type)
	{
		global $tbl_label, $logger;

		$sqlGetLabel = "SELECT * INTO $tbl_label WHERE `nom` ='"
			. mysql_real_escape_string($name)
			. "'";

		$result = mysql_query($sqlGetLabel);

		if ($row = mysql_fetch_array($result))
		{
			if ($row['type'] == $type)
				return $row['id'];
		}

		$sqlInsertLabel = "INSERT INTO $tbl_label SET `nom`='"
			. mysql_real_escape_string($name)
			. "', `type`='"
			. mysql_real_escape_string($type)
			. "'";

		$result = mysql_query($sqlInsertLabel);
		$logger->log('debug', 'db_function', "::INSERT_LABEL::query=" . $sqlInsertLabel, Logger::GRAN_MONTH);

		if ($result != FALSE)
		{
			$sqlGetLabel = "SELECT * FROM $tbl_label WHERE `nom`='"
			. mysql_real_escape_string($name)
			. "' AND `type`='"
			. mysql_real_escape_string($type)
			. "'";

			$result = mysql_query($sqlGetLabel);
			$logger->log('debug', 'db_function', "::INSERT_LABEL::query=" . $sqlGetLabel, Logger::GRAN_MONTH);

			if ($row = mysql_fetch_array($result))
			{
				return $row['id'];
			}
			return FALSE;
		}
		return FALSE;
	}

	/**
	 * 	getSupermerchantId(Int)
	 *  Retourne le SuperMerchant_id associé a une franchise
	 *
	 *  @param Int $category_id id de la categorie du marchand
	 */
	function getSupermerchantId($category_id)
	{
		global $logger, $tbl_marchands, $tbl_label;

		$logger->log('debug', 'db_function', "::getSupermarchandID::category_id=" . $category_id, Logger::GRAN_MONTH);

		$sqlGetCatgory = "SELECT * FROM $tbl_label WHERE `id`='"
			. mysql_real_escape_string($category_id)
			. "'";

		$result = mysql_query($sqlGetCatgory);

		if ($result == FALSE)
			return FALSE;

		$logger->log('debug', 'db_function', "::getSupermarchandID::TEST", Logger::GRAN_MONTH);

		if ($category_row = mysql_fetch_array($result))
		{
			if (!isset($category_row['type']) || empty($category_row['type']) || $category_row['type'] != "franchise")
				return FALSE;

			/*$logger->log('debug', 'db_function', "::getSupermarchandID::TEST", Logger::GRAN_MONTH);

			$sqlGetMerchant = "SELECT * FROM $tbl_marchands WHERE `name` = '"
				. mysql_real_escape_string($category_row['nom'])
				. "'";

			$result = mysql_query($sqlGetMerchant);*/

			/*if ($row = mysql_fetch_array($result))
				return $row['id'];*/

			if($category_row['supermarchand_id'] == -1)
				return FALSE;

			return $category_row['supermarchand_id'];
		}
		return FALSE;
	}

	function category_set_marchandId($application_id, $label_id)
	{
		global $logger, $tbl_marchands, $tbl_label;

		$logger->log('debug', 'db_function', "::category_set_marchandid::application_id=" . $application_id . " :: label_id=" . $label_id, Logger::GRAN_MONTH);

		$sqlGetMerchant = "SELECT * FROM $tbl_marchands WHERE `application_id`='"
			. mysql_real_escape_string($application_id)
			. "'";

		$result = mysql_query($sqlGetMerchant);

		if ($row = mysql_fetch_array($result))
		{
			/// Check if label exist
			$sqlGetLabel = "SELECT * FROM $tbl_label WHERE `id`='"
				. mysql_real_escape_string($label_id)
				. "'";

			$result = mysql_query($result);

			if ($rowLabel = mysql_fetch_array($result))
			{
				if ($rowLabel['supermarchand_id'] == $row['id'])
					return TRUE;
				else
					return FALSE;
			}

			$sqlUpdateLabel = "UPDATE $tbl_label SET `supermarchand_id`='"
				. mysql_real_escape_string($row['id'])
				. "' WHERE `id`='"
				. mysql_real_escape_string($label_id)
				. "'";

			$result = mysql_query($sqlUpdateLabel);
			if ($result != FALSE)
				return TRUE;
		}

		return FALSE;
	}

