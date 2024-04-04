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
			$logger->log('debug', 'lost_card', $message, Logger::GRAN_MONTH);
	}

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$tbl_mobileuser="mobileuser";

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	doLog("Request::" . $json);

	$errorMsg = "";
	$error = lostCard($jsonArray);

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

	doLog("Result::" . json_encode(array_map_utf8_encode($jsonResult)));

	echo(json_encode(array_map_utf8_encode($jsonResult)));

	function lostCard($jsonArray)
	{
		global $errorMsg, $tbl_mobileuser;

		if (!isset($jsonArray->usr_id) || !isset($jsonArray->qr_code))
		{
			//doLog("MissingParameters::usr_id=" . $jsonArray->usr_id . " merchant_id=" . $jsonArray->merchant_id . " products=" . $jsonArray->products);
			$errorMsg = "Error: Some parameters who are mandatory are missing...";
			return FALSE;
		}

		$usr_id = $jsonArray->usr_id;
		$qr_code = $jsonArray->qr_code;

		$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
			. mysql_real_escape_string($usr_id)
			. "'";

		$result = mysql_query($query);

		if (!($row = mysql_fetch_array($result)))
		{
			$errorMsg = "Error: There is no user in DBB with id=" . $usr_id;
			return FALSE;
		}

		$query = "UPDATE $tbl_mobileuser SET `qr_code`='"
			. mysql_real_escape_string($qr_code)
			. "' WHERE `id`='"
			. mysql_real_escape_string($usr_id)
			. "'";

		$result = mysql_query($query);

		if ($result == FALSE)
		{
			$errorMsg = "Error: During user update. check logs";
			return FALSE;
		}

		return TRUE;
	}

