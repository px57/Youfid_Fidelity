<?php

	require_once('Logger.class.php');

	if (!isset($logger))
		$logger = new Logger('logs/');

	function doLog($message)
	{
		global $logger;

		if (isset($logger))
			$logger->log('debug', 'sendLocation', $message, Logger::GRAN_MONTH);
	}

	/// table name
	$tbl_name="mobileuser";
	require_once('dbLogInfo.php');

	////////////////////////////////////////
	// Error properties
	$error = false;
	$errorMsg = "";

	////////////////////////////////////////
	// DataBase connection
	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$merchant_has_mbuser = "marchand_has_mobileuser";

	function check_sm_subscription($super_merchant_id, $user_id)
	{
		global $merchant_has_mbuser;

		$query = "SELECT * FROM $merchant_has_mbuser WHERE `marchand_id`='"
			. mysql_real_escape_string($super_merchant_id)
			. "' AND `mobileuser_id`='"
			. mysql_real_escape_string($user_id)
			. "'";

		$result = mysql_query($query);

		if (mysql_num_rows($result))
			return TRUE;

		return FALSE;
	}

	/// Inscrit un user a un super_marchand si il ne l'est pas.
	function subscribe_user_to_sm($merchant_id, $user_id)
	{
		global $merchant_has_mbuser;

		$merchant = get_merchant_2($merchant_id);
		if (!$merchant || $merchant['is_supermarchand'] == '1')
			return 2;
		if ($merchant['supermarchand_id'] == "-1")
			return 3;

		/// Verification de l'inscription
		if (check_sm_subscription($merchant['supermarchand_id'], $user_id))
			return 4;

		$query = "INSERT INTO $merchant_has_mbuser SET `marchand_id`='"
			. mysql_real_escape_string($merchant['supermarchand_id'])
			. "', `mobileuser_id`='"
			. mysql_real_escape_string($user_id)
			. "', `nb_use`='1'";

		$result = mysql_query($query);
		return 0;
	}

	////////////////////////////////////////
	// Getting Json Content
	$json = file_get_contents('php://input');
	$jsonArray = json_decode($json);

	doLog("QUERY::" . $json);

	/// sendLocation
	if (isset($jsonArray->usr_id) && isset($jsonArray->longitude) && isset($jsonArray->latitude) && isset($jsonArray->locationtime))
	{
		$lattitude = $jsonArray->latitude;
		$longitude = $jsonArray->longitude;
		$locationtime = $jsonArray->locationtime;

		//$sqlSetLocation = "UPDATE $tbl_name SET `lattitude`='$lattitude', `longitude`='$longitude', `locationtime`='$locationtime' WHERE `idclient`='$jsonArray->usr_id'";
		$sqlSetLocation = "UPDATE $tbl_name SET `lattitude`='"
			. mysql_real_escape_string($lattitude)
			. "', `longitude`='"
			. mysql_real_escape_string($longitude)
			. "', `locationtime`='"
			. mysql_real_escape_string($locationtime)
			. "' WHERE `id`='"
			. mysql_real_escape_string($jsonArray->usr_id)
			. "'";

		$sqlSetLocationResult = mysql_query($sqlSetLocation);

		if ($sqlSetLocationResult == false)
		{
			$error = true;
			$errorMsg = "Error with DB";
		}

		// On vérifie si le user est dans la zone d'un marchand:
		$queryGetProxyMerchants = "SELECT * FROM marchand WHERE is_active > 0 AND is_push_actif > 0 AND ("
			. "6371*acos(sin(latittude*pi()/180)*sin(("
			. floatval($lattitude)
			. ")*pi()/180) + cos(latittude*pi()/180)*cos(("
			. floatval($lattitude)
			. ")*pi()/180)*cos(("
			. floatval($longitude)
			. "-longitude)*pi()/180))"
			. ") * 1000 <= distance_push";

		doLog("Check Geoloc::" . $queryGetProxyMerchants);

		$resultGetProxyMerchants = mysql_query($queryGetProxyMerchants);

		// Si il y'a des marchands répondant aux circonstances:
		while ($marchand = mysql_fetch_array($resultGetProxyMerchants))
		{
			//echo("</br>In marchand " . $marchand['name']);

			$queryA = "SELECT * from marchand_has_mobileuser WHERE marchand_id = "
			. $marchand['id']
			. " AND mobileuser_id = "
			. $jsonArray->usr_id;

			//echo("</br>queryA = ".$queryA);

			$resultA = mysql_query($queryA);
			$num = mysql_num_rows($resultA);

			//echo("</br>num = " .$num);

			if($num==0)
			{
				$query = "INSERT INTO marchand_has_mobileuser SET `marchand_id`='"
					. $marchand['id']
					. "', `mobileuser_id`='"
					. $jsonArray->usr_id
					. "', `nb_use`='0'";

				//echo("</br>query = ".$query);
				doLog("Add mobileuser to marchand::" . $query);

				$result = mysql_query($query);

				if ($result != FALSE && $marchand['supermarchand_id'] != -1)
				{
					subscribe_user_to_sm($merchant_id, $mobileuser_id);
				}
			}
		}
	}
	else
	{
		$error = true;
		$errorMsg = "Bad parameters... Some parameters who are mandatory were not found";
	}

	/// Gestion d'erreur
	if ($error == true)
	{
		$status = "error";

		$jsonResult['status'] = $status;
		$jsonResult['message'] = $errorMsg;
		echo(json_encode(array_map_utf8_encode($jsonResult)));
	}
	else
	{
		$status = "ok";

		$jsonResult['status'] = $status;
		echo(json_encode(array_map_utf8_encode($jsonResult)));
	}

	doLog("Response::" . json_encode(array_map_utf8_encode($jsonResult)));
