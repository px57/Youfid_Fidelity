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

	$youfidDb = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

  $json = file_get_contents('php://input');
  $headers = array();
  if(function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
  } else {
    $headers = array(
      'Authorization' => $_SERVER['HTTP_AUTHORIZATION']
    );
  }

  $authorization = checkRequestAuthorization($youfidDb, $headers, $json);

	$jsonArray = json_decode($json);
	
	if(isset($jsonArray->merchant_id)) {
    $query = $youfidDb->prepare("SELECT * FROM marchand WHERE `id` = :id");
    $query->execute(array(
      'id' => $jsonArray->merchant_id
    ));
 
    $rowMarchand = false;
    if($query->rowCount() === 1) {
      $rowMarchand = $query->fetch(PDO::FETCH_ASSOC);
      // doLog("rowMarchand: " . print_r($rowMarchand, true));
      // doLog("Merchant found " . $rowMarchand['name']);

      if(isSecurityActivated($rowMarchand) && (!$authorization || !$authorization->granted || $rowMarchand['id'] != $authorization->merchantId)) {
        $error = TRUE;
        $errorMsg = 'Not authorized: ' . $authorization->error;
        // header("HTTP/1.1 401 Unauthorized");
        die(json_encode(
          array(
            'status' => "error",
            'message' => $errorMsg,
            'code' => 'ERR0401'
          )
        ));
      }
    } else {
      doLog("Merchant with id " . $jsonArray->merchant_id . " not found");
    }
	}
	
	mysql_connect("$host", "$username", "$password")or die('{"status":"error", "message":"cannot connect to DB"}');
	mysql_select_db("$db_name")or die('{"status":"error", "message":"cannot select DB"}');

	$tbl_merchant = "marchand";
	$tbl_mobileuser = "mobileuser";
	$tbl_merchant_hmbu = "marchand_has_mobileuser";

	// $json = file_get_contents('php://input');
	// $jsonArray = json_decode($json);

	doLog("Request=" . $json);

	/// Values for return
	$users_array = array();
	$errorMsg = "";
	$error = listUsers($jsonArray);

	if ($error == false)
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
	echo(json_encode( $jsonResult));


	function listUsers($jsonArray)
	{
		global $errorMsg, $users_array, $tbl_merchant_hmbu, $tbl_mobileuser;

		if (!isset($jsonArray->merchant_id) or empty($jsonArray->merchant_id) or !isset($jsonArray->usr_input))
		{
			$errorMsg = "Bad Parameters.";
			return false;
		}

		$merchant_id = $jsonArray->merchant_id;
		$usr_input = $jsonArray->usr_input;

		/*
		SELECT DISTINCT m.id, m.prenom, m.nom, m.mail FROM `mobileuser` m JOIN `marchand_has_mobileuser` mhm WHERE mhm.marchand_id = 192 AND mhm.nb_use > 0 AND ( m.prenom LIKE '%alex%' OR m.nom LIKE '%alex%' OR m.mail LIKE '%alex%')
		*/

		$query = "
			SELECT
				DISTINCT m.id, m.prenom, m.nom, m.mail, m.photo, m.qr_code
			FROM `mobileuser` m JOIN `marchand_has_mobileuser` mhm
			WHERE
				mhm.mobileuser_id = m.id AND
				mhm.marchand_id = " . intval($merchant_id) . " AND
				mhm.nb_use > 0 AND
				(
					m.prenom LIKE '%" . mysql_real_escape_string($usr_input) ."%' OR
					m.nom LIKE '%" . mysql_real_escape_string($usr_input) . "%' OR
					m.mail LIKE '%" . mysql_real_escape_string($usr_input) . "%'
				)
			LIMIT 100
		";

		if(@$jsonArray->extends_search) {

			$result = mysql_query("SELECT * FROM `marchand` WHERE `id` = " . intval($merchant_id));
			$row = mysql_fetch_array($result);
			$supermarchand_id = $row['supermarchand_id'];

			$query = "
				SELECT
					DISTINCT m.id, m.prenom, m.nom, m.mail, m.photo, m.qr_code
				FROM `mobileuser` m JOIN `marchand_has_mobileuser` mhm
				WHERE
					mhm.mobileuser_id = m.id AND
					mhm.marchand_id IN (
				        SELECT id FROM marchand WHERE supermarchand_id = " . intval($supermarchand_id) . "
				    ) AND
					mhm.nb_use > 0 AND
					(
						m.prenom LIKE '%" . mysql_real_escape_string($usr_input) ."%' OR
						m.nom LIKE '%" . mysql_real_escape_string($usr_input) . "%' OR
						m.mail LIKE '%" . mysql_real_escape_string($usr_input) . "%'
					)
				LIMIT 100
			";

		}

		//echo("SQL QUERY = " . $query);

		$result = mysql_query($query);

		if ($result == false)
		{
			$errorMsg = "Error with the db during sql request";
			return false;
		}

		while ($row = mysql_fetch_array($result))
		{
			$user = array();
			$user['usr_id'] = $row['id'];
			$user['first_name'] = $row['prenom'];
			$user['last_name'] = $row['nom'];
			$user['email'] = $row['mail'];
			$user['photo'] = $row['photo'];
			$user['qr_code'] = $row['qr_code'];


			//if($row['prenom']!="" && $row['nom']!="")
				array_push($users_array, $user);
		}

		return true;
	}

