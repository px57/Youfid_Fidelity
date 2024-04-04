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
			$logger->log('debug', 'listUsers', $message, Logger::GRAN_MONTH);
	}

	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$tbl_merchant="marchand";
	$tbl_mobileuser="mobileuser";
	$tbl_merchant_hmbu="marchand_has_mobileuser";

	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	doLog("Request=" . $json);

	/// Values for return
	$users_array = array();
	$errorMsg = "";
	$error = listUsers($jsonArray);

	if ($error == FALSE)
	{
		$jsonResult['status'] = "error";
		$jsonResult['message'] = $errorMsg;
	}
	else
	{
		$jsonResult['status'] = "ok";
		$jsonResult['users'] = $users_array;
	}

	/// Envoi de la reponse
	doLog("Response=" . json_encode(array_map_utf8_encode($jsonResult)));
	echo(json_encode(array_map_utf8_encode($jsonResult)));

	function listUsers($jsonArray)
	{
		global $errorMsg, $users_array, $tbl_merchant_hmbu, $tbl_mobileuser;

		if (!isset($jsonArray->merchant_id) || empty($jsonArray->merchant_id))
		{
			$errorMsg = "Bad Parameters.";
			return FALSE;
		}

		$merchant_id = $jsonArray->merchant_id;

		$query = "SELECT * FROM $tbl_merchant_hmbu WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "' AND `nb_use`>'0'";

		$result = mysql_query($query);

		if ($result == FALSE)
		{
			$errorMsg = "Error with the db durring getting merchant have mbusr";
			return FALSE;
		}

		$id_array = array();
		while ($row = mysql_fetch_array($result))
		{
			if (!in_array($row['mobileuser_id'], $id_array))
				array_push($id_array, $row['mobileuser_id']);
		}

		foreach ($id_array as $id)
		{
			$query = "SELECT * FROM $tbl_mobileuser WHERE `id`='"
				. mysql_real_escape_string($id)
				. "'";

			$result = mysql_query($query);
			if ($row = mysql_fetch_array($result))
			{
				$user = array();
				$user['usr_id'] = $row['id'];
				$user['first_name'] = $row['prenom'];
				$user['last_name'] = $row['nom'];
				$user['email'] = $row['mail'];

				//if($row['prenom']!="" && $row['nom']!="")
					array_push($users_array, $user);
			}
		}
		return TRUE;
	}

