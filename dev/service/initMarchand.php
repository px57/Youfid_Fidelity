<?php

	require_once('Logger.class.php');
	require_once('dbLogInfo.php');
	require_once('utils.php');

	if (!isset($logger))
		$logger = new Logger('logs/');

	function doLog($message)
	{
		global $logger;

		if (isset($logger))
			$logger->log('debug', 'initMarchand', $message, Logger::GRAN_MONTH);
	}

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$tbl_bo_usr = "backoffice_usr";
	$tbl_marchand="marchand";

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	doLog("Request::" . $json);

	$jsonResult = array();

	$errorMsg = "";
	$error = init_marchand($jsonArray);

	if ($error == FALSE)
	{
		$jsonResult['status'] = "error";
		$jsonResult['message'] = $errorMsg;
	}
	else
	{
		$jsonResult['status'] = "ok";
		$jsonResult['message'] = $errorMsg;
	}

	doLog("RESPONSE=" . json_encode(array_map_utf8_encode($jsonResult)));

	echo(json_encode(array_map_utf8_encode($jsonResult)));

	function init_marchand($jsonArray)
	{
		global $errorMsg, $tbl_bo_usr, $tbl_marchand, $jsonResult;

		if (!isset($jsonArray->login) || !isset($jsonArray->password))
		{
			$errorMsg = "Error: Some parameters who are mandatory are missing...";
			return FALSE;
		}

		$login = $jsonArray->login;
		$password = $jsonArray->password;

		$query = "SELECT * FROM $tbl_bo_usr WHERE `login`='"
			. mysql_real_escape_string($login)
			. "' AND `password`='"
			. mysql_real_escape_string($password)
			. "'";

		$result = mysql_query($query);
		if ($result == FALSE)
		{
			$errorMsg = "Error: Problem with the BDD";
			return FALSE;
		}

		/// Get associated merchant
		if ($row = mysql_fetch_array($result))
		{
			$query = "SELECT * FROM $tbl_marchand WHERE `id`='"
				. mysql_real_escape_string($row['id_marchand'])
				. "'";

			$result = mysql_query($query);

			if ($row = mysql_fetch_array($result))
			{
				$jsonResult['name'] = $row['name'];
				$jsonResult['logo'] = $row['logo'];
				$jsonResult['merchant_id'] = $row['id'];
				$jsonResult['application_id'] = $row['application_id'];
				$jsonResult['pin_code'] = $row['pin_code'];
				$jsonResult['is_accueil_client'] = $row['is_accueil_client'];
			}
			else
			{
				$errorMsg = "Error: Problem with the BDD";
				return FALSE;
			}
		}
		else
		{
			$errorMsg = "Error: There is no user in DBB with id=" . $login . " AND password=" . $password;
			return FALSE;
		}

		return TRUE;
	}

