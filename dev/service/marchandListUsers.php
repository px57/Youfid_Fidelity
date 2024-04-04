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
	doLog("Response=" . json_encode( $jsonResult));
	echo(json_encode(utf8_converter( $jsonResult)));
	function utf8_converter($array)
{
    array_walk_recursive($array, function(&$item, $key){
        if(!mb_detect_encoding($item, 'utf-8', true)){
                $item = utf8_encode($item);
        }
    });

    return $array;
}

	function listUsers($jsonArray)
	{
		global $errorMsg, $users_array, $tbl_merchant_hmbu, $tbl_mobileuser;

		if (!isset($jsonArray->merchant_id) || empty($jsonArray->merchant_id) || !isset($jsonArray->usr_input))
		{
			$errorMsg = "Bad Parameters.";
			return FALSE;
		}

		$merchant_id = $jsonArray->merchant_id;
		$usr_input = $jsonArray->usr_input;

		/*
		SELECT DISTINCT m.id, m.prenom, m.nom, m.mail FROM `mobileuser` m JOIN `marchand_has_mobileuser` mhm WHERE mhm.marchand_id = 192 AND mhm.nb_use > 0 AND ( m.prenom LIKE '%alex%' OR m.nom LIKE '%alex%' OR m.mail LIKE '%alex%')
		*/


		$query = "SELECT DISTINCT m.id, m.prenom, m.nom, m.mail, m.photo FROM `mobileuser` m JOIN `marchand_has_mobileuser` mhm WHERE mhm.mobileuser_id = m.id AND mhm.marchand_id='"
		. mysql_real_escape_string($merchant_id)
		. "' AND mhm.nb_use > 0 AND ( m.prenom LIKE '%"
		. mysql_real_escape_string($usr_input)
		."%' OR m.nom LIKE '%"
		. mysql_real_escape_string($usr_input)
		."%' OR m.mail LIKE '%"
		. mysql_real_escape_string($usr_input)
		."%')";

		//echo("SQL QUERY = " . $query);

		$result = mysql_query($query);

		if ($result == FALSE)
		{
			$errorMsg = "Error with the db during sql request";
			return FALSE;
		}

		while ($row = mysql_fetch_array($result))
		{
			$user = array();
			$user['usr_id'] = $row['id'];
			$user['first_name'] = $row['prenom'];
			$user['last_name'] = $row['nom'];
			$user['email'] = $row['mail'];
			$user['photo'] = $row['photo'];

			//if($row['prenom']!="" && $row['nom']!="")
				array_push($users_array, $user);
		}

//OLD
/*
		$query = "SELECT * FROM $tbl_merchant_hmbu JOIN tbl_mobileuser WHERE `marchand_id`='"
			. mysql_real_escape_string($merchant_id)
			. "' AND `nb_use`>'0'";

		$result = mysql_query($query);

		if ($result == FALSE)
		{
			$errorMsg = "Error with the db during sql request";
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
				$user['photo'] = $row['photo'];

				//if($row['prenom']!="" && $row['nom']!="")
					array_push($users_array, $user);
			}
		}
*/
		return TRUE;
	}

